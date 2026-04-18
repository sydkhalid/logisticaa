<!DOCTYPE html>
<html lang="en">
@include('v2.layouts.partials.head')
<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth auth-bg-1 theme-one">
        @yield('content')
      </div>
    </div>
  </div>

  <script src="{{ asset('v2/assets/vendors/js/vendor.bundle.base.js') }}"></script>
  <script src="{{ asset('v2/assets/js/off-canvas.js') }}"></script>
  <script src="{{ asset('v2/assets/js/template.js') }}"></script>
  <script src="{{ asset('v2/assets/js/settings.js') }}"></script>
</body>
</html>
