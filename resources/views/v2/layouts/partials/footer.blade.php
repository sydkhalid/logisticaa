<footer class="footer">
  <div class="d-sm-flex justify-content-center justify-content-sm-between">
    <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">
      {{ ($settings && $settings->copyright) ? $settings->copyright : ('Copyright ' . date('Y')) }}
    </span>
    <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">
      {{ $appName }}
    </span>
  </div>
</footer>
