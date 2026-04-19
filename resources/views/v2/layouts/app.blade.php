<!DOCTYPE html>
<html lang="en">
@include('v2.layouts.partials.head')
<body>
  <div class="container-scroller">
    @include('v2.layouts.partials.navbar')
    <div class="container-fluid page-body-wrapper">
      @include('v2.layouts.partials.sidebar')
      <div class="main-panel">
        <div class="content-wrapper">
          @include('v2.layouts.partials.page-header')
          @include('v2.layouts.partials.flash')
          @yield('content')
        </div>
        @include('v2.layouts.partials.footer')
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
  <script src="{{ asset('v2/assets/vendors/chart.js/chart.umd.js') }}"></script>
  <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
  <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
  <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
  <script src="{{ asset('v2/assets/vendors/select2/select2.min.js') }}"></script>
  <script src="{{ asset('v2/assets/vendors/sweetalert2/sweetalert2.min.js') }}"></script>
  <script src="{{ asset('v2/assets/js/off-canvas.js') }}"></script>
  <script src="{{ asset('v2/assets/js/template.js') }}"></script>
  <script src="{{ asset('v2/assets/js/settings.js') }}"></script>
  <script src="{{ asset('v2/assets/js/todolist.js') }}"></script>
  <script src="{{ asset('v2/assets/js/v2.js') }}"></script>
  @yield('scripts')
</body>
</html>
