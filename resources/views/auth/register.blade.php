@extends('layouts.frontend.index')

@section('content')
<!-- content start -->
<div class="container-fluid p-0 home-content container-top-border">
    <!-- account block start -->
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Create an account') }}</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('register') }}" id="registerForm">
                            @csrf
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label for="first_name">{{ __('First Name') }}</label>
                                    <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required autofocus>
                                    @error('first_name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name">{{ __('Last Name') }}</label>
                                    <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required>
                                    @error('last_name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">{{ __('Email ID') }}</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
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

                            <div class="form-group">
                                <label for="password-confirm">{{ __('Confirm Password') }}</label>
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                                @error('password_confirmation')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="offer" name="offer" {{ old('offer') ? 'checked' : '' }}>
                                <label class="form-check-label" for="offer">{{ __('Receive relevant offers & communications') }}</label>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block">{{ __('Register') }}</button>
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
    $("#registerForm").validate({
        rules: {
            first_name: {
                required: true
            },
            last_name: {
                required: true
            },
            email: {
                required: true,
                email: true,
                remote: "{{ url('checkUserEmailExists') }}"
            },
            password: {
                required: true,
                minlength: 6
            },
            password_confirmation: {
                required: true,
                equalTo: '#password'
            }
        },
        messages: {
            first_name: {
                required: 'The first name field is required.'
            },
            last_name: {
                required: 'The last name field is required.'
            },
            email: {
                required: 'The email field is required.',
                email: 'The email must be a valid email address.',
                remote: 'The email has already been taken.'
            },
            password: {
                required: 'The password field is required.',
                minlength: 'The password must be at least 6 characters.'
            },
            password_confirmation: {
                required: 'The password confirmation field is required.',
                equalTo: 'The password confirmation does not match.'
            }
        }
    });
});
</script>
@endsection
