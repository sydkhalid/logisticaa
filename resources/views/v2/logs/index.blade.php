@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'System Logs'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-tale">
        <div class="card-body">
          <p class="mb-4">Total Logs</p>
          <p class="fs-30 mb-2">{{ $summary['total'] }}</p>
          <p>Records in the selected date range</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-light-blue">
        <div class="card-body">
          <p class="mb-4">Success And Info</p>
          <p class="fs-30 mb-2">{{ $summary['success'] }}</p>
          <p>Healthy request activity</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-light-danger">
        <div class="card-body">
          <p class="mb-4">Warnings</p>
          <p class="fs-30 mb-2">{{ $summary['warning'] }}</p>
          <p>Requests needing attention</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-dark-blue">
        <div class="card-body">
          <p class="mb-4">Errors</p>
          <p class="fs-30 mb-2">{{ $summary['danger'] }}</p>
          <p>Failed or exceptional activity</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4 class="card-title mb-1">System Activity Log</h4>
              <p class="card-description mb-0">All saved user and system activity with date-wise filtering.</p>
            </div>
          </div>

          <form method="GET" action="{{ route('v2.logs.index') }}" class="row mb-4 g-3">
            <div class="col-md-3">
              <label for="log-from">From Date</label>
              <input type="date" class="form-control" id="log-from" name="from" value="{{ $filters['from'] }}">
            </div>
            <div class="col-md-3">
              <label for="log-to">To Date</label>
              <input type="date" class="form-control" id="log-to" name="to" value="{{ $filters['to'] }}">
            </div>
            <div class="col-md-2">
              <label for="log-type">Type</label>
              <select class="form-control" id="log-type" name="type">
                <option value="">All Types</option>
                @foreach ($types as $type)
                  <option value="{{ $type }}" {{ $filters['type'] === $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label for="log-actor">Actor</label>
              <input type="text" class="form-control" id="log-actor" name="actor" value="{{ $filters['actor'] }}" placeholder="User or system">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary w-100">Apply</button>
              <a href="{{ route('v2.logs.index') }}" class="btn btn-outline-primary w-100">Reset</a>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-striped v2-table" id="system-logs-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Title</th>
                  <th>Actor</th>
                  <th>URI</th>
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
      window.V2.initDataTable('#system-logs-table', {
        serverSide: true,
        processing: true,
        ajax: {
          url: '{{ route('v2.logs.data') }}',
          data: function (data) {
            data.from = document.getElementById('log-from').value;
            data.to = document.getElementById('log-to').value;
            data.type = document.getElementById('log-type').value;
            data.actor = document.getElementById('log-actor').value;
          }
        },
        order: [[1, 'desc']],
        columns: [
          { data: 'index', name: 'index' },
          { data: 'created_at', name: 'created_at' },
          { data: 'type', name: 'type' },
          { data: 'title', name: 'title' },
          { data: 'created_by', name: 'created_by' },
          { data: 'uri', name: 'uri' },
          { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ]
      });
    });
  </script>
@endsection
