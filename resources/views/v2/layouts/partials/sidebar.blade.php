@php
  $trackingOpen = request()->routeIs('v2.lr-trackings.*');
  $isAdmin = auth()->check() && method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin();
@endphp
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-overview">
    <span class="sidebar-overview__eyebrow">Control Center</span>
    <strong class="sidebar-overview__title">Logistics Workspace</strong>
    <small class="sidebar-overview__meta">{{ auth()->user()->name ?? 'User' }}</small>
  </div>
  <div class="sidebar-section-label">Navigation</div>
  <ul class="nav">
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('v2.home') ? 'active' : '' }}" href="{{ route('v2.home') }}">
        <i class="icon-grid menu-icon"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('v2.vehicles.*') ? 'active' : '' }}" href="{{ route('v2.vehicles.index') }}">
        <i class="icon-grid-2 menu-icon"></i>
        <span class="menu-title">Own Vehicles</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('v2.market-vehicles.*') ? 'active' : '' }}" href="{{ route('v2.market-vehicles.index') }}">
        <i class="icon-map menu-icon"></i>
        <span class="menu-title">Market Vehicles</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $trackingOpen ? 'active' : '' }}" data-bs-toggle="collapse" href="#trackingMenu" aria-expanded="{{ $trackingOpen ? 'true' : 'false' }}" aria-controls="trackingMenu">
        <i class="icon-layout menu-icon"></i>
        <span class="menu-title">LR Tracking</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ $trackingOpen ? 'show' : '' }}" id="trackingMenu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('v2.lr-trackings.index') || request()->routeIs('v2.lr-trackings.create') || request()->routeIs('v2.lr-trackings.edit') || request()->routeIs('v2.lr-trackings.show') ? 'active' : '' }}" href="{{ route('v2.lr-trackings.index') }}">
              Active LR
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('v2.lr-trackings.completed') ? 'active' : '' }}" href="{{ route('v2.lr-trackings.completed') }}">
              Completed LR
            </a>
          </li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('v2.weight-corrections.*') ? 'active' : '' }}" href="{{ route('v2.weight-corrections.index') }}">
        <i class="icon-bar-graph menu-icon"></i>
        <span class="menu-title">Weight Corrections</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('v2.epods.*') ? 'active' : '' }}" href="{{ route('v2.epods.index') }}">
        <i class="icon-paper menu-icon"></i>
        <span class="menu-title">EPOD Uploads</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('v2.reports.*') ? 'active' : '' }}" href="{{ route('v2.reports.index') }}">
        <i class="icon-pie-graph menu-icon"></i>
        <span class="menu-title">Reports</span>
      </a>
    </li>
    @if($isAdmin)
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('v2.logs.*') ? 'active' : '' }}" href="{{ route('v2.logs.index') }}">
          <i class="icon-book menu-icon"></i>
          <span class="menu-title">System Logs</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('v2.integrations.*') ? 'active' : '' }}" href="{{ route('v2.integrations.index') }}">
          <i class="icon-link menu-icon"></i>
          <span class="menu-title">Integrations</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('v2.settings.*') ? 'active' : '' }}" href="{{ route('v2.settings.edit') }}">
          <i class="icon-head menu-icon"></i>
          <span class="menu-title">Settings</span>
        </a>
      </li>
    @endif
  </ul>
</nav>
