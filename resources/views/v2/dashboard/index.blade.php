@extends('v2.layouts.app')

@php
  $breadcrumbs = [];
  $formatDate = function ($value, $format = 'd M, h:i A') {
    if (!$value) {
      return '-';
    }

    $date = $value instanceof \DateTimeInterface ? $value : date_create((string) $value);

    return $date ? $date->format($format) : (string) $value;
  };
  $severityGroups = [
    'critical' => ['label' => 'Critical', 'class' => 'dashboard-insight-group--critical', 'empty' => 'No critical issues right now.'],
    'warning' => ['label' => 'Warning', 'class' => 'dashboard-insight-group--warning', 'empty' => 'No warning backlog right now.'],
    'info' => ['label' => 'Info', 'class' => 'dashboard-insight-group--info', 'empty' => 'No additional info items right now.'],
  ];
  $alertTagClasses = [
    'warning' => 'dashboard-alert-tag dashboard-alert-tag--warning',
    'danger' => 'dashboard-alert-tag dashboard-alert-tag--critical',
    'emergency' => 'dashboard-alert-tag dashboard-alert-tag--critical',
  ];
  $canManageSystem = $canManageSystem ?? false;
  $summaryCards = [
    [
      'label' => 'Total LR',
      'value' => $summary['totalTrackings'],
      'icon' => 'mdi-file-document-multiple-outline',
      'meta' => 'All project LR records',
      'accent' => 'dashboard-summary-card--slate',
    ],
    [
      'label' => 'Active LR',
      'value' => $summary['activeTrackingCount'],
      'icon' => 'mdi-truck-fast-outline',
      'meta' => $summary['delayedTrackingCount'] . ' delayed shipments',
      'accent' => 'dashboard-summary-card--teal',
    ],
    [
      'label' => 'Closed LR',
      'value' => $summary['closedTrackingCount'],
      'icon' => 'mdi-check-decagram-outline',
      'meta' => $summary['epodCount'] . ' EPOD uploads recorded',
      'accent' => 'dashboard-summary-card--blue',
    ],
    [
      'label' => 'Open Issues',
      'value' => $summary['issueCount'],
      'icon' => 'mdi-alert-circle-outline',
      'meta' => $summary['pendingEpodCount'] . ' pending EPOD | ' . $summary['locationGapCount'] . ' location gaps',
      'accent' => 'dashboard-summary-card--amber',
    ],
  ];
  $todayMetrics = [
    ['label' => 'Trackings Created', 'value' => $today['trackingsCreated'], 'icon' => 'mdi-plus-box-outline'],
    ['label' => 'EPOD Uploaded', 'value' => $today['epodsUploaded'], 'icon' => 'mdi-cloud-upload-outline'],
    ['label' => 'Weight Corrections', 'value' => $today['weightCorrections'], 'icon' => 'mdi-scale-bathroom'],
    ['label' => 'Warnings Logged', 'value' => $today['warningsLogged'], 'icon' => 'mdi-alert-outline'],
  ];
@endphp

