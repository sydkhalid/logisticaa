<!-- Preloader -->
<div class="preloader flex-column justify-content-center align-items-center">
    <h2>{{ $set['name'] }}</h2>
  </div>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item dropdown">
        <a href="javascript:void" onclick="$('#logout-form').submit();" class="nav-link">
           @auth
               {{ auth()->user()->name }} <i class="nav-icon fas fa-sign-out-alt"></i>
           @endauth
        </a>
        <form id="logout-form" action="{{ route('v2.logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
      </li>

    </ul>
  </nav>
  <!-- /.navbar -->
