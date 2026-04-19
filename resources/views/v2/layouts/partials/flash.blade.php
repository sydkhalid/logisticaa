@if (session('message') || $errors->any())
  <div class="row">
    <div class="col-12">
      @if (session('message'))
        <div
          class="v2-flash d-none"
          data-type="{{ session('message_type') }}"
          data-title="{{ session('message_type') === 'success' ? 'Success' : (session('message_type') === 'warning' ? 'Warning' : 'Error') }}"
          data-message="{{ session('message') }}">
        </div>
        <noscript>
          <div class="alert alert-{{ session('message_type') === 'success' ? 'success' : (session('message_type') === 'warning' ? 'warning' : 'danger') }} alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        </noscript>
      @endif

      @if ($errors->any())
        <div
          class="v2-flash d-none"
          data-type="danger"
          data-title="Validation Errors"
          data-messages="{{ e(json_encode($errors->all())) }}">
        </div>
        <noscript>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>There are validation errors.</strong>
            <ul class="mt-2 mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        </noscript>
      @endif
    </div>
  </div>
@endif
