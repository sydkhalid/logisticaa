@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'System Logs'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-tale v2-stat-card">
        <div class="card-body">
          <div class="v2-stat-card__head mb-4">
            <p class="mb-0">Total Logs</p>
            <span class="v2-stat-card__icon"><i class="mdi mdi-clipboard-text-clock-outline"></i></span>
          </div>
          <p class="fs-30 mb-2">{{ $summary['total'] }}</p>
          <p>Records in the selected date range</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-light-blue v2-stat-card">
        <div class="card-body">
          <div class="v2-stat-card__head mb-4">
            <p class="mb-0">Success And Info</p>
            <span class="v2-stat-card__icon"><i class="mdi mdi-check-circle-outline"></i></span>
          </div>
          <p class="fs-30 mb-2">{{ $summary['success'] }}</p>
          <p>Healthy request activity</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-light-danger v2-stat-card">
        <div class="card-body">
          <div class="v2-stat-card__head mb-4">
            <p class="mb-0">Warnings</p>
            <span class="v2-stat-card__icon"><i class="mdi mdi-alert-outline"></i></span>
          </div>
          <p class="fs-30 mb-2">{{ $summary['warning'] }}</p>
          <p>Requests needing attention</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-dark-blue v2-stat-card">
        <div class="card-body">
          <div class="v2-stat-card__head mb-4">
            <p class="mb-0">Errors</p>
            <span class="v2-stat-card__icon"><i class="mdi mdi-alert-circle-outline"></i></span>
          </div>
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
          <div class="v2-section-toolbar align-items-start">
            <div class="v2-card-heading">
              <span class="v2-card-heading__icon"><i class="mdi mdi-clipboard-text-clock-outline"></i></span>
              <div>
                <h4 class="card-title mb-1">System Activity Log</h4>
                <p class="card-description mb-0">All saved user and system activity with date-wise filtering.</p>
              </div>
            </div>
            <div class="v2-action-cluster justify-content-end">
              <a href="{{ route('v2.logs.export', $filters) }}" class="btn btn-outline-primary btn-icon-text">
                <i class="mdi mdi-download"></i>
                <span>Export Logs</span>
              </a>

              @if ($canManageLogs)
                <form method="POST" action="{{ route('v2.logs.clear-old') }}" onsubmit="return window.V2.confirmDelete(this, 'Clear logs older than 30 days? This cannot be undone.');">
                  @csrf
                  <button type="submit" class="btn btn-outline-danger btn-icon-text" {{ $oldLogsCount < 1 ? 'disabled' : '' }}>
                    <i class="mdi mdi-calendar-remove-outline"></i>
                    <span>Clear Older Than 30 Days</span>
                  </button>
                </form>

                <form method="POST" action="{{ route('v2.logs.clear') }}" onsubmit="return window.V2.confirmDelete(this, 'Clear all system logs? This cannot be undone.');">
                  @csrf
                  <button type="submit" class="btn btn-danger btn-icon-text" {{ $allLogsCount < 1 ? 'disabled' : '' }}>
                    <i class="mdi mdi-delete-sweep-outline"></i>
                    <span>Clear All Logs</span>
                  </button>
                </form>
              @endif
            </div>
          </div>

          <form method="GET" action="{{ route('v2.logs.index') }}" class="row mb-4 g-3 v2-filter-form">
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
              <button type="submit" class="btn btn-primary btn-icon-text w-100">
                <i class="mdi mdi-filter"></i>
                <span>Apply</span>
              </button>
              <a href="{{ route('v2.logs.index') }}" class="btn btn-outline-primary btn-icon-text w-100">
                <i class="mdi mdi-restore"></i>
                <span>Reset</span>
              </a>
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
