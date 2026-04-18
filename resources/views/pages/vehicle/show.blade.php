@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
    ['name' => "Vehicle show",'url'=> route('vehicle.index'),'active' => 'no'],
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

                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>vehicle Number</label>
                                    <input value="{{ $vehicle_details['vehicleNumber'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Device Number</label>
                                    <input value="{{ $vehicle_details['deviceNumber'] }}"  class="form-control" readonly>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Vendor Code</label>
                                    <input value="{{ $vehicle_details['vendorCode'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Venndor Name</label>
                                    <input value="{{ $vehicle_details['venndorName'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Latitude</label>
                                    <input value="{{ $vehicle_details['latitude'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Longitude</label>
                                    <input value="{{ $vehicle_details['longitude'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Speed</label>
                                    <input value="{{ $vehicle_details['speed'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Created Date</label>
                                    <input value="{{ $vehicle_details['createdDateReadable'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>vehicle Type</label>
                                    <input value="{{ $vehicle_details['vehicleType'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ignition</label>
                                    <input value="{{ $vehicle_details['ignition'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Angle</label>
                                    <input value="{{ $vehicle_details['angle'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Charge On</label>
                                    <input value="{{ $vehicle_details['chargeOn'] }}"  class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea  class="form-control" readonly>{{ $vehicle_details['location'] }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="googleMap" style="width:100%;height:600px;"></div>
                            </div>

                            <div class="col-md-12 ">
                                <div class="float-sm-right">
                                    <a href="{{ url('vehicle') }}" class="btn  bg-gradient-danger btn-sm"><i
                                                class="fa fa-times"></i>&nbsp; Back</a>
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
    function myMap()
    {
      myCenter=new google.maps.LatLng({{ $vehicle_details['latitude'] }}, {{ $vehicle_details['longitude'] }});
      var mapOptions= {
        center:myCenter,
        zoom:16, scrollwheel: false, draggable: false,
        mapTypeId:google.maps.MapTypeId.ROADMAP
      };
      var map=new google.maps.Map(document.getElementById("googleMap"),mapOptions);

      var marker = new google.maps.Marker({
        position: myCenter,
      });
      marker.setMap(map);
    }
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDyLiQj3MnPbOp5SoMewKILkSPHHb0BQYQ&callback=myMap"></script>
@endsection
