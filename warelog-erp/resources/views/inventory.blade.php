@extends('layouts.navigation')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">📦 Inventory Management</div>
    @if(!isset($item))
        <button class="btn-green" data-bs-toggle="modal" data-bs-target="#addModal">+ Add New Item</button>
    @endif
</div>

@php
    $total = $items->count();
    $inStock = $items->where('qty', '>', 10)->count();
    $lowStock = $items->whereBetween('qty', [1, 10])->count();
    $outStock = $items->where('qty', '<=', 0)->count();
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><div class="label">Total Items</div><div class="num">{{ $total }}</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="label">In Stock</div><div class="num" style="color:#28a745">{{ $inStock }}</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="label">Low Stock</div><div class="num" style="color:#ffc107">{{ $lowStock }}</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="label">Out of Stock</div><div class="num" style="color:#dc3545">{{ $outStock }}</div></div></div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- EDIT FORM (kapag may piniling i-edit) --}}
@if(isset($item))
<div class="card-white">
    <h5>✏️ Edit Item: {{ $item->name }}</h5>
    <form method="POST" action="/inventory/update/{{ $item->id }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-4"><label>Item Code *</label><input type="text" name="code" class="form-control" value="{{ $item->code }}" required></div>
            <div class="col-md-4"><label>Item Name *</label><input type="text" name="name" class="form-control" value="{{ $item->name }}" required></div>
            <div class="col-md-4"><label>Category *</label>
                <select name="category" class="form-select" required>
                    <option value="">Choose...</option>
                    <option {{ $item->category=='Raw Materials'?'selected':'' }}>Raw Materials</option>
                    <option {{ $item->category=='Supplies'?'selected':'' }}>Supplies</option>
                    <option {{ $item->category=='Finished Goods'?'selected':'' }}>Finished Goods</option>
                    <option {{ $item->category=='Equipment'?'selected':'' }}>Equipment</option>
                </select>
            </div>
            <div class="col-md-3"><label>Stock Qty *</label><input type="number" name="qty" class="form-control" value="{{ $item->qty }}" required></div>
            <div class="col-md-3"><label>Unit *</label><input type="text" name="unit" class="form-control" value="{{ $item->unit }}" required></div>
            <div class="col-md-3"><label>Unit Price (₱) *</label><input type="number" step="0.01" name="price" class="form-control" value="{{ $item->price }}" required></div>
            <div class="col-md-3"><label>Location *</label>
                <select name="location" class="form-select" required>
                    <option value="">Choose...</option>
                    <option {{ $item->location=='Warehouse A'?'selected':'' }}>Warehouse A</option>
                    <option {{ $item->location=='Warehouse B'?'selected':'' }}>Warehouse B</option>
                    <option {{ $item->location=='Warehouse C'?'selected':'' }}>Warehouse C</option>
                </select>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn-green">💾 Save Changes</button>
            <a href="/inventory" class="btn-red" style="text-decoration:none;padding:8px 16px;border-radius:4px">Cancel</a>
        </div>
    </form>
</div>
@endif

<div class="card-white">
    <table>
        <thead><tr><th>QR</th><th>Item Code</th><th>Item Name</th><th>Category</th><th>Qty</th><th>Unit</th><th>Price</th><th>Location</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($items as $itm)
            <tr>
                {{-- ✅ QR CODE — auto-generated kada item! --}}
                <td>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ urlencode($itm->code . ' | ' . $itm->name) }}" 
                         alt="QR" style="width:50px;height:50px;cursor:pointer"
                         data-bs-toggle="modal" data-bs-target="#qrModal{{ $itm->id }}">
                </td>
                <td>{{ $itm->code }}</td>
                <td>{{ $itm->name }}</td>
                <td>{{ $itm->category }}</td>
                <td>{{ $itm->qty }}</td>
                <td>{{ $itm->unit }}</td>
                <td>₱{{ number_format($itm->price, 2) }}</td>
                <td>{{ $itm->location }}</td>
                <td>
                    @if($itm->qty > 10) <span class="badge-good">Good</span>
                    @elseif($itm->qty > 0) <span class="badge-low">Low Stock</span>
                    @else <span class="badge-out">Out of Stock</span>
                    @endif
                </td>
                <td>
                    <a href="/inventory/edit/{{ $itm->id }}" style="margin-right:8px">✏️ Edit</a>
                    <form method="POST" action="/inventory/delete/{{ $itm->id }}" style="display:inline" onsubmit="return confirm('Delete this item?')">
                        @csrf
                        <button type="submit" style="background:none;border:none;color:#dc3545;cursor:pointer">🗑️ Delete</button>
                    </form>
                </td>
            </tr>

            {{-- QR MODAL — malaking QR kada item --}}
            <div class="modal fade" id="qrModal{{ $itm->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content p-4 text-center">
                        <h5>{{ $itm->name }}</h5>
                        <p class="text-muted">Code: {{ $itm->code }}</p>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($itm->code . ' | ' . $itm->name . ' | ' . $itm->location) }}" 
                             style="width:250px;height:250px;margin:10px auto;display:block">
                        <button class="btn-green mt-2" onclick="window.print()">🖨️ Print QR</button>
                    </div>
                </div>
            </div>
            @empty
            <tr><td colspan="10" style="text-align:center;color:#999;padding:30px">No items yet — click "Add New Item"</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ADD MODAL --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content p-4">
            <h5 class="mb-3">➕ Add New Item</h5>
            <form method="POST" action="/inventory/add">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6"><label>Item Code *</label><input type="text" name="code" class="form-control" placeholder="ITM-00001" required></div>
                    <div class="col-md-6"><label>Item Name *</label><input type="text" name="name" class="form-control" placeholder="Name of product" required></div>
                    <div class="col-md-6"><label>Category *</label>
                        <select name="category" class="form-select" required>
                            <option value="">Choose...</option>
                            <option>Raw Materials</option>
                            <option>Supplies</option>
                            <option>Finished Goods</option>
                            <option>Equipment</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label>Unit of Measure *</label>
                        <select name="unit" class="form-select" required>
                            <option value="">Choose...</option>
                            <option>pcs</option><option>box</option><option>roll</option>
                            <option>kg</option><option>pack</option><option>set</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label>Initial Stock Qty *</label><input type="number" name="qty" class="form-control" value="0" required></div>
                    <div class="col-md-4"><label>Unit Price (₱) *</label><input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required></div>
                    <div class="col-md-4"><label>Warehouse / Location *</label>
                        <select name="location" class="form-select" required>
                            <option value="">Choose...</option>
                            <option>Warehouse A</option>
                            <option>Warehouse B</option>
                            <option>Warehouse C</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn-red" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-green">💾 Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection