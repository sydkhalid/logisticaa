@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
]])
<style>
       hr {
        position: relative;
        top: 20px;
        border: none;
        height: 12px;
        background: black;
        margin-bottom: 50px;
    }
    .blink_me {
        color: red;
        animation: blinker 1s linear infinite;
        }

        @keyframes blinker {
        50% {
            opacity: 0;
        }
        }
    </style>
<section class="content">
    <div class="container-fluid">
        <div class="col-md-12">
            <div class="card-body">
                <div class="col-md-12">
                    <div class="blink_me"><h5>Please give your consent to check your location from your service Provider. Dial 7303777719 and press 1 or reply with Y or N to  55502  -Idea, 5114040 -Airtel, 9167500066 -Vodafone.</h5></div>
                    <div class="row">
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-map"></i></span>
                                <div class="info-box-content">
                                <a href="{{ url('vehicle') }}">
                                    <span class="info-box-text">Total Vehicles</span>
                                    <span class="info-box-number">
                                        {{ $vehicleCount }}
                                    </span>
                                </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-truck"></i></span>
                                <div class="info-box-content">
                                <a href="{{ route('lrtracking.index') }}">
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
                                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-tasks"></i></span>
                                <div class="info-box-content">
                                <a href="{{ route('lrtracking.index') }}">
                                    <span class="info-box-text">Completed Lr Tracking</span>
                                    <span class="info-box-number">
                                        {{ $trackingCompleteedCount }}
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

            <hr>
            <h3>Flee Api</h3>
            <div class="card-body">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-truck"></i></span>
                                <div class="info-box-content">
                                <a href="{{ url('vehicle') }}">
                                    <span class="info-box-text">Total Vehicles</span>
                                    <span class="info-box-number">
                                        {{ $json_details['totalVehicles'] }}
                                    </span>
                                </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-running"></i></span>
                                <div class="info-box-content">
                                <a href="{{ route('lrtracking.index') }}">
                                    <span class="info-box-text">Running Vehicles</span>
                                    <span class="info-box-number">
                                        {{ $json_details['runningVehicles'] }}
                                    </span>
                                </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-tasks"></i></span>
                                <div class="info-box-content">
                                <a href="{{ route('lrtracking.index') }}">
                                    <span class="info-box-text">Idle Vehicles</span>
                                    <span class="info-box-number">
                                        {{ $json_details['idleVehicles'] }}
                                    </span>
                                </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-parking"></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">Parked Vehicles</span>
                                        <span class="info-box-number">
                                        {{ $json_details['parkedVehicles'] }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-store"></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">Inshop Vehicles</span>
                                        <span class="info-box-number">
                                        {{ $json_details['inshopVehicles'] }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-unlink"></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">Disconnected Vehicles</span>
                                        <span class="info-box-number">
                                        {{ $json_details['disconnectedVehicles'] }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-spinner"></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">Unreachable Vehicles</span>
                                        <span class="info-box-number">
                                        {{ $json_details['unreachableVehicles'] }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-power-off"></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">Immobilised Vehicles</span>
                                        <span class="info-box-number">
                                        {{ $json_details['immobilisedVehicles'] }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-plug"></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">No Power Vehicles</span>
                                        <span class="info-box-number">
                                        {{ $json_details['nopowerVehicles'] }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-globe-stand"></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">Standby Vehicles</span>
                                        <span class="info-box-number">
                                        {{ $json_details['standbyVehicles'] }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-battery-half
                                    "></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">Battery Discharged Vehicles</span>
                                        <span class="info-box-number">
                                        {{ $json_details['batteryDischargedVehicles'] }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-tools"></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">Utilization</span>
                                        <span class="info-box-number">
                                        {{ $json_details['utilization'] }}
                                        </span>
                                    </a>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-bell"></i></span>
                                <div class="info-box-content">
                                    <a href="#">
                                        <span class="info-box-text">Alarms</span>
                                        <span class="info-box-number">
                                        {{ $json_details['alarms'] }}
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
