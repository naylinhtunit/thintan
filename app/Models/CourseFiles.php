<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseFiles extends Model {
	protected $fillable = [
        'file_name',
        'file_title',
        'file_type',
        'file_extension',
        'file_size',
        'duration',
        'file_tag',
        'uploader_id',
        'processed',
    ];
	protected $table 		= 'course_files';
	protected $primaryKey 	= 'id';

	public $timestamps = false;
			
	public function __construct() {
		parent::__construct();
	}

	public function getDateFormat()
    {
        return 'U';
    }

    public function user(){
    	return $this->belongsTo('User');
    }
}