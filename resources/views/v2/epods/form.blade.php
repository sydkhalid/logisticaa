@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'EPOD Uploads', 'url' => route('v2.epods.index')],
    ['label' => 'Upload'],
  ];
  $defaultLspId = $defaultLspId ?? '';
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-10 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">{{ $pageTitle }}</h4>
          <p class="card-description">Accepted formats: JPG, JPEG, PNG, and PDF. If this LR already has an uploaded EPOD, the new Travis re-upload API will be used automatically.</p>

          <form method="POST" action="{{ route('v2.epods.store') }}" class="forms-sample" enctype="multipart/form-data">
            @csrf

            <div class="row">
              <div class="col-md-4 form-group">
                <label for="lspId">LSP ID</label>
                <input type="text" class="form-control" id="lspId" name="lspId" value="{{ old('lspId', $defaultLspId) }}" {{ $defaultLspId !== '' ? 'readonly' : '' }} required>
              </div>
              <div class="col-md-4 form-group">
                <label for="lrNumber">LR Number</label>
                <input type="text" class="form-control" id="lrNumber" name="lrNumber" value="{{ old('lrNumber') }}" list="completed-lrs" required>
                <datalist id="completed-lrs">
                  @foreach ($recentTrackings as $tracking)
                    <option value="{{ $tracking->lrNumber }}" data-lsp-id="{{ $tracking->lspId }}"></option>
                  @endforeach
                </datalist>
              </div>
              <div class="col-md-4 form-group">
                <label for="epod">EPOD File</label>
                <input type="file" class="form-control" id="epod" name="epod" accept=".jpg,.jpeg,.png,.pdf" required>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('v2.epods.index') }}" class="btn btn-light">Cancel</a>
              <button type="submit" class="btn btn-primary">Upload EPOD</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    window.addEventListener('DOMContentLoaded', function () {
      var lrInput = document.getElementById('lrNumber');
      var lspInput = document.getElementById('lspId');
      var options = document.querySelectorAll('#completed-lrs option');

      lrInput.addEventListener('change', function () {
        Array.prototype.forEach.call(options, function (option) {
          if (option.value === lrInput.value && option.dataset.lspId) {
            lspInput.value = option.dataset.lspId;
          }
        });
      });
    });
  </script>
@endsection
