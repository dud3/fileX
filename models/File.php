<?php

namespace FileSystemX;

use Eloquent;

class File extends \Eloquent {
    
    protected $table = "files";

	protected $guarded = array();

	public static $rules = array(
        'id',
        'private_token',
        'url' => 'required',
        'filename' => 'required',
        'full_filename' => 'required',
        'description',
        'uploader',
        'expires',
        'expiry_date',
        'public_allow',
        'public_token',
	);


    public function folder(){
        return $this->belongsTo('FileRelations');
    }

    public function user() {
        return $this->belongsTo('FileRelations');
    }

    public function company(){
    	return $this->belongsTo('FileRelations');
    }

    public function userGroup() {
        return $this->belongsTo('FileRelations');
    }

    public function workitem() {
        return $this->belongsTo('FileRelations');
    }

    public function training() {
        return $this->belongsTo('FileRelations');
    }

}
