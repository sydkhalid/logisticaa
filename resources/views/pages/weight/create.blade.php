@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
    ['name' => "Weight Correction",'url'=> route('weight-correction.index'),'active' => 'no'],
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
                <form method="POST" action="{{ route('weight-correction.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lrNumber"> LR Number</label> 
                                    <a href="#" id="fetchlr" class="btn  bg-gradient-success btn-sm"><i class="fa fa-save"></i>&nbsp;Fetch</a>

                                    <input value="" type="number" id="lrNumber" name ="lrNumber" class="form-control" placeholder="lrNumber">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lspId">LspId <span class="text-danger">(Required)</span></label>
                                    <input value="{{ config('integrations.travis.default_lsp_id') }}" type="number" id="lspId" name ="lspId" class="form-control" placeholder="lspId" {{ config('integrations.travis.default_lsp_id') ? 'readonly' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="actualWeight">ActualWeight <span class="text-danger">(Required)</span></label>
                                    <input value="" type="number" id="actualWeight" name ="actualWeight" class="form-control" placeholder="actualWeight" Required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="length">Length(volume (in Meters) upto 3 decimals) <span class="text-danger">(Required)</span></label>
                                    <input value="" type="number" id="length" name ="length" class="form-control" placeholder="length" Required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="breadth">Breadth(volume (in Meters) upto 3 decimals) <span class="text-danger">(Required)</span></label>
                                    <input value="" type="number" id="breadth" name ="breadth" class="form-control" placeholder="breadth" Required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="height">Height(volume (in Meters) upto 3 decimals) <span class="text-danger">(Required)</span></label>
                                    <input value="" type="number" id="height" name ="height" class="form-control" placeholder="height" Required>
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
<script type="text/javascript">
        $("#length").attr('readonly','readonly');
        $("#breadth").attr('readonly','readonly');
        $("#height").attr('readonly','readonly');
        $("#actualWeight").attr('readonly','readonly');
       $("#fetchlr").on('click', function (e) {
               var value = $('#lrNumber').val();
                $.ajax({
                    type: "POST",
                    url: '{{url('fetchlr')}}',
                    data: {value: value},
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    dataType: "json",
                    success: function (data) {
                        console.log(data);
                        if(data){
                           $("#actualWeight").val(data.actualWeight).removeAttr('readonly');
                           $("#length").val(data.length).removeAttr('readonly'); 
                           $("#breadth").val(data.breadth).removeAttr('readonly');
                           $("#height").val(data.height).removeAttr('readonly'); 
                        }else{
                           Swal.fire({
                            position: 'center',
                            icon: 'warning',
                            title: 'Please Enter Correct LR Number',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        }
                    },
                      error: function(xhr, status, error) {
                            // $('.loginError').show();
                            // $("#vehicleNo").val('');
                            // $(".loginError").delay(10000).hide(400);
                        }
                });
        });
        $(function () {
            $(".date").datepicker({
                yearRange: "-100:+200",
                showButtonPanel: true,
                changeMonth: true,
                changeYear: true,
                dateFormat: "YY-mm-dd H:M:S",
                showAnim: "slideDown"
            })
        });
</script>
@endsection
