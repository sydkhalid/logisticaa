@php
  $breadcrumbs = $breadcrumbs ?? [];
  $pageIcon = $pageIcon ?? 'mdi-view-dashboard-outline';
  $pageIconMap = [
    'v2.home' => 'mdi-view-dashboard-outline',
    'v2.vehicles.*' => 'mdi-truck-outline',
    'v2.market-vehicles.*' => 'mdi-truck-delivery-outline',
    'v2.lr-trackings.*' => 'mdi-clipboard-text-search-outline',
    'v2.weight-corrections.*' => 'mdi-weight',
    'v2.epods.*' => 'mdi-cloud-upload-outline',
    'v2.reports.*' => 'mdi-file-chart-outline',
    'v2.logs.*' => 'mdi-clipboard-text-clock-outline',
    'v2.integrations.*' => 'mdi-link-variant',
    'v2.settings.*' => 'mdi-cog-outline',
  ];

  foreach ($pageIconMap as $routePattern => $icon) {
    if (request()->routeIs($routePattern)) {
      $pageIcon = $icon;
      break;
    }
  }
@endphp
<div class="row">
  <div class="col-md-12 grid-margin">
    <div class="row align-items-center">
      <div class="col-12 col-lg-8">
        <div class="v2-page-heading">
          <span class="v2-page-heading__icon">
            <i class="mdi {{ $pageIcon }}"></i>
          </span>
          <div>
            <h3 class="font-weight-bold mb-1">{{ $pageTitle ?? 'Dashboard' }}</h3>
            @if (!empty($pageDescription))
              <h6 class="font-weight-normal mb-0">{{ $pageDescription }}</h6>
            @endif
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-4 mt-3 mt-lg-0">
        @if (!empty($breadcrumbs))
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-custom justify-content-start justify-content-lg-end mb-0">
              <li class="breadcrumb-item"><a href="{{ route('v2.home') }}">Home</a></li>
              @foreach ($breadcrumbs as $breadcrumb)
                <li class="breadcrumb-item {{ empty($breadcrumb['url']) ? 'active' : '' }}">
                  @if (!empty($breadcrumb['url']))
                    <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                  @else
                    {{ $breadcrumb['label'] }}
                  @endif
                </li>
              @endforeach
            </ol>
          </nav>
        @endif
      </div>
    </div>
  </div>
</div>
