@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Market Vehicles'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="card-title mb-1">Market Vehicle Register</h4>
              <p class="card-description mb-0">SIM-based vehicles managed through the FleetX workflow.</p>
            </div>
            <a href="{{ route('v2.market-vehicles.create') }}" class="btn btn-primary btn-icon-text">
              <i class="ti-plus btn-icon-prepend"></i> Add Market Vehicle
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-striped v2-table" id="market-vehicles-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Vehicle Number</th>
                  <th>Mobile Number</th>
                  <th>SIM Provider</th>
                  <th>Expiry</th>
                  <th>Stopped</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($vehicles as $vehicle)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $vehicle->vehicleNo }}</td>
                    <td>{{ $vehicle->mobileNo }}</td>
                    <td>{{ $vehicle->simProvider }}</td>
                    <td>{{ $vehicle->expireDate }}</td>
                    <td>
                      <span class="badge badge-{{ (int) $vehicle->statusStop === 1 ? 'danger' : 'success' }}">
                        {{ (int) $vehicle->statusStop === 1 ? 'Stopped' : 'Active' }}
                      </span>
                    </td>
                    <td class="text-end">
                      <a href="{{ route('v2.market-vehicles.show', $vehicle) }}" class="btn btn-outline-info btn-sm">View</a>
                      <a href="{{ route('v2.market-vehicles.edit', $vehicle) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                      <form class="d-inline" method="POST" action="{{ route('v2.market-vehicles.status', $vehicle) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm">Check</button>
                      </form>
                      <form class="d-inline" method="POST" action="{{ route('v2.market-vehicles.stop-tracking', $vehicle) }}" onsubmit="return window.V2.confirmDelete(this, 'Stop SIM tracking for this vehicle?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning btn-sm">Stop</button>
                      </form>
                      <form class="d-inline" method="POST" action="{{ route('v2.market-vehicles.destroy', $vehicle) }}" onsubmit="return window.V2.confirmDelete(this, 'Remove this market vehicle?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    window.addEventListener('DOMContentLoaded', function () {
      window.V2.initDataTable('#market-vehicles-table');
    });
  </script>
@endsection
