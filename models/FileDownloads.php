<?php

namespace FileSystemX;

use Eloquent;

class FileDownloads extends \Eloquent {
    
    protected $table = "file_downloads";

	protected $guarded = array();

	public static $rules = array(
        'id',
        'user_id' => 'required',
        'file_id' => 'required'
	);

    public function user() {
        return $this->belongsTo('User');
    }

    public function file() {
        return $this->belongsTo('File');
    }

}
