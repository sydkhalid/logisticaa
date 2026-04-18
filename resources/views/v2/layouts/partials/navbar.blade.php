<nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
    <a class="navbar-brand brand-logo me-5 d-flex align-items-center gap-2" href="{{ route('v2.home') }}">
      <img src="{{ asset('v2/assets/images/logo.svg') }}" class="me-2" alt="logo">
      <span class="brand-copy">{{ $appName }}</span>
    </a>
    <a class="navbar-brand brand-logo-mini" href="{{ route('v2.home') }}">
      <img src="{{ asset('v2/assets/images/logo-mini.svg') }}" alt="logo">
    </a>
  </div>
  <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
      <span class="icon-menu"></span>
    </button>
    <ul class="navbar-nav me-auto">
      <li class="nav-item nav-search d-none d-lg-block">
        <div class="input-group">
          <div class="input-group-prepend hover-cursor">
            <span class="input-group-text">
              <i class="icon-search"></i>
            </span>
          </div>
          <input type="text" class="form-control" value="{{ $pageTitle ?? 'Dashboard' }}" readonly>
        </div>
      </li>
    </ul>
    <ul class="navbar-nav navbar-nav-right">
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
      <li class="nav-item nav-settings d-none d-lg-flex">
        <span class="nav-link nav-chip">
          {{ auth()->user()->name ?? 'User' }}
        </span>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
      <span class="icon-menu"></span>
    </button>
  </div>
</nav>
