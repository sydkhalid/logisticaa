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
          @include('v2.layouts.partials.consent-banner')
          @include('v2.layouts.partials.flash')
          @yield('content')
        </div>
        @include('v2.layouts.partials.footer')
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
  <script src="{{ \App\Support\V2Routing::asset('assets/vendors/chart.js/chart.umd.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::publicAsset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::publicAsset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::publicAsset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::publicAsset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/vendors/select2/select2.min.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/vendors/sweetalert2/sweetalert2.min.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/js/off-canvas.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/js/template.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/js/settings.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/js/todolist.js') }}"></script>
  <script src="{{ \App\Support\V2Routing::asset('assets/js/v2.js') }}"></script>
  @yield('scripts')
</body>
</html>
