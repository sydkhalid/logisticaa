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
              <tbody></tbody>
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
      window.V2.initDataTable('#market-vehicles-table', {
        serverSide: true,
        processing: true,
        ajax: '{{ route('v2.market-vehicles.data') }}',
        order: [[0, 'desc']],
        columns: [
          { data: 'index', name: 'index' },
          { data: 'vehicleNo', name: 'vehicleNo' },
          { data: 'mobileNo', name: 'mobileNo' },
          { data: 'simProvider', name: 'simProvider' },
          { data: 'expireDate', name: 'expireDate' },
          { data: 'statusStop', name: 'statusStop' },
          { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ]
      });
    });
  </script>
@endsection
