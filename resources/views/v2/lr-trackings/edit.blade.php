@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'LR Tracking', 'url' => route('v2.lr-trackings.index')],
    ['label' => $tracking->lrNumber],
    ['label' => 'Update Status'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">{{ $pageTitle }}</h4>
          <p class="card-description">Update shipment status and mark delivered records when applicable.</p>

          <form method="POST" action="{{ $formAction }}" class="forms-sample">
            @csrf
            @method('PUT')

            <div class="form-group">
              <label for="vehicleNo">Vehicle Number</label>
              <input type="text" class="form-control" id="vehicleNo" value="{{ $tracking->vehicleNo }}" readonly>
            </div>
            <div class="form-group">
              <label for="lrStatus">LR Status</label>
              <select class="form-select" id="lrStatus" name="lrStatus" required>
                @foreach (['Shipment In Transit', 'Hub-Delivered', 'Out-For-Delivery', 'Delay', 'Customer', 'Shipment Delivered'] as $status)
                  <option value="{{ $status }}" {{ old('lrStatus', $tracking->lrStatus) === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label for="actualDeliveredDate">Actual Delivered Date</label>
              <input type="datetime-local" class="form-control" id="actualDeliveredDate" name="actualDeliveredDate" value="{{ old('actualDeliveredDate', $tracking->actualDeliveredDate ? date('Y-m-d\\TH:i', strtotime($tracking->actualDeliveredDate)) : '') }}">
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('v2.lr-trackings.index') }}" class="btn btn-light">Cancel</a>
              <button type="submit" class="btn btn-primary">Update LR Status</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
