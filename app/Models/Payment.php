<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class Payment extends Model
{
    protected $table = 'payments';
    protected $guarded = array();

    public function instructor()
    {
        return $this->belongsTo('App\Models\Instructor', 'instructor_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    public function course()
    {
        return $this->belongsTo('App\Models\Course', 'course_id', 'id');
    }
}
