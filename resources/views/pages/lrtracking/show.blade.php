@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
    ['name' => "Lr Tracking",'url'=> route('lrtracking.index'),'active' => 'no'],
    ['name' =>$page_title,'url'=> '','active' => 'yes'],
]])

<section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">{{ $page_title }}</h3>
                {{-- @if($lrtracking->status == 1)
                    <a href="#" class="btn btn-success btn-sm float-sm-right">&nbsp;Inserted Record Succefully </a>
                 @else
                    <a href="#" class="btn btn-danger btn-sm float-sm-right">&nbsp;Duplicated Record Inserted </a>
                 @endif --}}
            </div>
            <!-- /.card-header -->
            <div class="card-body">

                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lspId">LspId</label> : {{ $lrtracking->lspId }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrNumber">Lr Number</label> {{ $lrtracking->lrNumber }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrStatus">Lr Status</label> : {{ $lrtracking->lrStatus }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="latitude">Latitude</label> : {{ $lrtracking->latitude }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="longitude">Longitude</label> : {{ $lrtracking->longitude }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location">Location</label> : {{ $lrtracking->location }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pickUpDate">Pick Up Date</label> : {{ $lrtracking->pickUpDate }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrDate">Lr Date</label> : {{ $lrtracking->lrDate }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="actualDeliveredDate">Actual Delivered Date</label> : {{ $lrtracking->actualDeliveredDate }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edd">EDD</label> : {{ $lrtracking->edd }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="receiverName">Receiver Name</label> : {{ $lrtracking->receiverName }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="deliveredToPerson">Delivered To Person</label> : {{ $lrtracking->deliveredToPerson }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="actualWeight">ActualWeight</label> : {{ $lrtracking->actualWeight }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numberOfPackages">Number Of Packages</label> : {{ $lrtracking->numberOfPackages }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="length">Length</label> : {{ $lrtracking->length }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="breadth">Breadth</label> : {{ $lrtracking->breadth }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="height">Height</label> : {{ $lrtracking->height }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="truckType">truck Type</label> : {{ $lrtracking->truckType }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="truckTonnage">Truck Tonnage</label> : {{ $lrtracking->truckTonnage }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vehicleNo">vehicle No</label> : {{ $lrtracking->vehicleNo }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="deliveryNotes">Delivery Notes</label> : {{ $lrtracking->deliveryNotes }}
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
          </div>
        </div>
    </div>
    </div>
</section>
@endsection
@section('script')
<script>
        $(function () {
            $(".date").datepicker({
                yearRange: "-100:+200",
                showButtonPanel: true,
                changeMonth: true,
                changeYear: true,
                dateFormat: "YY-mm-dd H:M:S",
                showAnim: "slideDown"
            }
        });
@endsection
