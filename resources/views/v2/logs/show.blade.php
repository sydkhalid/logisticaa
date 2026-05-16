@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'System Logs', 'url' => route('v2.logs.index')],
    ['label' => 'Details'],
  ];
  $badgeMap = [
    'success' => 'success',
    'info' => 'info',
    'warning' => 'warning',
    'danger' => 'danger',
    'emergency' => 'danger',
  ];
@endphp

@section('styles')
  <style>
    .v2-json {
      background: rgba(15, 23, 42, 0.92);
      border-radius: 1rem;
      color: #e2e8f0;
      font-size: 0.86rem;
      line-height: 1.55;
      margin: 0;
      overflow: auto;
      padding: 1rem 1.1rem;
      white-space: pre-wrap;
      word-break: break-word;
    }
  </style>
@endsection

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card v2-detail-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-4">
            <div class="v2-card-heading">
              <span class="v2-card-heading__icon"><i class="mdi mdi-clipboard-text-clock-outline"></i></span>
              <div>
                <h4 class="card-title mb-1">{{ $logEntry->title }}</h4>
                <p class="card-description mb-0">{{ $logEntry->description }}</p>
              </div>
            </div>
            <span class="badge badge-{{ $badgeMap[$logEntry->type] ?? 'secondary' }}">{{ strtoupper((string) $logEntry->type) }}</span>
          </div>

          <div class="detail-grid detail-grid--wide mb-4">
            <div>
              <span>Date</span>
              <strong>{{ $displayDate }}</strong>
            </div>
            <div>
              <span>Actor</span>
              <strong>{{ $logEntry->created_by ?: 'System' }}</strong>
            </div>
            <div>
              <span>IP Address</span>
              <strong>{{ $logEntry->ip ?: '-' }}</strong>
            </div>
            <div>
              <span>User ID</span>
              <strong>{{ $logEntry->user_id ?: '-' }}</strong>
            </div>
            <div>
              <span>API Request</span>
              <strong>{{ (int) $logEntry->is_api === 1 ? 'Yes' : 'No' }}</strong>
            </div>
            <div>
              <span>URI</span>
              <strong>{{ $logEntry->uri ?: '-' }}</strong>
            </div>
          </div>

          <div class="v2-card-heading mb-3">
            <span class="v2-card-heading__icon"><i class="mdi mdi-code-json"></i></span>
            <h5 class="mb-0">Request Info</h5>
          </div>
          <pre class="v2-json">{{ $prettyInfo }}</pre>

          <div class="mt-4">
            <a href="{{ route('v2.logs.index') }}" class="btn btn-outline-primary btn-icon-text">
              <i class="mdi mdi-arrow-left"></i>
              <span>Back To Logs</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
