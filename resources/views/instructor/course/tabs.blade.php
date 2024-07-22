<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link py-1 {{ request()->is('instructor-course-info*') ? 'active' : '' }}" 
           href="{{ isset($course) && $course->id ? route('instructor.course.info.edit', $course->id) : route('instructor.course.info') }}">Course Info</a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-1 {{ request()->is('instructor-course-image*') ? 'active' : '' }} @if(!isset($course) || !$course->id) course-id-empty @endif" 
           href="{{ isset($course) && $course->id ? route('instructor.course.image.edit', $course->id) : 'javascript:void();' }}">Course Image</a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-1 {{ request()->is('instructor-course-video*') ? 'active' : '' }} @if(!isset($course) || !$course->id) course-id-empty @endif" 
           href="{{ isset($course) && $course->id ? route('instructor.course.video.edit', $course->id) : 'javascript:void();' }}">Promo Video</a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-1 {{ request()->is('instructor-course-curriculum*') ? 'active' : '' }} @if(!isset($course) || !$course->id) course-id-empty @endif" 
           href="{{ isset($course) && $course->id ? route('instructor.course.curriculum.edit', $course->id) : 'javascript:void();' }}">Curriculum</a>
    </li>
</ul>
