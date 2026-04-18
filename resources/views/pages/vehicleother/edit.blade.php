@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
    ['name' => "Vehicle Edit",'url'=> route('vehicle.create'),'active' => 'no'],
    ['name' =>$page_title,'url'=> '','active' => 'yes'],
]])

<section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">{{ $page_title }}<p style="color: red;"> - Before Resend Same Number Please Stop Sim Track Service.</p></h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                @if (count($errors) > 0)
                <div class="alert alert-danger alert-dismissible">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <form method="POST" action="{{ url('vehicleOther/'.$vehicle_details->id) }}" enctype="multipart/form-data">
                    {{ method_field('PUT') }}
                    @csrf
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="vehicleNo">Vehicle Number <span class="text-danger">(Required)</span></label>
                                    <input value="{{ $vehicle_details['vehicleNo'] }}" type="text" id="vehicleNo" name ="vehicleNo" class="form-control" placeholder="Vehicle Number" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mobileNo">Mobile Number <span class="text-danger">(Required)</span></label>
                                    <input value="{{ $vehicle_details['mobileNo'] }}" type="text" id="mobileNo" name ="mobileNo" class="form-control" placeholder="Mobile Number" Required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="simProvider">Sim Provider <span class="text-danger">(Required)</span></label>
                                    <select class="form-control select2" style="width: 100%;" name="simProvider"
                                    id="simProvider" Required>
                                    <option @if($vehicle_details['simProvider'] == 'AIRTEL') selected @endif value="AIRTEL">Airtel</option>
                                    <option @if($vehicle_details['simProvider'] == 'VODAFONE') selected @endif value="VODAFONE">Vodafone</option>
                                    <option @if($vehicle_details['simProvider'] == 'JIO') selected @endif value="JIO">Jio</option>
                                    <option @if($vehicle_details['simProvider'] == 'IDEA') selected @endif value="IDEA">Idea</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expireDate">Expire Date For Sim  <span class="text-danger">(Required)</span></label>
                                    <input value="{{ $vehicle_details['expireDate'] }}" type="datetime-local" id="expireDate" name ="expireDate" class="form-control" placeholder="Expire Date" Required>
                                </div>
                            </div>
                            <div class="col-md-12 ">
                                <div class="float-sm-right">
                                    <button type="submit" id="btnSubmit" class="btn  bg-gradient-success btn-sm"><i
                                                class="fa fa-save"></i>&nbsp;Save
                                    </button>
                                    <a href="{{ url('vehicleOther') }}" class="btn  bg-gradient-danger btn-sm"><i
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
