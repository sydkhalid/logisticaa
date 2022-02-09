@extends('layouts.app')
@section('content')
    @include('layouts.breadcrumb',['data' => [
        ['name' =>$page_title,'url'=> route('add_epod'),'active' => 'yes'],
    ]])
<section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">{{ $page_title }}</h3>
              <a href="{{ url('epod/add_epod')  }}" class="btn btn-primary btn-sm float-sm-right"><i
                class="fa fa-plus-circle"></i>&nbsp;Add Epod </a>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="user-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Lsp Id</th>
                    <th>Lr Number</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($epod as $epods)
                        <td>{{ $epods->lspId }}</td>
                        <td>{{ $epods->lrNumber }}</td>
                        <td>{{ $epods->status }}</td>
                        <td>{{ $epods->created_at }}</td>
                        <td>{{ $epods->status }}</td>
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
