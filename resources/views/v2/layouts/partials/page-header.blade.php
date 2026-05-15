@php
  $breadcrumbs = $breadcrumbs ?? [];
@endphp
<div class="row">
  <div class="col-md-12 grid-margin">
    <div class="row align-items-center">
      <div class="col-12 col-lg-8">
        <h3 class="font-weight-bold">{{ $pageTitle ?? 'Dashboard' }}</h3>
        @if (!empty($pageDescription))
          <h6 class="font-weight-normal mb-0">{{ $pageDescription }}</h6>
        @endif
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
