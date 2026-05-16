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
          <div class="v2-section-toolbar">
            <div class="v2-card-heading">
              <span class="v2-card-heading__icon"><i class="mdi mdi-weight"></i></span>
              <div>
                <h4 class="card-title mb-1">Weight Corrections</h4>
                <p class="card-description mb-0">Create initial corrections and re-corrections without editing the original LR record.</p>
              </div>
            </div>
            <a href="{{ route('v2.weight-corrections.create') }}" class="btn btn-primary btn-icon-text">
              <i class="mdi mdi-plus"></i>
              <span>Add Weight</span>
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
              <tbody></tbody>
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
      window.V2.initDataTable('#weights-table', {
        serverSide: true,
        processing: true,
        ajax: '{{ route('v2.weight-corrections.data') }}',
        order: [[0, 'desc']],
        columns: [
          { data: 'index', name: 'index' },
          { data: 'lrNumber', name: 'lrNumber' },
          { data: 'correctedWeight', name: 'correctedWeight' },
          { data: 'length', name: 'length' },
          { data: 'breadth', name: 'breadth' },
          { data: 'height', name: 'height' },
          { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ]
      });
    });
  </script>
@endsection
