<?php
/**
 * This class handles all hooks and functions for the structur.
 * If something will print out, this is a fasade-wrapper function
 * for the class RML_View (stored in private $view).
 * 
 * @author MatthiasWeb
 * @package real-media-library\inc\attachment
 * @since 1.0
 * @singleton
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class RML_Structure {
    
    private static $me = null;
    
    /**
     * Array of Databased readed rows
     */
    private $rows;
    /**
     * $rows formed to RML_Folder objects
     */
    private $parsed;
    /**
     * Tree of RML_Folder objects. @see $childrens of RML_Folder object.
     */
    private $tree;
    private $cntAttachments;
    private $view;

    /**
     * C'tor
     * When starting the structure by singleton getInstance()
     * then fetch all folders with their parents.
     * 
     * @author MatthiasWeb
     * @since 1.0
     */
    public function __construct() {
        $this->view = new RML_View($this);
        
        $this->resetData();
    }
    
    public function resetData() {
        $this->rows = array();
        $this->parsed = array();
        $this->tree = array();
        $this->cntAttachments = wp_count_posts('attachment')->inherit;
        $this->fetch();
    }
    
    /**
     * Fetching all available folders into an array.
     * 
     * @author MatthiasWeb
     * @since 1.0
     */
    private function fetch() {
        global $wpdb;
        
        $table_name = RML_Core::getInstance()->getTableName();
        $where = "";
        $blog_id = null;
        if (is_multisite()) {
            $blog_id = get_current_blog_id();
            $where = " WHERE bid=$blog_id ";
        }
        
        $sqlStatement = apply_filters("RML/Tree/SQLStatement", array("
            SELECT tn.*, (SELECT COUNT(*)
                        	FROM " . $wpdb->posts . " AS wpp
                        	INNER JOIN " . $wpdb->postmeta . " AS wppm ON ( wpp.ID = wppm.post_id )
                        	WHERE ( 
                        		( wppm.meta_key = '_rml_folder' AND CAST(wppm.meta_value AS CHAR) = tn.id )
                            ) AND wpp.post_type = 'attachment' AND ((wpp.post_status = 'inherit'))
                        ) AS cnt
            FROM $table_name AS tn
            $where
            ORDER BY parent, ord
        ", $table_name, $blog_id));
        $this->rows = $wpdb->get_results($sqlStatement[0]);
        $this->rows = apply_filters("RML/Tree/SQLRows",  $this->rows);
        
        $this->parse();
    }
    
    /**
     * This functions parses the readed rows into folder objects.
     * 
     * @author MatthiasWeb
     * @since 1.0
     */
    private function parse() {
        if (!empty($this->rows)) {
            foreach ($this->rows as $key => $value) {
                $this->parsed[] = new RML_Folder(
                    intval($value->id),
                    intval($value->parent),
                    $value->name,
                    $value->slug,
                    $value->absolute,
                    intval($value->ord),
                    intval($value->type),
                    intval($value->cnt)
                );
            }
        }
        
        // Create the tree
        $folder = null;
        foreach($this->parsed as $key => $category){
            $parent = $category->parent;
            if ($parent > -1) {
                $folder = $this->getFolderByID($parent);
                if ($folder !== null) {
                    $folder->children[] = $category;
                }
            }
        }
        
        $cats_tree = array();
        foreach ($this->parsed as $category) {
            if ($category->parent <= -1) {
                $cats_tree[] = $category;
            }
        }
        $this->tree = $cats_tree;
    }
    
    /**
     * Checks, if a parent is allowed to have the type child.
     * 
     * @param $parent int ID of the folder OR RML_Folder object
     * @param $type int RML_TYPE_...
     * @return boolean
     */
    public function isAllowedTo($parent, $type) {
        if (!$parent instanceof RML_Folder && !$this->isFolder($parent)) {
            return false;
        }
        
        if ($parent instanceof RML_Folder) {
            $parentObj = $parent;
            $parent = $parentObj->id;
        }
        
        if ($parent == "-1") {
            // Root directory, no gallerie allowed
            if ($type == 2) {
                return false;
            }
        }else{
            if (!isset($parentObj)) {
                $parentObj = $this->getFolderByID($parent);
            }
            
            // Check, if type is right for the parent folder
            if ($type == 0 && $parentObj->type != 0) {  // If it is a folder, parent must be a folder
                return false;
            } else if ($type == 1 && $parentObj->type == 2) { // If it is a collection, parent must be collection or folder
                return false;
            } else if ($type == 2 && $parentObj->type != 1) { // If it is a gallery, parent may only be a collection
                return false;
            }
        }
        return true;
    }
    
    /**
     * Creates a folder. At first it checks if a folder in parent already exists.
     * Then it checks if the given type is allowed in the parent.
     * 
     * @param name String Name of the folder
     * @param parent int ID of the parent (-1 for root)
     * @param type integer 0|1|2 @see Folder.class.inc
     * @return  int (ID) when successfully
     *          false when failed (name and type check)
     *          array with error strings
     * @api wp_rml_create
     */
    public function createFolder($name, $parent, $type) {
        global $wpdb;
        
        $name = trim($name);
	
	    if (is_numeric($type) &&
	        $type >= 0 && $type <= 2 &&
	        strpbrk($name, "\\/?%*:|\"<>") === FALSE && strlen($name) > 0) {
	            // Check if type is allowed in parent folder and the slug is allowed
	            if (!$this->isAllowedTo($parent, $type) || $this->hasChildSlug($parent, $name, false)) {
	                return false;
	            }
	            
	            // Check if other fails are counted
	            $errors = apply_filters("RML/Folder/ValidateCreate", array());
	            if (count($errors) > 0) {
	                return $errors;
	            }
	            
	            // Create it!
            	$table_name = RML_Core::getInstance()->getTableName();
            	$insert = $wpdb->insert( 
            		$table_name,
            		array( 
            			'parent' => $parent,
            			'slug' => sanitize_title($name, "", "folder"),
            			'name' => $name,
            			'bid' => get_current_blog_id(),
            			'type' => $type
            		)
            	);

            	if ($insert !== false) {
            	    $id = $wpdb->insert_id;
            	    $this->resetData();
                	do_action("RML/Folder/Created", $parent, $name, $type, $id);
                	return $id;
            	}else{
            	    return false;
            	}
	    }else{
	        return false;
	    }
    }
    
    /**
     * Deletes a specific folder.
     * 
     * @param id int ID of the folder     
     * @return  true when successfully
     *          false when failed
     *          array with error strings
     */
    public function deleteFolder($id) {
        if ($this->isFolder($id) && $id > 0) {
            // Check if other fails are counted
            $errors = apply_filters("RML/Folder/ValidateDelete", array());
            if (count($errors) > 0) {
                return $errors;
            }
            
            // Delete files in this folder
            $query = new WP_Query(array(
            	'post_status' => 'inherit',
            	'post_type' => 'attachment',
            	'meta_query' => array(
            	    array(
            	        'key' => '_rml_folder',
            	        'value' => $id,
            	        'compare' => '='
        	        ))
            ));
            $posts = $query->get_posts();
            foreach ($posts as $post) {
                wp_delete_attachment($post->ID);
            }
            
            // Delete folder
            global $wpdb;
            $table_name = RML_Core::getInstance()->getTableName();
            $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id = %d", $id));
            
            $this->resetData();
            do_action("RML/Folder/Deleted", $id, $posts);
            
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Renames a folder and then checks, if there is no duplicate folder in the
     * parent folder.
     * 
     * @param name String New name of the folder
     * @param id int ID of the folder
     * @return false or RML_Folder object when successfull
     */
    public function renameFolder($name, $id) {
        $folder = $this->getFolderByID($id);
        if ($folder !== null) {
            if ($folder->setName($name)) {
                return $folder;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    public function optionsFasade($selected, $disabled, $useAll = true) {
        return $this->view->optionsHTML($selected, null, "", "--", $useAll, $disabled);
    }
    
    public function isFolder($id) {
        if ($id == -1) {
            return true; // is root directory
        }
        
        return $this->getFolderByID($id) != null;
    }
    
    public function getFolderByID($id) {
        foreach ($this->parsed as $folder) {
            if ($folder->id == $id) {
                return $folder;
            }
        }
        return null;
    }
    
    public function getFolderByAbsolutePath($slug) {
        $slug = trim($slug, '/');
        foreach ($this->parsed as $folder) {
            if ($folder->absolutePath() == $slug) {
                return $folder;
            }
        }
        return null;
    }
    
    public function getBreadcrumbByID($id) {
        $folder = $this->getFolderByID($id);
        if ($folder === null) {
            return null;
        }
        
        $return = array($folder);
        
        while (true) {
            if ($folder->parent > 0) {
                $folder = $this->getFolderByID($folder->parent);
                if ($folder === null) {
                    return null;
                }else{
                    $return[] = $folder;
                }
            }else{
                break;
            }
        }
        
        return array_reverse($return);
    }
    
    /**
     * Checks, if root has a children with the name.
     * 
     * @param $slug String Slug or Name of folder
     * @param $isSlug boolean Set it to false, if the slug is not santizied
     * @return boolean true/false
     */
    public function hasRootChildSlug($slug, $isSlug = true) {
        if (!$isSlug) {
            $slug = sanitize_title($slug, "", "folder");
        }
        
        foreach ($this->tree as $value) {
            if ($value->slug() == $slug) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Checks, if a Parents folder has a child with the given slug.
     * This function is a wrapper for RML_Structure::hasRootChildSlug
     * and RML_Folder::hasChildSlug. That means, it checks, if a
     * given parent ID is root or folder.
     * 
     * @param $parentID Id of the Parent folder (-1 for root)
     * @param $slug String Slug or Name of folder
     * @param $isSlug boolean Set it to false, if the slug is not santizied
     * @return boolean true/false
     */
    public function hasChildSlug($parentID, $slug, $isSlug = true) {
        if ($parentID == "-1") {
            // Check if root has children
            return $this->hasRootChildSlug($slug, $isSlug);
        }else{
            $parentObj = $this->getFolderByID($parentID);
            // Check if children with this name already exists
            if ($parentObj == null || $parentObj->hasChildSlug($slug, $isSlug)) {
                return true;
            }else{
                return false;
            }
        }
    }
    
    public function getRows() {
        return $this->rows;
    }
    
    public function getParsed() {
        return $this->parsed;
    }
    
    public function getTree() {
        return $this->tree;
    }
    
    public function getCntAttachments() {
        return $this->cntAttachments;
    }
    
    public function getCntRoot() {
        $cnt = 0;
        foreach ($this->parsed as $folder) {
            $cnt += $folder->getCnt();
        }
        return $this->getCntAttachments() - $cnt;
    }
    
    public function getView() {
        return $this->view;
    }
    
    public static function getInstance() {
        if (self::$me == null) {
            self::$me = new RML_Structure();
        }
        return self::$me;
    }
    
    public static function newInstance() {
        return new RML_Structure();
    }
}

?>