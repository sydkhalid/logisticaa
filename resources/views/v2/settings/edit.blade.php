@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Settings'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4 class="card-title mb-1">Application Settings</h4>
              <p class="card-description mb-0">Manage the shared connection values used across the logistics workflow.</p>
            </div>
            <a href="{{ route('v2.integrations.index') }}" class="btn btn-outline-primary btn-sm">Open Health Check</a>
          </div>

          <form method="POST" action="{{ route('v2.settings.update') }}" class="forms-sample">
            @csrf
            <input type="hidden" name="id" value="{{ old('id', $setting->id) }}">

            <div class="row">
              <div class="col-md-6 form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $setting->name) }}">
              </div>
              <div class="col-md-6 form-group">
                <label for="copyright">Copyright</label>
                <input type="text" class="form-control" id="copyright" name="copyright" value="{{ old('copyright', $setting->copyright) }}">
              </div>
              <div class="col-md-6 form-group">
                <label for="bocsh_link">BOCSH Link</label>
                <input type="text" class="form-control" id="bocsh_link" name="bocsh_link" value="{{ old('bocsh_link', $setting->bocsh_link) }}">
              </div>
              <div class="col-md-6 form-group">
                <label for="tracing_link">WheelsEye Link</label>
                <input type="text" class="form-control" id="tracing_link" name="tracing_link" value="{{ old('tracing_link', $setting->tracing_link) }}">
              </div>
              <div class="col-md-6 form-group">
                <label for="flee_link">FleetX Link</label>
                <input type="text" class="form-control" id="flee_link" name="flee_link" value="{{ old('flee_link', $setting->flee_link) }}">
              </div>
              <div class="col-md-6 form-group">
                <label for="address">WheelsEye Token</label>
                <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $setting->address) }}">
              </div>
              <div class="col-md-12 form-group">
                <label for="access_token">FleetX Access Token</label>
                <input type="text" class="form-control" id="access_token" name="access_token" value="{{ old('access_token', $setting->access_token) }}">
              </div>
            </div>

            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary">Update Settings</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
