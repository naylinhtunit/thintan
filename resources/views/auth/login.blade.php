@extends('layouts.frontend.index')

@section('content')
<!-- content start -->
<div class="container-fluid p-0 home-content container-top-border">
    <!-- account block start -->
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Login your account') }}</div>
                    <div class="card-body">
                        <form id="loginForm" method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="form-group">
                                <label for="email">{{ __('Email ID') }}</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password">{{ __('Password') }}</label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="remember">
                                            {{ __('Remember Me') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block">
                                    {{ __('Login') }}
                                </button>
                            </div>

                            <div class="form-group text-center">
                                <div class="hr-container">
                                    <hr class="hr-inline" align="left">
                                    <span class="hr-text">or</span>
                                    <hr class="hr-inline" align="right">
                                </div>
                            </div>

                            <div class="form-group">
                                <a href="{{ url('login/google') }}" class="btn btn-danger btn-block">
                                    <i class="fab fa-google-plus-g"></i> {{ __('Continue with Google') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- account block end -->
</div>
<!-- content end -->
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    $("#loginForm").validate({
        rules: {
            email: {
                required: true,
                email: true
            },
            password: {
                required: true
            }
        },
        messages: {
            email: {
                required: 'The email field is required.',
                email: 'The email must be a valid email address.'
            },
            password: {
                required: 'The password field is required.'
            }
        }
    });
});
</script>
@endsection
