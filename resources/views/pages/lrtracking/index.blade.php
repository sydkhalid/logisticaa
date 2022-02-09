@extends('layouts.app')
@section('content')
    @include('layouts.breadcrumb',['data' => [
        ['name' =>$page_title,'url'=> route('lrtracking'),'active' => 'yes'],
    ]])
<section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">{{ $page_title }}</h3>
              <a href="{{ url('lrtracking/add_lrtracking')  }}" class="btn btn-primary btn-sm float-sm-right"><i
                class="fa fa-plus-circle"></i>&nbsp;Add Lr Tracking </a>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="user-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>lspId</th>
                    <th>lrNumber</th>
                    <th>lrDate</th>
                    <th>lrStatus</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($lrtracking as $lr)
                    <tr>
                        <td>{{ $lr->lspId }}</td>
                        <td>{{ $lr->lrNumber }}</td>
                        <td>{{ $lr->lrDate }}</td>
                        <td>{{ $lr->lrStatus }}</td>
                        <td>
                            @if($lr->status == 1)
                            <a href="#" class="btn btn-success btn-sm">&nbsp;Record Inserted</a>
                            @else
                            <a href="#" class="btn btn-danger btn-sm">&nbsp;Record Duplicated</a>
                            @endif
                        </td>
                        <td>
                            <a class="btn btn-link btn-warning btn-just-icon edit" href="{{ url('tracking_show/'.$lr->id.'') }}"><i class="fas fa-eye"></i></a>
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
