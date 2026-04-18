@extends('v2.layouts.app')

@php
  $breadcrumbs = [
    ['label' => 'EPOD Uploads'],
  ];
@endphp

@section('content')
  <div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="card-title mb-1">EPOD Upload History</h4>
              <p class="card-description mb-0">Upload proof of delivery files without changing the existing Laravel pages.</p>
            </div>
            <a href="{{ route('v2.epods.create') }}" class="btn btn-primary btn-icon-text">
              <i class="ti-upload btn-icon-prepend"></i> Upload EPOD
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-striped v2-table" id="epods-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>LSP ID</th>
                  <th>LR Number</th>
                  <th>Status</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($epods as $epod)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $epod->lspId }}</td>
                    <td>{{ $epod->lrNumber }}</td>
                    <td><span class="badge badge-success">Success</span></td>
                    <td>{{ $epod->created_at }}</td>
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
      window.V2.initDataTable('#epods-table');
    });
  </script>
@endsection
