<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WareLog ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif}
        body{background:#d9d9d9;display:flex;flex-direction:column;min-height:100vh}
        /* TOP BAR */
        .topbar{background:#1a3c3c;color:white;padding:12px 20px;display:flex;justify-content:space-between;align-items:center;font-size:20px;font-weight:bold;letter-spacing:1px}
        .topbar .icons{display:flex;gap:15px;align-items:center;font-size:20px}
        /* LAYOUT */
        .wrapper{display:flex;flex:1}
        /* SIDEBAR */
        .sidebar{width:160px;background:#2b2b2b;color:white;padding-top:10px;flex-shrink:0}
        .sidebar a{display:flex;align-items:center;gap:10px;color:#bbb;padding:14px 18px;text-decoration:none;font-size:14px;border-left:4px solid transparent}
        .sidebar a:hover,.sidebar a.active{background:#3a3a3a;color:white;border-left:4px solid #f0a500}
        .sidebar .icon{font-size:18px;width:22px;text-align:center}
        /* CONTENT */
        .content{flex:1;padding:20px;overflow-y:auto}
        .page-title{font-size:22px;font-weight:bold;margin-bottom:15px;display:flex;align-items:center;gap:10px}
        .stat-card{background:white;border-radius:6px;padding:15px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.1)}
        .stat-card .num{font-size:24px;font-weight:bold;margin-top:5px}
        .stat-card .label{font-size:12px;color:#666;margin-top:3px}
        .card-white{background:white;border-radius:6px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:15px}
        .btn-green{background:#28a745;color:white;border:none;border-radius:4px;padding:8px 16px;font-size:14px}
        .btn-green:hover{background:#218838;color:white}
        .btn-red{background:#dc3545;color:white;border:none;border-radius:4px;padding:8px 16px;font-size:14px}
        .btn-orange{background:#fd7e14;color:white;border:none;border-radius:4px;padding:8px 16px;font-size:14px}
        .btn-blue{background:#007bff;color:white;border:none;border-radius:4px;padding:6px 14px;font-size:13px}
        table{width:100%;border-collapse:collapse;font-size:13px}
        table th{background:#2b4a4a;color:white;padding:10px;text-align:left}
        table td{padding:10px;border-bottom:1px solid #eee}
        .badge-good{background:#28a745;color:white;padding:2px 8px;border-radius:10px;font-size:11px}
        .badge-low{background:#ffc107;color:#333;padding:2px 8px;border-radius:10px;font-size:11px}
        .badge-out{background:#dc3545;color:white;padding:2px 8px;border-radius:10px;font-size:11px}
        .badge-active{background:#28a745;color:white;padding:2px 8px;border-radius:10px;font-size:11px}
        .badge-transit{background:#007bff;color:white;padding:2px 8px;border-radius:10px;font-size:11px}
        .badge-delivered{background:#28a745;color:white;padding:2px 8px;border-radius:10px;font-size:11px}
        .badge-dispatch{background:#fd7e14;color:white;padding:2px 8px;border-radius:10px;font-size:11px}
        .form-control,.form-select{font-size:13px}
        .toggle{width:40px;height:20px;background:#ccc;border-radius:10px;position:relative;display:inline-block;cursor:pointer}
        .toggle.on{background:#28a745}
        .toggle::after{content:'';position:absolute;width:16px;height:16px;background:white;border-radius:50%;top:2px;left:2px;transition:0.2s}
        .toggle.on::after{left:22px}
    </style>
</head>
<body>

<div class="topbar">
    <span>WARELOG ERP SYSTEM</span>
    <div class="icons">
        <a href="/settings" style="color:white;text-decoration:none">⚙️</a>
        <span>👤</span>
    </div>
</div>

<div class="wrapper">
    @php $isAdmin = Auth::check() && Auth::user()->role === 'admin'; @endphp
    <div class="sidebar">
        @if($isAdmin)
            <a href="/dashboard" class="{{ Request::is('dashboard') ? 'active' : '' }}"><span class="icon">🖥️</span> Dashboard</a>
            <a href="/inventory" class="{{ Request::is('inventory') ? 'active' : '' }}"><span class="icon">📦</span> Inventory</a>
            <a href="/reports" class="{{ Request::is('reports') ? 'active' : '' }}"><span class="icon">📋</span> Reports</a>
            <a href="/settings" class="{{ Request::is('settings') ? 'active' : '' }}"><span class="icon">⚙️</span> Settings</a>
        @else
            <a href="/stockin" class="{{ Request::is('stockin') ? 'active' : '' }}"><span class="icon">📥</span> Stock In</a>
            <a href="/stockout" class="{{ Request::is('stockout') ? 'active' : '' }}"><span class="icon">📤</span> Stock Out</a>
            <a href="/logistics" class="{{ Request::is('logistics') ? 'active' : '' }}"><span class="icon">🚚</span> Logistics</a>
            <a href="/reports" class="{{ Request::is('reports') ? 'active' : '' }}"><span class="icon">📋</span> Reports</a>
            <a href="/settings" class="{{ Request::is('settings') ? 'active' : '' }}"><span class="icon">⚙️</span> Settings</a>
        @endif
        <form method="POST" action="{{ route('logout') }}" style="margin-top:20px">
            @csrf
            <button type="submit" style="display:flex;align-items:center;gap:10px;color:#bbb;padding:14px 18px;text-decoration:none;font-size:14px;background:none;border:none;width:100%;text-align:left;cursor:pointer;border-left:4px solid transparent">
                <span class="icon">🚪</span> Logout
            </button>
        </form>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>