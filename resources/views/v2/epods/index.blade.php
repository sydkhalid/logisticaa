@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'EPOD Uploads'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="v2-section-toolbar">
            <div class="v2-card-heading">
              <span class="v2-card-heading__icon"><i class="mdi mdi-cloud-upload-outline"></i></span>
              <div>
                <h4 class="card-title mb-1">EPOD Upload History</h4>
                <p class="card-description mb-0">Upload proof of delivery files without changing the existing Laravel pages.</p>
              </div>
            </div>
            <a href="{{ route('v2.epods.create') }}" class="btn btn-primary btn-icon-text">
              <i class="mdi mdi-cloud-upload-outline"></i>
              <span>Upload EPOD</span>
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-striped v2-table" id="epods-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>LSP ID</th>
                  <th>LR Number</th>
                  <th>Status</th>
                  <th>Created At</th>
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
      window.V2.initDataTable('#epods-table', {
        serverSide: true,
        processing: true,
        ajax: '{{ route('v2.epods.data') }}',
        order: [[0, 'desc']],
        columns: [
          { data: 'index', name: 'index' },
          { data: 'lspId', name: 'lspId' },
          { data: 'lrNumber', name: 'lrNumber' },
          { data: 'status', name: 'status' },
          { data: 'created_at', name: 'created_at' },
          { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ]
      });
    });
  </script>
@endsection
