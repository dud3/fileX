<?php

class FileRepository implements FileRepositoryInterface {

	public $notifcations;
	private $user;
	private $company;
	private $workitem;
	private $training;
	private $profile;

	public function __construct(
				_notificationInterface $notifications,
				UserRepositoryInterface $user, 
				CompanyRepositoryInterface $company,
				WorkitemRepositoryInterface $workitem, 
				TrainingRepositoryInterface $training,
				ProfileRepositoryInterface $profile
		) {

		$this->notifications = $notifications;
		$this->user = $user;
		$this->company = $company;
		$this->workitem = $workitem;
		$this->training = $training;
		$this->profile = $profile;
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
	public function getFullTree() {
		return FSX::getFullTree();
	}


	/**
	 * Get the full tree of folders.
	 * @return [type] [description]
	 */
	public function getFullTreePerUser() {
		return FSX::getFullTreePerUser();
	}



	/**
	 * Get Folders that doesn't have
	 * -> sub-folders.
	 * @return [type] [description]
	 */
	public function getLeafNodes() {
		return FSX::getLeafNodes();
	}


	/**
	 * Get Folders that doesn't have
	 * -> sub-folders.
	 * @return [type] [description]
	 */
	public function getSinglePath($path_name) {
		return FSX::getSinglePath();
	}


	/**
	 * Get user folders.
	 * @return [type] [description]
	 */
	public function user_folders($user_id = null) {

		if($user_id == null || empty($user_id)) {
			$user_id = $this->user->getCurrentUser()->id;
		}

		return FSX::user_folders($user_id);
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
	 * Get all files.
	 * @return [type] [description]
	 */
	public function getAll() {
		return FSX::getAll();
	}


	/**
	 * Get files by ID.
	 * @return [type] [description]
	 */
	public function getByID($id) {
		return FSX::getByID($id);
	}


	/**
	 * Get files by user.
	 * @return [type] [description]
	 */
	public function getByUser($user_id = null) {

		if($user_id == null || empty($user_id)) {
			$user_id = $this->user->getCurrentUser()->id;
		}

		return FSX::getByUser($user_id);
	}


	/**
	 * Get recent files per user.
	 * @param  [type] $user_id [user ID]
	 * @return [type]          [description]
	 */
	public function getRecentFiles($user_id = null) {

		if($user_id == null || empty($user_id)) {
			$user_id = $this->user->getCurrentUser()->id;
		}

		return FSX::getRecentFiles($user_id);
	}


	/**
	 * Get old files per user.
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	public function getOldFiles($user_id = null) {

		if($user_id == null || empty($user_id)) {
			$user_id = $this->user->getCurrentUser()->id;
		}

		return FSX::getOldFiles($user_id);
	}


	/**
	 * Get files by folder.
	 * @return [type] [description]
	 */
	public function getByFolder($folder_id, $user_id) {

		if($user_id === null) {
			$user_id = $this->user->getCurrentUser()->id;
		}

		return FSX::getByFolder($folder_id, $user_id);

	}


 	/**
 	 * Upload files.
 	 * @param  [type] $data [description]
 	 * @return [type]       [description]
 	 */
	public function upload($data, $user, $addit_data = null) {
		
		if($user == null) {
			$user = $this->user->getCurrentUser();
		} else {
			$user = $this->user->getUserByID($user);
		}

		$user->owner_id = $this->user->getCurrentUser()->id;

		$file = FSX::upload($data, $user, $addit_data);

		return $file;

	}

	 /**
     * Clone the same files, but might use different parametrs also.
     * Such parameters might/can be:
     * * user_id
     * * item_id(workitem_id, training_id, notification_id...)
     * 
     * @param  {[type]} file [file_id,
     *                        private_token, 
     *                        url, 
     *                        filename, 
     *                        full_filename, 
     *                        cut_full_filenamem, 
     *                        descriptionm, 
     *                        folder_idm, 
     *                        parent_folderm, 
     *                        folder, 
     *                        download_url,
     *                        size, 
     *                        created_at]
     *                        
     * @return {[object]}      [check the getRandomString() method in fileSystemX.php library]
     */
	public function clone_file($file, $owner = null) {
		$owner = $this->user->getCurrentUser()->id;
		return FSX::clone_file($file, $owner);
	}

	/**
	 * Download file based on token or ID.
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function download($token, $user = null) {
		$user = $this->user->getCurrentUser();
		return FSX::download($token, $user);
	}


	/**
	 * Update file info.
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function update($data, $user = null) {
		$user = $this->user->getCurrentUser();
		return FSX::update($data, $user);
	}


	/**
	 * Get training files.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getTrainingFiles($id) {
		return FSX::getTrainingFiles($id);
	}


	/**
	 * Get training files.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getLicenseFiles($id) {
		return FSX::getLicenseFiles($id);
	}



	/**
	 * Create Default folders.
	 * @return [type] [description]
	 */
	public function createDefaultFolders() {
		return FSX::createDefaultFolders();
	}


	/**
	 * Filter files.
	 * @return [type] [description]
	 */
	public function filterFiles($q) {
		return FSX::filterFiles($q);
	}


	/**
	 * Destroy/delete/remove file by ID.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function destroy($file, $user = null) {
		$user =$this->user->getCurrentUser();
		return FSX::destroy($file, $user);
	}

}