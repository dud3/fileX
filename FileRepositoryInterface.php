<?php

interface FileRepositoryInterface {

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
	public function getFullTree();

	/**
	 * Get the full tree of folders.
	 * @return [type] [description]
	 */
	public function getFullTreePerUser();

	/**
	 * Get Folders that doesn't have
	 * -> sub-folders.
	 * @return [type] [description]
	 */
	public function getLeafNodes();


	/**
	 * Get Folders that doesn't have
	 * -> sub-folders.
	 * @return [type] [description]
	 */
	public function getSinglePath($path_name);


	/**
	 * Get user folders.
	 * @return [type] [description]
	 */
	public function user_folders($user_id = null);



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
	public function getAll();

	/**
	 * Get files by ID.
	 * @return [type] [description]
	 */
	public function getByID($id);

	/**
	 * Get files by user.
	 * @return [type] [description]
	 */
	public function getByUser($user_id = null);


	/**
	 * Get recent files per user.
	 * @param  [type] $user_id [user ID]
	 * @return [type]          [description]
	 */
	public function getRecentFiles($user_id = null);


	/**
	 * Get old files per user.
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	public function getOldFiles($user_id = null);

	/**
	 * Get files by folder.
	 * @return [type] [description]
	 */
	public function getByFolder($folder_id, $user_id);


 	/**
 	 * Upload files.
 	 * @param  [type] $data [description]
 	 * @return [type]       [description]
 	 */
	public function upload($data, $user, $addit_data = null);
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
	public function clone_file($file, $owner = null);

	/**
	 * Download file based on token or ID.
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function download($token, $user = null);

	/**
	 * Update file info.
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function update($data, $user = null);


	/**
	 * Get training files.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getTrainingFiles($id);

	/**
	 * Get training files.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getLicenseFiles($id);


	/**
	 * Create Default folders.
	 * @return [type] [description]
	 */
	public function createDefaultFolders();


	/**
	 * Filter files.
	 * @return [type] [description]
	 */
	public function filterFiles($q);


	/**
	 * Destroy/delete/remove file by ID.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function destroy($file, $user = null);

}