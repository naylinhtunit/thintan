<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\ConfigController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Auth::routes();
Route::get('logout', [LoginController::class, 'logout'])->name('logOut');

// Route::get('/login/{social}', [LoginController::class, 'socialLogin'])->where('social', 'google');
// Route::get('/login/{social}/callback', [LoginController::class, 'handleProviderCallback'])->where('social', 'google');
Route::get('login/google', [LoginController::class, 'redirectToGoogle']);
Route::get('google/callback', [LoginController::class, 'handleGoogleCallback']);

Route::get('about', [HomeController::class, 'pageAbout'])->name('page.about');
Route::get('contact', [HomeController::class, 'pageContact'])->name('page.contact');
Route::get('instructor/{instructor_slug}', [InstructorController::class, 'instructorView'])->name('instructor.view');

Route::get('getCheckTime', [HomeController::class, 'getCheckTime']);
Route::get('checkUserEmailExists', [HomeController::class, 'checkUserEmailExists']);

Route::get('course-view/{course_slug}', [CourseController::class, 'courseView'])->name('course.view');
Route::get('courses', [CourseController::class, 'courseList'])->name('course.list');
Route::get('checkout/{course_slug}', [CourseController::class, 'checkout'])->name('course.checkout');
Route::get('course-breadcrumb', [CourseController::class, 'saveBreadcrumb'])->name('course.breadcurmb');

Route::post('become-instructor', [InstructorController::class, 'becomeInstructor'])->name('become.instructor');

Route::get('instructors', [InstructorController::class, 'instructorList'])->name('instructor.list');
Route::post('contact-instructor', [InstructorController::class, 'contactInstructor'])->name('contact.instructor');

Route::post('contact-admin', [HomeController::class, 'contactAdmin'])->name('contact.admin');

Route::get('blogs', [HomeController::class, 'blogList'])->name('blogs');
Route::get('blog/{blog_slug}', [HomeController::class, 'blogView'])->name('blog.view');

