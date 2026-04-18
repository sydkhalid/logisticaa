@extends('v2.layouts.app')

@php
  $breadcrumbs = [];
  $analyticsChart = [
    $analytics['runningVehicles'] ?? 0,
    $analytics['parkedVehicles'] ?? 0,
    $analytics['idleVehicles'] ?? 0,
    $analytics['disconnectedVehicles'] ?? 0,
    $analytics['unreachableVehicles'] ?? 0,
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-md-6 grid-margin stretch-card">
      <div class="card tale-bg">
        <div class="card-people mt-auto">
          <img src="{{ asset('v2/assets/images/dashboard/people.svg') }}" alt="people">
          <div class="weather-info">
            <div class="d-flex">
              <div>
                <h2 class="mb-0 font-weight-normal">{{ $stats['vehicleCount'] }}</h2>
              </div>
              <div class="ms-2">
                <h4 class="location font-weight-normal">Vehicles</h4>
                <h6 class="font-weight-normal">Live project footprint</h6>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 grid-margin transparent">
      <div class="row">
        <div class="col-md-6 mb-4 stretch-card transparent">
          <div class="card card-tale">
            <div class="card-body">
              <p class="mb-4">Active LR</p>
              <p class="fs-30 mb-2">{{ $stats['activeTrackingCount'] }}</p>
              <p>Open tracking records</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 mb-4 stretch-card transparent">
          <div class="card card-dark-blue">
            <div class="card-body">
              <p class="mb-4">Completed LR</p>
              <p class="fs-30 mb-2">{{ $stats['completedTrackingCount'] }}</p>
              <p>Delivered and EPOD-synced shipments</p>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-4 mb-lg-0 stretch-card transparent">
          <div class="card card-light-blue">
            <div class="card-body">
              <p class="mb-4">Uploaded EPOD</p>
              <p class="fs-30 mb-2">{{ $stats['epodCount'] }}</p>
              <p>Completed document syncs</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 stretch-card transparent">
          <div class="card card-light-danger">
            <div class="card-body">
              <p class="mb-4">Fleet Total</p>
              <p class="fs-30 mb-2">{{ $analytics['totalVehicles'] ?? 0 }}</p>
              <p>FleetX live analytics</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <p class="card-title mb-0">FleetX Live Snapshot</p>
              <p class="text-muted mb-0">Current operational mix from the integrated vehicle analytics feed.</p>
            </div>
            <a href="{{ route('v2.market-vehicles.index') }}" class="btn btn-primary btn-sm">Open Market Vehicles</a>
          </div>
          <canvas id="analytics-chart" height="110"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <p class="card-title">Quick Actions</p>
          <div class="d-grid gap-3">
            <a href="{{ route('v2.vehicles.create') }}" class="btn btn-outline-primary btn-fw">Add Own Vehicle</a>
            <a href="{{ route('v2.market-vehicles.create') }}" class="btn btn-outline-info btn-fw">Add Market Vehicle</a>
            <a href="{{ route('v2.lr-trackings.create') }}" class="btn btn-outline-success btn-fw">Create LR Tracking</a>
            <a href="{{ route('v2.weight-corrections.create') }}" class="btn btn-outline-warning btn-fw">Add Weight Correction</a>
            <a href="{{ route('v2.epods.create') }}" class="btn btn-outline-danger btn-fw">Upload EPOD</a>
            <a href="{{ route('v2.reports.index') }}" class="btn btn-outline-dark btn-fw">Open Reports</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-7 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="card-title mb-0">Recent Tracking Records</p>
            <a href="{{ route('v2.lr-trackings.index') }}" class="text-info">View all</a>
          </div>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Vehicle</th>
                  <th>LR Number</th>
                  <th>Status</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentTrackings as $tracking)
                  <tr>
                    <td>{{ $tracking->vehicleNo }}</td>
                    <td>{{ $tracking->lrNumber }}</td>
                    <td>
                      <span class="badge badge-{{ in_array((int) $tracking->status, [1, 3], true) ? 'success' : 'warning' }}">
                        {{ (int) $tracking->status === 3 ? 'EPOD Uploaded' : $tracking->lrStatus }}
                      </span>
                    </td>
                    <td>{{ $tracking->updated_at }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted">No tracking records yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-5 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <p class="card-title">Recent EPOD Uploads</p>
          <div class="schedule-mini">
            @forelse ($recentEpods as $epod)
              <div class="schedule-item">
                <div class="schedule-badge {{ (int) $epod->status === 1 ? 'bg-success' : 'bg-warning' }}"></div>
                <div>
                  <h6 class="mb-1">{{ $epod->lrNumber }}</h6>
                  <p class="mb-0 text-muted">{{ $epod->lspId }}</p>
                </div>
                <small class="text-muted">{{ $epod->created_at }}</small>
              </div>
            @empty
              <p class="text-muted mb-0">No EPOD uploads available.</p>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    window.addEventListener('DOMContentLoaded', function () {
      var context = document.getElementById('analytics-chart');
      if (!context) {
        return;
      }

      new Chart(context, {
        type: 'bar',
        data: {
          labels: ['Running', 'Parked', 'Idle', 'Disconnected', 'Unreachable'],
          datasets: [{
            label: 'Vehicles',
            data: @json($analyticsChart),
            backgroundColor: ['#4b49ac', '#7da0fa', '#7978e9', '#f3797e', '#ffb830'],
            borderRadius: 8,
          }]
        },
        options: {
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            }
          }
        }
      });
    });
  </script>
@endsection
