<nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
    <a class="navbar-brand brand-logo me-5 brand-lockup" href="{{ route('v2.home') }}" aria-label="{{ $appName }}">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-copy brand-copy--desktop">{{ $appName }}</span>
    </a>
    <a class="navbar-brand brand-logo-mini brand-lockup-mini" href="{{ route('v2.home') }}" aria-label="{{ $appName }}">
      <span class="brand-mini-mark" aria-hidden="true"></span>
      <span class="brand-copy brand-copy--mobile">{{ $appName }}</span>
    </a>
  </div>
  <div class="navbar-menu-wrapper d-flex align-items-center justify-content-between">
    <div class="v2-navbar-start">
      <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize" aria-label="Toggle sidebar">
        <span class="icon-menu"></span>
      </button>
    </div>
    <ul class="navbar-nav navbar-nav-right v2-navbar-actions">
      <li class="nav-item nav-profile dropdown">
        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="profileDropdown">
          <div class="profile-tile">
            <span class="profile-tile__name">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
          </div>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
          <a class="dropdown-item" href="{{ route('v2.settings.edit') }}">
            <i class="ti-settings text-primary"></i> Settings
          </a>
          <form method="POST" action="{{ route('v2.logout') }}">
            @csrf
            <button type="submit" class="dropdown-item">
              <i class="ti-power-off text-primary"></i> Logout
            </button>
          </form>
        </div>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas" aria-label="Open menu">
      <span class="icon-menu"></span>
    </button>
  </div>
</nav>
