    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-left">
                <li Class="breadcrumb-item" ><a href="{{ url('/home') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                @foreach($data as $value)
                    <li class="breadcrumb-item @if($value['active'] == 'yes') active @endif">@if($value['active'] == 'no')<a href="{{ $value['url'] }}">{{ $value['name'] }}</a>@else {{ $value['name'] }} @endif</li>
                @endforeach
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
