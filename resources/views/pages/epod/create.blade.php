@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
    ['name' => "Lr Tracking",'url'=> url('epod'),'active' => 'no'],
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
                <form method="POST" action="{{ route('epod_upload') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lspId">LspId</label>
                                    <input value="{{ config('integrations.travis.default_lsp_id') }}" type="text" id="lspId" name ="lspId" class="form-control" placeholder="lspId" {{ config('integrations.travis.default_lsp_id') ? 'readonly' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrNumber">Lr Number</label>
                                    <input value="" type="text" id="lrNumber" name ="lrNumber" class="form-control" placeholder="lrNumber">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lrNumber">Upload Epod(jpg,jpeg,pdf)</label>
                                    <input type="File" id="epod" name ="epod" class="form-control" accept=".jpg, .jpeg , .pdf" />
                                </div>
                            </div>

                            <div class="col-md-12 ">
                                <div class="float-sm-right">
                                    <button type="submit" id="btnSubmit" class="btn  bg-gradient-success btn-sm"><i
                                                class="fa fa-save"></i>&nbsp;Save
                                    </button>
                                    <a href="{{ url('epod') }}" class="btn  bg-gradient-danger btn-sm"><i
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
