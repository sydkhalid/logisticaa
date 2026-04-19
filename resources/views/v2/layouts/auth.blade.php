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
    <div class="v2-page-loader__panel">
      <span class="v2-page-loader__spinner" aria-hidden="true"></span>
      <div class="v2-page-loader__copy">
        <strong class="v2-page-loader__title">
          <span class="v2-page-loader__title-text">Loading data</span>
          <span class="v2-page-loader__dots" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
          </span>
        </strong>
        <small class="v2-page-loader__subtitle">Preparing the next view.</small>
      </div>
    </div>
  </div>

  <script src="{{ asset('v2/assets/vendors/js/vendor.bundle.base.js') }}"></script>
  <script src="{{ asset('v2/assets/vendors/sweetalert2/sweetalert2.min.js') }}"></script>
  <script src="{{ asset('v2/assets/js/off-canvas.js') }}"></script>
  <script src="{{ asset('v2/assets/js/template.js') }}"></script>
  <script src="{{ asset('v2/assets/js/settings.js') }}"></script>
  <script src="{{ asset('v2/assets/js/v2.js') }}"></script>
  @yield('scripts')
</body>
</html>
