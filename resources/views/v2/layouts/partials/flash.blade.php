@if (session('message') || $errors->any())
  <div class="row">
    <div class="col-12">
      @if (session('message'))
        <div class="alert alert-{{ session('message_type') === 'success' ? 'success' : (session('message_type') === 'warning' ? 'warning' : 'danger') }} alert-dismissible fade show" role="alert">
          {{ session('message') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <strong>There are validation errors.</strong>
          <ul class="mt-2 mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif
    </div>
  </div>
@endif
