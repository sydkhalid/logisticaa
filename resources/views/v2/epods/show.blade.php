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
    <div class="col-xl-8 grid-margin stretch-card">
      <div class="card v2-detail-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-4">
            <div class="v2-card-heading">
              <span class="v2-card-heading__icon"><i class="mdi mdi-cloud-upload-outline"></i></span>
              <div>
                <h4 class="card-title mb-1">EPOD Details</h4>
                <p class="card-description mb-0">Review the local EPOD file and retry pending Travis uploads.</p>
              </div>
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
            <a href="{{ route('v2.epods.index') }}" class="btn btn-light btn-icon-text">
              <i class="mdi mdi-arrow-left"></i>
              <span>Back</span>
            </a>
            @if($fileExists)
              <a href="{{ route('v2.epods.download', $epod) }}" class="btn btn-outline-primary btn-icon-text">
                <i class="mdi mdi-download"></i>
                <span>Download</span>
              </a>
              @if($isImageFile || $isPdfFile)
                <a href="{{ route('v2.epods.preview', $epod) }}" target="_blank" rel="noopener" class="btn btn-outline-info btn-icon-text">
                  <i class="mdi mdi-file-eye-outline"></i>
                  <span>Open Preview</span>
                </a>
              @endif
            @endif
            @if(!$uploaded && $fileExists)
              <form method="POST" action="{{ route('v2.epods.retry', $epod) }}">
                @csrf
                <button type="submit" class="btn btn-success btn-icon-text">
                  <i class="mdi mdi-refresh"></i>
                  <span>Retry Upload</span>
                </button>
              </form>
            @endif
            @if($canManageEpods)
              <form method="POST" action="{{ route('v2.epods.destroy', $epod) }}" onsubmit="return window.V2.confirmDelete(this, 'Delete this EPOD record and its local file?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-icon-text">
                  <i class="mdi mdi-delete-outline"></i>
                  <span>Delete</span>
                </button>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4 grid-margin stretch-card">
      <div class="card epod-preview-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <div class="v2-card-heading">
                <span class="v2-card-heading__icon"><i class="mdi mdi-file-eye-outline"></i></span>
                <div>
                  <h4 class="card-title mb-1">EPOD Preview</h4>
                  <p class="card-description mb-0">{{ $previewMime ?: 'No local file' }}</p>
                </div>
              </div>
            </div>
            <span class="badge badge-{{ $fileExists ? 'success' : 'danger' }}">
              {{ $fileExists ? 'File Ready' : 'Missing' }}
            </span>
          </div>

          <div class="epod-preview-frame">
            @if(!$fileExists)
              <div class="epod-preview-empty">
                <i class="mdi mdi-file-alert-outline"></i>
                <strong>File Missing</strong>
                <span>Upload the EPOD again to restore the preview.</span>
              </div>
            @elseif($isImageFile)
              <a href="{{ route('v2.epods.preview', $epod) }}" target="_blank" rel="noopener" class="epod-preview-link">
                <img src="{{ route('v2.epods.preview', $epod) }}" alt="EPOD image for {{ $epod->lrNumber ?: 'LR' }}">
              </a>
            @elseif($isPdfFile)
              <object data="{{ route('v2.epods.preview', $epod) }}" type="application/pdf">
                <div class="epod-preview-empty">
                  <i class="mdi mdi-file-pdf-box"></i>
                  <strong>PDF Preview</strong>
                  <span>Use Open Preview to view this PDF in a new tab.</span>
                </div>
              </object>
            @else
              <div class="epod-preview-empty">
                <i class="mdi mdi-file-question-outline"></i>
                <strong>Preview Not Available</strong>
                <span>This file type can still be downloaded.</span>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
