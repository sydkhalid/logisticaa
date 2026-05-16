@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Own Vehicles', 'url' => route('v2.vehicles.index')],
    ['label' => $pageTitle],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card v2-form-card">
        <div class="card-body">
          <div class="v2-card-heading mb-3">
            <span class="v2-card-heading__icon"><i class="mdi mdi-truck-outline"></i></span>
            <div>
              <h4 class="card-title mb-1">{{ $pageTitle }}</h4>
              <p class="card-description mb-0">Keep own-fleet registration separate from the SIM-tracked market fleet.</p>
            </div>
          </div>

          <form method="POST" action="{{ $formAction }}" class="forms-sample">
            @csrf
            @if ($formMethod !== 'POST')
              @method($formMethod)
            @endif

            <div class="form-group">
              <label for="vehicleNo">Vehicle Number</label>
              <input type="text" class="form-control" id="vehicleNo" name="vehicleNo" value="{{ old('vehicleNo', $vehicle->vehicleNo) }}" placeholder="Enter vehicle number" required>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('v2.vehicles.index') }}" class="btn btn-light btn-icon-text">
                <i class="mdi mdi-arrow-left"></i>
                <span>Cancel</span>
              </a>
              <button type="submit" class="btn btn-primary btn-icon-text">
                <i class="mdi mdi-content-save-outline"></i>
                <span>Save Vehicle</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
