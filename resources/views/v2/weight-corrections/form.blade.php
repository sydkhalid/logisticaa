@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Weight Corrections', 'url' => route('v2.weight-corrections.index')],
    ['label' => $pageTitle],
  ];
  $isEdit = $formMethod !== 'POST';
  $defaultLspId = $defaultLspId ?? '';
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-10 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">{{ $pageTitle }}</h4>
          <p class="card-description">Fetch LR dimensions first, then submit the corrected weight payload to BOCSH.</p>

          <form method="POST" action="{{ $formAction }}" class="forms-sample" id="weight-form">
            @csrf
            @if ($formMethod !== 'POST')
              @method($formMethod)
            @endif

            <div class="row">
              <div class="col-md-6 form-group">
                <label for="lrNumber">LR Number</label>
                <div class="d-flex gap-2">
                  <input type="text" class="form-control" id="lrNumber" name="lrNumber" value="{{ old('lrNumber', $weight->lrNumber) }}" list="recent-lrs" {{ $isEdit ? 'readonly' : '' }} required>
                  @unless ($isEdit)
                    <button type="button" class="btn btn-outline-primary" id="fetch-lr">Fetch</button>
                  @endunless
                </div>
                <datalist id="recent-lrs">
                  @foreach ($recentTrackings as $recentTracking)
                    <option value="{{ $recentTracking->lrNumber }}"></option>
                  @endforeach
                </datalist>
              </div>
              <div class="col-md-6 form-group">
                <label for="lspId">LSP ID</label>
                <input type="text" class="form-control" id="lspId" name="lspId" value="{{ old('lspId', $weight->lspId ?: $defaultLspId) }}" {{ $isEdit || $defaultLspId !== '' ? 'readonly' : '' }} required>
              </div>
              <div class="col-md-6 form-group">
                <label for="actualWeight">Corrected Weight</label>
                <input type="number" step="any" class="form-control" id="actualWeight" name="actualWeight" value="{{ old('actualWeight', $weight->correctedWeight) }}" required>
              </div>
              <div class="col-md-6 form-group">
                <label for="length">Length</label>
                <input type="number" step="any" class="form-control" id="length" name="length" value="{{ old('length', $weight->length) }}" required>
              </div>
              <div class="col-md-6 form-group">
                <label for="breadth">Breadth</label>
                <input type="number" step="any" class="form-control" id="breadth" name="breadth" value="{{ old('breadth', $weight->breadth) }}" required>
              </div>
              <div class="col-md-6 form-group">
                <label for="height">Height</label>
                <input type="number" step="any" class="form-control" id="height" name="height" value="{{ old('height', $weight->height) }}" required>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('v2.weight-corrections.index') }}" class="btn btn-light">Cancel</a>
              <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Weight' : 'Save Weight' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  @unless ($isEdit)
    <script>
      window.addEventListener('DOMContentLoaded', function () {
        var button = document.getElementById('fetch-lr');
        var lrInput = document.getElementById('lrNumber');
        var lspInput = document.getElementById('lspId');
        var weightInput = document.getElementById('actualWeight');
        var lengthInput = document.getElementById('length');
        var breadthInput = document.getElementById('breadth');
        var heightInput = document.getElementById('height');

        function showFetchAlert(icon, title, text) {
          if (window.V2 && typeof window.V2.fireAlert === 'function') {
            window.V2.fireAlert({
              icon: icon,
              title: title,
              text: text,
              confirmButtonText: 'Close'
            });
          }
        }

        button.addEventListener('click', function () {
          var lrNumber = lrInput.value.trim();

          if (!lrNumber) {
            lrInput.focus();
            showFetchAlert('warning', 'LR Number Required', 'Enter an LR number before fetching dimensions.');
            return;
          }

          button.disabled = true;
          button.textContent = 'Fetching...';

          fetch('{{ route('v2.weight-corrections.fetch-lr') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              lrNumber: lrNumber
            })
          })
            .then(function (response) {
              if (!response.ok) {
                return response.json().then(function (payload) {
                  throw new Error(payload.message || 'LR number not found.');
                }).catch(function (error) {
                  throw new Error(error.message || 'LR number not found.');
                });
              }
              return response.json();
            })
            .then(function (payload) {
              lspInput.value = payload.lspId || lspInput.value;
              weightInput.value = payload.actualWeight || '';
              lengthInput.value = payload.length || '';
              breadthInput.value = payload.breadth || '';
              heightInput.value = payload.height || '';
              showFetchAlert('success', 'LR Loaded', 'Weight and dimension fields were filled from the LR record.');
            })
            .catch(function (error) {
              showFetchAlert('error', 'LR Fetch Failed', error.message);
            })
            .finally(function () {
              button.disabled = false;
              button.textContent = 'Fetch';
            });
        });
      });
    </script>
  @endunless
@endsection
