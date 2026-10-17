@extends('layouts.navigation')
@section('content')

<style>
    .settings-card {
        background: #fff;
        border-radius: 8px;
        padding: 25px;
        border: 1px solid #e5e7eb;
        height: 100%;
    }
    .toggle-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .toggle-switch {
        position: relative;
        width: 48px;
        height: 24px;
    }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #d1d5db;
        border-radius: 24px;
        transition: 0.3s;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px; width: 18px;
        left: 3px; bottom: 3px;
        background-color: white;
        border-radius: 50%;
        transition: 0.3s;
    }
    .toggle-switch input:checked + .toggle-slider { background-color: #22c55e; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }
    .btn-save { background: #22c55e; color: white; border: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; }
    .btn-reset { background: #f97316; color: white; border: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; }
    .page-indicator { text-align: center; margin-bottom: 15px; }
    .page-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #d1d5db; margin: 0 4px; }
    .page-dot.active { background: #3b82f6; }
    .settings-page { display: none; }
    .settings-page.active { display: block; }
    .arrow-nav { float: right; background: transparent; border: none; font-size: 20px; cursor: pointer; color: #6b7280; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">⚙️ System Settings</h5>
</div>

@if(session('success'))
    <div class="alert alert-success">✅ Settings saved successfully!</div>
@endif

{{-- PAGE 1: Company & Preferences --}}
<div id="page-1" class="settings-page active">
    <button class="arrow-nav" onclick="nextPage()">→</button>
    
    <form method="POST" action="/settings/save">
        @csrf
        
        <div class="row g-4">
            {{-- COMPANY INFORMATION --}}
            <div class="col-md-6">
                <div class="settings-card">
                    <h6 class="mb-3">🏢 Company Information</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" 
                               value="{{ session('company_name', 'WareLog Solutions') }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" 
                               value="{{ session('address', 'San Jose del Monte, Bulacan') }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact No.</label>
                        <input type="text" name="contact_no" class="form-control" 
                               value="{{ session('contact_no', '09-12-345-6789') }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-select">
                            <option value="PHP" {{ session('currency', 'PHP')=='PHP'?'selected':'' }}>Philippine Peso (₱)</option>
                            <option value="USD" {{ session('currency')=='USD'?'selected':'' }}>US Dollar ($)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            {{-- SYSTEM PREFERENCES --}}
            <div class="col-md-6">
                <div class="settings-card">
                    <h6 class="mb-3">⚙️ System Preferences</h6>
                    
                    <div class="toggle-row">
                        <span>Low Stock Alerts</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="low_stock_alerts" 
                                   {{ session('low_stock_alerts', true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-row">
                        <span>Auto-generate Codes</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_codes" 
                                   {{ session('auto_codes', true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-row">
                        <span>Show Decimals</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_decimals" 
                                   {{ session('show_decimals', true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-row">
                        <span>Dark Mode</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="dark_mode" 
                                   {{ session('dark_mode') ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label">Date Format</label>
                        <select name="date_format" class="form-select">
                            <option value="m/d/Y" {{ session('date_format', 'm/d/Y')=='m/d/Y'?'selected':'' }}>MM/DD/YYYY</option>
                            <option value="d/m/Y" {{ session('date_format')=='d/m/Y'?'selected':'' }}>DD/MM/YYYY</option>
                            <option value="Y-m-d" {{ session('date_format')=='Y-m-d'?'selected':'' }}>YYYY-MM-DD</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-3 mt-4">
            <button type="submit" class="btn-save">💾 Save Changes</button>
            <button type="reset" class="btn-reset">↺ Reset to Default</button>
        </div>
    </form>
</div>

{{-- PAGE 2: Security & About --}}
<div id="page-2" class="settings-page">
    <button class="arrow-nav" onclick="prevPage()">←</button>
    
    <form method="POST" action="/settings/save">
        @csrf
        
        <div class="row g-4">
            {{-- SECURITY & ACCESS --}}
            <div class="col-md-6">
                <div class="settings-card">
                    <h6 class="mb-3">🔒 Security & Access</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Current User</label>
                        <input type="text" class="form-control bg-light" value="{{ Auth::user()->name }} ({{ strtoupper(Auth::user()->role) }})" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Change Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="New password...">
                    </div>
                    
                    <div class="toggle-row">
                        <span>Auto Logout</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_logout" 
                                   {{ session('auto_logout', true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-row">
                        <span>Backup Daily</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="backup_daily" 
                                   {{ session('backup_daily', true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            
            {{-- ABOUT & TOOLS --}}
            <div class="col-md-6">
                <div class="settings-card">
                    <h6 class="mb-3">ℹ️ About & Tools</h6>
                    
                    <p class="text-muted small mb-3">
                        <strong>WareLog ERP System v1.0</strong><br>
                        Design for Warehouse & Inventory Management
                    </p>
                    
                    <div class="toggle-row">
                        <span>Clear Cache / Logs</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="clear_cache">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-row">
                        <span>Export Database</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="export_db">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-3 mt-4">
            <button type="submit" class="btn-save">💾 Save Changes</button>
            <button type="reset" class="btn-reset">↺ Reset to Default</button>
        </div>
    </form>
</div>

{{-- PAGE INDICATORS --}}
<div class="page-indicator mt-3">
    <span class="page-dot active" id="dot-1"></span>
    <span class="page-dot" id="dot-2"></span>
</div>

<script>
let currentPage = 1;
function showPage(page) {
    document.querySelectorAll('.settings-page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.page-dot').forEach(d => d.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    document.getElementById('dot-' + page).classList.add('active');
    currentPage = page;
}
function nextPage() { if (currentPage < 2) showPage(currentPage + 1); }
function prevPage() { if (currentPage > 1) showPage(currentPage - 1); }
</script>
@endsection