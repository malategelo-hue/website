<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Delivery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

Route::get('/', fn() => view('welcome'))->name('home');
require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {

    // ==========================================
    // DASHBOARD
    // ==========================================
    Route::get('/dashboard', function () {
        $user = Auth::user();
        abort_if($user->role !== 'admin', 403, 'Admin access only');
        $items = Item::all();
        $months = ['Apr','May','Jun','Jul','Aug','Sep'];

        if ($user->role === 'admin') {
            // Admin: Green = Items Added | Red = 0 (walang stock in/out)
            $stockInData = [];
            $stockOutData = [];
            foreach (range(4, 9) as $m) {
                $stockInData[] = Item::whereMonth('created_at', $m)->count();
                $stockOutData[] = 0;
            }
            $chartNote = "Kapag nag-add ka ng item sa Inventory → tataas ang GREEN LINE";
        } else {
            // User: Stock In vs Stock Out
            $stockInData = [];
            $stockOutData = [];
            foreach (range(4, 9) as $m) {
                $stockInData[] = StockMovement::where('type','in')->whereMonth('created_at', $m)->sum('qty');
                $stockOutData[] = StockMovement::where('type','out')->whereMonth('created_at', $m)->sum('qty');
            }
            $chartNote = "Kapag nag-Stock In → 🟢 | Kapag nag-Stock Out → 🔴";
        }

        return view('dashboard', [
            'role' => $user->role,
            'items' => $items,
            'months' => $months,
            'stockInData' => $stockInData,
            'stockOutData' => $stockOutData,
            'chartNote' => $chartNote,
            'totalValue' => $items->sum(fn($i) => $i->qty * $i->price),
            'lowStock' => $items->whereBetween('qty', [1, 10])->count(),
            'outOfStock' => $items->where('qty', '<=', 0)->count(),
            'totalProducts' => $items->count(),
            'recentMovements' => StockMovement::latest()->take(5)->get(),
        ]);
    })->name('dashboard');

    // ==========================================
    // INVENTORY — ADMIN LANG: Add/Edit/Delete ✅ WALANG STOCK IN/OUT
    // ==========================================
    Route::get('/inventory', function () {
        abort_if(Auth::user()->role !== 'admin', 403, 'Admin access only');
        $items = Item::latest()->get();
        return view('inventory', compact('items'));
    })->name('inventory');

    Route::post('/inventory/add', function (Request $req) {
        abort_if(Auth::user()->role !== 'admin', 403);
        $data = $req->validate([
            'code' => 'required|unique:items,code',
            'name' => 'required',
            'category' => 'required',
            'qty' => 'required|integer|min:0',
            'unit' => 'required',
            'price' => 'required|numeric|min:0',
            'location' => 'required',
        ]);
        DB::transaction(function () use ($data) {
            $item = Item::create($data);
            if ($item->qty > 0) {
                StockMovement::create([
                    'type' => 'in',
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'qty' => $item->qty,
                    'supplier' => 'Opening inventory',
                    'user_id' => Auth::id(),
                ]);
            }
        });
        return redirect()->route('inventory')->with('success', 'Item added and opening stock recorded.');
    })->name('inventory.add');

    Route::get('/inventory/edit/{id}', function ($id) {
        abort_if(Auth::user()->role !== 'admin', 403);
        $item = Item::findOrFail($id);
        $items = Item::latest()->get();
        return view('inventory', compact('item', 'items'));
    })->name('inventory.edit');

    Route::post('/inventory/update/{id}', function (Request $req, $id) {
        abort_if(Auth::user()->role !== 'admin', 403);
        $item = Item::findOrFail($id);
        $data = $req->validate([
            'code' => 'required|unique:items,code,'.$id,
            'name' => 'required',
            'category' => 'required',
            'qty' => 'required|integer|min:0',
            'unit' => 'required',
            'price' => 'required|numeric|min:0',
            'location' => 'required',
        ]);
        DB::transaction(function () use ($item, $data) {
            $current = Item::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $oldQty = (int) $current->qty;
            $newQty = (int) $data['qty'];
            $current->update($data);

            if ($oldQty !== $newQty) {
                StockMovement::create([
                    'type' => $newQty > $oldQty ? 'in' : 'out',
                    'item_id' => $current->id,
                    'item_name' => $current->name,
                    'qty' => abs($newQty - $oldQty),
                    'supplier' => $newQty > $oldQty ? 'Admin inventory adjustment' : null,
                    'issued_to' => $newQty < $oldQty ? 'Admin inventory adjustment' : null,
                    'user_id' => Auth::id(),
                ]);
            }
        });
        return redirect('/inventory')->with('success', '✅ Item Updated!');
    })->name('inventory.update');

    Route::post('/inventory/delete/{id}', function ($id) {
        abort_if(Auth::user()->role !== 'admin', 403);
        Item::findOrFail($id)->delete();
        return redirect('/inventory')->with('success', '✅ Item Deleted!');
    })->name('inventory.delete');

    // ==========================================
    // STOCK IN / OUT — USER LANG ✅ HINDI MAKIKITA NG ADMIN
    // ==========================================
    Route::get('/stockin', function () {
        abort_if(Auth::user()->role !== 'user', 403, 'User access only');
        $items = Item::orderBy('name')->get();
        $movements = StockMovement::where('type','in')->latest()->take(10)->get();
        return view('stockin', compact('items','movements'));
    })->name('stockin');

    Route::post('/stockin', function (Request $req) {
        abort_if(Auth::user()->role !== 'user', 403);
        $data = $req->validate([
            'item_id' => 'required|exists:items,id',
            'qty' => 'required|integer|min:1',
            'supplier' => 'nullable|string|max:255',
        ]);
        $item = DB::transaction(function () use ($data) {
            $item = Item::whereKey($data['item_id'])->lockForUpdate()->firstOrFail();
            $item->increment('qty', (int) $data['qty']);
            StockMovement::create([
                'type' => 'in', 'item_id' => $item->id, 'item_name' => $item->name,
                'qty' => (int) $data['qty'], 'supplier' => $data['supplier'] ?? null, 'user_id' => Auth::id(),
            ]);
            return $item;
        });
        return redirect()->route('stockin')->with('success', "Stock-in recorded: {$item->name} +{$data['qty']}.");
    });

    Route::get('/stockout', function () {
        abort_if(Auth::user()->role !== 'user', 403, 'User access only');
        $items = Item::where('qty','>',0)->orderBy('name')->get();
        $movements = StockMovement::where('type','out')->latest()->take(10)->get();
        return view('stockout', compact('items','movements'));
    })->name('stockout');

    Route::post('/stockout', function (Request $req) {
        abort_if(Auth::user()->role !== 'user', 403);
        $data = $req->validate([
            'item_id' => 'required|exists:items,id',
            'qty' => 'required|integer|min:1',
            'issued_to' => 'required|string|max:255',
        ]);
        $item = DB::transaction(function () use ($data) {
            $item = Item::whereKey($data['item_id'])->lockForUpdate()->firstOrFail();
            if ($item->qty < $data['qty']) {
                throw ValidationException::withMessages([
                    'qty' => "Only {$item->qty} available.",
                ]);
            }
            $item->decrement('qty', (int) $data['qty']);
            StockMovement::create([
                'type' => 'out', 'item_id' => $item->id, 'item_name' => $item->name,
                'qty' => (int) $data['qty'], 'issued_to' => $data['issued_to'], 'user_id' => Auth::id(),
            ]);
            return $item;
        });
        return redirect()->route('stockout')->with('success', "Stock-out recorded: {$item->name} -{$data['qty']}.");
    });

    // ==========================================
    // REPORTS — MAY LAMAN NA!
    // ==========================================
    Route::get('/reports', function () {
        $items = Item::all();
        $movements = StockMovement::latest()->get();
        
        // Summary data
        $byCategory = [
            'Raw Materials' => $items->where('category','Raw Materials')->count(),
            'Supplies' => $items->where('category','Supplies')->count(),
            'Finished Goods' => $items->where('category','Finished Goods')->count(),
            'Equipment' => $items->where('category','Equipment')->count(),
        ];
        
        $byLocation = [
            'Warehouse A' => $items->where('location','Warehouse A')->count(),
            'Warehouse B' => $items->where('location','Warehouse B')->count(),
            'Warehouse C' => $items->where('location','Warehouse C')->count(),
        ];
        
        $totalStockIn = StockMovement::where('type','in')->sum('qty');
        $totalStockOut = StockMovement::where('type','out')->sum('qty');
        $totalValue = $items->sum(fn($i) => $i->qty * $i->price);

        return view('reports', compact(
            'items','movements','byCategory','byLocation',
            'totalStockIn','totalStockOut','totalValue'
        ));
    })->name('reports');

    // ==========================================
    // SETTINGS — SYSTEM PREFERENCES
    // ==========================================
    Route::get('/settings', function () {
        return view('settings');
    })->name('settings');

    Route::post('/settings/save', function (Request $req) {
        $req->validate([
            'system_name' => 'required|string|max:50',
            'warehouse_name' => 'required|string|max:50',
            'low_stock_threshold' => 'required|integer|min:1',
            'currency' => 'required',
            'date_format' => 'required',
            'timezone' => 'required',
        ]);
        
        // I-save sa session (pwede ilipat sa database mamaya)
        session([
            'system_name' => $req->system_name,
            'warehouse_name' => $req->warehouse_name,
            'low_stock_threshold' => $req->low_stock_threshold,
            'currency' => $req->currency,
            'date_format' => $req->date_format,
            'timezone' => $req->timezone,
            'notifications' => $req->has('notifications'),
            'auto_backup' => $req->has('auto_backup'),
        ]);

        return redirect('/settings')->with('success', '✅ Settings saved successfully!');
    })->name('settings.save');

    Route::get('/logistics', function () {
        abort_if(Auth::user()->role !== 'user', 403, 'User access only');
        $deliveries = Delivery::with('item')->latest()->get();
        $items = Item::where('qty', '>', 0)->orderBy('name')->get();
        return view('logistics', [
            'deliveries' => $deliveries,
            'items' => $items,
            'pendingOrders' => $deliveries->where('status', 'in_transit')->count(),
            'deliveredOrders' => $deliveries->where('status', 'delivered')->count(),
        ]);
    })->name('logistics');

    Route::post('/logistics/dispatch', function (Request $req) {
        abort_if(Auth::user()->role !== 'user', 403, 'User access only');
        $data = $req->validate([
            'item_id' => 'required|exists:items,id',
            'qty' => 'required|integer|min:1',
            'destination' => 'required|string|max:255',
            'driver' => 'nullable|string|max:120',
            'vehicle' => 'nullable|string|max:120',
            'scheduled_at' => 'required|date',
        ]);

        $delivery = DB::transaction(function () use ($data) {
            $item = Item::whereKey($data['item_id'])->lockForUpdate()->firstOrFail();
            if ($item->qty < $data['qty']) {
                throw ValidationException::withMessages([
                    'qty' => "Only {$item->qty} {$item->unit} available.",
                ]);
            }

            $item->decrement('qty', (int) $data['qty']);
            $reference = 'DISP-' . now()->format('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $delivery = Delivery::create([
                ...$data,
                'reference' => $reference,
                'status' => 'in_transit',
                'qr_token' => bin2hex(random_bytes(24)),
            ]);
            StockMovement::create([
                'type' => 'out',
                'item_id' => $item->id,
                'item_name' => $item->name,
                'qty' => (int) $data['qty'],
                'issued_to' => $data['destination'],
                'user_id' => Auth::id(),
            ]);

            return $delivery;
        });

        return redirect()->route('logistics')->with('success', "{$delivery->reference} dispatched and stock-out recorded.");
    })->name('logistics.dispatch');

    Route::post('/logistics/verify', function (Request $req) {
        abort_if(Auth::user()->role !== 'user', 403, 'User access only');
        $data = $req->validate(['qr_token' => 'required|string|max:120']);
        $token = trim($data['qr_token']);
        if (str_starts_with($token, 'WARELOG:DELIVERY:')) {
            $token = substr($token, strlen('WARELOG:DELIVERY:'));
        }
        validator(['qr_token' => $token], [
            'qr_token' => 'required|string|exists:deliveries,qr_token',
        ])->validate();

        $delivery = DB::transaction(function () use ($token) {
            $delivery = Delivery::where('qr_token', $token)->lockForUpdate()->firstOrFail();
            if ($delivery->status === 'delivered') {
                throw ValidationException::withMessages(['qr_token' => 'This delivery was already confirmed.']);
            }
            $delivery->update(['status' => 'delivered', 'delivered_at' => now()]);
            return $delivery;
        });

        return redirect()->route('logistics')->with('success', "{$delivery->reference} verified as delivered.");
    })->name('logistics.verify');
});

Route::get('/setup-accounts', function () {
    abort_unless(app()->environment('local') && User::query()->doesntExist(), 404);
    $a = new User(); $a->name='Admin'; $a->email='admin@warelog.com'; $a->password=bcrypt('admin123'); $a->role='admin'; $a->save();
    $u = new User(); $u->name='Staff'; $u->email='user@warelog.com'; $u->password=bcrypt('user123'); $u->role='user'; $u->save();
    return "✅ Accounts Ready!<br>🔐 ADMIN: admin@warelog.com / admin123<br>👤 USER: user@warelog.com / user123<br><a href='/login'>→ Login</a>";
});