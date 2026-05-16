@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Own Vehicles', 'url' => route('v2.vehicles.index')],
    ['label' => $vehicle->vehicleNo],
  ];
  $raw = $location['raw'] ?? [];
@endphp

@section('content')
  <div class="row">
    <div class="col-md-5 grid-margin stretch-card">
      <div class="card v2-detail-card">
        <div class="card-body">
          <div class="v2-card-heading mb-3">
            <span class="v2-card-heading__icon"><i class="mdi mdi-truck-outline"></i></span>
            <h4 class="card-title mb-0">Vehicle Summary</h4>
          </div>
          <div class="detail-grid">
            <div><span>Vehicle Number</span><strong>{{ $vehicle->vehicleNo }}</strong></div>
            <div><span>Tracking Source</span><strong>WheelsEye</strong></div>
            <div><span>Device Number</span><strong>{{ $raw['deviceNumber'] ?? 'N/A' }}</strong></div>
            <div><span>Vendor Code</span><strong>{{ $raw['vendorCode'] ?? 'N/A' }}</strong></div>
            <div><span>Vendor Name</span><strong>{{ $raw['venndorName'] ?? 'N/A' }}</strong></div>
            <div><span>Vehicle Type</span><strong>{{ $raw['vehicleType'] ?? 'N/A' }}</strong></div>
            <div><span>Speed</span><strong>{{ $raw['speed'] ?? 'N/A' }}</strong></div>
            <div><span>Ignition</span><strong>{{ $raw['ignition'] ?? 'N/A' }}</strong></div>
            <div><span>Angle</span><strong>{{ $raw['angle'] ?? 'N/A' }}</strong></div>
            <div><span>Charge On</span><strong>{{ $raw['chargeOn'] ?? 'N/A' }}</strong></div>
            <div><span>Latitude</span><strong>{{ $location['latitude'] ?? 'N/A' }}</strong></div>
            <div><span>Longitude</span><strong>{{ $location['longitude'] ?? 'N/A' }}</strong></div>
          </div>
          <div class="mt-3">
            <span class="text-muted d-block mb-2">Address</span>
            <p class="mb-0">{{ $location['location'] ?? 'Live location not available.' }}</p>
          </div>
          @if ($warning)
            <div class="alert alert-warning mt-3 mb-0">{{ $warning }}</div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-md-7 grid-margin stretch-card">
      <div class="card v2-detail-card">
        <div class="card-body">
          <div class="v2-card-heading mb-3">
            <span class="v2-card-heading__icon"><i class="mdi mdi-map-marker-radius"></i></span>
            <h4 class="card-title mb-0">Map Preview</h4>
          </div>
          @if (!empty($location['latitude']) && !empty($location['longitude']))
            <iframe
              class="map-frame"
              src="https://www.google.com/maps?q={{ $location['latitude'] }},{{ $location['longitude'] }}&hl=en&z=14&output=embed"
              loading="lazy">
            </iframe>
          @else
            <div class="empty-state">
              <p class="mb-0">No live coordinates available for this vehicle right now.</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
