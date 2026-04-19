@extends('v2.layouts.auth')

@section('styles')
  <style>
    .auth-panel {
      margin: 0 auto;
      max-width: 30rem;
    }

    .auth .auth-form-light {
      padding: 3.25rem 2.4rem !important;
    }

    .auth-wordmark {
      align-items: center;
      display: flex;
      flex-direction: column;
      gap: 0.55rem;
      margin: 0 auto 1.35rem;
      text-align: center;
      width: 100%;
    }

    .auth-wordmark__top {
      align-items: center;
      display: inline-flex;
      gap: 1rem;
      justify-content: center;
    }

    .auth-wordmark__mark {
      background: linear-gradient(180deg, #0f766e, #f59e0b);
      border-radius: 999px;
      box-shadow: 0 14px 28px rgba(15, 118, 110, 0.22);
      display: inline-block;
      height: 3.15rem;
      width: 1rem;
    }

    .auth-wordmark__text {
      color: #162033;
      font-size: 2.2rem;
      font-weight: 900;
      letter-spacing: 0.14em;
      line-height: 1;
      text-transform: uppercase;
    }

    .auth-copy {
      color: #475569;
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 1.9rem;
      text-align: center;
    }

    .auth-field {
      margin-bottom: 1.2rem;
    }

    .auth-input {
      align-items: center;
      background: rgba(255, 255, 255, 0.96);
      border: 1px solid rgba(148, 163, 184, 0.24);
      border-radius: 1.15rem;
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
      display: flex;
      min-height: 4.15rem;
      overflow: hidden;
      position: relative;
      transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .auth-input:focus-within {
      border-color: rgba(15, 118, 110, 0.4);
      box-shadow: 0 0 0 0.24rem rgba(15, 118, 110, 0.1);
      transform: translateY(-1px);
    }

    .auth-input__icon,
    .auth-input__toggle {
      align-items: center;
      color: #64748b;
      display: inline-flex;
      justify-content: center;
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 2;
    }

    .auth-input__icon {
      left: 1rem;
      pointer-events: none;
      width: 1.4rem;
    }

    .auth-input__toggle {
      background: transparent;
      border: 0;
      border-radius: 999px;
      cursor: pointer;
      height: 2.2rem;
      padding: 0;
      right: 0.85rem;
      transition: background-color 0.18s ease, color 0.18s ease;
      width: 2.2rem;
    }

    .auth-input__toggle:hover,
    .auth-input__toggle:focus {
      background: rgba(15, 118, 110, 0.08);
      color: #0f766e;
      outline: none;
    }

    .auth-input__icon i,
    .auth-input__toggle i {
      font-size: 1.15rem;
    }

    .auth-input .form-control {
      background: transparent;
      border: 0;
      min-height: 4.15rem;
      padding: 1rem 3.5rem 1rem 3.5rem;
    }

    .auth-input .form-control::placeholder {
      color: #94a3b8;
      font-weight: 500;
    }

    .auth-input .form-control,
    .auth-input .form-control:focus,
    .auth-input .form-control.is-invalid,
    .auth-input .form-control.is-invalid:focus {
      background: transparent;
      border: 0;
      box-shadow: none;
    }

    .auth-input.is-invalid {
      border-color: rgba(239, 68, 68, 0.82);
      box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.08);
    }

    .auth-feedback {
      color: #dc2626;
      display: none;
      font-size: 0.8rem;
      font-weight: 700;
      margin-top: 0.6rem;
      padding-left: 0.35rem;
    }

    .auth-feedback.d-block {
      display: block;
    }

    .auth-submit {
      align-items: center;
      border-radius: 1.15rem;
      display: inline-flex;
      gap: 0.75rem;
      justify-content: center;
      min-height: 4.1rem;
      transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
      width: 100%;
    }

    .auth-submit:hover {
      transform: translateY(-1px);
    }

    .auth-submit.is-submitting {
      box-shadow: 0 18px 32px rgba(15, 118, 110, 0.22);
      opacity: 0.94;
      transform: translateY(-1px) scale(0.99);
    }

    .auth-submit__spinner {
      animation: auth-spin 0.7s linear infinite;
      border: 2px solid rgba(255, 255, 255, 0.35);
      border-radius: 999px;
      border-top-color: #ffffff;
      display: none;
      height: 1rem;
      width: 1rem;
    }

    .auth-submit.is-submitting .auth-submit__spinner {
      display: inline-block;
    }

    .auth-submit.is-submitting .auth-submit__label {
      letter-spacing: 0.04em;
    }

    @keyframes auth-spin {
      to {
        transform: rotate(360deg);
      }
    }

    @media (max-width: 575.98px) {
      .auth .auth-form-light {
        padding: 2.5rem 1.3rem !important;
      }

      .auth-wordmark__top {
        gap: 0.8rem;
      }

      .auth-wordmark__text {
        font-size: 1.7rem;
        letter-spacing: 0.12em;
      }
    }
  </style>
@endsection

@section('content')
  <div class="row w-100 mx-0">
    <div class="col-lg-5 col-xl-4 mx-auto">
      <div class="auth-form-light text-left py-5 px-4 px-sm-5">
        <div class="auth-panel">
          <div class="auth-wordmark">
            <div class="auth-wordmark__top">
              <span class="auth-wordmark__mark" aria-hidden="true"></span>
              <span class="auth-wordmark__text">Logisticaa</span>
            </div>
          </div>
          <p class="auth-copy">Sign in to continue.</p>

          <form class="pt-2" method="POST" action="{{ route('v2.login.submit') }}" novalidate data-login-form>
            @csrf
            <div class="auth-field">
              <div class="auth-input @error('email') is-invalid @enderror">
                <span class="auth-input__icon" aria-hidden="true">
                  <i class="mdi mdi-email-outline"></i>
                </span>
                <input id="login-email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="Email address" autocomplete="email" required>
              </div>
              <div class="auth-feedback" data-feedback-for="login-email"></div>
              @error('email')
                <div class="auth-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            <div class="auth-field">
              <div class="auth-input auth-input--password @error('password') is-invalid @enderror">
                <span class="auth-input__icon" aria-hidden="true">
                  <i class="mdi mdi-lock-outline"></i>
                </span>
                <input id="login-password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" placeholder="Password" autocomplete="current-password" required>
                <button
                  type="button"
                  class="auth-input__toggle"
                  data-password-toggle
                  data-target="#login-password"
                  aria-label="Show password"
                  aria-pressed="false"
                >
                  <i class="mdi mdi-eye-outline"></i>
                </button>
              </div>
              <div class="auth-feedback" data-feedback-for="login-password"></div>
              @error('password')
                <div class="auth-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            <div class="mt-4 d-grid gap-2">
              <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn auth-submit" data-submit-button>
                <span class="auth-submit__spinner" aria-hidden="true"></span>
                <span class="auth-submit__label">Sign In</span>
              </button>
            </div>

            @error('login')
              <div class="auth-feedback d-block text-center mt-3">{{ $message }}</div>
            @enderror
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    (function () {
      var toggle = document.querySelector('[data-password-toggle]');
      if (toggle) {
        var target = document.querySelector(toggle.getAttribute('data-target'));
        var icon = toggle.querySelector('i');
        if (target && icon) {
          toggle.addEventListener('click', function () {
            var isPassword = target.getAttribute('type') === 'password';
            target.setAttribute('type', isPassword ? 'text' : 'password');
            toggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            icon.className = isPassword ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline';
          });
        }
      }

      var form = document.querySelector('[data-login-form]');
      if (!form) {
        return;
      }

      var submitButton = form.querySelector('[data-submit-button]');
      var submitLabel = submitButton ? submitButton.querySelector('.auth-submit__label') : null;
      var fields = [
        {
          input: form.querySelector('#login-email'),
          feedback: form.querySelector('[data-feedback-for="login-email"]'),
          message: function (input) {
            if (!input.value.trim()) {
              return 'Email address is required.';
            }

            if (input.validity.typeMismatch) {
              return 'Enter a valid email address.';
            }

            return '';
          }
        },
        {
          input: form.querySelector('#login-password'),
          feedback: form.querySelector('[data-feedback-for="login-password"]'),
          message: function (input) {
            if (!input.value.trim()) {
              return 'Password is required.';
            }

            return '';
          }
        }
      ];

      function setFieldState(field, message) {
        if (!field.input || !field.feedback) {
          return;
        }

        var wrapper = field.input.closest('.auth-input');
        field.input.classList.toggle('is-invalid', !!message);
        if (wrapper) {
          wrapper.classList.toggle('is-invalid', !!message);
        }
        field.feedback.textContent = message;
        field.feedback.style.display = message ? 'block' : 'none';
      }

      function validateField(field) {
        if (!field.input) {
          return true;
        }

        var message = field.message(field.input);
        setFieldState(field, message);
        return !message;
      }

      fields.forEach(function (field) {
        if (!field.input) {
          return;
        }

        field.input.addEventListener('blur', function () {
          validateField(field);
        });

        field.input.addEventListener('input', function () {
          if (field.input.classList.contains('is-invalid')) {
            validateField(field);
          }
        });
      });

      form.addEventListener('submit', function (event) {
        var isValid = true;

        fields.forEach(function (field) {
          if (!validateField(field)) {
            isValid = false;
          }
        });

        if (!isValid) {
          event.preventDefault();
          return;
        }

        if (submitButton) {
          submitButton.classList.add('is-submitting');
          submitButton.disabled = true;
        }

        if (submitLabel) {
          submitLabel.textContent = 'Signing In';
        }
      });
    }());
  </script>
@endsection
