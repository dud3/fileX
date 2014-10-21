<?php

namespace FileSystemX;

use Eloquent;

class FileRelations extends \Eloquent {
    
    protected $table = "file_relations";

	protected $guarded = array();

	public static $rules = array(
        'id',
        'file_id' => 'required',
        'folder_id',
        'user_id',
        'company_id',
        'group_id',
        'workitem_id',
        'training_id',
        'aircraft_id',
        'license_id',
        'hidden',
        'download_count'
	);


    public function filesx(){
        return $this->belongsTo('FileSystemX\File', 'file_id');
    }

    public function foldersx(){
        return $this->belongsTo('FileSystemX\Folder', 'folder_id');
    }

	public function user(){
        return $this->belongsTo('User');
    }

    public function company(){
    	return $this->belongsTo('Company');
    }

    public function userGroup() {
        return $this->belongsTo('Group');
    }

    public function workitem() {
        return $this->belongsTo('Workitem');
    }

    public function training() {
        return $this->belongsTo('Training');
    }

}
