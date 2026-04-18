@extends('layouts.app')
@section('content')
    @include('layouts.breadcrumb',['data' => [
        ['name' =>$page_title,'url'=> route('vehicle.create') ,'active' => 'yes'],
    ]])
<section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">{{ $page_title }}</h3>
              <a href="{{ route('vehicle.create')  }}" class="btn btn-primary btn-sm float-sm-right"><i
                class="fa fa-plus-circle"></i>&nbsp;Add Vehicle </a>
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
              <table id="user-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>No.</th>
                    <th>Vehicle Number</th>
                    <th>Created At</th>
                    <th>Edit </th>
                    <th>Delete</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($vehicles as $vehicle)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $vehicle->vehicleNo }}</td>
                            <td>{{ $vehicle->created_at }}</td>
                            <td>
                                <a class="btn-just-icon" href="{{ route('vehicle.edit', [$vehicle->id]) }}"><i class="fas fa-edit"></i></a>

                                <a class="btn-just-icon" href="{{ url('vehicle/'.$vehicle->id.'') }}"><i class="fas fa-eye"></i></a>
                            </td>
                            <td>
                                <form action="/vehicle/{{ $vehicle->id }}" method="post">
                                    @method('DELETE')
                                    @csrf
                                    <button class="btn btn-default" type="submit"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
              </table>
            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
  </section>

@endsection
@section('script')
<script type="text/javascript">
    $.ajaxSetup({
        headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $('#user-table').DataTable({
        buttons: [
            'copy', 'excel', 'pdf', 'print', 'pageLength'
            ],
        aaSorting: [[1, 'DESC']],
        paging : true,
        info : true,
        responsive :true,
        order: [[0, 'desc']]
    });
</script>
@endsection
