@extends('layouts.navigation')
@section('content')
<div class="page-title">📥 Stock In</div>
<p style="color:#666;margin-top:-10px;margin-bottom:15px">Enter items received into your warehouse</p>

<div class="card-white">
    <form method="POST" action="{{ route('stockin') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="item_id">Item</label>
                <select id="item_id" name="item_id" class="form-select" required>
                    <option value="">Select an item</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>{{ $item->code }} · {{ $item->name }} ({{ $item->qty }} {{ $item->unit }} on hand)</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="qty">Quantity received</label>
                <input id="qty" name="qty" type="number" class="form-control" min="1" step="1" value="{{ old('qty') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="supplier">Supplier / source</label>
                <input id="supplier" name="supplier" type="text" class="form-control" maxlength="255" value="{{ old('supplier') }}">
            </div>
        </div>
        @if($errors->any())
            <div class="alert alert-danger mt-3" role="alert">{{ $errors->first() }}</div>
        @endif
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn-green">Add to inventory</button>
            <a href="{{ route('stockin') }}" class="btn-red text-decoration-none">Cancel</a>
        </div>
    </form>
</div>

<div class="card-white">
    <h6>📋 Items to Receive</h6>
    <table>
        <thead><tr><th>#</th><th>Item Name</th><th>Quantity</th><th>Supplier</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($movements as $movement)
                <tr><td>{{ $movement->id }}</td><td>{{ $movement->item_name }}</td><td>{{ $movement->qty }}</td><td>{{ $movement->supplier ?: '—' }}</td><td>{{ $movement->created_at->format('M j, Y g:i A') }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No stock-in transactions yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection