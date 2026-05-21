@php
  $formatDate = function ($value, $format = 'd M, h:i A') {
    if (!$value) {
      return '-';
    }

    $date = $value instanceof \DateTimeInterface ? $value : date_create((string) $value);

    return $date ? $date->format($format) : (string) $value;
  };

  $secondaryHeading = 'Stage';
  $timeHeading = 'Updated';

  if ($panel === 'delayed') {
    $secondaryHeading = 'LR Stage';
    $timeHeading = 'EDD';
  } elseif ($panel === 'pending-epod') {
    $secondaryHeading = 'Delivery Stage';
    $timeHeading = 'Delivered On';
  } elseif ($panel === 'location-gaps') {
    $secondaryHeading = 'Missing';
    $timeHeading = 'Last Update';
  }
@endphp

<div class="dashboard-tab-panel">
  <div class="dashboard-tab-panel__head">
    <div class="dashboard-title-block">
      <span class="dashboard-title-icon dashboard-title-icon--slate">
        <i class="mdi mdi-format-list-bulleted-square"></i>
      </span>
      <div>
        <p class="card-title mb-0">{{ $title }}</p>
      </div>
    </div>
    <a href="{{ $actionUrl }}" class="btn btn-outline-primary btn-sm btn-icon-text">
      <i class="mdi mdi-open-in-new"></i>
      <span>{{ $actionLabel }}</span>
    </a>
  </div>

  <div class="table-responsive">
    <table class="table dashboard-data-table">
      <thead>
        <tr>
          <th>LR / Vehicle</th>
          <th>{{ $secondaryHeading }}</th>
          <th>{{ $timeHeading }}</th>
          <th class="text-right">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($records as $record)
          <tr>
            <td data-label="LR / Vehicle">
              <div class="dashboard-data-table__primary">{{ $record->lrNumber ?: '-' }}</div>
              <div class="dashboard-data-table__secondary">{{ $record->vehicleNo ?: '-' }}</div>
            </td>
            <td data-label="{{ $secondaryHeading }}">
              @if ($panel === 'location-gaps')
                <span class="dashboard-data-table__secondary">
                  {{ $record->location ?: 'Latitude / longitude not resolved' }}
                </span>
              @elseif ($panel === 'pending-epod')
                <span class="dashboard-data-table__secondary">
                  {{ $record->lrStatus ?: 'Delivered' }}
                </span>
              @else
                <span class="dashboard-data-table__secondary">
                  {{ $record->lrStatus ?: 'In Transit' }}
                </span>
              @endif
            </td>
            <td data-label="{{ $timeHeading }}">
              @if ($panel === 'delayed')
                {{ $formatDate($record->edd) }}
              @elseif ($panel === 'pending-epod')
                {{ $formatDate($record->actualDeliveredDate ?: $record->updated_at) }}
              @else
                {{ $formatDate($record->updated_at) }}
              @endif
            </td>
            <td class="text-right" data-label="Action">
              <a href="{{ route('v2.lr-trackings.show', $record) }}" class="btn btn-light btn-sm btn-icon-text">
                <i class="mdi mdi-eye-outline"></i>
                <span>View</span>
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4">
              <div class="dashboard-empty-state">{{ $emptyMessage }}</div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
