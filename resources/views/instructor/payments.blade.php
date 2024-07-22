@extends('layouts.backend.index')
@section('content')
<style type="text/css">
    .total-credit h5{
        color: #76838f;
        display: contents;
        font-weight: 500;
    }
    .total-credit .badge
    {
        padding: .4rem .8rem;
        font-size: 1rem;
    }
</style>
<div class="page-header">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('instructor.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Payments</li>
  </ol>
</div>
{{-- <div class="message"></div> --}}
<div class="page-content">

<div class="panel">
        <div class="panel-heading">
            <div class="panel-title">
                <div class="panel-actions total-credit">
                    <h5>Total PAYMENTS:</h5> 
                    <span class="badge badge-danger">$ {{ Auth::user()->instructor->total_payments }}</span>
                </div>
            </div>
        </div>
        
        <div class="panel-body">
          <table class="table table-hover table-striped w-full">
            <thead>
              <tr>
                <th>Sl.no</th>
                <th>User</th>
                <th>Category</th>
                <th>Course</th>
                <th>Price</th>
                <th>Pay Method</th>
                <th>Status</th>
                <th>Starded on</th>
              </tr>
            </thead>
            <tbody>
              @foreach($payments as $payment)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $payment->user->first_name.' '.$payment->user->last_name }}</td>
                <td>{{ $payment->course->category->name }}</td>
                <td>{{ $payment->course->course_title }}</td>
                <td>{{ $payment->payment }}</td>
                <td><img src="{{ asset('payment/' . $payment->pay_img) }}" width="80"></td>
                <td>
                  <input type="checkbox" class="toggle-class" data-id="{{$payment->id}}" data-toggle="toggle" data-on="Completed" data-off="Pending" {{ $payment->status == 'completed' ? 'checked' : '' }}>
                </td>
                <td>{{ $payment->created_at->format('d/m/Y h:i A') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
          
          <div class="float-right">
            {{ $payments->links() }}
          </div>
          
          
        </div>
      </div>
      <!-- End Panel Basic -->
</div>

@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function()
{   
  $('#toggle-two').bootstrapToggle({
    on: 'Enabled',
    off: 'Disabled'
  });
  $('.toggle-class').on('change', function(){
    var status = $(this).prop('checked') === true ? 'completed' : 'pending';
    var user_id = $(this).data('id');
    $.ajax({
      type: 'GET',
      dataType: 'json',
      url: '{{route("change.status")}}',
      data: {'status': status, 'user_id': user_id},
      // success:function(data){
      //   $('.message').html('<p class="alert alert-danger">'+data.success+'</p>')
      // }
    });
    toastr.success("Status Change Successfully");
  });
});
</script>
@endsection