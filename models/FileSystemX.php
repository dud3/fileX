<?php 

/**
 * FileSystem manager.
 */
namespace FileSystemX\FileSystemXGateway;

/**
 * Exception handler.
 */
use \RuntimeException;
use ValidationException;

/**
 * Empty objects.
 */
use \stdClass;

/**
 * Laravel packages.
 */
use \Eloquent;
use \DB;
use Config;
use Input;
use Validator;
use Response;
use Paginator;

/**
 * Filesystem Eloquent and other function classes.
 */
use FileSystemX\File as FileX;
use FileSystemX\Folder as FolderX;
use FileSystemX\FileRelations as FileRelations;
use FileSystemX\FileDownloads as FileDownloads;

/**
 * External Interface.
 * Does not belong to a company.
 */
use \CompanyRepositoryInterface;


class FilesystemX {


	/**
	 * Default directory persmissions (destination dir)
	 */
	protected $default_permissions = 750;


	/**
	 * Destination folder on current local server.(where the app resides). 
	 * @var [string]
	 */
	private $destination_folder_local;


	/**
	 * Destination folder on the remote FTP server.
	 * @var [string]
	 */
	private	$destination_folder_remote;


	/**
	 * User Destionation foler.
	 * @var [type]
	 */
	private $destination_folder_user;


	/**
	 * Prefix for user creationg folder
	 * @var string
	 */
	private $destination_folder_user_prefx = "/user_";


	/**
	 * File img thumbnails folder.
	 * @var [type]
	 */
	private $destination_folder_fileThumb;


	/**
	 * List of default folders and their child.
	 * @var [type]
	 */
	private $default_folders = [
									"User" => [],
									"Misc" => [], 
									"Workitem" => [], 

									"Training" => 
										[
											"Continuation Training",						   
											"Type Training",
											"Basic Training",
											"Company Training",
											"Other Training"
										], 	

									"License" => [], 
									"Courses" => [], 
									"Report" => [], 
									"Aircraft" => [],
									"Employment/CV" => []
								];


	/**
	 * Maximum postable file size.
	 * @var [int]
	 */
	private	$POST_MAX_SIZE;


	/**
	 * Maximum uploadable file size.
	 * @var [int]
	 */
	private	$UPLOAD_MAX_SIZE;



	/**
	 * Maximum file suze(max 5MB).
	 * @var [type]
	 */
	private $maximum_file_size = 5000000;


	/**
	* Fileinfo
	*
	* @var object
	*/
	protected $finfo;


	/**
	* Data about file
	*
	* @var array
	*/
	public $file = array();

	
	/**
	 * File object from the request.
	 */
	public $fileObj;


	/**
	 * File name.
	 * @var [string]
	 */
	private $filename;

	/**
	 * Full path file name;
	 * @var [type]
	 */
	private $full_path_filename;

	/**
	 * File prefix
	 * @var string
	 */
	private $filename_prefix = "user_";


	/**
	* All mime types
	*
	* @var array
	*/
	protected $mimes = array();


	/**
	 * Allowed mime tyes
	 * @var array
	 */
	protected $allowed_mimes = array();


	/**
	 * The source of the file download.
	 * @var [type]
	 */
	private $file_download_route = "/api/v1/fileSystem/download/";


	/**
	 * $_notifcations Repositorie Interface.
	 * @var [interface]
	 *
	 * $user Repositories Interface.
	 * @var [interface]
	 *
	 * $company Repositories Interface.
	 * @var [interface]
	 *
	 * $workitem Repositories Interface.
	 * @var [interface]
	 *
	 * $training Repositories Interface.
	 * @var [interface] 
	 *
	 * $profile Repositories Interface.
	 * @var [interface]
	 * 
	 */
	private $_notifcations;
	private $user;
	private $company;
	private $workitem;
	private $training;
	private $profile;


	public function __construct() {

		$this->destination_folder_local = Config::get("constants.g_local_file_uploads");
		$this->destination_folder_remote = Config::get("constants.g_remote_file_uploads");
		$this->destination_folder_fileThumb = Config::get("constants.g_fileThumb_Uploads");

		$this->POST_MAX_SIZE = Config::get("constants.POST_MAX_SIZE");
		$this->UPLOAD_MAX_SIZE = Config::get("constants.UPLOAD_MAX_SIZE");

		$this->allowed_mimes();
		$this->createDefaultFolders();

	}
 	

	/**
	 * Eager Eloquent way of main query.
	 * @return [type] [description]
	 */
	public function mainQuery() {
		return FileRelations::with('filesx', 'foldersx');

	}


	/**
	 * Classicall way of file relations query.
	 * @return [type] [description]
	 */
	public function classicMainQuery() {

		$sql_string = 			

			"SELECT f.id AS file_id, f.private_token, f.url, f.filename, f.full_filename, f.description, f.uploader,
					f_r.user_id, f_r.training_id, f_r.license_id, f_r.workitem_id, f_r.aircraft_id,
					f.expires, f.public_allow, f.created_at, f.updated_at,
					fo.id AS folder_id, fo.name AS folder_name, fo.parent AS folder_parent,
					DATE_FORMAT(f_r.created_at, '%d/%m/%Y') AS created_at

			FROM file_relations f_r

				JOIN files f
					ON f_r.file_id = f.id

				LEFT JOIN folders fo
					ON f_r.folder_id = fo.id";
					

		return $sql_string;

	}

	/**
	 * Creates default folders and their child.
	 * @return [type] [description]
	 */
	public function createDefaultFolders() {

		$check_table = FolderX::where("id", ">", 0)->count();

		if($check_table == 0) {

			DB::table('folders')->truncate();

			foreach ($this->default_folders as $keyfolders => $sub) {

				$check_foldersx = FolderX::where("name", "=", $keyfolders)->first();
				$key_index = array_search($keyfolders, array_keys($this->default_folders));

				if($key_index === 0) {
					$key_index = null;
				} else {
					$key_index = 1;
				}

				if($check_foldersx === null) {
					FolderX::create(["name" => $keyfolders, "parent" => $key_index]);
				}

				if(is_array($sub)) {
					foreach ($sub as $subkey => $child) {

						$parent_id = array_search($keyfolders, array_keys($this->default_folders));
						$check_sub_foldersx = FolderX::where("name", "=", $child)->first();

						if($check_sub_foldersx === null) {
							FolderX::create(["name" => $child, "parent" => $parent_id + 1]);

						}
					}
				}

			}
		}
	}


	// ---------------------------------------------------
	// Folder/Tree Section
	// ---------------------------------------------------
	// 
	// All the Folder/Tree nesscecary
	// -> manipulations goes here...
	// 
	// ---------------------------------------------------

	/**
	 * Get the full tree structure.
	 * All the folders and sub-folders. 
	 * @return [type] [description]
	 */
	public function getFullTree() {

		$sql_full_tree = DB::select(
			
			"SELECT t1.name AS lev1, t1.id as lev1_id,
					t2.name as lev2, t2.id as lev2_id, 
					t3.name as lev3  t3.id as lev3_id, 
					t4.name as lev4, t4.id as lev4_id

			FROM folders AS t1

				LEFT JOIN folder AS t2 
					ON t2.parent = t1.id

				LEFT JOIN folder AS t3 
					ON t3.parent = t2.id

				LEFT JOIN folder AS t4 
					ON t4.parent = t3.id

			WHERE t1.name = 'User'"

		);

		return $sql_full_tree;

	} 


	/**
	 * Get the full tree structure.
	 * All the folders and sub-folders. 
	 * @return [type] [description]
	 */
	public function getFullTreePerUser() {

		$sql_full_tree = DB::select(
			
			"SELECT t1.name AS lev1, t1.id as lev1_id,
					t2.name as lev2, t2.id as lev2_id, 
					t3.name as lev3  t3.id as lev3_id, 
					t4.name as lev4, t4.id as lev4_id

			FROM folders AS t1

				LEFT JOIN folder AS t2 
					ON t2.parent = t1.id

				LEFT JOIN folder AS t3 
					ON t3.parent = t2.id

				LEFT JOIN folder AS t4 
					ON t4.parent = t3.id

			WHERE t1.name = 'User'" 

		);

		return $sql_full_tree;

	} 



	/**
	 * Get Folders that doesn't have
	 * -> sub-folders.
	 * @return [type] [description]
	 */
	public function getLeafNodes() {

		$sql_leaf_nodes = DB::select(

			"SELECT t1.name 

			FROM folders AS t1 

			LEFT JOIN category as t2
				ON t1.category_id = t2.parent

			WHERE t2.category_id IS NULL"

		);

		return $sql_leaf_nodes;

	}


	/**
	 * Get a sinle path/folder and it's 
	 * -> child paths/folders.
	 * @return [type] [description]
	 */
	public function getSinglePath($path_name) {

		$sql_full_tree = DB::select(
			
				"SELECT t1.name AS lev1, t1.id as lev1_id,
						t2.name as lev2, t2.id as lev2_id, 
						t3.name as lev3  t3.id as lev3_id, 
						t4.name as lev4, t4.id as lev4_id

				FROM folders AS t1

					LEFT JOIN folder AS t2 
						ON t2.parent = t1.id

					LEFT JOIN folder AS t3 
						ON t3.parent = t2.id

					LEFT JOIN folder AS t4 
						ON t4.parent = t3.id

				WHERE t1.name = 'User'
				AND t4.name = ?", 

		[$path_name]);

		return $sql_full_tree;

	}


