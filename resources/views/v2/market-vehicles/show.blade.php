@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Market Vehicles', 'url' => route('v2.market-vehicles.index')],
    ['label' => $vehicle->vehicleNo],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-md-5 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Vehicle Summary</h4>
          <div class="detail-grid">
            <div><span>Vehicle Number</span><strong>{{ $vehicle->vehicleNo }}</strong></div>
            <div><span>Mobile Number</span><strong>{{ $vehicle->mobileNo }}</strong></div>
            <div><span>SIM Provider</span><strong>{{ $vehicle->simProvider }}</strong></div>
            <div><span>Expiry</span><strong>{{ $vehicle->expireDate }}</strong></div>
            <div><span>Fleet Vehicle ID</span><strong>{{ $details['vehicleId'] ?? 'N/A' }}</strong></div>
            <div><span>Driver Name</span><strong>{{ $details['driverName'] ?? 'N/A' }}</strong></div>
            <div><span>Status</span><strong>{{ $details['status'] ?? 'N/A' }}</strong></div>
            <div><span>Vehicle Type</span><strong>{{ $details['vehicleTypeValue'] ?? 'N/A' }}</strong></div>
            <div><span>Speed</span><strong>{{ $details['speed'] ?? 'N/A' }}</strong></div>
            <div><span>Latitude</span><strong>{{ $details['latitude'] ?? 'N/A' }}</strong></div>
            <div><span>Longitude</span><strong>{{ $details['longitude'] ?? 'N/A' }}</strong></div>
            <div><span>Stopped</span><strong>{{ (int) $vehicle->statusStop === 1 ? 'Yes' : 'No' }}</strong></div>
          </div>
          <div class="mt-3">
            <span class="text-muted d-block mb-2">Address</span>
            <p class="mb-0">{{ $details['address'] ?? 'Live FleetX address not available.' }}</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-7 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Map Preview</h4>
          @if (!empty($details['latitude']) && !empty($details['longitude']))
            <iframe
              class="map-frame"
              src="https://www.google.com/maps?q={{ $details['latitude'] }},{{ $details['longitude'] }}&hl=en&z=14&output=embed"
              loading="lazy">
            </iframe>
          @else
            <div class="empty-state">
              <p class="mb-0">FleetX has not returned live coordinates for this vehicle yet.</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
