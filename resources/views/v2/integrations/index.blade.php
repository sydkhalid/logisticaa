@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Integration Health'],
  ];
  $stateMap = [
    'online' => ['label' => 'Online', 'class' => 'success'],
    'warning' => ['label' => 'Attention', 'class' => 'warning'],
    'offline' => ['label' => 'Offline', 'class' => 'danger'],
  ];
@endphp

@section('styles')
  <style>
    .integration-card {
      border: 1px solid rgba(148, 163, 184, 0.18);
      position: relative;
    }

    .integration-card::before {
      border-radius: 1.35rem;
      content: '';
      inset: 0;
      opacity: 0.9;
      pointer-events: none;
      position: absolute;
    }

    .integration-card--online::before {
      background: linear-gradient(135deg, rgba(15, 118, 110, 0.08), transparent 55%);
    }

    .integration-card--warning::before {
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.12), transparent 58%);
    }

    .integration-card--offline::before {
      background: linear-gradient(135deg, rgba(185, 28, 28, 0.12), transparent 58%);
    }

    .integration-card .card-body {
      position: relative;
      z-index: 1;
    }

    .integration-toolbar {
      align-items: flex-start;
      display: flex;
      gap: 1rem;
      justify-content: space-between;
    }

    .integration-value--wide strong {
      overflow-wrap: anywhere;
      word-break: break-word;
    }

    .integration-note {
      background: rgba(255, 255, 255, 0.68);
      border: 1px solid rgba(148, 163, 184, 0.18);
      border-radius: 1rem;
      color: #475569;
      font-size: 0.88rem;
      line-height: 1.55;
      padding: 0.85rem 1rem;
    }

    .integration-note strong {
      color: #162033;
    }

    .integration-note + .integration-note {
      margin-top: 0.75rem;
    }

    .integration-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }
  </style>
@endsection

@section('content')
  <div class="row">
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-tale">
        <div class="card-body">
          <p class="mb-4">Online</p>
          <p class="fs-30 mb-2">{{ $summary['online'] }}</p>
          <p>Integrations fully healthy</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-light-danger">
        <div class="card-body">
          <p class="mb-4">Attention</p>
          <p class="fs-30 mb-2">{{ $summary['warning'] }}</p>
          <p>Integrations with partial coverage</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card card-dark-blue">
        <div class="card-body">
          <p class="mb-4">Offline</p>
          <p class="fs-30 mb-2">{{ $summary['offline'] }}</p>
          <p>Integrations needing direct repair</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <p class="mb-4">Checked At</p>
          <h5 class="mb-2">{{ $checkedAt }}</h5>
          <p>{{ $pageDescription }}</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    @foreach (['fleetx', 'wheelseye', 'travis'] as $key)
      @php
        $item = $health[$key];
        $state = $stateMap[$item['status']] ?? $stateMap['offline'];
      @endphp
      <div class="col-xl-4 col-lg-6 grid-margin stretch-card">
        <div class="card integration-card integration-card--{{ $item['status'] }}">
          <div class="card-body d-flex flex-column">
            <div class="integration-toolbar mb-3">
              <div>
                <h4 class="card-title mb-1">{{ $item['label'] }}</h4>
                <p class="card-description mb-0">{{ $item['message'] }}</p>
              </div>
              <span class="badge badge-{{ $state['class'] }}">{{ $state['label'] }}</span>
            </div>

            <div class="detail-grid">
              <div class="integration-value--wide">
                <span>Base URL</span>
                <strong>{{ $item['base_url'] ?: '-' }}</strong>
              </div>
              <div>
                <span>Configured</span>
                <strong>{{ $item['configured'] ? 'Yes' : 'No' }}</strong>
              </div>
              <div>
                <span>Token Source</span>
                <strong>{{ $item['token_source'] }}</strong>
              </div>
              <div class="integration-value--wide">
                <span>Stored Token</span>
                <strong>{{ $item['stored_token'] }}</strong>
              </div>

              @if ($key === 'fleetx')
                <div>
                  <span>Local Market Vehicles</span>
                  <strong>{{ $item['local_vehicle_count'] }}</strong>
                </div>
                <div>
                  <span>Live Fleet Vehicles</span>
                  <strong>{{ $item['remote_vehicle_count'] }}</strong>
                </div>
                <div>
                  <span>Matched Vehicles</span>
                  <strong>{{ $item['matched_vehicle_count'] }}</strong>
                </div>
                <div>
                  <span>Coverage</span>
                  <strong>{{ $item['coverage_percent'] }}%</strong>
                </div>
                <div>
                  <span>Running Vehicles</span>
                  <strong>{{ $item['running_vehicle_count'] }}</strong>
                </div>
                <div>
                  <span>Token Refreshed</span>
                  <strong>{{ $item['token_refreshed'] ? 'Yes' : 'No' }}</strong>
                </div>
              @elseif ($key === 'wheelseye')
                <div>
                  <span>Local Own Vehicles</span>
                  <strong>{{ $item['local_vehicle_count'] }}</strong>
                </div>
                <div>
                  <span>Remote Vehicles</span>
                  <strong>{{ $item['remote_vehicle_count'] }}</strong>
                </div>
                <div>
                  <span>Matched Vehicles</span>
                  <strong>{{ $item['matched_vehicle_count'] }}</strong>
                </div>
                <div>
                  <span>Coverage</span>
                  <strong>{{ $item['coverage_percent'] }}%</strong>
                </div>
              @else
                <div class="integration-value--wide">
                  <span>System Email</span>
                  <strong>{{ $item['system_email'] }}</strong>
                </div>
                <div class="integration-value--wide">
                  <span>Fresh Login Token</span>
                  <strong>{{ $item['issued_token'] }}</strong>
                </div>
                <div>
                  <span>Active LR</span>
                  <strong>{{ $item['active_tracking_count'] }}</strong>
                </div>
                <div>
                  <span>Completed LR</span>
                  <strong>{{ $item['completed_tracking_count'] }}</strong>
                </div>
              @endif
            </div>

            @if (!empty($item['issues']))
              <div class="integration-note mt-3">
                @foreach ($item['issues'] as $issue)
                  <div>{{ $issue }}</div>
                @endforeach
              </div>
            @endif

            @if (!empty($item['sample_remote']))
              <div class="integration-note mt-3">
                <strong>Sample remote vehicle:</strong> {{ $item['sample_remote'] }}
              </div>
            @endif

            @if (!empty($item['sample_matches']))
              <div class="integration-note mt-3">
                <strong>Matched examples:</strong> {{ implode(', ', $item['sample_matches']) }}
              </div>
            @endif

            <div class="integration-actions mt-auto pt-4">
              <a href="{{ route('v2.settings.edit') }}" class="btn btn-outline-primary btn-sm">Open Settings</a>

              @if ($key === 'fleetx')
                <form method="POST" action="{{ route('v2.integrations.fleetx.refresh-token') }}">
                  @csrf
                  <button type="submit" class="btn btn-primary btn-sm">Refresh FleetX Token</button>
                </form>
              @elseif ($key === 'travis')
                <form method="POST" action="{{ route('v2.integrations.travis.refresh-token') }}">
                  @csrf
                  <button type="submit" class="btn btn-primary btn-sm">Refresh Travis Token</button>
                </form>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endsection