	/**
	 * Ger Folders and sub-folders by user_id;
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	public function user_folders($user_id) {

		$_folder_parent;
		$_child_folders;

		// Get each folder and 
		// -> parent ids.
		// A file can belong to a root folder
		// -> and/or to the child folders.
		$file_relations = DB::select(

				"SELECT DISTINCT fo.id, fo.parent

					FROM file_relations f_r

					LEFT JOIN folders fo 
						ON f_r.folder_id = fo.id

				WHERE f_r.user_id = ?
				AND f_r.folder_id <> ''"
			
		,[$user_id]);

		// Get the parent folder
		$_folder_parent = DB::select(

			"SELECT *

				FROM folders fo

			WHERE fo.id IN(3, 4, 10, 13)
			
		");

	
		foreach ($_folder_parent as $parent) {
			
			// Get the child folder
			$_folder_child = DB::select(

				"SELECT *

					FROM folders fo

				WHERE fo.parent = ? "

			,[$parent->id]);

			$parent->child = $_folder_child;

		}


		foreach ($_folder_parent as $parent) {

			if(!empty($parent->child) || count($parent->child) > 0) {
				$sql_count = " WHERE fo.parent = ? ";
			} else {
				$sql_count = " WHERE fo.id = ? ";
			}

			// Count files per each folder
			$_folder_files_count = DB::select(

				"SELECT count(f_r.id) as count_obj

					FROM folders fo

					LEFT JOIN file_relations f_r
						ON fo.id = f_r.folder_id

				" . $sql_count . "
				AND f_r.user_id = ? "

			,[$parent->id, $user_id])[0]->count_obj;

			$parent->count_files = $_folder_files_count;

		}

		return $_folder_parent;

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
		return $this->mainQuery()->get();
	}


	/**
	 * Get files by ID.
	 * @return [type] [description]
	 */
	public function getByID($id) {
		return $this->mainQuery()->where('file_id', '=', $id)->get();
	}


	/**
	 * Get files by user.
	 * @return [type] [description]
	 */
	public function getByUser($user_id) {

		$sql = DB::select(
		
			$this->classicMainQuery() .	" WHERE f_r.user_id = ?

			ORDER BY file_id DESC",

		[$user_id]);


		foreach ($sql as $key => $file) {

			$file->full_file_path = $this->get_full_filename($file->user_id, $file->url);
			$file->file_exits = file_exists($file->full_file_path);

			if(file_exists($file->full_file_path)) {

				$file->size = $this->get_real_size($file->full_file_path);
				$file->download_url = $this->file_download_route . $file->private_token;
				$file->file_extension = pathinfo($file->full_file_path, PATHINFO_EXTENSION);

				if($file->file_extension == "jpeg" 
					|| $file->file_extension == "jpg" 
					|| $file->file_extension == "png"
					|| $file->file_extension == "bmp" 
					|| $file->file_extension == "gif") {

					// $file->file_thumbnail = $this->genImgThumbnail($file->full_file_path);

				}

			} else {

				// Not sure if we should splice
				// the array if the real file does
				// -> not exits...
				array_splice($sql, $key, 1);

			}

		}

		return $sql;

	}

	/**
	 * Get recen files per user.
	 * @return [type] [description]
	 */
	public function getRecentFiles($user_id) {

		$sql = DB::select(
		
			$this->classicMainQuery() .	" WHERE f_r.user_id = ?

			AND f_r.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)

			ORDER BY file_id DESC",

		[$user_id]);


		foreach ($sql as $key => $file) {

			$file->full_file_path = $this->get_full_filename($file->user_id, $file->url);
			$file->file_exits = file_exists($file->full_file_path);

			if(file_exists($file->full_file_path)) {

				$file->size = $this->get_real_size($file->full_file_path);
				$file->download_url = $this->file_download_route . $file->private_token;
				$file->file_extension = pathinfo($file->full_file_path, PATHINFO_EXTENSION);
				
				if($file->file_extension == "jpeg" 
					|| $file->file_extension == "jpg"
					|| $file->file_extension == "png"
					|| $file->file_extension == "bmp" 
					|| $file->file_extension == "gif") {
					
					// $file->file_thumbnail = $this->genImgThumbnail($file->full_file_path);

				}

			} else {

				// Not sure if we should splice
				// the array if the real file does
				// -> not exits...
				array_splice($sql, $key, 1);

			}

		}

