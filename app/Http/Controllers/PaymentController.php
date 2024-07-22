<?php  
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Validator, Redirect; 
use App\Models\Course;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Config;
use App\Models\CourseTaken;
use App\Models\Instructor;
use Illuminate\Support\Js;
use SiteHelpers;
use Illuminate\Support\Facades\Storage;
use Image;
use Illuminate\Support\Facades\Input;

class PaymentController extends Controller {

	public function __construct()
	{
		
	}
	
	function getSuccess()
	{

		//get the transaction id from session, so as to update the status and order details
		$transaction = Transaction::find(\Session::get('transaction_id'));

		$course_id = \Session::get('course_id');
		$course = Course::find($course_id);

		//process only if the acknowledgement is success
		if($transaction->amount!=0)
		{
			$save_transaction['id'] = \Session::get('transaction_id');
			$save_transaction['status'] = 'completed';
			$transaction_id = $this->save_transaction($save_transaction);
			// add for taken course by user
			$courseTaken = new CourseTaken;
			$courseTaken->user_id = \Auth::user()->id;
			$courseTaken->course_id = $course_id;
			$courseTaken->save();

			\Session::forget('course_id');
			\Session::forget('transaction_id');

			return view('site/course/success')->with('course', $course)->with('title', 'Course')->with('status', 'success')->with('transId', $transaction_id);
		}else{
			return view('site/course/success')->with('course', $course)->with('status', 'failed')->with('transId', \Session::get('transaction_id'))->with('title', 'Course');
		}
	}

	function paymentForm(Request $request)
	{
		// get all values from form
		$payment_method = $request->input('payment_method');
		$course_id = $request->input('course_id');

		$pay_img =  $request->file('pay_img')->getClientOriginalName();
		$file_name   = $request->file('pay_img');
		$file_name->move(public_path('payment'), $pay_img);
			
		$course = Course::find($course_id);
		$amount = $course->price;
			
		//save the transaction details in DB
		$transaction = new Transaction;
		$transaction->user_id = \Auth::user()->id;
		$transaction->course_id = $course_id;
		$transaction->pay_img = $pay_img;
		$transaction->amount = floatval($amount);
		$transaction->status = 'pending';
		$transaction->payment_method = $payment_method;
		
		$transaction->save();

		\Session::put('transaction_id', $transaction->id);
		\Session::put('course_id', $course_id);
		\Session::save();
		
		if($amount == 0){
			return Redirect::to('payment/failure');
		}else{
			return Redirect::to('payment/success');
		}
	}

	function save_transaction($data)
	{
		//check if the status is completed
		$completed = in_array('completed', $data) ? true : false;
		
		//check if there is transaction id, if so find it or else create a new one
		$transaction = array_key_exists('id', $data) ? Transaction::find($data['id']) : new Transaction;
		//insert all the values in object
		foreach ($data as $key => $value) 
		{
			$transaction->$key = $value;
		}
		$transaction->save();


		//process the invoice generation(get transaction details and save it in invoice table), if the status is completed
		if($completed)
		{
			//save payments
			$this->save_payments($transaction->id);
		}
		return $transaction->id;
	}

	function save_payments($transaction_id)
	{
		//get transaction details
		$transaction = Transaction::find($transaction_id);
		$amount = $transaction->amount;

		//get instructor id for the course id
		$course = Course::find($transaction->course_id);
		$instructor_id = $course->instructor_id;

		//save payment for instructor
		$payment = new Payment();
		$payment->transaction_id = $transaction_id;
		$payment->instructor_id = $instructor_id;
		$payment->course_id = $transaction->course_id;
		$payment->user_id = $transaction->user_id;
		$payment->is_admin = 0;
		$payment->payments_for = 1;
		$payment->payment = $amount;
		$payment->pay_img = $transaction->pay_img;
		$payment->status = 'pending';
		$payment->created_at = time();

		$payment->save();

        //update the total payments
        $instructor = Instructor::find($instructor_id)->increment('total_payments', $amount);
        
		//save payment for instructor
		$payment = new Payment;
		$payment->transaction_id = $transaction_id;
		$payment->instructor_id = 0;
		$payment->course_id = $transaction->course_id;
		$payment->user_id = $transaction->user_id;
		$payment->is_admin = 1;
		$payment->payments_for = 2;
		$payment->payment = $amount;
		$payment->save();
	}

}
