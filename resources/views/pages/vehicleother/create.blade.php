@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
    ['name' => "Vehicle",'url'=> route('vehicle.create'),'active' => 'no'],
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
                <div class="alert alert-danger alert-dismissible">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <form method="POST" action="{{ url('vehicleOther') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="vehicleNo">Vehicle Number <span class="text-danger">(Required)</span></label>
                                    <input value="" type="text" id="vehicleNo" name ="vehicleNo" class="form-control" placeholder="Vehicle Number" Required style='text-transform:uppercase'/>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mobileNo">Mobile Number <span class="text-danger">(Required)</span></label>
                                    <input value="91" type="text" id="mobileNo" name ="mobileNo" class="form-control" placeholder="Mobile Number" Required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="simProvider">Sim Provider <span class="text-danger">(Required)</span></label>
                                    <select class="form-control select2" style="width: 100%;" name="simProvider"
                                    id="simProvider" Required>
                                    <option value="AIRTEL">Airtel</option>
                                    <option value="VODAFONE">Vodafone</option>
                                    <option value="JIO">Jio</option>
                                    <option value="IDEA">Idea</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expireDate">Expire Date For Sim  <span class="text-danger">(Required)</span></label>
                                    <input value="" type="datetime-local" id="expireDate" name ="expireDate" class="form-control" placeholder="Expire Date" Required>
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
