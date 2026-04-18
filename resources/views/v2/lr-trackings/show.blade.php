@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'LR Tracking', 'url' => route('v2.lr-trackings.index')],
    ['label' => $tracking->lrNumber],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
              <h4 class="card-title mb-1">LR Tracking Details</h4>
              <p class="card-description mb-0">Shipment metadata, location payload, and delivery dimensions.</p>
            </div>
            <form method="POST" action="{{ route('v2.lr-trackings.refresh', $tracking) }}">
              @csrf
              <button type="submit" class="btn btn-primary btn-sm">Refresh Location</button>
            </form>
          </div>

          <div class="detail-grid detail-grid--wide">
            <div><span>LSP ID</span><strong>{{ $tracking->lspId }}</strong></div>
            <div><span>Vehicle Number</span><strong>{{ $tracking->vehicleNo }}</strong></div>
            <div><span>LR Number</span><strong>{{ $tracking->lrNumber }}</strong></div>
            <div><span>LR Status</span><strong>{{ $tracking->lrStatus }}</strong></div>
            <div><span>Latitude</span><strong>{{ $tracking->latitude ?: 'N/A' }}</strong></div>
            <div><span>Longitude</span><strong>{{ $tracking->longitude ?: 'N/A' }}</strong></div>
            <div><span>Location</span><strong>{{ $tracking->location ?: 'N/A' }}</strong></div>
            <div><span>Pick Up Date</span><strong>{{ $tracking->pickUpDate ?: 'N/A' }}</strong></div>
            <div><span>LR Date</span><strong>{{ $tracking->lrDate ?: 'N/A' }}</strong></div>
            <div><span>Delivered Date</span><strong>{{ $tracking->actualDeliveredDate ?: 'N/A' }}</strong></div>
            <div><span>EDD</span><strong>{{ $tracking->edd ?: 'N/A' }}</strong></div>
            <div><span>Receiver Name</span><strong>{{ $tracking->receiverName ?: 'N/A' }}</strong></div>
            <div><span>Delivered To Person</span><strong>{{ $tracking->deliveredToPerson ?: 'N/A' }}</strong></div>
            <div><span>Actual Weight</span><strong>{{ $tracking->actualWeight ?: 'N/A' }}</strong></div>
            <div><span>Packages</span><strong>{{ $tracking->numberOfPackages ?: 'N/A' }}</strong></div>
            <div><span>Length</span><strong>{{ $tracking->length ?: 'N/A' }}</strong></div>
            <div><span>Breadth</span><strong>{{ $tracking->breadth ?: 'N/A' }}</strong></div>
            <div><span>Height</span><strong>{{ $tracking->height ?: 'N/A' }}</strong></div>
            <div><span>Truck Type</span><strong>{{ $tracking->truckType ?: 'N/A' }}</strong></div>
            <div><span>Truck Tonnage</span><strong>{{ $tracking->truckTonnage ?: 'N/A' }}</strong></div>
            <div><span>Delivery Notes</span><strong>{{ $tracking->deliveryNotes ?: 'N/A' }}</strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
