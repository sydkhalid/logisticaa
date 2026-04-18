@extends('layouts.app')
@section('content')
@include('layouts.breadcrumb',['data' => [
    ['name' => "Lr Tracking",'url'=> route('lrtracking.index'),'active' => 'no'],
    ['name' =>$page_name,'url'=> '','active' => 'yes'],
]])
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form class="form-horizontal"
                        action="{{ route('settings.store') }}" method="POST" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="row">
                                <input type="hidden" name="id" value="{{ $setting['id'] }}">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group bmd-form-group">
                                                <label for="name" class="bmd-label-floating">Name</label>
                                                <input type="text" class="form-control" name="name" value="{{ $setting['name'] }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group bmd-form-group">
                                                <label for="copyright" class="bmd-label-floating">Copy Rights</label>
                                                <input type="text" class="form-control" name="copyright" value="{{ $setting['copyright'] }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group bmd-form-group">
                                                <label for="bocsh_link" class="bmd-label-floating">Bocsh Link</label>
                                                <input type="text" class="form-control" name="bocsh_link" value="{{ $setting['bocsh_link'] }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group bmd-form-group">
                                                <label for="tracing_link" class="bmd-label-floating">Track Link</label>
                                                <input type="text" class="form-control" name="tracing_link" value="{{ $setting['tracing_link'] }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group bmd-form-group">
                                                <label for="address" class="bmd-label-floating">Token</label>
                                                <input type="text" class="form-control" name="address" value="{{ $setting['address'] }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group bmd-form-group">
                                                <label for="access_token" class="bmd-label-floating">flee Access Token</label>
                                                <input type="text" class="form-control" name="access_token" value="{{ $setting['access_token'] }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-round pull-right"> Update
                                <div class="ripple-container">
                                </div>
                            </button>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>
@endsection
