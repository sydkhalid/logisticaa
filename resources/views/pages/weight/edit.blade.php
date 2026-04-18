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
                <form method="POST" action="{{ url('weight-correction/'.$weight->id) }}" enctype="multipart/form-data">
                    {{ method_field('PUT') }}
                    @csrf
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrNumber">LR Number <span class="text-danger">(Required)</span></label>
                                    <input value="{{ $weight->lrNumber }}" type="number" id="lrNumber" name ="lrNumber" class="form-control" placeholder="lrNumber" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lspId">LspId <span class="text-danger">(Required)</span></label>
                                    <input value="{{ $weight->lspId }}" type="number" id="lspId" name ="lspId" class="form-control" placeholder="lspId" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="actualWeight">ActualWeight <span class="text-danger">(Required)</span></label>
                                    <input value="{{ $weight->correctedWeight }}" type="number" id="actualWeight" name ="actualWeight" class="form-control" placeholder="actualWeight" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="length">Length(volume (in Meters) upto 3 decimals) <span class="text-danger">(Required)</span></label>
                                    <input value="{{ $weight->length }}" type="number" id="length" name ="length" class="form-control" placeholder="length" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="breadth">Breadth(volume (in Meters) upto 3 decimals) <span class="text-danger">(Required)</span></label>
                                    <input value="{{ $weight->breadth }}" type="number" id="breadth" name ="breadth" class="form-control" placeholder="breadth" Required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="height">Height(volume (in Meters) upto 3 decimals) <span class="text-danger">(Required)</span></label>
                                    <input value="{{ $weight->height }}" type="number" id="height" name ="height" class="form-control" placeholder="height" Required>
                                </div>
                            </div>
                            <div class="col-md-12 ">
                                <div class="float-sm-right">
                                    <button type="submit" id="btnSubmit" class="btn  bg-gradient-success btn-sm"><i
                                                class="fa fa-save"></i>&nbsp;Update
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
