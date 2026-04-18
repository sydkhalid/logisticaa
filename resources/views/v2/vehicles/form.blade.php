@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Own Vehicles', 'url' => route('v2.vehicles.index')],
    ['label' => $pageTitle],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">{{ $pageTitle }}</h4>
          <p class="card-description">Keep own-fleet registration separate from the SIM-tracked market fleet.</p>

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
              <a href="{{ route('v2.vehicles.index') }}" class="btn btn-light">Cancel</a>
              <button type="submit" class="btn btn-primary">Save Vehicle</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
