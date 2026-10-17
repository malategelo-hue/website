@extends('layouts.navigation')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">🚚 Logistics &amp; Deliveries</div>
    <button class="btn-green" type="button" data-bs-toggle="modal" data-bs-target="#dispatchModal">+ New Dispatch</button>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif

<div class="card-white">
    <h6>Verify a delivery QR</h6>
    <form id="deliveryVerifyForm" method="POST" action="{{ route('logistics.verify') }}" class="d-flex flex-wrap align-items-end gap-2">
        @csrf
        <div class="flex-grow-1">
            <label for="deliveryQrToken" class="form-label">Scan with camera or enter the QR token</label>
            <input id="deliveryQrToken" name="qr_token" type="text" class="form-control" value="{{ old('qr_token') }}" placeholder="WARELOG:DELIVERY:..." required>
        </div>
        <button type="button" id="startDeliveryScanner" class="btn-blue">Start camera</button>
        <button type="submit" class="btn-green">Verify delivery</button>
    </form>
    <div id="deliveryQrScanner" class="mt-3" style="max-width:420px" hidden></div>
    <div id="deliveryScannerStatus" class="small text-muted mt-2" role="status"></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="stat-card"><div class="label">In Transit</div><div class="num" style="color:#007bff">{{ $pendingOrders }}</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="label">Delivered</div><div class="num" style="color:#28a745">{{ $deliveredOrders }}</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="label">Total Dispatches</div><div class="num">{{ $deliveries->count() }}</div></div></div>
</div>

<div class="card-white">
    <form method="GET" action="{{ route('logistics') }}" class="d-flex gap-2 mb-3">
        <input name="q" type="search" class="form-control" value="{{ request('q') }}" placeholder="Search dispatch, destination, or item..." aria-label="Search deliveries">
        <button class="btn-blue" type="submit">Search</button>
        @if(request('q')) <a class="btn-red text-decoration-none" href="{{ route('logistics') }}">Clear</a> @endif
    </form>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Dispatch</th><th>Item</th><th>Destination</th><th>Driver / Vehicle</th><th>Scheduled</th><th>Status</th><th>QR / Action</th></tr></thead>
            <tbody>
                @php
                    $query = strtolower(trim((string) request('q')));
                    $visibleDeliveries = $deliveries->filter(fn ($delivery) => !$query || str_contains(strtolower($delivery->reference . ' ' . $delivery->destination . ' ' . $delivery->item->name), $query));
                @endphp
                @forelse($visibleDeliveries as $delivery)
                    <tr>
                        <td>{{ $delivery->reference }}</td>
                        <td>{{ $delivery->item->name }} ({{ $delivery->qty }} {{ $delivery->item->unit }})</td>
                        <td>{{ $delivery->destination }}</td>
                        <td>{{ $delivery->driver ?: '—' }} / {{ $delivery->vehicle ?: '—' }}</td>
                        <td>{{ $delivery->scheduled_at->format('M j, Y g:i A') }}</td>
                        <td>
                            @if($delivery->status === 'delivered')
                                <span class="badge-delivered">Delivered</span>
                            @else
                                <span class="badge-transit">In transit</span>
                            @endif
                        </td>
                        <td class="d-flex align-items-center gap-2">
                            <button type="button" class="btn-blue" data-bs-toggle="modal" data-bs-target="#qrModal{{ $delivery->id }}">Show QR</button>
                            @if($delivery->status === 'in_transit')
                                <form method="POST" action="{{ route('logistics.verify') }}" onsubmit="return confirm('Confirm this delivery was received?')">
                                    @csrf
                                    <input type="hidden" name="qr_token" value="{{ $delivery->qr_token }}">
                                    <button type="submit" class="btn-green">Confirm delivered</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ $query ? 'No deliveries match your search.' : 'No deliveries yet. Create a dispatch to begin tracking.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($deliveries as $delivery)
    <div class="modal fade" id="qrModal{{ $delivery->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content p-4 text-center">
            <h5>{{ $delivery->reference }}</h5>
            <p>{{ $delivery->item->name }} · {{ $delivery->destination }}</p>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&amp;data={{ urlencode('WARELOG:DELIVERY:' . $delivery->qr_token) }}" alt="Delivery verification QR" width="250" height="250" class="mx-auto">
            <code class="d-block mt-2">{{ $delivery->qr_token }}</code>
            <button type="button" class="btn-green mt-2" onclick="window.print()">Print QR</button>
        </div></div>
    </div>
@endforeach

<div class="modal fade" id="dispatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content p-4">
        <h5 class="mb-3">Create delivery dispatch</h5>
        <form method="POST" action="{{ route('logistics.dispatch') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-7"><label for="dispatch_item" class="form-label">Item</label>
                    <select id="dispatch_item" name="item_id" class="form-select" required>
                        <option value="">Select available inventory</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->code }} · {{ $item->name }} ({{ $item->qty }} {{ $item->unit }} available)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5"><label for="dispatch_qty" class="form-label">Quantity</label><input id="dispatch_qty" name="qty" type="number" class="form-control" min="1" step="1" required></div>
                <div class="col-md-12"><label for="dispatch_destination" class="form-label">Destination</label><input id="dispatch_destination" name="destination" type="text" class="form-control" maxlength="255" required></div>
                <div class="col-md-6"><label for="dispatch_driver" class="form-label">Driver</label><input id="dispatch_driver" name="driver" type="text" class="form-control" maxlength="120"></div>
                <div class="col-md-6"><label for="dispatch_vehicle" class="form-label">Vehicle</label><input id="dispatch_vehicle" name="vehicle" type="text" class="form-control" maxlength="120"></div>
                <div class="col-md-6"><label for="dispatch_schedule" class="form-label">Delivery schedule</label><input id="dispatch_schedule" name="scheduled_at" type="datetime-local" class="form-control" required></div>
            </div>
            <div class="mt-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn-red" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-green">Dispatch &amp; create QR</button>
            </div>
        </form>
    </div></div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.getElementById('startDeliveryScanner').addEventListener('click', async function () {
    const scannerElement = document.getElementById('deliveryQrScanner');
    const statusElement = document.getElementById('deliveryScannerStatus');
    const tokenInput = document.getElementById('deliveryQrToken');
    if (!window.Html5Qrcode) {
        statusElement.textContent = 'Camera scanner could not load. Enter the code printed below the QR instead.';
        return;
    }

    scannerElement.hidden = false;
    this.disabled = true;
    const scanner = new Html5Qrcode('deliveryQrScanner');
    try {
        await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 230, height: 230 } }, async text => {
            tokenInput.value = text;
            await scanner.stop();
            document.getElementById('deliveryVerifyForm').requestSubmit();
        }, () => {});
    } catch (error) {
        statusElement.textContent = 'Camera access is unavailable. Allow camera access or enter the QR token manually.';
        this.disabled = false;
    }
});
</script>
@endpush