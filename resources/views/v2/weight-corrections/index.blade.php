@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'Weight Corrections'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="card-title mb-1">Weight Corrections</h4>
              <p class="card-description mb-0">Create initial corrections and re-corrections without editing the original LR record.</p>
            </div>
            <a href="{{ route('v2.weight-corrections.create') }}" class="btn btn-primary btn-icon-text">
              <i class="ti-plus btn-icon-prepend"></i> Add Weight
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-striped v2-table" id="weights-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>LR Number</th>
                  <th>Corrected Weight</th>
                  <th>Length</th>
                  <th>Breadth</th>
                  <th>Height</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($weights as $weight)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $weight->lrNumber }}</td>
                    <td>{{ $weight->correctedWeight }}</td>
                    <td>{{ $weight->length }}</td>
                    <td>{{ $weight->breadth }}</td>
                    <td>{{ $weight->height }}</td>
                    <td class="text-end">
                      <a href="{{ route('v2.weight-corrections.edit', $weight) }}" class="btn btn-outline-primary btn-sm">Re-Correct</a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    window.addEventListener('DOMContentLoaded', function () {
      window.V2.initDataTable('#weights-table');
    });
  </script>
@endsection
