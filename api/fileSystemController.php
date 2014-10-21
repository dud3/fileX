<?php

namespace api\v1\filesystem;

use BaseController;
use Response;
use Input;
use Redirect;

use FileRepositoryInterface;

/*
use _notificationInterface;
use UserRepositoryInterface;
use CompanyRepositoryInterface;

use WorkitemRepositoryInterface;
use TrainingRepositoryInterface;

use ProfileRepositoryInterface;
*/

class fileSystemController extends BaseController {

	protected $file;

	/*
	protected $notifications;
	protected $user;
	protected $company;

	protected $workitem;
	protected $training;

	protected $profile;
	*/
	
	public function __construct(FileRepositoryInterface $file,
								_notificationInterface $notifications,  
								TrainingRepositoryInterface $training, UserRepositoryInterface $user)
	{
		header('Content-Type: text/plain; charset=utf-8');
		$this->file = $file;
		/*
		$this->training = $training;
		$this->user = $user;
		*/
	}


	// ---------------------------------------------------
	// Folders Section
	// ---------------------------------------------------
	// 
	// All the folder nesscecary
	// -> manipulations goes here...
	// 
	// ---------------------------------------------------

	/**
	 * Get the full tree of folders.
	 * @return [type] [description]
	 */
	public function getFullTree() 
	{

		$folders = $this->file->getFullTree();

		if($folders) {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders failed", "code" => 400, "error" => true], 400);
		}

		return $respose;

	}


	/**
	 * Get the full tree of folders.
	 * @return [type] [description]
	 */
	public function getFullTreePerUser() 
	{

		$folders = $this->file->getFullTreePerUser();

		if($folders) {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders failed", "code" => 400, "error" => true], 400);
		}

		return $respose;

	}



	/**
	 * Get Folders that doesn't have
	 * -> sub-folders.
	 * @return [type] [description]
	 */
	public function getLeafNodes() 
	{

		$folders = $this->file->getLeafNodes();

		if($folders) {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders failed", "code" => 400, "error" => true], 400);
		}

		return $respose;

	}


	/**
	 * Get Folders that doesn't have
	 * -> sub-folders.
	 * @return [type] [description]
	 */
	public function getSinglePath() 
	{

		$folders = $this->file->getSinglePath($path_name);

		if($folders) {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders failed", "code" => 400, "error" => true], 400);
		}

		return $respose;

	}


	/**
	 * Get user folders and sub-folders.
	 * @return [type] [description]
	 */
	public function getFolders() {

		$folders = $this->file->user_folders();

		if($folders) {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["folders" => $folders, "msg" => "Get folders failed", "code" => 400, "error" => true], 400);
		}

