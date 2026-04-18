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
              <tbody>
                @foreach ($trackings as $tracking)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $tracking->vehicleNo }}</td>
                    <td>{{ $tracking->lspId }}</td>
                    <td>{{ $tracking->lrNumber }}</td>
                    <td>{{ $tracking->lrDate }}</td>
                    <td>
                      <span class="badge badge-{{ in_array((int) $tracking->status, [1, 3], true) ? 'success' : 'warning' }}">
                        {{ $showCompleted && (int) $tracking->status === 3 ? 'EPOD Uploaded' : $tracking->lrStatus }}
                      </span>
                    </td>
                    <td class="text-end">
                      @if (!$showCompleted)
                        <a href="{{ route('v2.lr-trackings.edit', $tracking) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                      @endif
                      <form class="d-inline" method="POST" action="{{ route('v2.lr-trackings.refresh', $tracking) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm">Refresh</button>
                      </form>
                      <a href="{{ route('v2.lr-trackings.show', $tracking) }}" class="btn btn-outline-info btn-sm">View</a>
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
      window.V2.initDataTable('#trackings-table');
    });
  </script>
@endsection
