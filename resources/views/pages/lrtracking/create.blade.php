@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
    ['name' => "Lr Tracking",'url'=> route('lrtracking'),'active' => 'no'],
    ['name' =>$page_title,'url'=> '','active' => 'yes'],
]])

<section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">{{ $page_title }}</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                @if (count($errors) > 0)
                <div class="alert alert-success alert-dismissible">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <form method="POST" action="{{ route('ltrtracking_upload') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lspId">LspId</label>
                                    <input value="0097457655" type="text" id="lspId" name ="lspId" class="form-control" placeholder="lspId">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrNumber">Lr Number</label>
                                    <input value="7059" type="text" id="lrNumber" name ="lrNumber" class="form-control" placeholder="lrNumber">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrStatus">Lr Status</label>
                                    <select class="form-control select2" style="width: 100%;" name="lrStatus"
                                    id="lrStatus" Required>
                                    <option value="">Please choose</option>
                                    <option value="Shipment In Transit">Shipment In Transit</option>
                                    <option value="Hub-Delivered">Hub-Delivered</option>
                                    <option value="Out-For-Delivery">Out-For-Delivery</option>
                                    <option value="Delay">Delay</option>
                                    <option value="Customer">Customer Appointment Delivery</option>
                                    <option value="Shipment Delivered">Shipment Delivered</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="latitude">Latitude</label>
                                    <input value="79.00002132213" type="text" id="latitude" name ="latitude" class="form-control" placeholder="latitude">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="longitude">Longitude</label>
                                    <input value="80.0122321341" type="text" id="longitude" name ="longitude" class="form-control" placeholder="longitude">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <input value="Bengaluru" type="text" id="location" name ="location" class="form-control" placeholder="location" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pickUpDate">Pick Up Date</label>
                                    <input value="2021-06-28 19:50:26" type="text" id="pickUpDate" name ="pickUpDate" class="form-control date" placeholder="pickUpDate">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrDate">Lr Date</label>
                                    <input value="2021-06-28 19:50:26" type="text" id="lrDate" name ="lrDate" class="form-control" placeholder="lrDate">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="actualDeliveredDate">Actual Delivered Date</label>
                                    <input value="" type="text" id="actualDeliveredDate" name ="actualDeliveredDate" class="form-control" placeholder="actualDeliveredDate" >
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edd">EDD(Estimated date of delivery)</label>
                                    <input value="2021-06-28 19:50:26" type="text" id="edd" name ="edd" class="form-control" placeholder="edd" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="receiverName">Receiver Name</label>
                                    <input value="Receiver name at the source" type="text" id="receiverName" name ="receiverName" class="form-control" placeholder="receiverName">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="deliveredToPerson">Delivered To Person</label>
                                    <input value="Reciever name at the final destination" type="text" id="deliveredToPerson" name ="deliveredToPerson" class="form-control" placeholder="deliveredToPerson">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="actualWeight">ActualWeight</label>
                                    <input value="10" type="text" id="actualWeight" name ="actualWeight" class="form-control" placeholder="actualWeight" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numberOfPackages">Number Of Packages(boxes/bins/pallets)</label>
                                    <input value="10" type="text" id="numberOfPackages" name ="numberOfPackages" class="form-control" placeholder="numberOfPackages" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="length">Length(volume (in Meters) upto 3 decimals)</label>
                                    <input value="100.001" type="text" id="length" name ="length" class="form-control" placeholder="length" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="breadth">Breadth(volume (in Meters) upto 3 decimals)</label>
                                    <input value="100.001" type="text" id="breadth" name ="breadth" class="form-control" placeholder="breadth" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="height">Height(volume (in Meters) upto 3 decimals)</label>
                                    <input value="100.001" type="text" id="height" name ="height" class="form-control" placeholder="height" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="truckType">truck Type</label>
                                    <select class="form-control select2" style="width: 100%;" name="truckType"
                                    id="truckType" Required>
                                    <option value="">Please choose</option>
                                    <option value="LTL">LTL</option>
                                    <option value="FTL=">FTL</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="truckTonnage">Truck Tonnage</label>
                                        <select class="form-control select2" style="width: 100%;" name="truckTonnage"
                                        id="truckTonnage" Required>
                                        <option value="">Please choose</option>
                                        <option value="1 T">1 T</option>
                                        <option value="2.5 T">2.5 T</option>
                                        <option value="3.5 T">3.5 T</option>
                                        <option value="5.5 T">5.5 T</option>
                                        <option value="9 T Single axle">9 T Single axle</option>
                                        <option value="9 T Multi axle">9 T Multi axle</option>
                                        <option value="16 T">16 T</option>
                                        <option value="22 T">22 T</option>
                                        <option value="28 T">28 T</option>
                                        </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vehicleNo">vehicle No</label>
                                    <input value="AP03 CS 1111" type="text" id="vehicleNo" name ="vehicleNo" class="form-control" placeholder="vehicleNo" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="deliveryNotes">Delivery Notes</label>
                                    <textarea type="text" id="deliveryNotes" name ="deliveryNotes" class="form-control" placeholder="deliveryNotes">Delivery Notes</textarea>
                                </div>
                            </div>


                            <div class="col-md-12 ">
                                <div class="float-sm-right">
                                    <button type="submit" id="btnSubmit" class="btn  bg-gradient-success btn-sm"><i
                                                class="fa fa-save"></i>&nbsp;Save
                                    </button>
                                    <a href="{{ route('lrtracking') }}" class="btn  bg-gradient-danger btn-sm"><i
                                                class="fa fa-times"></i>&nbsp; Cancel</a>
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
