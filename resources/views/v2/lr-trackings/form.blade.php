@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'LR Tracking', 'url' => route('v2.lr-trackings.index')],
    ['label' => 'Create'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">{{ $pageTitle }}</h4>
          <p class="card-description">Create a fresh LR record and sync the current vehicle location to BOCSH.</p>

          <form method="POST" action="{{ $formAction }}" class="forms-sample" id="lr-form">
            @csrf
            <div class="row">
              <div class="col-md-4 form-group">
                <label for="vehicle_id">Vehicle Number</label>
                <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                  <option value="">Choose a vehicle</option>
                  @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                      {{ $vehicle->vehicleNo }}{{ (int) $vehicle->vehicleStatus === 1 ? ' - Market' : ' - Own' }}
                    </option>
                  @endforeach
                </select>
                <div id="vehicle-approval" class="small mt-2 text-muted">Vehicle approval will be checked automatically.</div>
              </div>
              <div class="col-md-4 form-group">
                <label for="lspId">LSP ID</label>
                <input type="text" class="form-control" id="lspId" name="lspId" value="{{ old('lspId', '0097457655') }}" required readonly>
              </div>
              <div class="col-md-4 form-group">
                <label for="lrNumber">LR Number</label>
                <input type="text" class="form-control" id="lrNumber" name="lrNumber" value="{{ old('lrNumber') }}" required>
              </div>
              <div class="col-md-4 form-group">
                <label for="lrStatus">LR Status</label>
                <select class="form-select" id="lrStatus" name="lrStatus" required>
                  <option value="">Please choose</option>
                  @foreach ($lrStatuses as $status)
                    <option value="{{ $status }}" {{ old('lrStatus') === $status ? 'selected' : '' }}>{{ $status }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4 form-group">
                <label for="pickUpDate">Pick Up Date</label>
                <input type="datetime-local" class="form-control" id="pickUpDate" name="pickUpDate" value="{{ old('pickUpDate') }}">
              </div>
              <div class="col-md-4 form-group">
                <label for="lrDate">LR Date</label>
                <input type="datetime-local" class="form-control" id="lrDate" name="lrDate" value="{{ old('lrDate') }}">
              </div>
              <div class="col-md-4 form-group">
                <label for="edd">EDD</label>
                <input type="datetime-local" class="form-control" id="edd" name="edd" value="{{ old('edd') }}" required>
              </div>
              <div class="col-md-4 form-group">
                <label for="receiverName">Receiver Name</label>
                <input type="text" class="form-control" id="receiverName" name="receiverName" value="{{ old('receiverName') }}">
              </div>
              <div class="col-md-4 form-group">
                <label for="deliveredToPerson">Delivered To Person</label>
                <input type="text" class="form-control" id="deliveredToPerson" name="deliveredToPerson" value="{{ old('deliveredToPerson') }}">
              </div>
              <div class="col-md-4 form-group">
                <label for="actualWeight">Actual Weight</label>
                <input type="number" step="any" class="form-control" id="actualWeight" name="actualWeight" value="{{ old('actualWeight') }}" required>
              </div>
              <div class="col-md-4 form-group">
                <label for="numberOfPackages">Number Of Packages</label>
                <input type="number" step="1" class="form-control" id="numberOfPackages" name="numberOfPackages" value="{{ old('numberOfPackages') }}" required>
              </div>
              <div class="col-md-4 form-group">
                <label for="length">Length</label>
                <input type="number" step="any" class="form-control" id="length" name="length" value="{{ old('length') }}" required>
              </div>
              <div class="col-md-4 form-group">
                <label for="breadth">Breadth</label>
                <input type="number" step="any" class="form-control" id="breadth" name="breadth" value="{{ old('breadth') }}" required>
              </div>
              <div class="col-md-4 form-group">
                <label for="height">Height</label>
                <input type="number" step="any" class="form-control" id="height" name="height" value="{{ old('height') }}" required>
              </div>
              <div class="col-md-4 form-group">
                <label for="truckType">Truck Type</label>
                <select class="form-select" id="truckType" name="truckType" required>
                  <option value="">Please choose</option>
                  @foreach ($truckTypes as $type)
                    <option value="{{ $type }}" {{ old('truckType') === $type ? 'selected' : '' }}>{{ $type }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4 form-group">
                <label for="truckTonnage">Truck Tonnage</label>
                <select class="form-select" id="truckTonnage" name="truckTonnage" required>
                  <option value="">Please choose</option>
                  @foreach ($truckTonnages as $tonnage)
                    <option value="{{ $tonnage }}" {{ old('truckTonnage') === $tonnage ? 'selected' : '' }}>{{ $tonnage }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-8 form-group">
                <label for="deliveryNotes">Delivery Notes</label>
                <textarea class="form-control" id="deliveryNotes" name="deliveryNotes" rows="4">{{ old('deliveryNotes') }}</textarea>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('v2.lr-trackings.index') }}" class="btn btn-light">Cancel</a>
              <button type="submit" class="btn btn-primary" id="lr-submit">Create LR Tracking</button>
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
      var vehicleSelect = document.getElementById('vehicle_id');
      var notice = document.getElementById('vehicle-approval');
      var submit = document.getElementById('lr-submit');

      function checkVehicle() {
        if (!vehicleSelect.value) {
          notice.textContent = 'Vehicle approval will be checked automatically.';
          notice.className = 'small mt-2 text-muted';
          submit.disabled = false;
          return;
        }

        fetch('{{ route('v2.lr-trackings.vehicle-availability') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            vehicle_id: vehicleSelect.value
          })
        })
          .then(function (response) { return response.json(); })
          .then(function (payload) {
            notice.textContent = payload.message;
            notice.className = 'small mt-2 ' + (payload.approved ? 'text-success' : 'text-danger');
            submit.disabled = !payload.approved;
          })
          .catch(function () {
            notice.textContent = 'Vehicle approval check failed.';
            notice.className = 'small mt-2 text-danger';
            submit.disabled = false;
          });
      }

      vehicleSelect.addEventListener('change', checkVehicle);
      if (vehicleSelect.value) {
        checkVehicle();
      }
    });
  </script>
@endsection
