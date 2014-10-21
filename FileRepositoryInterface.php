<?php

interface FileRepositoryInterface {
	
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
	 * Download file based on token or ID.
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function download($data);


	/**
	 * Update file info.
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function update($data);


	/**
	 * Destroy/delete/remove file by ID.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function destroy($id);

}