@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'LR Tracking'],
    ['label' => $showCompleted ? 'Completed' : 'Active'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="card-title mb-1">{{ $showCompleted ? 'Completed LR Records' : 'Active LR Records' }}</h4>
              <p class="card-description mb-0">Track shipment state, location sync, and delivery readiness.</p>
            </div>
            <a href="{{ route('v2.lr-trackings.create') }}" class="btn btn-primary btn-icon-text">
              <i class="ti-plus btn-icon-prepend"></i> Add LR Tracking
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-striped v2-table" id="trackings-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Vehicle</th>
                  <th>LSP ID</th>
                  <th>LR Number</th>
                  <th>LR Date</th>
                  <th>Status</th>
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
      window.V2.initDataTable('#trackings-table', {
        serverSide: true,
        processing: true,
        ajax: '{{ $showCompleted ? route('v2.lr-trackings.completed.data') : route('v2.lr-trackings.data') }}',
        order: [[0, 'desc']],
        columns: [
          { data: 'index', name: 'index' },
          { data: 'vehicleNo', name: 'vehicleNo' },
          { data: 'lspId', name: 'lspId' },
          { data: 'lrNumber', name: 'lrNumber' },
          { data: 'lrDate', name: 'lrDate' },
          { data: 'status', name: 'status' },
          { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ]
      });
    });
  </script>
@endsection