		return $sql;

	}


	/**
	 * Get old files per user.
	 * @return [type] [description]
	 */
	public function getOldFiles($user_id) {

		$sql = DB::select(
		
			$this->classicMainQuery() .	" WHERE f_r.user_id = ?

			AND f_r.created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)

			ORDER BY file_id DESC",

		[$user_id]);


		foreach ($sql as $key => $file) {

			$file->full_file_path = $this->get_full_filename($file->user_id, $file->url);
			$file->file_exits = file_exists($file->full_file_path);

			if(file_exists($file->full_file_path)) {

				$file->size = $this->get_real_size($file->full_file_path);
				$file->download_url = $this->file_download_route . $file->private_token;
				$file->file_extension = pathinfo($file->full_file_path, PATHINFO_EXTENSION);
				
				if($file->file_extension == "jpeg" 
					|| $file->file_extension == "jpg"
					|| $file->file_extension == "png"
					|| $file->file_extension == "bmp" 
					|| $file->file_extension == "gif") {
					
					// $file->file_thumbnail = $this->genImgThumbnail($file->full_file_path);

				}

			} else {

				// Not sure if we should splice
				// the array if the real file does
				// -> not exits...
				array_splice($sql, $key, 1);

			}

		}

		return $sql;

	}


	/**
	 * Get training files.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getTrainingFiles($training_id) {

		$sql = DB::select(
			
			$this->classicMainQuery() . " WHERE f_r.training_id = ?

			ORDER BY file_id DESC",

		[$training_id]);


		foreach ($sql as $key => $file) {

			$file->full_file_path = $this->get_full_filename($file->user_id, $file->url);
			$file->file_exits = file_exists($file->full_file_path);

			if(file_exists($file->full_file_path)) {

				$file->size = $this->get_real_size($file->full_file_path);
				$file->download_url = $this->file_download_route . $file->private_token;
				$file->file_extension = pathinfo($file->full_file_path, PATHINFO_EXTENSION);
				
				if($file->file_extension == "jpeg" 
					|| $file->file_extension == "jpg"
					|| $file->file_extension == "png"
					|| $file->file_extension == "bmp" 
					|| $file->file_extension == "gif") {
					
					// $file->file_thumbnail = $this->genImgThumbnail($file->full_file_path);

				}

			} else {

				// Not sure if we should splice
				// the array if the real file does
				// -> not exits...
				array_splice($sql, $key, 1);

			}

		}

		return $sql;
	}



	/**
	 * Get training files.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getLicenseFiles($license_id) {

		$sql = DB::select(
			
			$this->classicMainQuery() . " WHERE f_r.license_id = ?

			ORDER BY file_id DESC",

		[$license_id]);


		foreach ($sql as $key => $file) {

			$file->full_file_path = $this->get_full_filename($file->user_id, $file->url);
			$file->file_exits = file_exists($file->full_file_path);

			if(file_exists($file->full_file_path)) {

				$file->size = $this->get_real_size($file->full_file_path);
				$file->download_url = $this->file_download_route . $file->private_token;
				$file->file_extension = pathinfo($file->full_file_path, PATHINFO_EXTENSION);
				
				if($file->file_extension == "jpeg"
					|| $file->file_extension == "jpg"
					|| $file->file_extension == "png"
					|| $file->file_extension == "bmp" 
					|| $file->file_extension == "gif") {
					
					// $file->file_thumbnail = $this->genImgThumbnail($file->full_file_path);

				}

			} else {

				// Not sure if we should splice
				// the array if the real file does
				// -> not exits...
				array_splice($sql, $key, 1);

			}

		}

		return $sql;
	}


	/**
	 * Get files by user.
	 * @return [type] [description]
	 */
	public function getByFileRelation($param = null) {

		$query = $this->mainQuery();

		if($param === null) {
			
			$query = $this->getByUser();

		} else {

			if(isset($param['file_id'])) {
				$query = $query->where('file_id', '=', $param['file_id']);
			}

			if(isset($param['user_id'])) {
				$query = $query->where('user_id', '=', $param['user_id']);
			}

			if(isset($param['company_id'])) {
				$query = $query->where('company_id', '=', $param['company_id']);
			}

			if(isset($param['group_id'])) {
				$query = $query->where('group_id', '=', $param['group_id']);
			}

			if(isset($param['workitem_id'])) {
				$query = $query->where('workitem_id', '=', $param['workitem_id']);
			}

			if(isset($param['training_id'])) {
				$query = $query->where('training_id', '=', $param['training_id']);
			}

			if(isset($param['folder_id'])) {
				$query = $query->where('folder_id', '=', $param['folder_id']);
			}

			if(isset($param['hidden'])) {
				$query = $query->where('hidden', '=', $param['hidden']);
			}

			if(isset($param['created_at'])) {
				$query = $query->where('created_at', '=', $param['created_at']);
			}

			$query = $query->get();
		}

		return $query;
	}


	/**
	 * Get file by name.
	 * @param  [type] $filename [description]
	 * @return [type]           [description]
	 */
	public function filterFiles($filename) {

		$sql = DB::select(DB::raw(
			
			$this->classicMainQuery() . " WHERE f_r.folder_id LIKE '%" . $filename . "%'

			ORDER BY file_id DESC"

		));

		foreach ($sql as $key => $file) {

			$file->full_file_path = $this->get_full_filename($file->user_id, $file->url);
			$file->file_exits = file_exists($file->full_file_path);

			if(file_exists($file->full_file_path)) {
				$file->size = $this->get_real_size($file->full_file_path);
				$file->download_url = $this->file_download_route . $file->private_token;
			} else {

				// Not sure if we should splice
				// the array if the real file does
				// -> not exits...
				array_splice($sql, $key, 1);

			}

		}

	}


	/**
	 * Get files by folder.
	 * @return [type] [description]
	 */
	public function getByFolder($folder_id, $user_id) {

		$child_arr = [];
		$sql_folders = ""; 

		try {

			// Default
			$sql_folders = " AND f_r.folder_id = " . $folder_id;

			$check_child = DB::select(
				
				"SELECT id

					FROM folders

				WHERE parent = ? "

			, [$folder_id]);

			if(!empty($check_child)) {
				foreach ($check_child as $child) {
					array_push($child_arr, $child->id);
				}

				$child_arr = implode(", ", $child_arr);
				$sql_folders = " AND f_r.folder_id IN ( " .$child_arr. " )";
			}

			$sql = DB::select(
				
				$this->classicMainQuery() 

				. " WHERE f_r.user_id = ? " 

				. $sql_folders . 
				
				" ORDER BY f_r.file_id DESC "

			,[$user_id]);

			foreach ($sql as $key => $file) {

				$file->full_file_path = $this->get_full_filename($file->user_id, $file->url);
				$file->file_exits = file_exists($file->full_file_path);

				if(file_exists($file->full_file_path)) {
					$file->size = $this->get_real_size($file->full_file_path);
					$file->download_url = $this->file_download_route . $file->private_token;
				} else {

					// Not sure if we should splice
					// the array if the real file does
					// -> not exits...
					array_splice($sql, $key, 1);

				}

			}

			return $sql;

		}  catch (RuntimeException $e) {

    		$error = new stdClass();
    		$error->message = $e->getMessage();
    		$error->code = $e->getCode();
    		$error->error = true;

    		return $error;

		}
	}



 	/**
 	 * Upload files.
 	 * @param  [type] $data [description]
 	 * @return [type]       [description]
 	 */
	public function upload($data, $user, $addit_data) {

		$std_file = new stdClass();

		$this->fileObj = $data;

		$this->file['path'] = $this->fileObj->getRealPath();
		$this->file['name'] = $this->fileObj->getClientOriginalName();
		$this->file['extension'] = $this->fileObj->getClientOriginalExtension();
		$this->file['basename'] = basename($this->file['name'], "." . $this->file['extension']);
		$this->file['size'] = $this->fileObj->getSize();
		$this->file['mimetype'] = strtolower($this->fileObj->getMimeType());

		// Additional filde data
		// -> such as file description
		// -> new creation folder info and such...
		// $this->file['additional_file_data'] = json_decode($addit_data);
		$this->file['additional_file_data'] = new stdClass();
		$this->file['additional_file_data']->folder = "";

		$user_id = $user->id;
		$user_name = $user->first_name . " " . $user->last_name;

		// Prefix for the user files.
		$this->filename_prefix = $this->filename_prefix . $user_id . "_";

		// Filename Transform to lowercase
		$str_low = strtolower($this->file['name']);
		$str_low = str_replace(',', '_', $str_low);

		// The user filename.
		$this->filename = $this->filename_prefix . str_replace(' ', '_', $str_low);
		// Destination folder of the user
		$this->destination_folder_user = $this->destination_folder_local . $this->destination_folder_user_prefx . $user_id;
		// Full path name of the file
		$this->full_path_filename = $this->destination_folder_user . "/" . $this->filename;

		$valid_data = [
		    'url' => $this->filename,
        	'filename' =>  $this->file['basename'],
        	'full_filename' => $this->file['name']
        ];

		try {
			
			$this->check_destination();
			$this->check_user_destination($this->destination_folder_user);

				if($this->validate_file($valid_data)) {

					if(in_array($this->file['mimetype'], $this->allowed_mimes)) {
					
						if($this->file['size'] < $this->maximum_file_size) {

							if(!file_exists($this->full_path_filename)) {
								$uploadFile = $this->fileObj->move($this->destination_folder_user, $this->filename);
							} else {
								$this->filename = $this->filename_prefix . $this->getRandomString($length = 5) . "_" . $this->file['name'];
								$this->full_path_filename = $this->destination_folder_user . "/" . $this->filename;
								$uploadFile = $this->fileObj->move($this->destination_folder_user, $this->filename);
							}

								if($uploadFile) {

									//
									// Generate a PDF thumbnails and other type of image
									// -> thumbnails.
									// 
									if($this->file["mimetype"] == "application/pdf") {
										
										$this->genPdfThumbnail($this->full_path_filename, $this->filename);

									} elseif($this->file["mimetype"] == "image/jpeg" || $this->file["mimetype"] == "image/png"
											 || $this->file["mimetype"] == "image/bmp" || $this->file["mimetype"] == "image/gif") {

										$this->genImgThumbnail($this->full_path_filename, $this->filename);
									
									}

									$file_model = FileX::create([
											'private_token' => $this->getRandomString($length = 45),
											'url' => $this->filename,
											'filename' => $this->file['basename'],
											'full_filename' => $this->file['name'],
											'uploader' => $user_name
											// 'description' => $this->file['additional_file_data']->description
										]);

										if($file_model || !empty($file_model)) {

											// if we actually want to create a folder
											/*
											if(isset($this->file['additional_file_data']->folder) 
												&& !empty($this->file['additional_file_data']->folder)
												&& strlen($this->file['additional_file_data']->folder) !== 0) {
											
												$folder_exists = FolderX::where('name', '=', $this->file['additional_file_data']->folder)
																 ->first();

													if($folder_exists === null) {

														$folder_model =	FolderX::create([
																'parent' => $this->destination_folder_user,
																'name' => $this->file['additional_file_data']->folder,
																'user_id' => $user_id
															]);

													} else {

														$folder_model = new stdClass();
														$folder_model->id = $folder_exists->id;
														$folder_model->parent = $folder_exists->parent;
														$folder_model->name = $folder_exists->name;

													}

												} else {

													$folder_model = new stdClass();
													$folder_model->id = NULL;
													$folder_model->parent = NULL;
													$folder_model->name = NULL;

												}
												*/

												// if($folder_model || !empty($folder_model)) {

													$file_relations = FileRelations::create([
															'file_id' => $file_model->id,
															'user_id' => $user_id,
															'workitem_id' => (!empty($this->file['additional_file_data']->workitem_id)) ?: NULL,
															'training_id' => (!empty($this->file['additional_file_data']->training_id)) ?: NULL,
															'folder_id' => null
														]);

														if($file_relations || !empty($file_relations)) {

															$std_file->file_id = $file_model->id;
															$std_file->private_token = $file_model->private_token;
															$std_file->url = $file_model->url;
															$std_file->filename = $file_model->filename;
															$std_file->full_filename = $file_model->full_filename;
															$std_file->cut_full_filename = (strlen($file_model->full_filename) > 39) ? substr($file_model->full_filename, 0, 40) . "..." : $file_model->full_filename;
															$std_file->description = $file_model->description;
															$std_file->folder_id = null;
															$std_file->parent_folder = null;
															$std_file->folder = null;
															$std_file->download_url = $this->file_download_route . $file_model->private_token;
															$std_file->size = $this->get_real_size($this->full_path_filename);
															$std_file->created_at = date("d/m/Y", strtotime($file_relations->created_at));
															$std_file->error = false;

															return $std_file;
														
													} else {
														throw new RuntimeException("Unable to insert into file ralations table.", 0.1);
													}


												// } else {
												//	throw new RuntimeException("Unable to insert into folder table..", 0.2);
												// }

										} else {
											throw new RuntimeException("Unable to insert into file table.", 0.3);
										}
								
								} else {
									throw new RuntimeException("Extended maximum file upload size, only 5MB allowed.", 1);
								}

						} else {
							throw new RuntimeException("Extended maximum file upload size, only 5MB allowed.", 1.2);
						}

					} else {
						throw new RuntimeException("Wrong mime type, only 'doc'/'gif'/'jpeg'/'jpeg'/'jpg'/'jpg' are allowed", 1.3);
					}

				}

		} catch (RuntimeException $e) {

    		$error = new stdClass();
    		$error->message = $e->getMessage();
    		$error->code = $e->getCode();
    		$error->error = true;

    		return $error;

		}
		
	}


	/**
	 * Download file based on token or ID.
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function download($token, $user) {

		$user_id = $user->id;
		$user_company = $user->company_id;

		$file_by_token = FileX::where('private_token', '=', $token)->first();

		if($file_by_token !== null) {

			$file_relations = FileRelations::where('file_id', '=', $file_by_token->toArray()["id"])->first()->toArray();

			$file_user_id = $file_relations["user_id"];

			$file_access = DB::select('SELECT count(*) as belongs_to_company 
									 FROM users WHERE id = ? AND company_id = ?', 
									 [$file_user_id, $user_company])[0];

			if($file_access !== null) {
				$file_access = $file_access->belongs_to_company;
			} else {
				$file_access = 0;
			}

		}
		
		try {

			if($file_by_token !== null) {
				
				$file_relations = $this->getByFileRelation(["file_id" => $file_by_token->id])->first()["attributes"];

				if($user_id == $file_relations["user_id"] || $file_access > 0) {

					$this->full_filename = $this->get_full_filename($file_relations["user_id"], $file_by_token->url);

					if(file_exists($this->full_filename)) {

						while (ob_get_level()) ob_end_clean();
						header('Content-Type: application/octet-stream');
						header('Content-Disposition: attachment; filename='.basename($this->full_filename));
						header('Expires: 0');
						header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
						header('Pragma: public');
						header('Cache-Control: private',false);
						header('Content-Length: ' . $this->get_real_size($this->full_filename));
						header('Connection: close');
						readfile($this->full_filename);
						exit;

					} else {
						throw new RuntimeException("File not found.", 0.1);
					}

				} else {
					throw new RuntimeException("This file doesn not belong to the current user.", 0.2);
				}

			} else {
				throw new RuntimeException("Invalid Token.", 0.3);
			}

		} catch (RuntimeException $e) {

			$error = new stdClass();
    		$error->message = $e->getMessage();
    		$error->code = $e->getCode();
    		$error->error = true;

    		return $error;
			
		}

	}


	/**
	 * Update file info.
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function update($data, $user) {

		$update_files = [];
		$std_file = new stdClass();

		foreach ($data as $key => $file) {

			$file_relations = $this->getByID($file["id"])->first();

			if($file_relations !== null) {

				$std_file->id = $file_relations["attributes"]["id"];

					if(isset($file["folder"]) || !empty($file["folder"])) {
						$_folder = $this->createFolder($file["folder"], $type = 'training');
						$file_relations->fill(["folder_id" => $_folder["id"]]);

						$std_file->folder_id = $_folder["id"];
						$std_file->folder_name = $_folder["name"];
					}

					if(isset($file["training_id"]) || !empty($file["training_id"])) {
						$file_relations->fill(["training_id" => $file["training_id"]]);
						$file_relations->save();

						$std_file->training_id = $file_relations->training_id;
					}

					if(isset($file["license_id"]) || !empty($file["license_id"])) {
						$file_relations->fill(["license_id" => $file["license_id"]]);
						$file_relations->save();

						$std_file->license_id = $file_relations->license_id;
					}

					if(isset($file["aircraft_id"]) || !empty($file["aircraft_id"])) {
						$file_relations->fill(["aircraft_id" => $file["aircraft_id"]]);
						$file_relations->save();
						
						$std_file->license_id = $file_relations->license_id;
					}

					if(isset($file["employment_id"]) || !empty($file["employment_id"])) {
						$file_relations->fill(["employment_id" => $file["employment_id"]]);
						$file_relations->save();
						
						$std_file->license_id = $file_relations->license_id;
					}

				array_push($update_files, $std_file);

			}

		}

		return $update_files;
	}


	/**
	 * Create folder.
	 * @param  [type] $folder_name [description]
	 * @return [type]              [description]
	 */
	public function createFolder($folder_name, $type) {
		
		$folder = FolderX::where("name", '=', $folder_name)->first();

		if($folder === null) {
			if($type == 'training') {
				$folder = FolderX::create(["name" => $folder_name,
											"parent" => 3]);
			} else {
				$folder = FolderX::create(["name" => $folder_name]);
			}
		}

		return $folder->toArray();

	}


	/**
	 * Destroy/delete/remove file by ID.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function destroy($id, $user) {
		
		$eager_file = $this->getByID($id)->first();
		$file_relations = $eager_file["attributes"];
		$file = $eager_file["relations"]["filesx"]["attributes"];
		$folder = $eager_file["relations"]["foldersx"]["attributes"];

		$user_id = $user->id;
		$user_company = $user->company_id;
		$permissions = $this->file_permission('company', $file_relations["user_id"], ["company_id" => $user_company]);

		try {

			if($user_id == $file_relations["user_id"] || $permissions > 0) {

				if($file || !empty($file)) {

					$this->full_filename = $this->get_full_filename($file_relations["user_id"], $file["url"]);

						if(file_exists($this->full_filename)) {

							$delete_file = $this->delete_file($this->full_filename);

								if($delete_file) {

									$_file_delete = FileX::find($id)->delete();
									$file_relations = FileRelations::where("file_id", '=', $id)->delete();

										if($_file_delete && $file_relations) {

											$std_file = new stdClass();
											$std_file->delete = true;
											$std_file->message = "File deleted successfully";
											$std_file->error = false;

											return $std_file;

										} else {
											throw new RuntimeException("Eloquent file unable to delete", 1);
										}

								} else {
									throw new RuntimeException("Unable to remove the file from the disk.", 0.1);
								}

						} else {
							throw new RuntimeException("File not found.", 0.2);
						}

				} else {
					throw new RuntimeException("Eloquent File does not exits.", 1.2);
				}

			} else {
				throw new RuntimeException("This file doesn not belong to the current user.", 1.1);
			}

		} catch (RuntimeException $e) {

			$error = new stdClass();
    		$error->message = $e->getMessage();
    		$error->code = $e->getCode();
    		$error->error = true;

    		return $error;

		}

	}

	/**
	 * Destroy by different type of file attributes.
	 * @param  [string] $type [type of item destroy]
	 * @param  [int] $data [id of the item]
	 * @return [type]       [description]
	 */
	public function destroyBy($type, $id) {

		switch ($type) {

			case 'user':
				# code...
				break;

			case 'company':
				# code...
				break;

			case 'group':
				# code...
				break;

			case 'training':

					$training_file = $this->getTrainingFiles($id);
					$file_relations = FileRelations::where("training_id", '=', $id)->first();
					$file_relations = $file_relations["attributes"];

					foreach ($training_file as $t_file) {

						FileX::where("id", "=", $t_file->file_id)->delete();
							
						$this->full_filename = $this->get_full_filename($file_relations["user_id"], $t_file->url);
						$delete_file = $this->delete_file($this->full_filename);

					}

					$file_relations = FileRelations::where("training_id", '=', $id)->delete();

				break;

			case 'workitem':
				# code...
				break;

			case 'aircraft':
				# code...
				break;

			case 'employment':
				# code...
				break;

			case 'license':
				
					$license_file = $this->getLicenseFiles($id);
					$file_relations = FileRelations::where("license_id", '=', $id)->first();
					$file_relations = $file_relations["attributes"];

					foreach ($license_file as $t_file) {

						FileX::where("id", "=", $t_file->file_id)->delete();
							
						$this->full_filename = $this->get_full_filename($file_relations["user_id"], $t_file->url);
						$delete_file = $this->delete_file($this->full_filename);

					}

					$file_relations = FileRelations::where("training_id", '=', $id)->delete();

				break;
				
		}

	}


	// ---------------------------------------------------
	// Files/Folder Helper functions/methods
	// ---------------------------------------------------
	// 
	// All the Files/Folders helper functions/methods
	// -> reside here, such as:
	// 
	// * Validators
	// * Server side - Folder Creation
	// * Server side - File Creation
	// * Server side - File/folder checkers
	// * Random string generators
	// * PDF thumbnail generators
	// * Mime types
	// * Server side - File size converters
	// * Server side - File size checkers
	// 
	// More to be added...
	// 
	// ---------------------------------------------------

	/**
	 * Validate the file data.
	 * @return [type] [description]
	 */
	public function validate_file($data) {

			$validator = Validator::make($data, FileX::$rules);
			if($validator->fails()) throw new ValidationException($validator);
			return true;
	}


	/** 
	 * Simply way of checking file permissions.
	 * @param  [type] $type         [description]
	 * @param  [type] $file_user_id [description]
	 * @param  [type] $data         [description]
	 * @return [type]               [description]
	 */
	public function file_permission($type, $file_user_id, $data) {

			$file_access = null;

			// No full permission check yet
			// -> only check if the user belongs to that collection
			// -> and give the permission and not only if
			// -> it is the higher permissed user, since we didn't decide
			// -> group/permissions system properly...
			// 
			// Basically check if the user belongs to that current 
			// -> company and allow them to share files.
			// 
			switch ($type) {
				case 'company':
					$file_access = DB::select('SELECT count(*) as belongs_to_company 
												 FROM users WHERE id = ? 
												 AND company_id = ?', 
												 [$file_user_id, $data["company_id"]])[0];
					break;
				
				default:
					# code...
					break;
			}

			

			if($file_access !== null) {
				$file_access = $file_access->belongs_to_company;
			} else {
				$file_access = 0;
			}

			return $file_access;

	}


	/**
	 * check_if_SQL_files_exits
	 * 
	 * Check if the file_names that comes from the
	 * _> SQL statement excist on the machine hard-drive or not.
	 * If the "real files" doesn't exits, simply don't show them.
	 * 
	 * @return [object] [objcet of real files]
	 */
	public function check_if_SQL_files_exits($sql) {

		foreach ($sql as $key => $file) {

			$file->full_file_path = $this->get_full_filename($file->user_id, $file->url);
			$file->file_exits = file_exists($file->full_file_path);

			if(file_exists($file->full_file_path)) {

				$file->size = $this->get_real_size($file->full_file_path);
				$file->download_url = $this->file_download_route . $file->private_token;
				$file->file_extension = pathinfo($file->full_file_path, PATHINFO_EXTENSION);
				
				if($file->file_extension == "jpeg" 
					|| $file->file_extension == "jpg"
					|| $file->file_extension == "png"
					|| $file->file_extension == "bmp" 
					|| $file->file_extension == "gif") {
					
					// $file->file_thumbnail = $this->genImgThumbnail($file->full_file_path);

				}

			} else {

				// Not sure if we should splice
				// the array if the real file does
				// -> not exits...
				array_splice($sql, $key, 1);

			}

		}

		return $sql;

	}


	/**
	 * Get full file name based on file and user data.
	 * @param  [int] 	$user_id   [user ID]
	 * @param  [string] $file_name [name of the file]
	 * @return [string]            [full path of the file]
	 */
	public function get_full_filename($user_id, $file_name) {
		
		// The user filename.
		$this->filename = $file_name;

		// Destination folder of the user
		$this->destination_folder_user = $this->destination_folder_local . $this->destination_folder_user_prefx . $user_id;
		
		// Full path name of the file
		$this->full_path_filename = $this->destination_folder_user . "/" . $this->filename;


		return $this->full_path_filename;

	}


	/**
	 * Set target filename
	 * 
	 * @param string $filename
	 */
	public function set_filename($filename) {
		
		$this->filename = $filename;
		
	}


	/**
	 * Check if the folder exits.
	 * @return [type] [description]
	 */
	protected function check_destination() {

		if(!file_exists($this->destination_folder_local)) {
			$this->create_destination();
		}

	}
	
	/**
	 * Check if the thumbnails folder exits.
	 * @param  [type] $destination [description]
	 * @return [type]              [description]
	 */
	protected function check_thumbnails_destination($destination) {

		if(!file_exists($destination)) {
			$this->create_thumbnails_destination($destination);
		}

	}



	/**
	 * Check if the user folder exits.
	 * @param  [type] $destination [description]
	 * @return [type]              [description]
	 */
	protected function check_user_destination($destination) {

		if(!file_exists($destination)) {
			$this->create_user_destination($destination);
		}

	}


	/**
	 * Create path to destination
	 * 
	 * @param string $dir
	 * @return bool
	 */
	protected function create_destination() {
		
		return mkdir($this->destination_folder_local, $this->default_permissions, true);
		
	}


	/**
	 * Create path based on destination
	 * 
	 * @return [string] Path name.
	 */
	public function create_thumbnails_destination($destination) {

		return mkdir($destination, $this->default_permissions, true);
	}


	/**
	 * Create path to destination
	 * 
	 * @param string $dir
	 * @return bool
	 */
	protected function create_user_destination($destination) {
		
		return mkdir($destination, $this->default_permissions, true);
		
	}
	
	
	/**
	 * Set unique filename
	 * 
	 * @return string
	 */
	protected function create_new_filename($destination, $user_id) {
		
		$filename = "user_" . $user_id . "_" . sha1(mt_rand(1, 9999) . $destination . uniqid()) . time();
		$this->set_filename($filename);
		
	}
	

	/**
	 * Generate a random string.
	 *
	 * @return string
	 */
	public function getRandomString($length)
	{

		// Set a default length
		if(empty($length) || $length == null) 
		{
			$length = 42;
		}

		// We'll check if the user has OpenSSL installed with PHP. If they do
		// we'll use a better method of getting a random string. Otherwise, we'll
		// fallback to a reasonably reliable method.
		if (function_exists('openssl_random_pseudo_bytes'))
		{
			// We generate twice as many bytes here because we want to ensure we have
			// enough after we base64 encode it to get the length we need because we
			// take out the "/", "+", and "=" characters.
			$bytes = openssl_random_pseudo_bytes($length * 2);

			// We want to stop execution if the key fails because, well, that is bad.
			if ($bytes === false)
			{
				throw new \RuntimeException('Unable to generate random string.');
			}

			return substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $length);
		}

		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);

	}

	
	/**
	 * Convert bytes to mb.
	 *  
	 * @param int $bytes
	 * @return int
	 */
	protected function bytes_to_mb($bytes) {
		
		return round(($bytes / 1048576), 2);
		
	}


	/**
	 * Allowed mimes.
	 * @return [type] [description]
	 */
	public function allowed_mimes() {

		$this->set_mimes();
		
		$this->allowed_mimes = [

			'pdf' => $this->mimes['.pdf'],
			'doc' => $this->mimes['.doc'],

			'gif' => $this->mimes['.gif'],
			'jpeg' => $this->mimes['.jpeg'],
			'jpg' => $this->mimes['.jpg'],
			'pjpeg' => $this->mimes['.pjpeg'],
			'pjpg' => $this->mimes['.pjpeg'],
			'png' => $this->mimes['.png'],
			'x-png' => $this->mimes['.x-png'],
			'bmp' => $this->mimes['.bmp'],
			'bmpw' => $this->mimes['.bmpw'],

			'gzip' => $this->mimes['.gzip'],
			'gtar' => $this->mimes['.gtar'],
			'qtif' => $this->mimes['.qtif'],
			'tif' => $this->mimes['.tif'],
			'tifx' => $this->mimes['.tifx'],
			'tiff' => $this->mimes['.tiff'],
			'tiffx' => $this->mimes['.tiffx'],

			'docx' => $this->mimes['.docx'],
			'xlsx' => $this->mimes['.xlsx'],
			'xltx' => $this->mimes['.xltx'],
			'potx' => $this->mimes['.potx'],
			'ppsx' => $this->mimes['.ppsx'],
			'pptx' => $this->mimes['.pptx'],
			'sldx' => $this->mimes['.sldx'],
			'dotx' => $this->mimes['.dotx'],
			'xlam' => $this->mimes['.xlam'],
			'xlsb' => $this->mimes['.xlsb'],

			'odt' => $this->mimes['.odt'],
			'ott' => $this->mimes['.ott'],
			'oth' => $this->mimes['.oth'],
			'odm' => $this->mimes['.odm'],
			'odg' => $this->mimes['.odg'],
			'otg' => $this->mimes['.otg'],
			'odp' => $this->mimes['.odp'],
			'otp' => $this->mimes['.otp'],
			'ods' => $this->mimes['.ods'],
			'ots' => $this->mimes['.ots'],
			'odc' => $this->mimes['.odc'],
			'odf' => $this->mimes['.odf'],
			'odb' => $this->mimes['.odb'],
			'odi' => $this->mimes['.odi'],
			'oxt' => $this->mimes['.oxt'],

			'zipxc' => $this->mimes['.zipxc'],
			'zipxz' => $this->mimes['.zipxz'],
			'zip' => $this->mimes['.zip'],
			'zipx' => $this->mimes['.zipx'],
			'zoo' => $this->mimes['.zoo'],
			'rar' => $this->mimes['.rar'],
			'raro' => $this->mimes['.raro'],

			'textap' => $this->mimes['.textap'],
			'txt' => $this->mimes['.txt'],
			'text' => $this->mimes['.text']

		];

		return $this->allowed_mimes;
	}

	/**
	 * Generate thumbnail of the PDF page.
	 * @param  [type] $source [description]
	 * @param  [type] $target [description]
	 * @return [type]         [description]
	 */
	public function genPdfThumbnail($source, $target)
    {

    	$this->check_thumbnails_destination($this->destination_folder_fileThumb);
    	$target = substr_replace($target , 'jpg', strrpos($target , '.') +1);

        $im = new \Imagick($source."[0]"); // 0-first page, 1-second page
        $im->setImageColorspace(255); // prevent image colors from inverting
        $im->setimageformat("jpeg");
        $im->thumbnailimage(69, 75); // width and height
        $im->writeimage($this->destination_folder_fileThumb . DIRECTORY_SEPARATOR . $target);
        $im->clear();
        $im->destroy();

    }

    /**
     * Generate image based thumbnails.
     * @param [string] $source [soruce of the file]
     * @return resized and cached image.
     */
    public function genImgThumbnail($source, $target) 
    {
    	$this->check_thumbnails_destination($this->destination_folder_fileThumb);
    	$copy = copy($source, $this->destination_folder_fileThumb . DIRECTORY_SEPARATOR . $target);
        return $copy;
    }

    /**
     * Get resized image on cache.
     * @param  [width] $x [description]
     * @param  [height] $y [description]
     * @return [type]    [description]
     */
   	public function getResizedImg($x, $y, $source) {
		$_x = $x; 
		$_y = $y;
		$img_link = \ImgProxy::link($source, $x, $y);
		return $img_link;
   	}

	
	/**
	 * Receives the size of a file in bytes, and formats it for readability.
	 * Used on files listings (templates and the files manager).
	 */
	public function format_file_size($file)
	{
		if ($file < 1024) {
			 /** No digits so put a ? much better than just seeing Byte */
			echo (ctype_digit($file))? $file . ' Byte' :  ' ? ' ;
		} elseif ($file < 1048576) {
			echo round($file / 1024, 2) . ' KB';
		} elseif ($file < 1073741824) {
			echo round($file / 1048576, 2) . ' MB';
		} elseif ($file < 1099511627776) {
			echo round($file / 1073741824, 2) . ' GB';
		} elseif ($file < 1125899906842624) {
			echo round($file / 1099511627776, 2) . ' TB';
		} elseif ($file < 1152921504606846976) {
			echo round($file / 1125899906842624, 2) . ' PB';
		} elseif ($file < 1180591620717411303424) {
			echo round($file / 1152921504606846976, 2) . ' EB';
		} elseif ($file < 1208925819614629174706176) {
			echo round($file / 1180591620717411303424, 2) . ' ZB';
		} else {
			echo round($file / 1208925819614629174706176, 2) . ' YB';
		}
	}


	/**
	 * Since filesize() was giving trouble with files larger
	 * than 2gb, I looked for a solution and found this great
	 * function by Alessandro Marinuzzi from www.alecos.it on
	 * http://stackoverflow.com/questions/5501451/php-x86-how-
	 * to-get-filesize-of-2gb-file-without-external-program
	 *
	 * I changed the name of the function and split it in 2,
	 * because I do not want to display it directly.
	 */
	public function get_real_size($file)
	{
		clearstatcache();
	    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
			if (class_exists("COM")) {
				$fsobj = new COM('Scripting.FileSystemObject');
				$f = $fsobj->GetFile(realpath($file));
				$ff = $f->Size;
			}
			else {
		        $ff = trim(exec("for %F in (\"" . $file . "\") do @echo %~zF"));
			}
	    }
		elseif (PHP_OS == 'Darwin') {
			$ff = trim(shell_exec("stat -f %z " . escapeshellarg($file)));
	    }
		elseif ((PHP_OS == 'Linux') || (PHP_OS == 'FreeBSD') || (PHP_OS == 'Unix') || (PHP_OS == 'SunOS')) {
			$ff = trim(shell_exec("stat -c%s " . escapeshellarg($file)));
	    }
		else {
			$ff = filesize($file);
		}

		/** Fix for 0kb downloads by AlanReiblein */
		if (!ctype_digit($ff)) {
			 /* returned value not a number so try filesize() */
			$ff=filesize($file);
		}

		return $ff;
	}


	/**
	 * Delete just one file.
	 * Used on the files managment page.
	 */
	public function delete_file($filename)
	{

		chmod($filename, 0777);
		$unlink = unlink($filename);

		if($unlink) {
			return true;
		} else {
			return false;
		}

	}


	/**
	 * Deletes all files and sub-folders of the selected directory.
	 * Used when deleting a client.
	 */
	public function delete_recursive($dir)
	{
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false ) {
					if( $file != "." && $file != ".." ) {
						if( is_dir( $dir . $file ) ) {
							delete_recursive( $dir . $file . "/" );
							rmdir( $dir . $file );
						}
						else {
							chmod($dir.$file, 0777);
							unlink($dir.$file);
						}
					}
			   }
			   closedir($dh);
			   rmdir($dir);
		   }
		}
	}


	/**
	 * Set mimes
	 */
	public function set_mimes() {
		$this->mimes = [
			'.3dm' => 'x-world/x-3dmf',
			'.3dmf' => 'x-world/x-3dmf',
			'.a' => 'application/octet-stream',
			'.aab' => 'application/x-authorware-bin',
			'.aam' => 'application/x-authorware-map',
			'.aas' => 'application/x-authorware-seg',
			'.abc' => 'text/vnd.abc',
			'.acgi' => 'text/html',
			'.afl' => 'video/animaflex',
			'.ai' => 'application/postscript',
			'.aif' => 'audio/aiff',
			'.aif' => 'audio/x-aiff',
			'.aifc' => 'audio/aiff',
			'.aifc' => 'audio/x-aiff',
			'.aiff' => 'audio/aiff',
			'.aiff' => 'audio/x-aiff',
			'.aim' => 'application/x-aim',
			'.aip' => 'text/x-audiosoft-intra',
			'.ani' => 'application/x-navi-animation',
			'.aos' => 'application/x-nokia-9000-communicator-add-on-software',
			'.aps' => 'application/mime',
			'.arc' => 'application/octet-stream',
			'.arj' => 'application/arj',
			'.arj' => 'application/octet-stream',
			'.art' => 'image/x-jg',
			'.asf' => 'video/x-ms-asf',
			'.asm' => 'text/x-asm',
			'.asp' => 'text/asp',
			'.asx' => 'application/x-mplayer2',
			'.asx' => 'video/x-ms-asf',
			'.asx' => 'video/x-ms-asf-plugin',
			'.au' => 'audio/basic',
			'.au' => 'audio/x-au',
			'.avi' => 'application/x-troff-msvideo',
			'.avi' => 'video/avi',
			'.avi' => 'video/msvideo',
			'.avi' => 'video/x-msvideo',
			'.avs' => 'video/avs-video',
			'.bcpio' => 'application/x-bcpio',
			'.bin' => 'application/mac-binary',
			'.bin' => 'application/macbinary',
			'.bin' => 'application/octet-stream',
			'.bin' => 'application/x-binary',
			'.bin' => 'application/x-macbinary',
			'.bm' => 'image/bmp',
			'.bmp' => 'image/bmp',
			'.bmpw' => 'image/x-windows-bmp',
			'.boo' => 'application/book',
			'.book' => 'application/book',
			'.boz' => 'application/x-bzip2',
			'.bsh' => 'application/x-bsh',
			'.bz' => 'application/x-bzip',
			'.bz2' => 'application/x-bzip2',
			'.c' => 'text/plain',
			'.c++' => 'text/plain',
			'.cat' => 'application/vnd.ms-pki.seccat',
			'.cc' => 'text/plain',
			'.cc' => 'text/x-c',
			'.ccad' => 'application/clariscad',
			'.cco' => 'application/x-cocoa',
			'.cdf' => 'application/cdf',
			'.cdf' => 'application/x-cdf',
			'.cdf' => 'application/x-netcdf',
			'.cer' => 'application/pkix-cert',
			'.cer' => 'application/x-x509-ca-cert',
			'.cha' => 'application/x-chat',
			'.chat' => 'application/x-chat',
			'.class' => 'application/java',
			'.class' => 'application/java-byte-code',
			'.class' => 'application/x-java-class',
			'.com' => 'application/octet-stream',
			'.com' => 'text/plain',
			'.conf' => 'text/plain',
			'.cpio' => 'application/x-cpio',
			'.cpp' => 'text/x-c',
			'.cpt' => 'application/mac-compactpro',
			'.cpt' => 'application/x-compactpro',
			'.cpt' => 'application/x-cpt',
			'.crl' => 'application/pkcs-crl',
			'.crl' => 'application/pkix-crl',
			'.crt' => 'application/pkix-cert',
			'.crt' => 'application/x-x509-ca-cert',
			'.crt' => 'application/x-x509-user-cert',
			'.csh' => 'application/x-csh',
			'.csh' => 'text/x-script.csh',
			'.css' => 'application/x-pointplus',
			'.css' => 'text/css',
			'.cxx' => 'text/plain',
			'.dcr' => 'application/x-director',
			'.deepv' => 'application/x-deepv',
			'.def' => 'text/plain',
			'.der' => 'application/x-x509-ca-cert',
			'.dif' => 'video/x-dv',
			'.dir' => 'application/x-director',
			'.dl' => 'video/dl',
			'.dl' => 'video/x-dl',
			'.doc' => 'application/msword',
			'.dot' => 'application/msword',
			'.dp' => 'application/commonground',
			'.drw' => 'application/drafting',
			'.dump' => 'application/octet-stream',
			'.dv' => 'video/x-dv',
			'.dvi' => 'application/x-dvi',
			'.dwf' => 'drawing/x-dwf (old)',
			'.dwf' => 'model/vnd.dwf',
			'.dwg' => 'application/acad',
			'.dwg' => 'image/vnd.dwg',
			'.dwg' => 'image/x-dwg',
			'.dxf' => 'application/dxf',
			'.dxf' => 'image/vnd.dwg',
			'.dxf' => 'image/x-dwg',
			'.dxr' => 'application/x-director',
			'.el' => 'text/x-script.elisp',
			'.elc' => 'application/x-bytecode.elisp (compiled elisp)',
			'.elc' => 'application/x-elc',
			'.env' => 'application/x-envoy',
			'.eps' => 'application/postscript',
			'.es' => 'application/x-esrehber',
			'.etx' => 'text/x-setext',
			'.evy' => 'application/envoy',
			'.evy' => 'application/x-envoy',
			'.exe' => 'application/octet-stream',
			'.f' => 'text/plain',
			'.f' => 'text/x-fortran',
			'.f77' => 'text/x-fortran',
			'.f90' => 'text/plain',
			'.f90' => 'text/x-fortran',
			'.fdf' => 'application/vnd.fdf',
			'.fif' => 'application/fractals',
			'.fif' => 'image/fif',
			'.fli' => 'video/fli',
			'.fli' => 'video/x-fli',
			'.flo' => 'image/florian',
			'.flx' => 'text/vnd.fmi.flexstor',
			'.fmf' => 'video/x-atomic3d-feature',
			'.for' => 'text/plain',
			'.for' => 'text/x-fortran',
			'.fpx' => 'image/vnd.fpx',
			'.fpx' => 'image/vnd.net-fpx',
			'.frl' => 'application/freeloader',
			'.funk' => 'audio/make',
			'.g' => 'text/plain',
			'.g3' => 'image/g3fax',
			'.gif' => 'image/gif',
			'.gl' => 'video/gl',
			'.gl' => 'video/x-gl',
			'.gsd' => 'audio/x-gsm',
			'.gsm' => 'audio/x-gsm',
			'.gsp' => 'application/x-gsp',
			'.gss' => 'application/x-gss',
			'.gtar' => 'application/x-gtar',
			'.gz' => 'application/x-compressed',
			'.gz' => 'application/x-gzip',
			'.gzip' => 'application/x-gzip',
			'.gzip' => 'multipart/x-gzip',
			'.h' => 'text/plain',
			'.h' => 'text/x-h',
			'.hdf' => 'application/x-hdf',
			'.help' => 'application/x-helpfile',
			'.hgl' => 'application/vnd.hp-hpgl',
			'.hh' => 'text/plain',
			'.hh' => 'text/x-h',
			'.hlb' => 'text/x-script',
			'.hlp' => 'application/hlp',
			'.hlp' => 'application/x-helpfile',
			'.hlp' => 'application/x-winhelp',
			'.hpg' => 'application/vnd.hp-hpgl',
			'.hpgl' => 'application/vnd.hp-hpgl',
			'.hqx' => 'application/binhex',
			'.hqx' => 'application/binhex4',
			'.hqx' => 'application/mac-binhex',
			'.hqx' => 'application/mac-binhex40',
			'.hqx' => 'application/x-binhex40',
			'.hqx' => 'application/x-mac-binhex40',
			'.hta' => 'application/hta',
			'.htc' => 'text/x-component',
			'.htm' => 'text/html',
			'.html' => 'text/html',
			'.htmls' => 'text/html',
			'.htt' => 'text/webviewhtml',
			'.htx' => 'text/html',
			'.ice' => 'x-conference/x-cooltalk',
			'.ico' => 'image/x-icon',
			'.idc' => 'text/plain',
			'.ief' => 'image/ief',
			'.iefs' => 'image/ief',
			'.iges' => 'application/iges',
			'.iges' => 'model/iges',
			'.igs' => 'application/iges',
			'.igs' => 'model/iges',
			'.ima' => 'application/x-ima',
			'.imap' => 'application/x-httpd-imap',
			'.inf' => 'application/inf',
			'.ins' => 'application/x-internett-signup',
			'.ip' => 'application/x-ip2',
			'.isu' => 'video/x-isvideo',
			'.it' => 'audio/it',
			'.iv' => 'application/x-inventor',
			'.ivr' => 'i-world/i-vrml',
			'.ivy' => 'application/x-livescreen',
			'.jam' => 'audio/x-jam',
			'.jav' => 'text/plain',
			'.jav' => 'text/x-java-source',
			'.java' => 'text/plain',
			'.java' => 'text/x-java-source',
			'.jcm' => 'application/x-java-commerce',
			'.jfif' => 'image/jpeg',
			'.jfif' => 'image/pjpeg',
			'.jfif-tbnl' => 'image/jpeg',
			'.jpe' => 'image/jpeg',
			'.jpe' => 'image/pjpeg',
			'.jpeg' => 'image/jpeg',
			'.pjpeg' => 'image/pjpeg',
			'.jpg' => 'image/jpeg',
			'.pjpg' => 'image/pjpeg',
			'.jps' => 'image/x-jps',
			'.js' => 'application/x-javascript',
			'.jut' => 'image/jutvision',
			'.kar' => 'audio/midi',
			'.kar' => 'music/x-karaoke',
			'.ksh' => 'application/x-ksh',
			'.ksh' => 'text/x-script.ksh',
			'.la' => 'audio/nspaudio',
			'.la' => 'audio/x-nspaudio',
			'.lam' => 'audio/x-liveaudio',
			'.latex' => 'application/x-latex',
			'.lha' => 'application/lha',
			'.lha' => 'application/octet-stream',
			'.lha' => 'application/x-lha',
			'.lhx' => 'application/octet-stream',
			'.list' => 'text/plain',
			'.lma' => 'audio/nspaudio',
			'.lma' => 'audio/x-nspaudio',
			'.log' => 'text/plain',
			'.lsp' => 'application/x-lisp',
			'.lsp' => 'text/x-script.lisp',
			'.lst' => 'text/plain',
			'.lsx' => 'text/x-la-asf',
			'.ltx' => 'application/x-latex',
			'.lzh' => 'application/octet-stream',
			'.lzh' => 'application/x-lzh',
			'.lzx' => 'application/lzx',
			'.lzx' => 'application/octet-stream',
			'.lzx' => 'application/x-lzx',
			'.m' => 'text/plain',
			'.m' => 'text/x-m',
			'.m1v' => 'video/mpeg',
			'.m2a' => 'audio/mpeg',
			'.m2v' => 'video/mpeg',
			'.m3u' => 'audio/x-mpequrl',
			'.man' => 'application/x-troff-man',
			'.map' => 'application/x-navimap',
			'.mar' => 'text/plain',
			'.mbd' => 'application/mbedlet',
			'.mc' => 'application/x-magic-cap-package-1.0',
			'.mcd' => 'application/mcad',
			'.mcd' => 'application/x-mathcad',
			'.mcf' => 'image/vasa',
			'.mcf' => 'text/mcf',
			'.mcp' => 'application/netmc',
			'.me' => 'application/x-troff-me',
			'.mht' => 'message/rfc822',
			'.mhtml' => 'message/rfc822',
			'.mid' => 'application/x-midi',
			'.mid' => 'audio/midi',
			'.mid' => 'audio/x-mid',
			'.mid' => 'audio/x-midi',
			'.mid' => 'music/crescendo',
			'.mid' => 'x-music/x-midi',
			'.midi' => 'application/x-midi',
			'.midi' => 'audio/midi',
			'.midi' => 'audio/x-mid',
			'.midi' => 'audio/x-midi',
			'.midi' => 'music/crescendo',
			'.midi' => 'x-music/x-midi',
			'.mif' => 'application/x-frame',
			'.mif' => 'application/x-mif',
			'.mime' => 'message/rfc822',
			'.mime' => 'www/mime',
			'.mjf' => 'audio/x-vnd.audioexplosion.mjuicemediafile',
			'.mjpg' => 'video/x-motion-jpeg',
			'.mm' => 'application/base64',
			'.mm' => 'application/x-meme',
			'.mme' => 'application/base64',
			'.mod' => 'audio/mod',
			'.mod' => 'audio/x-mod',
			'.moov' => 'video/quicktime',
			'.mov' => 'video/quicktime',
			'.movie' => 'video/x-sgi-movie',
			'.mp2' => 'audio/mpeg',
			'.mp2' => 'audio/x-mpeg',
			'.mp2' => 'video/mpeg',
			'.mp2' => 'video/x-mpeg',
			'.mp2' => 'video/x-mpeq2a',
			'.mp3' => 'audio/mpeg3',
			'.mp3' => 'audio/x-mpeg-3',
			'.mp3' => 'video/mpeg',
			'.mp3' => 'video/x-mpeg',
			'.mpa' => 'audio/mpeg',
			'.mpa' => 'video/mpeg',
			'.mpc' => 'application/x-project',
			'.mpe' => 'video/mpeg',
			'.mpeg' => 'video/mpeg',
			'.mpg' => 'audio/mpeg',
			'.mpg' => 'video/mpeg',
			'.mpga' => 'audio/mpeg',
			'.mpp' => 'application/vnd.ms-project',
			'.mpt' => 'application/x-project',
			'.mpv' => 'application/x-project',
			'.mpx' => 'application/x-project',
			'.mrc' => 'application/marc',
			'.ms' => 'application/x-troff-ms',
			'.mv' => 'video/x-sgi-movie',
			'.my' => 'audio/make',
			'.mzz' => 'application/x-vnd.audioexplosion.mzz',
			'.nap' => 'image/naplps',
			'.naplps' => 'image/naplps',
			'.nc' => 'application/x-netcdf',
			'.ncm' => 'application/vnd.nokia.configuration-message',
			'.nif' => 'image/x-niff',
			'.niff' => 'image/x-niff',
			'.nix' => 'application/x-mix-transfer',
			'.nsc' => 'application/x-conference',
			'.nvd' => 'application/x-navidoc',
			'.o' => 'application/octet-stream',
			'.oda' => 'application/oda',
			'.omc' => 'application/x-omc',
			'.omcd' => 'application/x-omcdatamaker',
			'.omcr' => 'application/x-omcregerator',
			'.p' => 'text/x-pascal',
			'.p10' => 'application/pkcs10',
			'.p10' => 'application/x-pkcs10',
			'.p12' => 'application/pkcs-12',
			'.p12' => 'application/x-pkcs12',
			'.p7a' => 'application/x-pkcs7-signature',
			'.p7c' => 'application/pkcs7-mime',
			'.p7c' => 'application/x-pkcs7-mime',
			'.p7m' => 'application/pkcs7-mime',
			'.p7m' => 'application/x-pkcs7-mime',
			'.p7r' => 'application/x-pkcs7-certreqresp',
			'.p7s' => 'application/pkcs7-signature',
			'.part' => 'application/pro_eng',
			'.pas' => 'text/pascal',
			'.pbm' => 'image/x-portable-bitmap',
			'.pcl' => 'application/vnd.hp-pcl',
			'.pcl' => 'application/x-pcl',
			'.pct' => 'image/x-pict',
			'.pcx' => 'image/x-pcx',
			'.pdb' => 'chemical/x-pdb',
			'.pdf' => 'application/pdf',
			'.pfunk' => 'audio/make',
			'.pgm' => 'image/x-portable-greymap',
			'.pic' => 'image/pict',
			'.pict' => 'image/pict',
			'.pkg' => 'application/x-newton-compatible-pkg',
			'.pko' => 'application/vnd.ms-pki.pko',
			'.pl' => 'text/plain',
			'.pl' => 'text/x-script.perl',
			'.plx' => 'application/x-pixclscript',
			'.pm' => 'image/x-xpixmap',
			'.pm' => 'text/x-script.perl-module',
			'.pm4' => 'application/x-pagemaker',
			'.pm5' => 'application/x-pagemaker',
			'.png' => 'image/png',
			'.pnm' => 'application/x-portable-anymap',
			'.pnm' => 'image/x-portable-anymap',
			'.pot' => 'application/mspowerpoint',
			'.pot' => 'application/vnd.ms-powerpoint',
			'.pov' => 'model/x-pov',
			'.ppa' => 'application/vnd.ms-powerpoint',
			'.ppm' => 'image/x-portable-pixmap',
			'.pps' => 'application/mspowerpoint',
			'.pps' => 'application/vnd.ms-powerpoint',
			'.ppt' => 'application/mspowerpoint',
			'.ppt' => 'application/powerpoint',
			'.ppt' => 'application/vnd.ms-powerpoint',
			'.ppt' => 'application/x-mspowerpoint',
			'.ppz' => 'application/mspowerpoint',
			'.pre' => 'application/x-freelance',
			'.prt' => 'application/pro_eng',
			'.ps' => 'application/postscript',
			'.psd' => 'application/octet-stream',
			'.pvu' => 'paleovu/x-pv',
			'.pwz' => 'application/vnd.ms-powerpoint',
			'.py' => 'text/x-script.phyton',
			'.pyc' => 'applicaiton/x-bytecode.python',
			'.qcp' => 'audio/vnd.qcelp',
			'.qd3' => 'x-world/x-3dmf',
			'.qd3d' => 'x-world/x-3dmf',
			'.qif' => 'image/x-quicktime',
			'.qt' => 'video/quicktime',
			'.qtc' => 'video/x-qtc',
			'.qti' => 'image/x-quicktime',
			'.qtif' => 'image/x-quicktime',
			'.ra' => 'audio/x-pn-realaudio',
			'.ra' => 'audio/x-pn-realaudio-plugin',
			'.ra' => 'audio/x-realaudio',
			'.ram' => 'audio/x-pn-realaudio',
			'.ras' => 'application/x-cmu-raster',
			'.ras' => 'image/cmu-raster',
			'.ras' => 'image/x-cmu-raster',
			'.rast' => 'image/cmu-raster',
			'.rexx' => 'text/x-script.rexx',
			'.rf' => 'image/vnd.rn-realflash',
			'.rgb' => 'image/x-rgb',
			'.rm' => 'application/vnd.rn-realmedia',
			'.rm' => 'audio/x-pn-realaudio',
			'.rmi' => 'audio/mid',
			'.rmm' => 'audio/x-pn-realaudio',
			'.rmp' => 'audio/x-pn-realaudio',
			'.rmp' => 'audio/x-pn-realaudio-plugin',
			'.rng' => 'application/ringing-tones',
			'.rng' => 'application/vnd.nokia.ringing-tone',
			'.rnx' => 'application/vnd.rn-realplayer',
			'.roff' => 'application/x-troff',
			'.rp' => 'image/vnd.rn-realpix',
			'.rpm' => 'audio/x-pn-realaudio-plugin',
			'.rt' => 'text/richtext',
			'.rt' => 'text/vnd.rn-realtext',
			'.rtf' => 'application/rtf',
			'.rtf' => 'application/x-rtf',
			'.rtf' => 'text/richtext',
			'.rtx' => 'application/rtf',
			'.rtx' => 'text/richtext',
			'.rv' => 'video/vnd.rn-realvideo',
			'.s' => 'text/x-asm',
			'.s3m' => 'audio/s3m',
			'.saveme' => 'aapplication/octet-stream',
			'.sbk' => 'application/x-tbook',
			'.scm' => 'application/x-lotusscreencam',
			'.scm' => 'text/x-script.guile',
			'.scm' => 'text/x-script.scheme',
			'.scm' => 'video/x-scm',
			'.sdml' => 'text/plain',
			'.sdp' => 'application/sdp',
			'.sdp' => 'application/x-sdp',
			'.sdr' => 'application/sounder',
			'.sea' => 'application/sea',
			'.sea' => 'application/x-sea',
			'.set' => 'application/set',
			'.sgm' => 'text/sgml',
			'.sgm' => 'text/x-sgml',
			'.sgml' => 'text/sgml',
			'.sgml' => 'text/x-sgml',
			'.sh' => 'application/x-bsh',
			'.sh' => 'application/x-sh',
			'.sh' => 'application/x-shar',
			'.sh' => 'text/x-script.sh',
			'.shar' => 'application/x-bsh',
			'.shar' => 'application/x-shar',
			'.shtml' => 'text/html',
			'.shtml' => 'text/x-server-parsed-html',
			'.sid' => 'audio/x-psid',
			'.sit' => 'application/x-sit',
			'.sit' => 'application/x-stuffit',
			'.skd' => 'application/x-koan',
			'.skm' => 'application/x-koan',
			'.skp' => 'application/x-koan',
			'.skt' => 'application/x-koan',
			'.sl' => 'application/x-seelogo',
			'.smi' => 'application/smil',
			'.smil' => 'application/smil',
			'.snd' => 'audio/basic',
			'.snd' => 'audio/x-adpcm',
			'.sol' => 'application/solids',
			'.spc' => 'application/x-pkcs7-certificates',
			'.spc' => 'text/x-speech',
			'.spl' => 'application/futuresplash',
			'.spr' => 'application/x-sprite',
			'.sprite' => 'application/x-sprite',
			'.src' => 'application/x-wais-source',
			'.ssi' => 'text/x-server-parsed-html',
			'.ssm' => 'application/streamingmedia',
			'.sst' => 'application/vnd.ms-pki.certstore',
			'.step' => 'application/step',
			'.stl' => 'application/sla',
			'.stl' => 'application/vnd.ms-pki.stl',
			'.stl' => 'application/x-navistyle',
			'.stp' => 'application/step',
			'.sv4cpio' =>'application/x-sv4cpio',
			'.sv4crc' => 'application/x-sv4crc',
			'.svf' => 'image/vnd.dwg',
			'.svf' => 'image/x-dwg',
			'.svr' => 'application/x-world',
			'.svr' => 'x-world/x-svr',
			'.swf' => 'application/x-shockwave-flash',
			'.t' => 'application/x-troff',
			'.talk' => 'text/x-speech',
			'.tar' => 'application/x-tar',
			'.tbk' => 'application/toolbook',
			'.tbk' => 'application/x-tbook',
			'.tcl' => 'application/x-tcl',
			'.tcl' => 'text/x-script.tcl',
			'.tcsh' => 'text/x-script.tcsh',
			'.tex' => 'application/x-tex',
			'.texi' => 'application/x-texinfo',
			'.texinfo' =>' lication/x-texinfo',
			'.textap' => 'application/plain',
			'.text' => 'text/plain',
			'.tgz' => 'application/gnutar',
			'.tgz' => 'application/x-compressed',
			'.tif' => 'image/tiff',
			'.tifx' => 'image/x-tiff',
			'.tiff' => 'image/tiff',
			'.tiffx' => 'image/x-tiff',
			'.tr' => 'application/x-troff',
			'.tsi' => 'audio/tsp-audio',
			'.tsp' => 'application/dsptype',
			'.tsp' => 'audio/tsplayer',
			'.tsv' => 'text/tab-separated-values',
			'.turbot' => 'image/florian',
			'.txt' => 'text/plain',
			'.uil' => 'text/x-uil',
			'.uni' => 'text/uri-list',
			'.unis' => 'text/uri-list',
			'.unv' => 'application/i-deas',
			'.uri' => 'text/uri-list',
			'.uris' => 'text/uri-list',
			'.ustar' => 'application/x-ustar',
			'.ustar' => 'multipart/x-ustar',
			'.uu' => 'application/octet-stream',
			'.uu' => 'text/x-uuencode',
			'.uue' => 'text/x-uuencode',
			'.vcd' => 'application/x-cdlink',
			'.vcs' => 'text/x-vcalendar',
			'.vda' => 'application/vda',
			'.vdo' => 'video/vdo',
			'.vew' => 'application/groupwise',
			'.viv' => 'video/vivo',
			'.viv' => 'video/vnd.vivo',
			'.vivo' => 'video/vivo',
			'.vivo' => 'video/vnd.vivo',
			'.vmd' => 'application/vocaltec-media-desc',
			'.vmf' => 'application/vocaltec-media-file',
			'.voc' => 'audio/voc',
			'.voc' => 'audio/x-voc',
			'.vos' => 'video/vosaic',
			'.vox' => 'audio/voxware',
			'.vqe' => 'audio/x-twinvq-plugin',
			'.vqf' => 'audio/x-twinvq',
			'.vql' => 'audio/x-twinvq-plugin',
			'.vrml' => 'application/x-vrml',
			'.vrml' => 'model/vrml',
			'.vrml' => 'x-world/x-vrml',
			'.vrt' => 'x-world/x-vrt',
			'.vsd' => 'application/x-visio',
			'.vst' => 'application/x-visio',
			'.vsw' => 'application/x-visio',
			'.w60' => 'application/wordperfect6.0',
			'.w61' => 'application/wordperfect6.1',
			'.w6w' => 'application/msword',
			'.wav' => 'audio/wav',
			'.wav' => 'audio/x-wav',
			'.wb1' => 'application/x-qpro',
			'.wbmp' => 'image/vnd.wap.wbmp',
			'.web' => 'application/vnd.xara',
			'.wiz' => 'application/msword',
			'.wk1' => 'application/x-123',
			'.wmf' => 'windows/metafile',
			'.wml' => 'text/vnd.wap.wml',
			'.wmlc' => 'application/vnd.wap.wmlc',
			'.wmls' => 'text/vnd.wap.wmlscript',
			'.wmlsc' => 'application/vnd.wap.wmlscriptc',
			'.word' => 'application/msword',
			'.wp' => 'application/wordperfect',
			'.wp5' => 'application/wordperfect',
			'.wp5' => 'application/wordperfect6.0',
			'.wp6' => 'application/wordperfect',
			'.wpd' => 'application/wordperfect',
			'.wpd' => 'application/x-wpwin',
			'.wq1' => 'application/x-lotus',
			'.wri' => 'application/mswrite',
			'.wri' => 'application/x-wri',
			'.wrl' => 'application/x-world',
			'.wrl' => 'model/vrml',
			'.wrl' => 'x-world/x-vrml',
			'.wrz' => 'model/vrml',
			'.wrz' => 'x-world/x-vrml',
			'.wsc' => 'text/scriplet',
			'.wsrc' => 'application/x-wais-source',
			'.wtk' => 'application/x-wintalk',
			'.xbm' => 'image/x-xbitmap',
			'.xbm' => 'image/x-xbm',
			'.xbm' => 'image/xbm',
			'.xdr' => 'video/x-amt-demorun',
			'.xgz' => 'xgl/drawing',
			'.xif' => 'image/vnd.xiff',
			'.xl' => 'application/excel',
			'.xla' => 'application/excel',
			'.xla' => 'application/x-excel',
			'.xla' => 'application/x-msexcel',
			'.xlb' => 'application/excel',
			'.xlb' => 'application/vnd.ms-excel',
			'.xlb' => 'application/x-excel',
			'.xlc' => 'application/excel',
			'.xlc' => 'application/vnd.ms-excel',
			'.xlc' => 'application/x-excel',
			'.xld' => 'application/excel',
			'.xld' => 'application/x-excel',
			'.xlk' => 'application/excel',
			'.xlk' => 'application/x-excel',
			'.xll' => 'application/excel',
			'.xll' => 'application/vnd.ms-excel',
			'.xll' => 'application/x-excel',
			'.xlm' => 'application/excel',
			'.xlm' => 'application/vnd.ms-excel',
			'.xlm' => 'application/x-excel',
			'.xls' => 'application/excel',
			'.xls' => 'application/vnd.ms-excel',
			'.xls' => 'application/x-excel',
			'.xls' => 'application/x-msexcel',
			'.xlt' => 'application/excel',
			'.xlt' => 'application/x-excel',
			'.xlv' => 'application/excel',
			'.xlv' => 'application/x-excel',
			'.xlw' => 'application/excel',
			'.xlw' => 'application/vnd.ms-excel',
			'.xlw' => 'application/x-excel',
			'.xlw' => 'application/x-msexcel',
			'.xm' => 'audio/xm',
			'.xml' => 'application/xml',
			'.xml' => 'text/xml',
			'.xmz' => 'xgl/movie',
			'.xpix' => 'application/x-vnd.ls-xpix',
			'.xpm' => 'image/x-xpixmap',
			'.xpm' => 'image/xpm',
			'.x-png' => 'image/png',
			'.xsr' => 'video/x-amt-showrun',
			'.xwd' => 'image/x-xwd',
			'.xwd' => 'image/x-xwindowdump',
			'.xyz' => 'chemical/x-pdb',
			'.z' => 'application/x-compress',
			'.z' => 'application/x-compressed',
			'.zipxc' => 'application/x-compressed',
			'.zipxz' => 'application/x-zip-compressed',
			'.zip' => 'application/zip',
			'.zipx' => 'multipart/x-zip',
			'.zoo' => 'application/octet-stream',
			'.zsh' => 'text/x-script.zsh)',
			'.rar' => 'application/octet-stream',
			'.raro' => 'application/octet-stream',
			'.xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'.xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'.potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'.ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'.pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'.sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'.dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'.xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
			'.xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.1',
			'.odt' => 'application/vnd.oasis.opendocument.text',
			'.ott'	=> 'application/vnd.oasis.opendocument.text-template',
			'.oth'	=> 'application/vnd.oasis.opendocument.text-web',
			'.odm'	=> 'application/vnd.oasis.opendocument.text-master',
			'.odg' => 'application/vnd.oasis.opendocument.graphics',
			'.otg'	=> 'application/vnd.oasis.opendocument.graphics-template',
			'.odp'	=> 'application/vnd.oasis.opendocument.presentation',
			'.otp'	=> 'application/vnd.oasis.opendocument.presentation-template',
			'.ods' => 'application/vnd.oasis.opendocument.spreadsheet',
			'.ots'	=> 'application/vnd.oasis.opendocument.spreadsheet-template',
			'.odc'	=> 'application/vnd.oasis.opendocument.chart',
			'.odf' => 'application/vnd.oasis.opendocument.formula',
			'.odb' => 'application/vnd.oasis.opendocument.database',
			'.odi' => 'application/vnd.oasis.opendocument.image',
			'.oxt' => 'application/vnd.openofficeorg.extension'

		];
	}

}
