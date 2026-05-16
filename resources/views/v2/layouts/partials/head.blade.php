<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $pageTitle ?? $appName }} | {{ $appName }}</title>

  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/vendors/feather/feather.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/vendors/ti-icons/css/themify-icons.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/vendors/css/vendor.bundle.base.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/vendors/font-awesome/css/font-awesome.min.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::publicAsset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::publicAsset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/vendors/select2/select2.min.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/vendors/sweetalert2/sweetalert2.min.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\V2Routing::asset('assets/css/v2.css') }}">
  <link rel="shortcut icon" href="{{ \App\Support\V2Routing::asset('assets/images/favicon.png') }}">
  @yield('styles')
</head>