		return $respose;

	}


	// ---------------------------------------------------
	// Files Section
	// ---------------------------------------------------
	// 
	// All the Files nesscecary
	// -> manipulations goes here...
	// 
	// --------------------------------------------------- 

	/**
	 * Get all the files
	 * @param  [type] $type [description]
	 * @return [type]       [description]
	 */
	public function getAll()
	{

		$files = $this->file->getAll();
		if($files) {
			$respose = Response::json(["files" => $files, "msg" => "Get files success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["files" => $files, "msg" => "Get files failed", "code" => 400, "error" => true], 400);
		}

		return $respose;

	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getByID($id)
	{

		$file = $this->file->getByID($id);

		if($file) {
			$respose = Response::json(["files" => $file, "msg" => "Get File, success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["files" => $file, "msg" => "Get File, failed", "code" => 400, "error" => true], 400);
		}
		
		return $respose;
	}

	/**
	 * Get files by user.
	 * @return [type] [description]
	 */
	public function getByUser() 
	{

		$input = Input::get('user_id');

		if($input) {
			$user_id = $input;
		} else {
			$user_id = null;
		}

		$file = $this->file->getByUser($user_id);

		if($file) {
			$respose = Response::json(["files" => $file, "msg" => "Get File, success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["files" => $file, "msg" => "Get File, failed", "code" => 200, "error" => false], 200);
		}
		
		return $respose;

	}

	/**
	 * Get Recent files per user.
	 * @return [type] [description]
	 */
	public function getRecentFiles() 
	{

		$input = Input::get('user_id');

		if($input) {
			$user_id = $input;
		} else {
			$user_id = null;
		}

		$file = $this->file->getRecentFiles($user_id);

		if($file) {
			$respose = Response::json(["files" => $file, "msg" => "Get File, success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["files" => $file, "msg" => "Get File, failed", "code" => 400, "error" => true], 400);
		}
		
		return $respose;

	}

	/**
	 * Get old files per user
	 * @return [type] [description]
	 */
	public function getOldFiles() {

		$input = Input::get('user_id');

		if($input) {
			$user_id = $input;
		} else {
			$user_id = null;
		}

		$file = $this->file->getOldFiles($user_id);

		if($file) {
			$respose = Response::json(["files" => $file, "msg" => "Get File, success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["files" => $file, "msg" => "Get File, failed", "code" => 400, "error" => true], 400);
		}
		
		return $respose;


	}

	/**
	 * List files based on file folder.
	 * @return [type] [description]
	 */
	public function getByFolder($folder_id) 
	{

		if($folder_id === null) {
			$folder_id = Input::get('folder_id');
		}

		$file = $this->file->getByFolder($folder_id, $user_id = null);

		if($file || empty($file)) {
			$respose = Response::json(["files" => $file, "msg" => "Get File, success", "code" => 200, "error" => false], 200);			
		} else {
			$respose = Response::json(["files" => $file, "msg" => "Get File, failed", "code" => 400, "error" => true], 400);
		}
		
		return $respose;
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function upload()
	{

		$refered_user_id = null;

		$input = Input::file('file');
		$refered_user_id = Input::get('user_id');
		
		$user = $refered_user_id;

		$files = $this->file->upload($input, $user, $addit_data = null);

		if($files->error) {

			if($files->code < 1) {
				$respose = Response::json(["errorType" => "Eloquent", "msg" => $files->message, "code" => 406, "error" => true], 406);
			} else if($files->code >= 1) {
				$respose = Response::json(["errorType" => "File", "msg" => $files->message, "code" => 406, "error" => true], 406);
			}

		} else {
		
			if($files) {
				$respose = Response::json(["files" => $files, "msg" => "Upload file(s) success", "code" => 200, "error" => false], 200);
			} 

		}

		return $respose;

	}

	/**
	 * Downlod file by it's token.
	 * @return [type] [description]
	 */
	public function download($token)
	{

		$input = Input::all();

		if($input === null || empty($input)) {
			$input = $token;
		} 

		$files = $this->file->download($input);

		if($files->error) {

			if($files->code == 0) {
				return Redirect::to('/Exceptions/fileNotFound');
			}

		}

	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update()
	{

		$input = Input::all();

		$files = $this->file->update($input, $user = null);

		if($files) {
			$respose = Response::json(["files" => $files, "msg" => "Update file(s) success", "code" => 200, "error" => false], 200);
		} 

		return $respose;

	}


	/**
	 * Get training files.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getTrainingFiles($id) {

		$files = $this->file->getTrainingFiles($id);

		if($files) {
			$respose = Response::json(["files" => $files, "msg" => "Get File(s) success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["files" => $files, "msg" => "No Files found", "code" => 200, "error" => false], 200);
		}

		return $respose;

	}


	/**
	 * Get License files.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getLicenseFiles($id) {

		$files = $this->file->getLicenseFiles($id);

		if($files) {
			$respose = Response::json(["files" => $files, "msg" => "Get File(s) success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["files" => $files, "msg" => "No Files found", "code" => 200, "error" => false], 200);
		}

		return $respose;

	}


	/**
	 * Filter files
	 * @return [type] [description]
	 */
	public function filterFiles() {
		
		$q = Input::all();

		$files = $this->file->filterFiles($q);

		if($files) {
			$respose = Response::json(["files" => $files, "msg" => "Get File(s) success", "code" => 200, "error" => false], 200);
		} else {
			$respose = Response::json(["files" => $files, "msg" => "No Files found", "code" => 200, "error" => false], 200);
		}

		return $respose;

	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{

		$files = $this->file->destroy($id);

		if($files->error) {

			if($files->code < 1) {
				$respose = Response::json(["errorType" => "Eloquent", "msg" => $files->message, "code" => 406, "error" => true], 406);
			} else if($files->code >= 1) {
				$respose = Response::json(["errorType" => "File", "msg" => $files->message, "code" => 406, "error" => true], 406);
			}

		} else {

			if($files) {
				$respose = Response::json(["files" => $files, "msg" => "Destroy file(s) success", "code" => 200, "error" => false], 200);
			}

		}

		return $respose;

	}

}