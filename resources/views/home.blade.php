@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
]])

<section class="content">
    <div class="container-fluid">
        <div class="col-md-12">
            <div class="card-body">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-truck"></i></span>
                                <div class="info-box-content">
                                <a href="{{ route('lrtracking') }}">
                                    <span class="info-box-text">Lr Tracking</span>
                                    <span class="info-box-number">
                                        {{ $trackingCount }}
                                    </span>
                                </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-upload"></i></span>
                                <div class="info-box-content">
                                    <a href="{{ route('epod') }}">
                                        <span class="info-box-text">Upload Epod</span>
                                        <span class="info-box-number">
                                            {{ $uploadCount }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
