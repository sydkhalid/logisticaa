@extends('layout.master-mini')

@section('content')
<div class="content-wrapper d-flex align-items-center justify-content-center auth theme-one" style="background-image: url({{ url('assets/images/auth/login_1.jpg') }}); background-size: cover;">
  <div class="row w-100">
    <div class="col-lg-4 mx-auto">
      <div class="auto-form-wrapper">
      <h2 class="text-center mb-4">Register</h2>
        <form action="{{ url('lrtracking') }}">
        <div class="form-group">
            <div class="input-group">
                <input type="text" id="name" name ="name" class="form-control" placeholder="Name">
                <div class="input-group-append">
                <span class="input-group-text">
                    <i class="mdi mdi-check-circle-outline"></i>
                </span>
                </div>
            </div>
            </div>
            <div class="form-group">
                <div class="input-group">
                    <input type="email" id="email" name ="email" class="form-control" placeholder="E-Mail Id"autocomplete="false" >
                    <div class="input-group-append">
                    <span class="input-group-text">
                        <i class="mdi mdi-check-circle-outline"></i>
                    </span>
                    </div>
                </div>
            </div>
          <div class="form-group">
            <div class="input-group">
              <input type="password" id="password" name ="password" class="form-control" placeholder="Password">
              <div class="input-group-append">
                <span class="input-group-text">
                  <i class="mdi mdi-check-circle-outline"></i>
                </span>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <input type="text" id="lspId" name ="lspId"  class="form-control" placeholder="lspId">
              <div class="input-group-append">
                <span class="input-group-text">
                  <i class="mdi mdi-check-circle-outline"></i>
                </span>
              </div>
            </div>
          </div>
          <div class="form-group">
            <button class="btn btn-primary submit-btn btn-block">Register</button>
          </div>
          <div class="text-block text-center my-3">
            <span class="text-small font-weight-semibold">Already have and account ?</span>
            <a href="{{ url('/') }}" class="text-black text-small">Login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
