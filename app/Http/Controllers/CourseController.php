<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Category;
use App\Models\CourseRating;
use App\Models\InstructionLevel;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Storage;
use Image;
use SiteHelpers;
use Crypt;
use App\Library\VideoHelpers;
use App\Library\thintanHelpers;
use URL;
use App\Models\CourseVideos;
use App\Models\CourseFiles;
use App\Models\Payment;
use Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->model = new Course();
    }

    public function myCourses(Request $request)
    {
        $user_id = Auth::user()->id;
        $courses = DB::table('courses')
                    ->select('courses.*', 'instructors.first_name', 'instructors.last_name')
                    ->join('instructors', 'instructors.id', '=', 'courses.instructor_id')
                    ->join('course_taken', 'course_taken.course_id', '=', 'courses.id')
                    ->where('course_taken.user_id',$user_id)->get();
        
        return view('site.course.my-courses', compact('courses'));
    }

    public function courseRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $rating_id = $request->input('rating_id');
        
        if($rating_id) {
            $rating = CourseRating::find($rating_id);
            $success_message = 'Your review has been updated successfully';
        } else {
            $rating = new CourseRating();
            $success_message = 'Your review has been added successfully';
        }
        
        $rating->user_id = Auth::user()->id;
        $rating->course_id = $request->input('course_id');
        $rating->rating = number_format($request->input('rating'), 1);
        $rating->comments = $request->input('comments');
        $rating->save();

        return redirect()->back()->with('success', $success_message);
    }

    public function deleteRating(Request $request, $rating_id)
    {
        CourseRating::where('id', $rating_id)->delete();
        return redirect()->back()->with('success', 'Your rating has been deleted successfully');
    }

    public function courseView(Request $request, $course_slug = null)
    {
        $course_breadcrumb = Session::get('course_breadcrumb');
        $course = Course::where('course_slug', $course_slug)->firstOrFail();

        $curriculum = $this->model->getcurriculum($course->id, $course_slug);

        $curriculum_sections = $curriculum['sections'];
        $lectures_count = $curriculum['lectures_count'];
        $videos_count = $curriculum['videos_count'];
        $is_curriculum = $curriculum['is_curriculum'];
        $video = null;
        if ($course->course_video) {
            $video = $this->model->getvideoinfoFirst($course->course_video);
        }
        
        return view('site.course.view', compact('course', 'curriculum_sections', 'lectures_count', 'videos_count', 'video', 'course_breadcrumb', 'is_curriculum'));
    }

    public function courseLearn(Request $request, $course_slug = null)
    {
        $payments = Payment::all();
        $course_breadcrumb = Session::get('course_breadcrumb');
        $course = Course::where('course_slug', $course_slug)->firstOrFail();

        $students_count = $this->model->students_count($course->id);
        $curriculum = $this->model->getcurriculum($course->id);
        $curriculum_sections = $curriculum['sections'];
        $lectures_count = $curriculum['lectures_count'];
        $videos_count = $curriculum['videos_count'];
        $is_curriculum = $curriculum['is_curriculum'];
        $video = null;
        if ($course->course_video) {
            $video = $this->model->getvideoinfoFirst($course->course_video);
        }
        $course_rating = CourseRating::where('course_id', $course->id)->where('user_id', Auth::user()->id)->first();
        if (!$course_rating) {
            $course_rating = new CourseRating(); // replace with proper initialization if necessary
        }
        return view('site.course.learn', compact('payments', 'course', 'curriculum_sections', 'lectures_count', 'videos_count', 'video', 'course_breadcrumb', 'is_curriculum', 'course_rating', 'students_count'));
    }

    public function updateLectureStatus($course_id = '', $lecture_id = '', $status = '')
    {
        if ($course_id && $lecture_id) {
            $this->model->updateLectureStatus($course_id, $lecture_id, $status);
        }
    }

    public function getDownloadResource($resource_id, $slug)
    {
        $file_details = DB::table('course_files')->where('id', $resource_id)->first();
        $course = DB::table('courses')->where('course_slug', $slug)->first();
        
        $file = public_path('storage/course/' . $course->id . '/' . $file_details->file_name . '.' . $file_details->file_extension);
        $headers = array(
            'Content-Type: application/pdf',
        );

        return Response::download($file, $file_details->file_title, $headers);
    }

    public function readPDF($file_id)
    {
        $file_id = SiteHelpers::encrypt_decrypt($file_id, 'd');
        $file_details = $this->model->getFileDetails($file_id);
        if ($file_details) {
            $file = Storage::url('course/' . $file_details->course_id . '/' . $file_details->file_name . '.' . $file_details->file_extension);

            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename=document.pdf');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            @readfile($file);
        }
    }

    public function courseEnrollAPI(Request $request, $course_slug = null, $lecture_slug = '', $is_sidebar = 'true')
    {
        $course = Course::where('course_slug', $course_slug)->first();
        $lecture_id = SiteHelpers::encrypt_decrypt($lecture_slug, 'd');

        if ($is_sidebar == 'true') {
            $curriculum = $this->model->getcurriculumArray($course->id, $course_slug);
        }
        $curriculum['lecture_details'] = $this->model->getlecturedetails($lecture_id);

        $lectures_all = $this->model->getalllecture($course->id);
        $next = $prev = false;
        if (count($lectures_all) > 0) {
            for ($lec = 0; $lec < count($lectures_all); $lec++) {
                if ($lectures_all[$lec]->lecture_quiz_id == $lecture_id) {
                    if ($lec - 1 >= 0) {
                        $prev = $lectures_all[$lec - 1]->lecture_quiz_id;
                    } else {
                        $prev = false;
                    }

                    if ($lec + 1 < count($lectures_all)) {
                        $next = $lectures_all[$lec + 1]->lecture_quiz_id;
                    } else {
                        $next = false;
                    }

                    break;
                }
            }
        }
        if ($this->model->getCoursecompletedStatus($lecture_id)) {
            $curriculum['lecture_details']->completion_status = true;
        } else {
            $curriculum['lecture_details']->completion_status = false;
        }
        
        $curriculum['lecture_details']->media = SiteHelpers::encrypt_decrypt($curriculum['lecture_details']->media);
        $curriculum['lecture_details']->next = SiteHelpers::encrypt_decrypt($next);
        $curriculum['lecture_details']->prev = SiteHelpers::encrypt_decrypt($prev);
        
        $curriculum['lecture_details']->resources = $this->model->getResources($curriculum['lecture_details']->resources);

        return response()->json($curriculum);
    }

    public function courseList(Request $request, $course_slug = null)
    {
        $paginate_count = 9;
        $categories = Category::where('is_active', 1)->get();
        $instruction_levels = InstructionLevel::get();

        $category_search = $request->input('category_id');
        $instruction_level_id = $request->input('instruction_level_id');
        $prices = $request->input('price_id');
        $sort_price = $request->input('sort_price');
        $keyword = $request->input('keyword');
        
        $query = DB::table('courses')
                    ->select('courses.*', 'instructors.first_name', 'instructors.last_name')
                    ->selectRaw('AVG(course_ratings.rating) AS average_rating')
                    ->leftJoin('course_ratings', 'course_ratings.course_id', '=', 'courses.id')
                    ->join('instructors', 'instructors.id', '=', 'courses.instructor_id')
                    ->where('courses.is_active', 1);
        
        if ($category_search) {
            $query->whereIn('courses.category_id', $category_search);
        }

        if ($keyword) {
            $query->where('courses.course_title', 'LIKE', '%' . $keyword . '%');
        }

        if ($instruction_level_id) {
            $query->whereIn('courses.instruction_level_id', $instruction_level_id);
        }
        
        if ($prices) {
            $price_count = count($prices);
            $is_greater_500 = false;
            foreach ($prices as $p => $price) {
                $p++;
                $price_split = explode('-', $price);
                
                if ($price_count == 1) {
                    $from = $price_split[0];
                    if ($price == 500) {
                        $is_greater_500 = true;
                    } else {
                        $to = $price_split[1];
                    }
                    
                } elseif ($p == 1) {
                    $from = $price_split[0];
                } elseif ($p == $price_count) {
                    if ($price == 500) {
                        $is_greater_500 = true;
                    } else {
                        $to = $price_split[1];
                    }
                }
            }
            $query->where('courses.price', '>=', $from);
            if (!$is_greater_500) {
                $query->where('courses.price', '<=', $to);
            }
        }

        if ($sort_price) {
            $query->orderBy('courses.price', $sort_price);
        }

        $courses = $query->groupBy('courses.id')->paginate($paginate_count);

        return view('site.course.list', compact('courses', 'categories', 'instruction_levels'));
    }

    public function checkout(Request $request, $course_slug = null)
    {
        $course_breadcrumb = Session::get('course_breadcrumb');
        $course = Course::where('course_slug', $course_slug)->first();
        
        return view('site.course.checkout', compact('course', 'course_breadcrumb'));
    }

    public function saveBreadcrumb(Request $request)
    {
        $link = $request->input('link');
        Session::put('course_breadcrumb', $link);
        Session::save();
    }

    public function deletePhoto(Request $request)
    {
        $content = $request->input('data_content');
        $input = json_decode(Crypt::decryptString($content));

        DB::table($input->model)
            ->where($input->pid, $input->id)
            ->update([$input->field => '']);

        Storage::delete($input->photo);
    }
    public function instructorCourseList(Request $request)
    {
        $paginate_count = 10;
        $instructor_id = Auth::user()->instructor->id;

        if ($request->has('search')) {
            $search = $request->input('search');

            $courses = DB::table('courses')
                ->select('courses.*', 'categories.name as category_name')
                ->leftJoin('categories', 'categories.id', '=', 'courses.category_id')
                ->where('courses.instructor_id', $instructor_id)
                ->where(function ($query) use ($search) {
                    $query->where('courses.course_title', 'LIKE', '%' . $search . '%')
                          ->orWhere('courses.course_slug', 'LIKE', '%' . $search . '%')
                          ->orWhere('categories.name', 'LIKE', '%' . $search . '%');
                })
                ->paginate($paginate_count);
        } else {
            $courses = DB::table('courses')
                ->select('courses.*', 'categories.name as category_name')
                ->leftJoin('categories', 'categories.id', '=', 'courses.category_id')
                ->where('courses.instructor_id', $instructor_id)
                ->paginate($paginate_count);
        }

        return view('instructor.course.list', compact('courses'));
    }

    public function instructorCourseInfo(Request $request, $course_id = null)
    {
        $categories = Category::where('is_active', 1)->get();
        $instruction_levels = InstructionLevel::get();

        $course = $course_id ? Course::find($course_id) : new Course();

        return view('instructor.course.create_info', compact('course', 'categories', 'instruction_levels'));
    }

    public function instructorCourseImage( Request $request, $course_id = null)
    {
        $course = Course::find($course_id);

        if (!$course) {
            return redirect()->route('instructor.course.info')->with('error', 'Course not found.');
        }

        return view('instructor.course.create_image', compact('course'));
    }

    public function instructorCourseVideo(Request $request, $course_id = null)
    {
        $course = Course::find($course_id);
        $video = null;

        if ($course->course_video) {
            $video = CourseVideos::find($course->course_video);
        }

        return view('instructor.course.create_video', compact('course', 'video'));
    }

    public function instructorCourseCurriculum(Request $request, $course_id = null)
    {
        $course = Course::find($course_id);
        $user_id = Auth::user()->instructor->id;
        $coursecurriculum = $this->model->getcurriculuminfo($course_id, $user_id);

        return view('instructor.course.create_curriculum', [
            'course' => $course,
            'sections' => $coursecurriculum['sections'],
            'lecturesquiz' => $coursecurriculum['lecturesquiz'],
            'lecturesquizquestions' => $coursecurriculum['lecturesquizquestions'],
            'lecturesmedia' => $coursecurriculum['lecturesmedia'],
            'lecturesresources' => $coursecurriculum['lecturesresources'],
            'uservideos' => $coursecurriculum['uservideos'],
            'userpresentation' => $coursecurriculum['userpresentation'],
            'userdocuments' => $coursecurriculum['userdocuments'],
            'userresources' => $coursecurriculum['userresources']
        ]);
    }

    public function instructorCourseImageSave(Request $request)
    {
        $course_id = $request->input('course_id');
        $input = $request->all();

        if ($request->hasFile('course_image') && $request->has('course_image_base64')) {
            // Delete old file
            if (Storage::exists($input['old_course_image'])) {
                Storage::delete($input['old_course_image']);
            }

            if (Storage::exists($input['old_thumb_image'])) {
                Storage::delete($input['old_thumb_image']);
            }

            // Get filename
            $file_name = $request->file('course_image')->getClientOriginalName();
            $image_make = Image::make($request->input('course_image_base64'))->encode('jpg');
            $path = "course/" . $course_id;
            $new_file_name = SiteHelpers::checkFileName($path, $file_name);

            // Save the image using storage
            Storage::put($path . "/" . $new_file_name, $image_make->__toString(), 'public');

            // Resize image for thumbnail
            $thumb_image = "thumb_" . $new_file_name;
            $resize = Image::make($request->input('course_image_base64'))->resize(258, 172)->encode('jpg');
            Storage::put($path . "/" . $thumb_image, $resize->__toString(), 'public');

            $course = Course::find($course_id);
            $course->course_image = $path . "/" . $new_file_name;
            $course->thumb_image = $path . "/" . $thumb_image;
            $course->save();
        }

        return redirect()->route('instructor.course.image', $course_id)->with('success', 'Course image updated successfully');
    }

    

    public function instructorCourseInfoSave(Request $request)
    {
        $course_id = $request->input('course_id');
        $validation_rules = [
            'course_title' => 'required|string|max:50',
            'category_id' => 'required',
            'instruction_level_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $validation_rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($course_id) {
            $course = Course::find($course_id);
            $success_message = 'Course updated successfully';
        } else {
            $course = new Course();
            $success_message = 'Course added successfully';
            $slug = Str::slug($request->input('course_title'), '-');
            $results = DB::select("SELECT count(*) as total from courses where course_slug REGEXP '^{$slug}(-[0-9]+)?$'");
            $finalSlug = ($results[0]->total > 0) ? "{$slug}-{$results[0]->total}" : $slug;
            $course->course_slug = $finalSlug;
        }

        $course->course_title = $request->input('course_title');
        $course->instructor_id = Auth::user()->instructor->id;
        $course->category_id = $request->input('category_id');
        $course->instruction_level_id = $request->input('instruction_level_id');
        $course->keywords = $request->input('keywords');
        $course->overview = $request->input('overview');
        $course->duration = $request->input('duration');
        $course->price = $request->input('price');
        $course->strike_out_price = $request->input('strike_out_price');
        $course->is_active = $request->input('is_active');
        $course->save();

        return redirect()->route('instructor.course.info', $course->id)->with('success', $success_message);
    }

    public function instructorCourseVideoSave(Request $request)
    {
        $course_id = $request->input('course_id');
        $video = $request->file('course_video');
        $file_tmp_name = $video->getPathName();
        $file_name = explode('.', $video->getClientOriginalName())[0] . '_' . time() . rand(4, 9999);
        $file_type = $video->getClientMimeType();
        $extension = $video->getClientOriginalExtension();
        $file_title = $video->getClientOriginalName();
        $file_name = Str::slug($file_name, "-");
        $ffmpeg_path = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? base_path() . '\resources\assets\ffmpeg\ffmpeg_win\ffmpeg' : base_path() . '/resources/assets/ffmpeg/ffmpeg_lin/ffmpeg.exe';

        $ffmpeg = new VideoHelpers($ffmpeg_path, $file_tmp_name, $file_name);
        $duration = explode('.', $ffmpeg->getDuration())[0];
        $created_at = time();
        $path = 'course/' . $course_id;
        $video_name = 'raw_' . $created_at . '_' . $file_name . '.' . $extension;
        $video_path = $path . '/' . $video_name;
        $video_image_name = 'raw_' . $created_at . '_' . $file_name . '.jpg';
        $video_image_path = storage_path('app/public/' . $path . '/' . $video_image_name);
        $ffmpeg->convertImages($video_image_path);
        $request->file('course_video')->storeAs($path, $video_name);

        $courseVideos = new CourseVideos();
        $courseVideos->video_title = 'raw_' . $created_at . '_' . $file_name;
        $courseVideos->video_name = $file_title;
        $courseVideos->video_type = $extension;
        $courseVideos->duration = $duration;
        $courseVideos->image_name = $video_image_name;
        $courseVideos->video_tag = 'curriculum';
        $courseVideos->uploader_id = Auth::user()->instructor->id;
        $courseVideos->course_id = $course_id;
        $courseVideos->processed = '1';
        $courseVideos->created_at = $created_at;
        $courseVideos->updated_at = $created_at;

        if ($courseVideos->save()) {
            $course = Course::find($course_id);
            $old_video = CourseVideos::find($course->course_video);

            if ($old_video) {
                $old_file_name = 'course/' . $old_video->course_id . '/' . $old_video->video_title . '.' . $old_video->video_type;
                $old_file_image_name = 'course/' . $old_video->course_id . '/' . $old_video->video_title . '.jpg';

                if (Storage::exists($old_file_name)) {
                    Storage::delete($old_file_name);
                }

                if (Storage::exists($old_file_image_name)) {
                    Storage::delete($old_file_image_name);
                }
            }

            $course->course_video = $courseVideos->id;
            $course->save();

            return back()->with('message', 'Promo Video Uploaded Successfully');
        } else {
            return back()->with('error', 'Promo Video Upload Failed');
        }
    }

    /* Curriculum start */

    public function postSectionSave(Request $request)
    {   
        $data = [
            'course_id' => $request->input('courseid'),
            'title' => $request->input('section'),
            'sort_order' => $request->input('position'),
            'createdOn' => now(),
            'updatedOn' => now(),
        ];
        
        $newID = $request->input('sid') == 0 ?
            $this->model->insertSectionRow($data, '') :
            $this->model->insertSectionRow($data, $request->input('sid'));
        
        return response()->json(['newID' => $newID]);
    }

    public function postLectureSave(Request $request)
    {   
        $data = [
            'section_id' => $request->input('sectionid'),
            'title' => $request->input('lecture'),
            'sort_order' => $request->input('position'),
            'type' => '0',
            'createdOn' => now(),
            'updatedOn' => now(),
        ];

        $newID = $request->input('lid') == 0 ?
            $this->model->insertLectureQuizRow($data, '') :
            $this->model->insertLectureQuizRow($data, $request->input('lid'));
        
        return response()->json(['newID' => $newID]);
    }
    
    public function postCurriculumSort(Request $request)
    {   
        if ($request->input('type') === 'section') {
            $sections = $request->input('sectiondata');
            foreach ($sections as $section) {
                $data = [
                    'sort_order' => $section['position'],
                ];
                $this->model->insertSectionRow($data, $section['id']);
            }
        } elseif ($request->input('type') === 'lecturequiz') {
            $lecturequiz = $request->input('lecturequizdata');
            foreach ($lecturequiz as $lq) {
                $data = [
                    'section_id' => $lq['sectionid'],
                    'sort_order' => $lq['position'],
                ];
                $this->model->insertLectureQuizRow($data, $lq['id']);
            }
        }
        
        return response()->json(['message' => 'Sorting updated successfully']);
    }

    public function postSectionDelete(Request $request)
    {
        $this->model->postSectionDelete($request->input('sid'));
        return response()->json(['message' => 'Section deleted successfully']);
    }
    
    public function postLectureQuizDelete(Request $request)
    {
        $this->model->postLectureQuizDelete($request->input('lid'));
        return response()->json(['message' => 'Lecture or quiz deleted successfully']);
    }
    
    public function postLectureResourceDelete(Request $request)
    {
        $this->model->postLectureResourceDelete($request->input('lid'), $request->input('rid'));
        return response()->json(['message' => 'Lecture resource deleted successfully']);
    }
    
    public function postLectureDescSave(Request $request)
    {   
        $data = [
            'description' => $request->input('lecturedescription'),
            'updatedOn' => now(),
        ];

        $newID = $request->input('lid') == 0 ?
            $this->model->insertLectureQuizRow($data, '') :
            $this->model->insertLectureQuizRow($data, $request->input('lid'));
        
        return response()->json(['newID' => $newID]);
    }
    
    public function postLectureVideoSave(Request $request, $lid)
    {
        $course_id = $request->input('course_id');
        $video = $request->file('lecturevideo');
        
        $validator = Validator::make($request->all(), [
            'lecturevideo' => 'required|mimes:mp4,mov,avi,flv',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid file format.']);
        }
        
        $file_name = Str::slug($video->getClientOriginalName(), '-') . '_' . time() . '_' . rand(4, 9999);
        $extension = $video->getClientOriginalExtension();
        $file_path = 'course/' . $course_id . '/';
        $file_name_with_extension = $file_name . '.' . $extension;
        
        Storage::putFileAs($file_path, $video, $file_name_with_extension);
        
        $courseVideo = new CourseVideos();
        $courseVideo->video_title = $file_name;
        $courseVideo->video_name = $video->getClientOriginalName();
        $courseVideo->video_type = $extension;
        $courseVideo->duration = 0; // Replace with actual duration if available
        $courseVideo->image_name = $file_name . '.jpg'; // Replace with actual thumbnail if available
        $courseVideo->video_tag = 'curriculum';
        $courseVideo->uploader_id = auth()->user()->instructor->id;
        $courseVideo->course_id = $course_id;
        $courseVideo->processed = 1; // Assuming video processing status
        $courseVideo->save();
        
        if (!empty($lid)) {
            $this->model->checkDeletePreviousFiles($lid);
            $data = [
                'media' => $courseVideo->id,
                'media_type' => '0',
                'publish' => '0',
            ];
            $this->model->insertLectureQuizRow($data, $lid);
        }
        
        $return_data = [
            'status' => true,
            'file_name' => $file_name_with_extension,
            'file_link' => Storage::url($file_path . $file_name_with_extension),
        ];
        
        return response()->json($return_data);
    }
    
    public function postLecturePresentationSave(Request $request, $lid)
    {
        $course_id = $request->input('course_id');
        $document = $request->file('lecturepre');
        
        $validator = Validator::make($request->all(), [
            'lecturepre' => 'required|mimes:pdf',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid file format.']);
        }
        
        $file_name = Str::slug($document->getClientOriginalName(), '-') . '_' . time() . '_' . rand(4, 9999);
        $extension = $document->getClientOriginalExtension();
        $file_path = 'course/' . $course_id . '/';
        $file_name_with_extension = $file_name . '.' . $extension;
        
        Storage::putFileAs($file_path, $document, $file_name_with_extension);
        
        $courseFile = new CourseFiles();
        $courseFile->file_name = $file_name;
        $courseFile->file_title = $document->getClientOriginalName();
        $courseFile->file_type = $extension;
        $courseFile->file_extension = $extension;
        $courseFile->file_size = $document->getSize();
        $courseFile->duration = 0; // Replace with actual duration if available
        $courseFile->file_tag = 'curriculum';
        $courseFile->uploader_id = auth()->user()->instructor->id;
        $courseFile->save();
        
        if (!empty($lid)) {
            $data = [
                'media' => $courseFile->id,
                'media_type' => '2',
                'publish' => '0',
            ];
            $this->model->insertLectureQuizRow($data, $lid);
        }
        
        $return_data = [
            'status' => true,
            'file_name' => $file_name_with_extension,
            'file_link' => Storage::url($file_path . $file_name_with_extension),
        ];
        
        return response()->json($return_data);
    }
        
    public function postLectureDocumentSave(Request $request, $lid)
    {
        $request->validate([
            'lecturedoc' => 'required|mimes:pdf'
        ]);

        $document = $request->file('lecturedoc');
        $course_id = $request->input('course_id');
        $pdftext = file_get_contents($document);
        $pdfPages = preg_match_all("/\/Page\W/", $pdftext, $dummy);

        $file_name = $document->getClientOriginalName();
        $file_title = pathinfo($file_name, PATHINFO_FILENAME);
        $file_type = $document->getClientOriginalExtension();
        $file_size = $document->getSize();

        $filePath = $document->storeAs('course/' . $course_id, $file_name);

        $courseFiles = new CourseFiles();
        $courseFiles->fill([
            'file_name' => $file_name,
            'file_title' => $file_title,
            'file_type' => $file_type,
            'file_extension' => $file_type,
            'file_size' => $file_size,
            'duration' => $pdfPages,
            'file_tag' => 'curriculum',
            'uploader_id' => auth()->user()->instructor->id,
        ]);
        $courseFiles->save();

        if (!empty($lid)) {
            $data = [
                'media' => $courseFiles->id,
                'media_type' => '2',
                'publish' => '0',
            ];
            $newID = $this->model->insertLectureQuizRow($data, $lid);
        }

        $pdfPage = ($pdfPages == 1) ? $pdfPages . ' Page' : $pdfPages . ' Pages';
        $return_data = [
            'status' => true,
            'file_title' => $file_title,
            'duration' => $pdfPage
        ];

        return response()->json($return_data);
    }
        
    public function postLectureResourceSave(Request $request, $lid)
    {
        $request->validate([
            'lectureres' => 'required'
        ]);

        $document = $request->file('lectureres');
        $course_id = $request->input('course_id');

        $file_name = $document->getClientOriginalName();
        $file_title = pathinfo($file_name, PATHINFO_FILENAME);
        $file_type = $document->getClientOriginalExtension();
        $file_size = $document->getSize();

        if ($file_type == 'pdf') {
            $pdftext = file_get_contents($document);
            $pdfPages = preg_match_all("/\/Page\W/", $pdftext, $dummy);
        } else {
            $pdfPages = null;
        }

        $filePath = $document->storeAs('course/' . $course_id, $file_name);

        $courseFiles = new CourseFiles();
        $courseFiles->fill([
            'file_name' => $file_name,
            'file_title' => $file_title,
            'file_type' => $file_type,
            'file_extension' => $file_type,
            'file_size' => $file_size,
            'duration' => $pdfPages,
            'file_tag' => 'curriculum_resource',
            'uploader_id' => auth()->user()->instructor->id,
        ]);
        $courseFiles->save();

        if (!empty($lid)) {
            $data = [
                'resources' => $courseFiles->id,
            ];
            $newID = $this->model->insertLectureQuizResourceRow($data, $lid);
        }

        $return_data = [
            'status' => true,
            'file_id' => $courseFiles->id,
            'file_title' => $file_title,
            'file_size' => thintanHelpers::HumanFileSize($file_size)
        ];

        return response()->json($return_data);
    }

    public function postLectureTextSave(Request $request)
    {
        $document = $request->input('lecturedescription');
        $lid = $request->input('lid');

        if (!empty($lid)) {
            $data = [
                'contenttext' => $document,
                'media_type' => '3',
                'publish' => '0',
            ];
            $newID = $this->model->insertLectureQuizRow($data, $lid);
        }

        $return_data = [
            'status' => true,
            'file_title' => 'Text'
        ];

        return response()->json($return_data);
    }

    public function postLectureLibrarySave(Request $request)
    {
        $request->validate([
            'lib' => 'required'
        ]);

        $course_id = $request->input('course_id');
        $data = [
            'media' => $request->input('lib'),
            'media_type' => $request->input('type'),
        ];
        $newID = $this->model->insertLectureQuizRow($data, $request->input('lid'));

        switch ($request->input('type')) {
            case 0:
                $libraryDetails = $this->model->getvideoinfo($request->input('lib'));
                $file_title = $libraryDetails[0]->video_name;
                $duration = $libraryDetails[0]->duration;
                $processed = $libraryDetails[0]->processed;
                $file_link = ($processed == 1) ? Storage::url('course/' . $course_id . '/' . $libraryDetails[0]->video_title . '.webm') : '';
                break;

            case 1:
                $libraryDetails = $this->model->getfileinfo($request->input('lib'));
                $file_title = $libraryDetails[0]->file_title;
                $duration = $libraryDetails[0]->duration;
                $file_link = Storage::url('course/' . $course_id . '/' . $libraryDetails[0]->file_name . '.' . $libraryDetails[0]->file_extension);
                break;

            case 2:
            case 5:
                $libraryDetails = $this->model->getfileinfo($request->input('lib'));
                $file_title = $libraryDetails[0]->file_title;
                $pdfPage = ($libraryDetails[0]->duration <= 1) ? $libraryDetails[0]->duration . ' Page' : $libraryDetails[0]->duration . ' Pages';
                $duration = $pdfPage;
                $file_link = '';
                break;

            default:
                $file_title = '';
                $duration = '';
                $file_link = '';
                break;
        }

        $return_data = [
            'status' => true,
            'duration' => $duration,
            'file_title' => $file_title,
            'file_link' => $file_link
        ];

        return response()->json($return_data);
    }

    public function postLectureLibraryResourceSave(Request $request)
    {
        $request->validate([
            'lib' => 'required'
        ]);
    
        $data = [
            'resources' => $request->input('lib'),
        ];
        $newID = $this->model->insertLectureQuizResourceRow($data, $request->input('lid'));
    
        $return_data = [
            'status' => true,
            'file_id' => $request->input('lib')
        ];
    
        return response()->json($return_data);
    }
    

    public function postLectureExternalResourceSave(Request $request)
    {
        $request->validate([
            'link' => 'required',
            'title' => 'required'
        ]);
    
        $lid = $request->input('lid');
        $courseFiles = new CourseFiles();
        $courseFiles->fill([
            'file_name' => $request->input('link'),
            'file_title' => $request->input('title'),
            'file_type' => 'link',
            'file_extension' => 'link',
            'file_tag' => 'curriculum_resource_link',
            'uploader_id' => auth()->user()->instructor->id,
        ]);
        $courseFiles->save();
    
        if (!empty($lid)) {
            $data = [
                'resources' => $courseFiles->id,
            ];
            $newID = $this->model->insertLectureQuizResourceRow($data, $lid);
        }
    
        $return_data = [
            'status' => true,
            'file_id' => $courseFiles->id,
            'file_title' => $request->input('title'),
            'file_size' => ''
        ];
    
        return response()->json($return_data);
    }
    

    public function postLecturePublishSave(Request $request)
    {
        $data = [
            'publish' => $request->input('publish'),
        ];
        $lid = $request->input('lid');
        
        if ($lid == 0) {
            $newID = $this->model->insertLectureQuizRow($data, '');
        } else {
            $newID = $this->model->insertLectureQuizRow($data, $lid);
        }
    
        $publish = $request->input('publish');
    
        if ($publish == '1' && $lid != '0') {
            $getcourseid = $this->model->getLectureQuizRow($lid);
        }
    
        return response()->json([
            'status' => true,
            'publish' => $publish
        ]);
    }

    public function postVideo(Request $request)
    {
        $video_id = $request->input('vid');
        $video = $this->model->getVideobyid($video_id);

        if (!$video) {
            return response()->json(['error' => 'Video not found'], 404);
        }

        return response()->json(['video_title' => $video->video_title]);
    }

    /* Curriculum end */
}
