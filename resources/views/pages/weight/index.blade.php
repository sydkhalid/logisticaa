@extends('layouts.app')
@section('content')
    @include('layouts.breadcrumb',['data' => [
        ['name' =>$page_title,'url'=> route('lrtracking.index'),'active' => 'yes'],
    ]])
<section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">{{ $page_title }}</h3>
              <a href="{{ route('weight-correction.create')  }}" class="btn btn-primary btn-sm float-sm-right"><i
                class="fa fa-plus-circle"></i>&nbsp;Add Weight </a>
            </div>

            <!-- /.card-header -->
            <div class="card-body">
                @if(session('message_type'))
                    <div class="alert alert-danger alert-dismissible">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <ul>
                            @if(session('message_type') == 'success')
                                <li>{{ session('message') }}</li>
                            @else
                                <li>{{ session('message') }}</li>
                            @endif
                        </ul>
                    </div>
                @endif
              <table id="user-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>S.No</th>
                    <th>lrNumber</th>
                    <th>Weight</th>
                    <th>Length</th>
                    <th>Breath</th>
                    <th>Height</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($lrtracking as $lr)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $lr->lrNumber }}</td>
                        <td>{{ $lr->correctedWeight }}</td>
                        <td>{{ $lr->length }}</td>
                        <td>{{ $lr->breadth }}</td>
                        <td>{{ $lr->height }}</td>
                        <td>
                            <a class="btn-just-icon" href="{{ route('weight-correction.edit', [$lr->id]) }}" title="Edit Lr Tracking"><i class="fas fa-edit"> Re-Correct Weight</i></a>
                        </td>
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
