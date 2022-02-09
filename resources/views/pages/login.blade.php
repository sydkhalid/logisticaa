@extends('pages.app')
@section('content')
<!-- /.login-logo -->
<div class="card card-outline card-primary">
   <div class="card-header text-center">
      <a href="{{ URL::to('login')}}" class="h1"><b>Neo</b>Crm</a>
   </div>
   <div class="card-body">
      <form method="POST" action="{{ route('login') }}">
         @csrf
         @if( count($errors) > 0)
         @foreach($errors->all() as $error)
             <div class="alert alert-danger" role="alert">
                 <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                 <span class="sr-only">{{ trans('labels.Error') }}:</span>
                 {{ $error }}
             </div>
         @endforeach
         @endif

         <div class="input-group mb-3">
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"  required autocomplete="email" value="connect@logisticaa.co.in" placeholder="E-Mail Id" autofocus>
            <div class="input-group-append">
               <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
               </div>
            </div>
         </div>
         <div class="input-group mb-3">
            <input id="password" type="password"  value="!Meenakshi1" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
            <div class="input-group-append">
               <div class="input-group-text">
                  <span class="fas fa-lock"></span>
               </div>
            </div>
         </div>
         <div class="row">
            <!-- /.col -->
            <div class="col-4">
               <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </div>
            <!-- /.col -->
         </div>
      </form>
      <p class="mb-1">
         {{-- @if (Route::has('password.request')) --}}
         <a class="btn btn-link" href="#">Forgot password?</a>
         {{-- @endif --}}
      </p>
   </div>
   <!-- /.card-body -->
</div>
<!-- /.card -->
</div>
<!-- /.login-box -->
@endsection
