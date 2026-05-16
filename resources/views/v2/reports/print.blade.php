<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $pageTitle }} - {{ $appName }}</title>
  <style>
    body {
      color: #1f2937;
      font-family: Arial, sans-serif;
      font-size: 12px;
      line-height: 1.45;
      margin: 0;
      padding: 24px;
    }
    .toolbar {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
      margin-bottom: 18px;
    }
    .toolbar a,
    .toolbar button {
      background: #4b49ac;
      border: 0;
      border-radius: 4px;
      color: #fff;
      cursor: pointer;
      font-size: 12px;
      padding: 8px 12px;
      text-decoration: none;
    }
    .toolbar a {
      background: #e5e7eb;
      color: #111827;
    }
    .report-header {
      border-bottom: 2px solid #4b49ac;
      margin-bottom: 18px;
      padding-bottom: 12px;
    }
    h1,
    h2 {
      margin: 0;
    }
    h1 {
      font-size: 24px;
    }
    h2 {
      font-size: 15px;
      margin: 24px 0 8px;
    }
    .muted {
      color: #6b7280;
    }
    .summary-grid {
      display: grid;
      gap: 8px;
      grid-template-columns: repeat(3, 1fr);
      margin: 16px 0;
    }
    .summary-item {
      border: 1px solid #d1d5db;
      border-radius: 4px;
      padding: 10px;
    }
    .summary-item span {
      color: #6b7280;
      display: block;
      font-size: 11px;
      margin-bottom: 3px;
    }
    .summary-item strong {
      font-size: 18px;
    }
    table {
      border-collapse: collapse;
      margin-bottom: 12px;
      width: 100%;
    }
    th,
    td {
      border: 1px solid #d1d5db;
      padding: 6px;
      text-align: left;
      vertical-align: top;
    }
    th {
      background: #f3f4f6;
      color: #111827;
      font-weight: 700;
    }
    @media print {
      body {
        padding: 0;
      }
      .toolbar {
        display: none;
      }
      .report-header {
        break-after: avoid;
      }
      table {
        page-break-inside: auto;
      }
      tr {
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <a href="{{ route('v2.reports.index', ['from' => $filters['from'], 'to' => $filters['to']]) }}">Back</a>
    <button type="button" onclick="window.print()">Print / Save PDF</button>
  </div>

  <header class="report-header">
    <h1>{{ $appName }} Operational Report</h1>
    <p class="muted">
      Reporting Window: {{ $filters['from'] }} to {{ $filters['to'] }} |
      Generated: {{ $generatedAt }}
    </p>
  </header>

  <section>
    <div class="summary-grid">
      <div class="summary-item"><span>Trackings Created</span><strong>{{ $summary['trackingsCreated'] }}</strong></div>
      <div class="summary-item"><span>Active LR</span><strong>{{ $summary['trackingsActive'] }}</strong></div>
      <div class="summary-item"><span>Closed Shipments</span><strong>{{ $summary['trackingsClosed'] }}</strong></div>
      <div class="summary-item"><span>EPOD Uploaded</span><strong>{{ $summary['epodsUploaded'] }}</strong></div>
      <div class="summary-item"><span>Weight Corrections</span><strong>{{ $summary['weightCorrections'] }}</strong></div>
      <div class="summary-item"><span>Pending Location Sync</span><strong>{{ $summary['pendingLocationSyncs'] }}</strong></div>
    </div>
  </section>

  <section>
    <h2>Fleet Snapshot</h2>
    <table>
      <tbody>
        <tr>
          <th>Total Vehicles</th>
          <td>{{ $fleetSummary['totalVehicles'] }}</td>
          <th>Own Vehicles</th>
          <td>{{ $fleetSummary['ownVehicles'] }}</td>
          <th>Market Vehicles</th>
          <td>{{ $fleetSummary['marketVehicles'] }}</td>
        </tr>
        <tr>
          <th>Stopped Market Vehicles</th>
          <td>{{ $fleetSummary['stoppedMarketVehicles'] }}</td>
          <th>FleetX Running</th>
          <td>{{ $fleetSummary['fleetAnalytics']['runningVehicles'] ?? 0 }}</td>
          <th>FleetX Disconnected</th>
          <td>{{ $fleetSummary['fleetAnalytics']['disconnectedVehicles'] ?? 0 }}</td>
        </tr>
      </tbody>
    </table>
  </section>

  <section>
    <h2>Tracking Status Mix</h2>
    <table>
      <thead>
        <tr>
          <th>Status</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($trackingStatusBreakdown as $status)
          <tr>
            <td>{{ $status->label }}</td>
            <td>{{ $status->aggregate }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="2">No status data in this reporting window.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </section>

  <section>
    <h2>Recent LR Activity</h2>
    <table>
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
            <td colspan="6">No LR activity in this reporting window.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </section>

  <section>
    <h2>Recent EPOD Uploads</h2>
    <table>
      <thead>
        <tr>
          <th>LR Number</th>
          <th>LSP ID</th>
          <th>File</th>
          <th>Created At</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($recentEpods as $epod)
          <tr>
            <td>{{ $epod->lrNumber }}</td>
            <td>{{ $epod->lspId }}</td>
            <td>{{ $epod->epod }}</td>
            <td>{{ $epod->created_at }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4">No EPOD uploads in this reporting window.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </section>

  <section>
    <h2>Recent Weight Corrections</h2>
    <table>
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
            <td colspan="5">{{ $weightsEnabled ? 'No weight corrections in this reporting window.' : 'Weights table is not available in this environment.' }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </section>
</body>
</html>
