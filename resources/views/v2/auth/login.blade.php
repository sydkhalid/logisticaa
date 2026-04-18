@extends('v2.layouts.auth')

@section('content')
  <div class="row w-100 mx-0">
    <div class="col-lg-4 mx-auto">
      <div class="auth-form-light text-left py-5 px-4 px-sm-5">
        <div class="brand-logo mb-4">
          <img src="{{ asset('v2/assets/images/logo.svg') }}" alt="logo">
        </div>
        <h4>{{ $appName }}</h4>
        <h6 class="font-weight-light mb-4">Sign in to open the new v2 workspace.</h6>

        @if (session('message'))
          <div class="alert alert-{{ session('message_type') === 'success' ? 'success' : 'danger' }}">
            {{ session('message') }}
          </div>
        @endif

        @if ($errors->has('login'))
          <div class="alert alert-danger">{{ $errors->first('login') }}</div>
        @endif

        <form class="pt-3" method="POST" action="{{ route('v2.login.submit') }}">
          @csrf
          <div class="form-group">
            <input type="email" class="form-control form-control-lg" name="email" value="{{ old('email') }}" placeholder="Email address" required>
          </div>
          <div class="form-group">
            <input type="password" class="form-control form-control-lg" name="password" placeholder="Password" required>
          </div>
          <div class="mt-3 d-grid gap-2">
            <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">
              Sign In To v2
            </button>
          </div>
          <div class="mt-4 small text-muted">
            Existing Laravel models and database remain shared. Only the web/controller/view layer is new.
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
