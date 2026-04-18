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
                <form method="POST" action="{{ url('lrtracking/'.$lrtracking->id) }}" enctype="multipart/form-data">
                    {{ method_field('PUT') }}
                    @csrf
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="lspId">vehicle Number</label>
                                    <input value="{{ $lrtracking['vehicleNo'] }}" type="text" id="vehicleNo" name ="vehicleNo" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrStatus">Lr Status <span class="text-danger">(Required)</span></label>
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
                                    <label for="actualDeliveredDate">Actual Delivered Date <span class="text-danger">(If Shipment Delivered)</span></label>
                                    <input value="" type="datetime-local" id="actualDeliveredDate" name ="actualDeliveredDate" class="form-control" placeholder="actualDeliveredDate" >
                                </div>
                            </div>

                            <div class="col-md-12 ">
                                <div class="float-sm-right">
                                    <button type="submit" id="btnSubmit" class="btn  bg-gradient-success btn-sm"><i
                                                class="fa fa-save"></i>&nbsp;Save
                                    </button>
                                    <a href="{{ route('lrtracking.index') }}" class="btn  bg-gradient-danger btn-sm"><i
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