// Functions accessed by only authenticated users
Route::middleware('auth')->group(function () {
    Route::post('delete-photo', [CourseController::class, 'deletePhoto']);
    Route::post('payment-form', [PaymentController::class, 'paymentForm'])->name('payment.form');

    Route::get('payment/success', [PaymentController::class, 'getSuccess'])->name('payment.success');
    Route::get('payment/failure', [PaymentController::class, 'getFailure'])->name('payment.failure');

    // Functions accessed by only students
    Route::middleware('role:student')->group(function () {
        Route::get('course-enroll-api/{course_slug}/{lecture_slug}/{is_sidebar}', [CourseController::class, 'courseEnrollAPI']);
        Route::get('readPDF/{file_id}', [CourseController::class, 'readPDF']);
        Route::get('update-lecture-status/{course_id}/{lecture_id}/{status}', [CourseController::class, 'updateLectureStatus']);

        Route::get('download-resource/{resource_id}/{course_slug}', [CourseController::class, 'getDownloadResource']);

        Route::get('my-courses', [CourseController::class, 'myCourses'])->name('my.courses');
        Route::get('course-learn/{course_slug}', [CourseController::class, 'courseLearn'])->name('course.learn');

        Route::post('course-rate', [CourseController::class, 'courseRate'])->name('course.rate');
        Route::get('delete-rating/{raing_id}', [CourseController::class, 'deleteRating'])->name('delete.rating');
    });

    // Functions accessed by both student and instructor
    Route::middleware('role:instructor')->group(function () {
        Route::get('instructor-dashboard', [InstructorController::class, 'dashboard'])->name('instructor.dashboard');

        Route::get('instructor-profile', [InstructorController::class, 'getProfile'])->name('instructor.profile.get');
        Route::post('instructor-profile', [InstructorController::class, 'saveProfile'])->name('instructor.profile.save');

        Route::get('course-create', [CourseController::class, 'createInfo'])->name('instructor.course.create');
        Route::get('instructor-course-list', [CourseController::class, 'instructorCourseList'])->name('instructor.course.list');
        Route::get('instructor-course-info/{course_id?}', [CourseController::class, 'instructorCourseInfo'])->name('instructor.course.info');
        Route::get('instructor-course-info/{course_id}', [CourseController::class, 'instructorCourseInfo'])->name('instructor.course.info.edit');
        Route::post('instructor-course-info-save', [CourseController::class, 'instructorCourseInfoSave'])->name('instructor.course.info.save');

        Route::get('instructor-course-image/{course_id?}', [CourseController::class, 'instructorCourseImage'])->name('instructor.course.image');
        Route::get('instructor-course-image/{course_id}', [CourseController::class, 'instructorCourseImage'])->name('instructor.course.image.edit');
        Route::post('instructor-course-image-save', [CourseController::class, 'instructorCourseImageSave'])->name('instructor.course.image.save');

        Route::get('instructor-course-video/{course_id}', [CourseController::class, 'instructorCourseVideo'])->name('instructor.course.video.edit');
        Route::post('instructor-course-video-save', [CourseController::class, 'instructorCourseVideoSave'])->name('instructor.course.video.save');

        Route::get('instructor-course-curriculum/{course_id}', [CourseController::class, 'instructorCourseCurriculum'])->name('instructor.course.curriculum.edit');
        Route::post('instructor-course-curriculum-save', [CourseController::class, 'instructorCourseCurriculumSave'])->name('instructor.course.curriculum.save');

        Route::get('instructor-payments', [InstructorController::class, 'payments'])->name('instructor.payments');
        Route::get('change-status', [InstructorController::class, 'changeStatus'])->name('change.status');

        // Save Curriculum
        Route::post('courses/section/save', [CourseController::class, 'postSectionSave']);
        Route::post('courses/section/delete', [CourseController::class, 'postSectionDelete']);
        Route::post('courses/lecture/save', [CourseController::class, 'postLectureSave']);
        Route::post('courses/video', [CourseController::class, 'postVideo']);

        Route::post('courses/lecturequiz/delete', [CourseController::class, 'postLectureQuizDelete']);
        Route::post('courses/lecturedesc/save', [CourseController::class, 'postLectureDescSave']);
        Route::post('courses/lecturepublish/save', [CourseController::class, 'postLecturePublishSave']);
        Route::post('courses/lecturevideo/save/{lid}', [CourseController::class, 'postLectureVideoSave']);
        Route::post('courses/lecturepre/save/{lid}', [CourseController::class, 'postLecturePresentationSave']);
        Route::post('courses/lecturedoc/save/{lid}', [CourseController::class, 'postLectureDocumentSave']);
        Route::post('courses/lectureres/save/{lid}', [CourseController::class, 'postLectureResourceSave']);
        Route::post('courses/lecturetext/save', [CourseController::class, 'postLectureTextSave']);
        Route::post('courses/lectureres/delete', [CourseController::class, 'postLectureResourceDelete']);
        Route::post('courses/lecturelib/save', [CourseController::class, 'postLectureLibrarySave']);
        Route::post('courses/lecturelibres/save', [CourseController::class, 'postLectureLibraryResourceSave']);
        Route::post('courses/lectureexres/save', [CourseController::class, 'postLectureExternalResourceSave']);

        // Sorting Curriculum
        Route::post('courses/curriculum/sort', [CourseController::class, 'postCurriculumSort']);
    });

    // Functions accessed by only admin users
    Route::middleware('role:admin')->group(function () {
        Route::get('admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('admin/users', [UserController::class, 'index'])->name('admin.users');
        Route::get('admin/user-form', [UserController::class, 'getForm'])->name('admin.getForm');
        Route::get('admin/user-form/{user_id}', [UserController::class, 'getForm']);
        Route::post('admin/save-user', [UserController::class, 'saveUser'])->name('admin.saveUser');
        Route::get('admin/users/getData', [UserController::class, 'getData'])->name('admin.users.getData');

        Route::get('admin/categories', [CategoryController::class, 'index'])->name('admin.categories');
        Route::get('admin/category-form', [CategoryController::class, 'getForm'])->name('admin.categoryForm');
        Route::get('admin/category-form/{Category_id}', [CategoryController::class, 'getForm']);
        Route::post('admin/save-category', [CategoryController::class, 'saveCategory'])->name('admin.saveCategory');
        Route::get('admin/delete-category/{Category_id}', [CategoryController::class, 'deleteCategory']);

        Route::get('admin/blogs', [BlogController::class, 'index'])->name('admin.blogs');
        Route::get('admin/blog-form', [BlogController::class, 'getForm'])->name('admin.blogForm');
        Route::get('admin/blog-form/{blog_id}', [BlogController::class, 'getForm']);
        Route::post('admin/save-blog', [BlogController::class, 'saveBlog'])->name('admin.saveBlog');
        Route::get('admin/delete-blog/{blog_id}', [BlogController::class, 'deleteBlog']);

        Route::post('admin/config/save-config', [ConfigController::class, 'saveConfig'])->name('admin.saveConfig');
        Route::get('admin/config/page-home', [ConfigController::class, 'pageHome'])->name('admin.pageHome');
        Route::get('admin/config/page-about', [ConfigController::class, 'pageAbout'])->name('admin.pageAbout');
        Route::get('admin/config/page-contact', [ConfigController::class, 'pageContact'])->name('admin.pageContact');

        Route::get('admin/config/setting-general', [ConfigController::class, 'settingGeneral'])->name('admin.settingGeneral');
    });

    Route::middleware('subscribed')->group(function () {
        // Route for react js
        Route::get('course-enroll/{course_slug}/{lecture_slug}', function () {
            return view('site/course/course_enroll');
        });
        Route::get('course-learn/{course_slug}', [CourseController::class, 'courseLearn'])->name('course.learn');
    });
});
