@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Reports'],
  ];
  $statusChartLabels = [];
  $statusChartValues = [];

  foreach ($trackingStatusBreakdown as $statusItem) {
    $statusChartLabels[] = $statusItem->label;
    $statusChartValues[] = $statusItem->aggregate;
  }
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
              <h4 class="card-title mb-1">Reporting Window</h4>
              <p class="card-description mb-0">Filter operational activity and export the same range as CSV.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
              <a href="{{ route('v2.reports.export', ['dataset' => 'trackings', 'from' => $filters['from'], 'to' => $filters['to']]) }}" class="btn btn-outline-primary btn-sm">Export Trackings</a>
              <a href="{{ route('v2.reports.export', ['dataset' => 'vehicles', 'from' => $filters['from'], 'to' => $filters['to']]) }}" class="btn btn-outline-info btn-sm">Export Vehicles</a>
              <a href="{{ route('v2.reports.export', ['dataset' => 'epods', 'from' => $filters['from'], 'to' => $filters['to']]) }}" class="btn btn-outline-success btn-sm">Export EPOD</a>
              @if ($weightsEnabled)
                <a href="{{ route('v2.reports.export', ['dataset' => 'weights', 'from' => $filters['from'], 'to' => $filters['to']]) }}" class="btn btn-outline-warning btn-sm">Export Weights</a>
              @endif
            </div>
          </div>

          <form method="GET" action="{{ route('v2.reports.index') }}" class="row">
            <div class="col-md-4 form-group">
              <label for="from">From</label>
              <input type="date" class="form-control" id="from" name="from" value="{{ $filters['from'] }}">
            </div>
            <div class="col-md-4 form-group">
              <label for="to">To</label>
              <input type="date" class="form-control" id="to" name="to" value="{{ $filters['to'] }}">
            </div>
            <div class="col-md-4 form-group d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary">Apply Filters</button>
              <a href="{{ route('v2.reports.index') }}" class="btn btn-light">Reset</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-4 grid-margin stretch-card">
      <div class="card card-tale">
        <div class="card-body">
          <p class="mb-2">Trackings Created</p>
          <p class="fs-30 mb-2">{{ $summary['trackingsCreated'] }}</p>
          <p class="mb-0">Records opened in the selected window.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
      <div class="card card-dark-blue">
        <div class="card-body">
          <p class="mb-2">Closed Shipments</p>
          <p class="fs-30 mb-2">{{ $summary['trackingsClosed'] }}</p>
          <p class="mb-0">Delivered and EPOD-uploaded LR records.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
      <div class="card card-light-blue">
        <div class="card-body">
          <p class="mb-2">EPOD Uploaded</p>
          <p class="fs-30 mb-2">{{ $summary['epodsUploaded'] }}</p>
          <p class="mb-0">Successful proof-of-delivery uploads.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
      <div class="card card-light-danger">
        <div class="card-body">
          <p class="mb-2">Weight Corrections</p>
          <p class="fs-30 mb-2">{{ $summary['weightCorrections'] }}</p>
          <p class="mb-0">Corrections recorded in the selected window.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <p class="mb-2">Active LR</p>
          <p class="fs-30 mb-2">{{ $summary['trackingsActive'] }}</p>
          <p class="mb-0">Open LR records still in progress.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <p class="mb-2">Pending Location Sync</p>
          <p class="fs-30 mb-2">{{ $summary['pendingLocationSyncs'] }}</p>
          <p class="mb-0">Active LR records still missing live coordinates.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-7 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <p class="card-title mb-0">Tracking Status Mix</p>
              <p class="text-muted mb-0">Breakdown of LR status values in the filtered reporting window.</p>
            </div>
          </div>
          <canvas id="tracking-status-chart" height="120"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-5 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <p class="card-title">Fleet Snapshot</p>
          <div class="detail-grid">
            <div><span>Total Vehicles</span><strong>{{ $fleetSummary['totalVehicles'] }}</strong></div>
            <div><span>Own Vehicles</span><strong>{{ $fleetSummary['ownVehicles'] }}</strong></div>
            <div><span>Market Vehicles</span><strong>{{ $fleetSummary['marketVehicles'] }}</strong></div>
            <div><span>Stopped Market Vehicles</span><strong>{{ $fleetSummary['stoppedMarketVehicles'] }}</strong></div>
            <div><span>FleetX Total</span><strong>{{ $fleetSummary['fleetAnalytics']['totalVehicles'] ?? 0 }}</strong></div>
            <div><span>FleetX Running</span><strong>{{ $fleetSummary['fleetAnalytics']['runningVehicles'] ?? 0 }}</strong></div>
            <div><span>FleetX Parked</span><strong>{{ $fleetSummary['fleetAnalytics']['parkedVehicles'] ?? 0 }}</strong></div>
            <div><span>FleetX Disconnected</span><strong>{{ $fleetSummary['fleetAnalytics']['disconnectedVehicles'] ?? 0 }}</strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Recent LR Activity</h4>
          <div class="table-responsive">
            <table class="table table-striped v2-table" id="report-trackings-table">
              <thead>
                <tr>
                  <th>Vehicle</th>
                  <th>LSP ID</th>
                  <th>LR Number</th>
                  <th>Status</th>
                  <th>Stage</th>
                  <th>Updated At</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentTrackings as $tracking)
                  <tr>
                    <td>{{ $tracking->vehicleNo }}</td>
                    <td>{{ $tracking->lspId }}</td>
                    <td>{{ $tracking->lrNumber }}</td>
                    <td>{{ $tracking->lrStatus }}</td>
                    <td>{{ (int) $tracking->status === 3 ? 'EPOD Uploaded' : ((int) $tracking->status === 1 ? 'Delivered' : 'Active') }}</td>
                    <td>{{ $tracking->updated_at }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted">No LR activity in this reporting window.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Recent Weight Corrections</h4>
          <div class="table-responsive">
            <table class="table table-striped v2-table" id="report-weights-table">
              <thead>
                <tr>
                  <th>LR Number</th>
                  <th>LSP ID</th>
                  <th>Corrected Weight</th>
                  <th>Dimensions</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentWeights as $weight)
                  <tr>
                    <td>{{ $weight->lrNumber }}</td>
                    <td>{{ $weight->lspId }}</td>
                    <td>{{ $weight->correctedWeight }}</td>
                    <td>{{ $weight->length }} x {{ $weight->breadth }} x {{ $weight->height }}</td>
                    <td>{{ $weight->created_at }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted">{{ $weightsEnabled ? 'No weight corrections in this reporting window.' : 'Weights table is not available in this environment.' }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Recent EPOD Uploads</h4>
          <div class="table-responsive">
            <table class="table table-striped v2-table" id="report-epods-table">
              <thead>
                <tr>
                  <th>LR Number</th>
                  <th>LSP ID</th>
                  <th>File</th>
                  <th>Status</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentEpods as $epod)
                  <tr>
                    <td>{{ $epod->lrNumber }}</td>
                    <td>{{ $epod->lspId }}</td>
                    <td>{{ $epod->epod }}</td>
                    <td><span class="badge badge-success">Uploaded</span></td>
                    <td>{{ $epod->created_at }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted">No EPOD uploads in this reporting window.</td>
                  </tr>
                @endforelse
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
      window.V2.initDataTable('#report-trackings-table');
      window.V2.initDataTable('#report-weights-table');
      window.V2.initDataTable('#report-epods-table');

      var context = document.getElementById('tracking-status-chart');
      if (!context) {
        return;
      }

      new Chart(context, {
        type: 'doughnut',
        data: {
          labels: @json($statusChartLabels),
          datasets: [{
            data: @json($statusChartValues),
            backgroundColor: ['#4b49ac', '#7da0fa', '#f3797e', '#ffc100', '#57b657', '#248afd'],
            borderWidth: 0
          }]
        },
        options: {
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    });
  </script>
@endsection
