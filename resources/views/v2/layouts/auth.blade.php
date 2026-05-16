<!DOCTYPE html>
<html lang="en">
@include('v2.layouts.partials.head')
<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth auth-bg-1 theme-one">
        <div class="w-100">
          <div class="row">
            <div class="col-12">
              @include('v2.layouts.partials.flash')
            </div>
          </div>
          @yield('content')
        </div>
      </div>
    </div>
  </div>
  <div id="v2-page-loader" class="v2-page-loader" hidden>
    <div class="v2-page-loader__panel" role="status" aria-live="polite" aria-label="Loading">
      <div class="v2-page-loader__motion" aria-hidden="true">
        <span class="v2-page-loader__halo"></span>
        <span class="v2-page-loader__pulse"></span>
        <span class="v2-page-loader__spinner"></span>
        <span class="v2-page-loader__dot v2-page-loader__dot--one"></span>
        <span class="v2-page-loader__dot v2-page-loader__dot--two"></span>
        <span class="v2-page-loader__dot v2-page-loader__dot--three"></span>
      </div>
    </div>
  </div>

  <script src="{{ \App\Support\V2Routing::asset('assets/vendors/js/vendor.bundle.base.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/vendors/sweetalert2/sweetalert2.min.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/js/off-canvas.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/js/template.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/js/settings.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/js/v2.js') }}"></script>
  @yield('scripts')
</body>
</html>
