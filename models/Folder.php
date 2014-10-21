<?php

namespace FileSystemX;

use Eloquent;

class Folder extends \Eloquent {
    
    protected $table = "folders";

	protected $guarded = array();

	public static $rules = array(
        'id',
        'parent',
        'name',
        'user_id',
        'group_id'
	);

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