@section('content')
  <details class="v2-consent-disclosure" open>
    <summary class="v2-consent-disclosure__summary">
      <span class="v2-consent-disclosure__title">
        <span class="v2-consent-disclosure__icon">
          <i class="mdi mdi-map-marker-radius"></i>
        </span>
        <span>Location Consent</span>
      </span>
      <span class="v2-consent-disclosure__toggle" aria-hidden="true">
        <i class="mdi mdi-chevron-down"></i>
      </span>
    </summary>
    <div class="v2-consent-disclosure__body">
      @include('v2.layouts.partials.consent-banner')
    </div>
  </details>

  <div class="dashboard-summary-strip">
    @foreach ($summaryCards as $card)
      <div class="dashboard-summary-card {{ $card['accent'] }}">
        <div class="dashboard-summary-card__icon">
          <i class="mdi {{ $card['icon'] }}"></i>
        </div>
        <div class="dashboard-summary-card__body">
          <span class="dashboard-summary-card__label">{{ $card['label'] }}</span>
          <strong class="dashboard-summary-card__value">{{ $card['value'] }}</strong>
          <small class="dashboard-summary-card__meta">{{ $card['meta'] }}</small>
        </div>
      </div>
    @endforeach
  </div>

  <div class="row">
    <div class="col-xl-8 grid-margin stretch-card">
      <div class="dashboard-main-stack">
        <div class="card dashboard-surface">
          <div class="card-body">
            <div class="dashboard-card-head">
              <div class="dashboard-title-block">
                <span class="dashboard-title-icon dashboard-title-icon--teal">
                  <i class="mdi mdi-chart-donut"></i>
                </span>
                <div>
                  <p class="card-title mb-0">Shipment Flow</p>
                </div>
              </div>
              <a href="{{ route('v2.lr-trackings.index') }}" class="btn btn-outline-primary btn-sm btn-icon-text">
                <i class="mdi mdi-format-list-bulleted"></i>
                <span>Open LR</span>
              </a>
            </div>

            @if ($hasShipmentData)
              <div class="dashboard-flow-layout">
                <div class="dashboard-flow-chart">
                  <canvas id="shipment-mix-chart" height="190"></canvas>
                </div>
                <div class="dashboard-flow-legend">
                  @foreach ($shipmentSegments as $segment)
                    <div class="dashboard-flow-legend__item">
                      <div class="dashboard-flow-legend__label">
                        <span class="dashboard-flow-legend__dot" style="background: {{ $segment['color'] }}"></span>
                        <span>{{ $segment['label'] }}</span>
                      </div>
                      <div class="dashboard-flow-legend__metrics">
                        <strong>{{ $segment['value'] }}</strong>
                        <small>{{ $segment['percentage'] }}%</small>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @else
              <div class="dashboard-empty-graph">
                <i class="mdi mdi-chart-donut-variant"></i>
                <strong>No Data Available</strong>
                <p class="mb-0">Shipment flow will appear here when LR records start moving through the workflow.</p>
              </div>
            @endif
          </div>
        </div>

        <div class="card dashboard-surface">
          <div class="card-body">
            <div class="dashboard-card-head">
              <div class="dashboard-title-block">
                <span class="dashboard-title-icon dashboard-title-icon--blue">
                  <i class="mdi mdi-chart-line"></i>
                </span>
                <div>
                  <p class="card-title mb-0">7-Day Throughput</p>
                </div>
              </div>
              <a href="{{ route('v2.reports.index', ['from' => $reportWindows['week']['from'], 'to' => $reportWindows['week']['to']]) }}" class="btn btn-outline-primary btn-sm btn-icon-text">
                <i class="mdi mdi-file-chart-outline"></i>
                <span>Weekly Report</span>
              </a>
            </div>

            <div class="dashboard-throughput-metrics">
              <div class="dashboard-throughput-metric">
                <span>Trackings</span>
                <strong>{{ array_sum($trend['trackings']) }}</strong>
              </div>
              <div class="dashboard-throughput-metric">
                <span>EPOD</span>
                <strong>{{ array_sum($trend['epods']) }}</strong>
              </div>
              <div class="dashboard-throughput-metric">
                <span>Weights</span>
                <strong>{{ $weightsEnabled ? array_sum($trend['weights']) : 0 }}</strong>
              </div>
            </div>

            @if ($hasTrendData)
              <div class="dashboard-chart-shell dashboard-chart-shell--line">
                <canvas id="throughput-trend-chart" height="124"></canvas>
              </div>
            @else
              <div class="dashboard-empty-graph dashboard-empty-graph--compact">
                <i class="mdi mdi-chart-line"></i>
                <strong>No Data Available</strong>
                <p class="mb-0">This graph will populate when new LR, EPOD, or weight records are created.</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4 grid-margin stretch-card">
      <div class="dashboard-side-stack">
        <div class="card dashboard-surface dashboard-surface--highlight">
          <div class="card-body">
            <div class="dashboard-card-head">
              <div class="dashboard-title-block">
                <span class="dashboard-title-icon dashboard-title-icon--teal">
                  <i class="mdi mdi-calendar-check-outline"></i>
                </span>
                <div>
                  <p class="card-title mb-0">Today Summary</p>
                </div>
              </div>
              <a href="{{ route('v2.reports.index', ['from' => $reportWindows['today']['from'], 'to' => $reportWindows['today']['to']]) }}" class="btn btn-light btn-sm btn-icon-text">
                <i class="mdi mdi-file-chart-outline"></i>
                <span>Today Report</span>
              </a>
            </div>

            <div class="dashboard-summary-panel-grid">
              @foreach ($todayMetrics as $metric)
                <div class="dashboard-summary-panel-item">
                  <span class="dashboard-summary-panel-item__icon">
                    <i class="mdi {{ $metric['icon'] }}"></i>
                  </span>
                  <div>
                    <span class="dashboard-summary-panel-item__label">{{ $metric['label'] }}</span>
                    <strong class="dashboard-summary-panel-item__value">{{ $metric['value'] }}</strong>
                  </div>
                </div>
              @endforeach
            </div>

            <div class="dashboard-progress-list">
              <div class="dashboard-progress-item">
                <div class="dashboard-progress-item__head">
                  <span>Completion Rate</span>
                  <strong>{{ $performance['completionRate'] }}%</strong>
                </div>
                <div class="dashboard-progress-item__track">
                  <span class="dashboard-progress-item__bar" style="width: {{ min($performance['completionRate'], 100) }}%"></span>
                </div>
              </div>
              <div class="dashboard-progress-item">
                <div class="dashboard-progress-item__head">
                  <span>EPOD Closure</span>
                  <strong>{{ $performance['epodClosureRate'] }}%</strong>
                </div>
                <div class="dashboard-progress-item__track">
                  <span class="dashboard-progress-item__bar dashboard-progress-item__bar--amber" style="width: {{ min($performance['epodClosureRate'], 100) }}%"></span>
                </div>
              </div>
              <div class="dashboard-progress-item">
                <div class="dashboard-progress-item__head">
                  <span>Fleet Utilization</span>
                  <strong>{{ $performance['fleetUtilization'] }}%</strong>
                </div>
                <div class="dashboard-progress-item__track">
                  <span class="dashboard-progress-item__bar dashboard-progress-item__bar--blue" style="width: {{ min($performance['fleetUtilization'], 100) }}%"></span>
                </div>
              </div>
            </div>

            <div class="dashboard-inline-metrics">
              <div class="dashboard-inline-metric">
                <span>Own Fleet</span>
                <strong>{{ $summary['ownVehicles'] }}</strong>
              </div>
              <div class="dashboard-inline-metric">
                <span>Market Active</span>
                <strong>{{ $summary['activeMarketVehicles'] }}</strong>
              </div>
              <div class="dashboard-inline-metric">
                <span>Market Stopped</span>
                <strong>{{ $summary['stoppedMarketVehicles'] }}</strong>
              </div>
            </div>
          </div>
        </div>

        <div class="card dashboard-surface">
          <div class="card-body">
            <div class="dashboard-card-head">
              <div class="dashboard-title-block">
                <span class="dashboard-title-icon dashboard-title-icon--amber">
                  <i class="mdi mdi-lightbulb-on-outline"></i>
                </span>
                <div>
                  <p class="card-title mb-0">Insights</p>
                </div>
              </div>
              @if($canManageSystem)
                <a href="{{ route('v2.logs.index') }}" class="btn btn-outline-primary btn-sm btn-icon-text">
                  <i class="mdi mdi-alert-circle-outline"></i>
                  <span>System Logs</span>
                </a>
              @endif
            </div>

            <div class="dashboard-insight-groups">
              @foreach ($severityGroups as $key => $group)
                <div class="dashboard-insight-group {{ $group['class'] }}">
                  <div class="dashboard-insight-group__head">
                    <span>{{ $group['label'] }}</span>
                    <strong>{{ count($insightGroups[$key]) }}</strong>
                  </div>

                  @if (!empty($insightGroups[$key]))
                    <div class="dashboard-insight-list">
                      @foreach ($insightGroups[$key] as $insight)
                        <a href="{{ $insight['action_url'] }}" class="dashboard-insight-card">
                          <strong class="dashboard-insight-card__title">{{ $insight['title'] }}</strong>
                          <p class="mb-0">{{ $insight['description'] }}</p>
                          <span class="dashboard-insight-card__link">{{ $insight['action_label'] }}</span>
                        </a>
                      @endforeach
                    </div>
                  @else
                    <div class="dashboard-insight-empty">{{ $group['empty'] }}</div>
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12 grid-margin stretch-card">
      <div class="card dashboard-surface">
        <div class="card-body">
          <div class="dashboard-card-head">
            <div class="dashboard-title-block">
              <span class="dashboard-title-icon dashboard-title-icon--slate">
                <i class="mdi mdi-clipboard-list-outline"></i>
              </span>
              <div>
                <p class="card-title mb-0">Action Queue</p>
              </div>
            </div>
            <a href="{{ route('v2.reports.index') }}" class="btn btn-outline-primary btn-sm btn-icon-text">
              <i class="mdi mdi-file-chart-outline"></i>
              <span>All Reports</span>
            </a>
          </div>

          <div class="dashboard-tabs-toolbar">
            <div class="dashboard-tab-nav" role="tablist" aria-label="Action queue tabs">
              @foreach ($attentionTabs as $index => $tab)
                <button
                  type="button"
                  class="dashboard-tab {{ $index === 0 ? 'is-active' : '' }}"
                  data-panel="{{ $tab['key'] }}"
                  data-url="{{ route('v2.home.attention', ['panel' => $tab['key']]) }}"
                  aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                >
                  <span class="dashboard-tab__label">{{ $tab['label'] }}</span>
                  <strong class="dashboard-tab__count">{{ $tab['count'] }}</strong>
                </button>
              @endforeach
            </div>

            <div class="dashboard-tab-select-wrap">
              <select id="dashboard-attention-select" class="form-control" data-placeholder="Choose queue">
                @foreach ($attentionTabs as $tab)
                  <option value="{{ $tab['key'] }}" data-url="{{ route('v2.home.attention', ['panel' => $tab['key']]) }}">
                    {{ $tab['label'] }} ({{ $tab['count'] }})
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <div
            id="dashboard-attention-content"
            class="dashboard-tab-content"
            data-default-panel="{{ $attentionTabs[0]['key'] }}"
          >
            <div class="dashboard-skeleton-stack" aria-hidden="true">
              <div class="dashboard-skeleton dashboard-skeleton--heading"></div>
              <div class="dashboard-skeleton dashboard-skeleton--row"></div>
              <div class="dashboard-skeleton dashboard-skeleton--row"></div>
              <div class="dashboard-skeleton dashboard-skeleton--row"></div>
              <div class="dashboard-skeleton dashboard-skeleton--row"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="{{ $canManageSystem ? 'col-xl-8' : 'col-12' }} grid-margin stretch-card">
      <div class="card dashboard-surface">
        <div class="card-body">
          <div class="dashboard-card-head">
            <div class="dashboard-title-block">
              <span class="dashboard-title-icon dashboard-title-icon--blue">
                <i class="mdi mdi-radar"></i>
              </span>
              <div>
                <p class="card-title mb-0">Recent Tracking Activity</p>
              </div>
            </div>
            <a href="{{ route('v2.lr-trackings.index') }}" class="btn btn-outline-primary btn-sm btn-icon-text">
              <i class="mdi mdi-arrow-right"></i>
              <span>View All</span>
            </a>
          </div>

          <div class="table-responsive">
            <table class="table dashboard-activity-table">
              <thead>
                <tr>
                  <th>Vehicle</th>
                  <th>LR Number</th>
                  <th>Stage</th>
                  <th>Location</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentTrackings as $tracking)
                  <tr>
                    <td data-label="Vehicle">
                      <div class="dashboard-data-table__primary">{{ $tracking->vehicleNo ?: '-' }}</div>
                      <div class="dashboard-data-table__secondary">{{ $tracking->lspId ?: '-' }}</div>
                    </td>
                    <td data-label="LR Number">{{ $tracking->lrNumber ?: '-' }}</td>
                    <td data-label="Stage">
                      @if ((int) $tracking->status === 3)
                        <span class="dashboard-status-badge dashboard-status-badge--success">EPOD Uploaded</span>
                      @elseif ((int) $tracking->status === 1)
                        <span class="dashboard-status-badge dashboard-status-badge--info">Delivered</span>
                      @else
                        <span class="dashboard-status-badge dashboard-status-badge--warning">{{ $tracking->lrStatus ?: 'Active' }}</span>
                      @endif
                    </td>
                    <td data-label="Location">{{ $tracking->location ?: 'Awaiting location' }}</td>
                    <td data-label="Updated">{{ $formatDate($tracking->updated_at) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5">
                      <div class="dashboard-empty-state">No tracking activity is available yet.</div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    @if($canManageSystem)
      <div class="col-xl-4 grid-margin stretch-card">
        <div class="card dashboard-surface">
          <div class="card-body">
            <div class="dashboard-card-head">
              <div class="dashboard-title-block">
                <span class="dashboard-title-icon dashboard-title-icon--red">
                  <i class="mdi mdi-bell-alert-outline"></i>
                </span>
                <div>
                  <p class="card-title mb-0">Recent Alerts</p>
                </div>
              </div>
              <a href="{{ route('v2.logs.index') }}" class="btn btn-outline-primary btn-sm btn-icon-text">
                <i class="mdi mdi-open-in-new"></i>
                <span>Open Logs</span>
              </a>
            </div>

            <div class="dashboard-alert-list">
              @forelse ($recentAlerts as $alert)
                <a href="{{ route('v2.logs.show', $alert) }}" class="dashboard-alert-card">
                  <div class="dashboard-alert-card__head">
                    <span class="{{ $alertTagClasses[$alert->type] ?? 'dashboard-alert-tag dashboard-alert-tag--info' }}">
                      {{ strtoupper($alert->type) }}
                    </span>
                    <small>{{ $formatDate($alert->created_at) }}</small>
                  </div>
                  <strong class="dashboard-alert-card__title">{{ $alert->title ?: 'Untitled log entry' }}</strong>
                </a>
              @empty
                <div class="dashboard-empty-state">No warning or failure logs were found for the latest activity window.</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
@endsection

@section('scripts')
  <script>
    window.addEventListener('DOMContentLoaded', function () {
      function renderChartWhenVisible(canvas, config) {
        var chartInstance = null;

        if (!canvas || typeof Chart === 'undefined') {
          return;
        }

        function buildChart() {
          if (!chartInstance) {
            chartInstance = new Chart(canvas, config);
          }
        }

        if ('IntersectionObserver' in window) {
          var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                buildChart();
                observer.disconnect();
              }
            });
          }, { rootMargin: '120px 0px' });

          observer.observe(canvas);
        } else {
          buildChart();
        }
      }

      var shipmentCanvas = document.getElementById('shipment-mix-chart');
      if (shipmentCanvas) {
        renderChartWhenVisible(shipmentCanvas, {
          type: 'doughnut',
          data: {
            labels: @json($shipmentMix['labels']),
            datasets: [{
              data: @json($shipmentMix['values']),
              backgroundColor: ['#0f766e', '#ef4444', '#f59e0b', '#2563eb'],
              borderWidth: 0,
              hoverOffset: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    var total = context.dataset.data.reduce(function (sum, value) {
                      return sum + value;
                    }, 0);
                    var percentage = total > 0 ? ((context.raw * 100) / total).toFixed(1) : '0.0';
                    return context.label + ': ' + context.raw + ' (' + percentage + '%)';
                  }
                }
              }
            }
          }
        });
      }

      var trendCanvas = document.getElementById('throughput-trend-chart');
      if (trendCanvas) {
        renderChartWhenVisible(trendCanvas, {
          type: 'line',
          data: {
            labels: @json($trend['labels']),
            datasets: [{
              label: 'Trackings',
              data: @json($trend['trackings']),
              borderColor: '#0f766e',
              backgroundColor: 'rgba(15, 118, 110, 0.12)',
              tension: 0.35,
              fill: true,
              borderWidth: 2.5,
              pointRadius: 2
            }, {
              label: 'EPOD',
              data: @json($trend['epods']),
              borderColor: '#2563eb',
              backgroundColor: 'rgba(37, 99, 235, 0.08)',
              tension: 0.35,
              fill: true,
              borderWidth: 2,
              pointRadius: 2
            }@if ($weightsEnabled), {
              label: 'Weights',
              data: @json($trend['weights']),
              borderColor: '#f59e0b',
              backgroundColor: 'rgba(245, 158, 11, 0.08)',
              tension: 0.35,
              fill: true,
              borderWidth: 2,
              pointRadius: 2
            }@endif]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              intersect: false,
              mode: 'index'
            },
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  usePointStyle: true,
                  boxWidth: 8,
                  padding: 18
                }
              }
            },
            scales: {
              x: {
                grid: {
                  display: false
                }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  precision: 0
                },
                grid: {
                  color: 'rgba(148, 163, 184, 0.18)'
                }
              }
            }
          }
        });
      }

      var attentionShell = document.getElementById('dashboard-attention-content');
      var attentionButtons = document.querySelectorAll('.dashboard-tab');
      var attentionSelect = document.getElementById('dashboard-attention-select');
      var attentionCache = {};

      function skeletonMarkup() {
        return [
          '<div class="dashboard-skeleton-stack" aria-hidden="true">',
          '<div class="dashboard-skeleton dashboard-skeleton--heading"></div>',
          '<div class="dashboard-skeleton dashboard-skeleton--row"></div>',
          '<div class="dashboard-skeleton dashboard-skeleton--row"></div>',
          '<div class="dashboard-skeleton dashboard-skeleton--row"></div>',
          '<div class="dashboard-skeleton dashboard-skeleton--row"></div>',
          '</div>'
        ].join('');
      }

      function setActivePanel(panel) {
        Array.prototype.forEach.call(attentionButtons, function (button) {
          var isActive = button.getAttribute('data-panel') === panel;
          button.classList.toggle('is-active', isActive);
          button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        if (attentionSelect) {
          attentionSelect.value = panel;
        }
      }

      function loadPanel(panel, url) {
        if (!attentionShell || !panel || !url) {
          return;
        }

        setActivePanel(panel);

        if (attentionCache[panel]) {
          attentionShell.innerHTML = attentionCache[panel];
          return;
        }

        attentionShell.innerHTML = skeletonMarkup();

        window.fetch(url, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-Skip-Loader': '1'
          },
          skipLoader: true
        }).then(function (response) {
          if (!response.ok) {
            throw new Error('Unable to load the requested queue.');
          }

          return response.json();
        }).then(function (payload) {
          attentionCache[panel] = payload.html;
          attentionShell.innerHTML = payload.html;
        }).catch(function () {
          attentionShell.innerHTML = '<div class="dashboard-empty-state">Unable to load this queue right now. Please try again.</div>';

          if (window.V2 && typeof window.V2.fireAlert === 'function') {
            window.V2.fireAlert({
              icon: 'error',
              title: 'Load Failed',
              text: 'The action queue could not be loaded.'
            });
          }
        });
      }

      Array.prototype.forEach.call(attentionButtons, function (button) {
        button.addEventListener('click', function () {
          loadPanel(button.getAttribute('data-panel'), button.getAttribute('data-url'));
        });
      });

      if (attentionSelect) {
        attentionSelect.addEventListener('change', function () {
          var option = attentionSelect.options[attentionSelect.selectedIndex];
          loadPanel(option.value, option.getAttribute('data-url'));
        });
      }

      if (attentionButtons.length > 0) {
        loadPanel(
          attentionButtons[0].getAttribute('data-panel'),
          attentionButtons[0].getAttribute('data-url')
        );
      }
    });
  </script>
@endsection
