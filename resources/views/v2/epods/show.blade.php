@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'EPOD Uploads', 'url' => route('v2.epods.index')],
    ['label' => 'Details'],
  ];
  $uploaded = (int) $epod->status === 1;
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
              <h4 class="card-title mb-1">EPOD Details</h4>
              <p class="card-description mb-0">Review the local EPOD file and retry pending Travis uploads.</p>
            </div>
            <span class="badge badge-{{ $uploaded ? 'success' : 'warning' }}">
              {{ $uploaded ? 'Uploaded' : 'Pending Retry' }}
            </span>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <small class="text-muted d-block">LSP ID</small>
              <strong>{{ $epod->lspId ?: '-' }}</strong>
            </div>
            <div class="col-md-6 mb-3">
              <small class="text-muted d-block">LR Number</small>
              <strong>{{ $epod->lrNumber ?: '-' }}</strong>
            </div>
            <div class="col-md-6 mb-3">
              <small class="text-muted d-block">File</small>
              <strong>{{ $epod->epod ?: '-' }}</strong>
            </div>
            <div class="col-md-6 mb-3">
              <small class="text-muted d-block">File Status</small>
              <strong class="{{ $fileExists ? 'text-success' : 'text-danger' }}">
                {{ $fileExists ? 'Available' : 'Missing' }}
              </strong>
            </div>
            <div class="col-md-6 mb-3">
              <small class="text-muted d-block">Created</small>
              <strong>{{ $epod->created_at ? $epod->created_at->format('d M Y, h:i A') : '-' }}</strong>
            </div>
            <div class="col-md-6 mb-3">
              <small class="text-muted d-block">Tracking Status</small>
              <strong>
                @if($tracking)
                  {{ (int) $tracking->status === 3 ? 'EPOD Closed' : ($tracking->lrStatus ?: 'Available') }}
                @else
                  -
                @endif
              </strong>
            </div>
          </div>

          <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
            <a href="{{ route('v2.epods.index') }}" class="btn btn-light">Back</a>
            @if($fileExists)
              <a href="{{ route('v2.epods.download', $epod) }}" class="btn btn-outline-primary">Download</a>
            @endif
            @if(!$uploaded && $fileExists)
              <form method="POST" action="{{ route('v2.epods.retry', $epod) }}">
                @csrf
                <button type="submit" class="btn btn-success">Retry Upload</button>
              </form>
            @endif
            @if($canManageEpods)
              <form method="POST" action="{{ route('v2.epods.destroy', $epod) }}" onsubmit="return window.V2.confirmDelete(this, 'Delete this EPOD record and its local file?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Delete</button>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
