@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Market Vehicles', 'url' => route('v2.market-vehicles.index')],
    ['label' => $pageTitle],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card v2-form-card">
        <div class="card-body">
          <div class="v2-card-heading mb-3">
            <span class="v2-card-heading__icon"><i class="mdi mdi-truck-delivery-outline"></i></span>
            <div>
              <h4 class="card-title mb-1">{{ $pageTitle }}</h4>
              <p class="card-description mb-0">Register or update FleetX-managed vehicles and their SIM metadata.</p>
            </div>
          </div>

          <form method="POST" action="{{ $formAction }}" class="forms-sample">
            @csrf
            @if ($formMethod !== 'POST')
              @method($formMethod)
            @endif

            <div class="row">
              <div class="col-md-6 form-group">
                <label for="vehicleNo">Vehicle Number</label>
                <input type="text" class="form-control" id="vehicleNo" name="vehicleNo" value="{{ old('vehicleNo', $vehicle->vehicleNo) }}" placeholder="Vehicle number" required>
              </div>
              <div class="col-md-6 form-group">
                <label for="mobileNo">Mobile Number</label>
                <input type="text" class="form-control" id="mobileNo" name="mobileNo" value="{{ old('mobileNo', $vehicle->mobileNo ?: '91') }}" placeholder="Driver mobile" required>
              </div>
              <div class="col-md-6 form-group">
                <label for="simProvider">SIM Provider</label>
                <select class="form-select" id="simProvider" name="simProvider" data-placeholder="Choose SIM provider" required>
                  @foreach (['AIRTEL', 'VODAFONE', 'JIO', 'IDEA'] as $provider)
                    <option value="{{ $provider }}" {{ old('simProvider', $vehicle->simProvider) === $provider ? 'selected' : '' }}>
                      {{ $provider }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6 form-group">
                <label for="expireDate">SIM Expiry</label>
                <input type="datetime-local" class="form-control" id="expireDate" name="expireDate" value="{{ old('expireDate', $vehicle->expireDate ? date('Y-m-d\\TH:i', strtotime($vehicle->expireDate)) : '') }}" required>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('v2.market-vehicles.index') }}" class="btn btn-light btn-icon-text">
                <i class="mdi mdi-arrow-left"></i>
                <span>Cancel</span>
              </a>
              <button type="submit" class="btn btn-primary btn-icon-text">
                <i class="mdi mdi-content-save-outline"></i>
                <span>Save Market Vehicle</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
