@extends('layouts.frontend.index')
@section('content')
<!-- content start -->
<div class="container-fluid p-0 home-content">
    <!-- banner start -->
    <div class="subpage-slide-blue">
        <div class="container">
            <p class="py-3 mb-0">ငွေလွှဲပြီးသွားပါက “ငွေလွှဲပြေစာ မှတဆင့် ငွေလွှဲထားတဲ့ Screen Shoot ကို” ထည့်သွင်း၍ ပေးပို့အတည်ပြုရန် လိုအပ်ပါတယ်။ အကူညီလိုအပ်ပါက 09766033123 သို့ ဆက်သွယ်နိုင်ပါသည်။</p>
        </div>
    </div>
    <!-- banner end -->

    <!-- breadcrumb start -->
    <div class="breadcrumb-container">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="@if($course_breadcrumb) {{ $course_breadcrumb }} @else {{ route('course.list') }} @endif">Course List</a></li>
                <li class="breadcrumb-item"><a href="{{ route('course.view', $course->course_slug) }}">Course</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </div>
    </div>
    <!-- breadcrumb end -->

    <article class="container mt-4">
        <div class="row">
            <div class="col-xl-7 offset-xl-3 col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                <div class="confirm-purchase-section">
                    <h6 class="underline-heading mb-4">Confirm Purchase</h6>

                    <div class="row mb-1">
                        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-3 col-4">
                            <img src="@if(!is_null($course->thumb_image) && Storage::exists($course->thumb_image)){{ asset('storage/'.$course->thumb_image) }}@else{{ asset('backend/assets/images/course_detail_thumb.jpg') }}@endif" width="120" height="90">
                        </div>
                        <div class="col-xl-9 col-lg-9 col-md-9 col-sm-9 col-8">
                            <h6 class="mb-xl-0">{{ $course->course_title }}</h6>
                            <div class="instructor-clist mb-0 mt-1 d-sm-block d-none">
                                <div class="ml-1">
                                    <i class="far fa-bookmark"></i>&nbsp;&nbsp;
                                    <span>Category <b>{{ $course->category->name }}</b></span>
                                </div>
                            </div>
                            <div class="instructor-clist mb-0 mt-1">
                                <div>
                                    <i class="fa fa-chalkboard-teacher"></i>&nbsp;
                                    <span>Created by <b>{{ $course->instructor->first_name.' '.$course->instructor->last_name }}</b></span>
                                </div>
                            </div>
                            @php 
                                $course_price = $course->price == 0 ? 'Free' : config('config.default_currency').$course->price; 
                            @endphp
                            <h6 class="c-price-checkout">{{  $course_price }} Ks &nbsp;<s>{{ $course->strike_out_price ? $course->strike_out_price : '' }} Ks</s></h6>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('payment.form') }}" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <input type="hidden" name="course_title" value="{{ $course->course_title }}">
                        <p>ငွေလွှဲမည့် ဘဏ်ရွေးပါ။</p>
                        <div class="row">
                            <div class="col-2 p-0 text-center">
                                <input id="kbz" type="radio" name="payment_method" value="Kpay">
                                <label for="kbz" class="align-middle">Kpay</label>
                            </div>
                            <div class="col-2 p-0 text-center">
                                <input id="cb" type="radio" name="payment_method" value="CBpay">
                                <label for="cb" class="align-middle">CBpay</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col" id="kpay" style="display:none;">
                                <p class="mb-0">09766033123</p>
                                <img src="{{ asset('frontend/img/kbz.png') }}" alt="Kpay" width="120">
                            </div>
                            <div class="col" id="cbpay" style="display:none;">
                                <p class="mb-0">09766033123</p>
                                <img src="{{ asset('frontend/img/cb.png') }}" alt="CBpay" width="120">
                            </div>
                        </div>

                        <div class="container mt-4 upload-section" id="uploadSection">
                            <div class="upload-area">
                                <p class="text-primary">ငွေလွှဲပြေစာထည့်ရန်</p>
                                <label for="pay_img" class="text-primary">
                                    <i class="fas fa-upload fa-2x"></i>
                                </label>
                                <input type="file" class="d-none" id="pay_img" name="pay_img" required>
                            </div>
                        </div>

                        <div class="uploaded-image d-none" id="uploadedImage">
                            <img id="uploadedImg">
                            <button class="delete-btn" id="deleteBtn"><i class="fas fa-trash-alt"></i></button>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="social-btn mt-3 mb-0 btn-block text-center">Confirm Purchase</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </article>
    <!-- content end -->
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    // Handle payment method selection
    $("input[type='radio']").change(function() {
        if ($(this).val() == "Kpay") {
            $("#kpay").fadeIn();
            $("#cbpay").hide();
        } else {
            $("#cbpay").fadeIn();
            $("#kpay").hide();
        }
    });

    // Handle file upload preview
    $("#pay_img").change(function() {
        readURL(this);
        $("#uploadSection").hide();
        $("#uploadedImage").removeClass("d-none");
    });

    // Handle delete uploaded image
    $("#deleteBtn").click(function(e) {
        e.preventDefault();
        $("#pay_img").val('');
        $("#uploadSection").fadeIn();
        $("#uploadedImage").addClass("d-none");
    });

    // Function to read URL of uploaded image
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#uploadedImg').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
});
</script>
@endsection
