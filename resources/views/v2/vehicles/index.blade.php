@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Own Vehicles'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="card-title mb-1">Own Vehicle Register</h4>
              <p class="card-description mb-0">Vehicles tracked through the WheelsEye integration.</p>
            </div>
            <a href="{{ route('v2.vehicles.create') }}" class="btn btn-primary btn-icon-text">
              <i class="ti-plus btn-icon-prepend"></i> Add Vehicle
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-striped v2-table" id="vehicles-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Vehicle Number</th>
                  <th>Created At</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($vehicles as $vehicle)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $vehicle->vehicleNo }}</td>
                    <td>{{ $vehicle->created_at }}</td>
                    <td class="text-end">
                      <a href="{{ route('v2.vehicles.show', $vehicle) }}" class="btn btn-outline-info btn-sm">View</a>
                      <a href="{{ route('v2.vehicles.edit', $vehicle) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                      <form class="d-inline" method="POST" action="{{ route('v2.vehicles.destroy', $vehicle) }}" onsubmit="return window.V2.confirmDelete(this, 'Remove this vehicle?');">
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
      window.V2.initDataTable('#vehicles-table');
    });
  </script>
@endsection
