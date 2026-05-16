@php
  $dashboardActive = request()->routeIs('v2.home', 'v2.home.attention');
  $vehiclesActive = request()->routeIs('v2.vehicles.*');
  $marketVehiclesActive = request()->routeIs('v2.market-vehicles.*');
  $activeLrActive = request()->routeIs(
    'v2.lr-trackings.index',
    'v2.lr-trackings.data',
    'v2.lr-trackings.create',
    'v2.lr-trackings.store',
    'v2.lr-trackings.show',
    'v2.lr-trackings.edit',
    'v2.lr-trackings.update',
    'v2.lr-trackings.refresh',
    'v2.lr-trackings.vehicle-availability'
  );
  $completedLrActive = request()->routeIs('v2.lr-trackings.completed', 'v2.lr-trackings.completed.data');
  $trackingOpen = $activeLrActive || $completedLrActive;
  $weightCorrectionsActive = request()->routeIs('v2.weight-corrections.*');
  $epodsActive = request()->routeIs('v2.epods.*');
  $reportsActive = request()->routeIs('v2.reports.*');
  $logsActive = request()->routeIs('v2.logs.*');
  $integrationsActive = request()->routeIs('v2.integrations.*');
  $settingsActive = request()->routeIs('v2.settings.*');
  $isAdmin = auth()->check() && method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin();
@endphp
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-section-label">Navigation</div>
  <ul class="nav">
    <li class="nav-item {{ $dashboardActive ? 'active' : '' }}">
      <a class="nav-link {{ $dashboardActive ? 'active' : '' }}" href="{{ route('v2.home') }}" @if($dashboardActive) aria-current="page" @endif>
        <i class="icon-grid menu-icon"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>
    <li class="nav-item {{ $vehiclesActive ? 'active' : '' }}">
      <a class="nav-link {{ $vehiclesActive ? 'active' : '' }}" href="{{ route('v2.vehicles.index') }}" @if($vehiclesActive) aria-current="page" @endif>
        <i class="icon-grid-2 menu-icon"></i>
        <span class="menu-title">Own Vehicles</span>
      </a>
    </li>
    <li class="nav-item {{ $marketVehiclesActive ? 'active' : '' }}">
      <a class="nav-link {{ $marketVehiclesActive ? 'active' : '' }}" href="{{ route('v2.market-vehicles.index') }}" @if($marketVehiclesActive) aria-current="page" @endif>
        <i class="icon-map menu-icon"></i>
        <span class="menu-title">Market Vehicles</span>
      </a>
    </li>
    <li class="nav-item {{ $trackingOpen ? 'active' : '' }}">
      <a class="nav-link {{ $trackingOpen ? 'active' : '' }}" data-bs-toggle="collapse" href="#trackingMenu" aria-expanded="{{ $trackingOpen ? 'true' : 'false' }}" aria-controls="trackingMenu">
        <i class="icon-layout menu-icon"></i>
        <span class="menu-title">LR Tracking</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ $trackingOpen ? 'show' : '' }}" id="trackingMenu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item {{ $activeLrActive ? 'active' : '' }}">
            <a class="nav-link {{ $activeLrActive ? 'active' : '' }}" href="{{ route('v2.lr-trackings.index') }}" @if($activeLrActive) aria-current="page" @endif>
              Active LR
            </a>
          </li>
          <li class="nav-item {{ $completedLrActive ? 'active' : '' }}">
            <a class="nav-link {{ $completedLrActive ? 'active' : '' }}" href="{{ route('v2.lr-trackings.completed') }}" @if($completedLrActive) aria-current="page" @endif>
              Completed LR
            </a>
          </li>
        </ul>
      </div>
    </li>
    <li class="nav-item {{ $weightCorrectionsActive ? 'active' : '' }}">
      <a class="nav-link {{ $weightCorrectionsActive ? 'active' : '' }}" href="{{ route('v2.weight-corrections.index') }}" @if($weightCorrectionsActive) aria-current="page" @endif>
        <i class="icon-bar-graph menu-icon"></i>
        <span class="menu-title">Weight Corrections</span>
      </a>
    </li>
    <li class="nav-item {{ $epodsActive ? 'active' : '' }}">
      <a class="nav-link {{ $epodsActive ? 'active' : '' }}" href="{{ route('v2.epods.index') }}" @if($epodsActive) aria-current="page" @endif>
        <i class="icon-paper menu-icon"></i>
        <span class="menu-title">EPOD Uploads</span>
      </a>
    </li>
    <li class="nav-item {{ $reportsActive ? 'active' : '' }}">
      <a class="nav-link {{ $reportsActive ? 'active' : '' }}" href="{{ route('v2.reports.index') }}" @if($reportsActive) aria-current="page" @endif>
        <i class="icon-pie-graph menu-icon"></i>
        <span class="menu-title">Reports</span>
      </a>
    </li>
    @if($isAdmin)
      <li class="nav-item {{ $logsActive ? 'active' : '' }}">
        <a class="nav-link {{ $logsActive ? 'active' : '' }}" href="{{ route('v2.logs.index') }}" @if($logsActive) aria-current="page" @endif>
          <i class="icon-book menu-icon"></i>
          <span class="menu-title">System Logs</span>
        </a>
      </li>
      <li class="nav-item {{ $integrationsActive ? 'active' : '' }}">
        <a class="nav-link {{ $integrationsActive ? 'active' : '' }}" href="{{ route('v2.integrations.index') }}" @if($integrationsActive) aria-current="page" @endif>
          <i class="icon-link menu-icon"></i>
          <span class="menu-title">Integrations</span>
        </a>
      </li>
      <li class="nav-item {{ $settingsActive ? 'active' : '' }}">
        <a class="nav-link {{ $settingsActive ? 'active' : '' }}" href="{{ route('v2.settings.edit') }}" @if($settingsActive) aria-current="page" @endif>
          <i class="icon-head menu-icon"></i>
          <span class="menu-title">Settings</span>
        </a>
      </li>
    @endif
  </ul>
</nav>
