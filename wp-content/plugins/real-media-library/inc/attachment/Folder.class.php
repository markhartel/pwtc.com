<?php
/**
 * This class creates a folder object. The $type variable defines,
 * if it is a:
 * 
 * RML_TYPE_FOLDER
 * RML_TYPE_COLLECTION
 * RML_TYPE_GALLERY
 * 
 * @author MatthiasWeb
 * @since 1.0
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class RML_Folder {
    
    public $id;
    public $parent;
    public $name;
    private $cnt;
    public $order;
    
    /**
     * Defines the RML_TYPE_...
     * @since 2.2
     */
    public $type;
    
    /**
     * A array of childrens RML_Folder object
     */
    public $children;
    
    /**
     * The slug of this folder for URLs, use getter.
     */
    private $slug;
    
    /**
     * The absolute path to this folder, use getter.
     */
    private $absolutePath;

    public function __construct($id, $parent, $name, $slug, $absolute, $order = 999, $type = 0, $cnt) {
        $this->id = $id;
        $this->parent = $parent;
        $this->name = $name;
        $this->cnt = $cnt;
        $this->order = $order;
        $this->type = $type;
        $this->children = array();
        $this->slug = $slug;
        $this->absolutePath = $absolute;
    }
    
    /**
     * Move several items to this folder.
     * 
     * @param $ids array of post ids
     * @author MatthiasWeb
     * @since 1.0
     */
    public function moveItemsHere($ids) {
        if (is_array($ids) && count($ids) > 0 && $this->type != 1) {
            foreach ($ids as $value) {
                if ($this->type == 2 && !wp_attachment_is_image($value)) { // if it is a gallery, there are only images allowed
                    continue;
                }else{
                    // Check if other fails are counted
    	            $errors = apply_filters("RML/Item/ValidateMove", array(), $ids, $this);
    	            if (count($errors) > 0) {
    	                continue;
    	            }
                    
                    update_post_meta($value, "_rml_folder", $this->id);
                    //do_action("RML/Item/Moved", $value, $this); @deprecated, @see RML_Filter::update_postmeta
                }
            }
        }
    }
    
    /**
     * Fetch all attachment ids currently in this folder.
     * 
     * @return array of post ids
     */
    public function fetchFileIds($order = null, $orderby = null) {
        return self::sFetchFileIds($this->id, $order, $orderby);
    }
    
    public static function sFetchFileIds($id, $order = null, $orderby = null) {
        $args = array(
        	'post_status' => 'inherit',
        	'post_type' => 'attachment',
        	'posts_per_page' => -1,
        	/*'meta_query' => array( array( 'key' => '_rml_folder', 'value' => $id, 'compare' => '=' )),*/
	        'rml_folder' => $id,
	        'fields' => 'ids'
        );
        
        // Set orders
        if ($order !== null) {
            $args["order"] = $order;
        }
        if ($orderby !== null) {
            $args["orderby"] = $orderby;
        }
        
        $args = apply_filters('RML/Folder/QueryArgs', $args);
        $query = new WP_Query($args);
        $posts = $query->get_posts();
        $posts = apply_filters('RML/Folder/QueryResult', $posts);
        return $posts;
    }
    
    /**
     * Returns a santitized title for the folder. If the slug is empty
     * or forced to, it will be updated in the database, too.
     * 
     * @param force Forces to regenerate the slug
     * @return string slug
     */
    public function slug($force = false) {
        if ($this->slug == "" || $force) {
            $this->slug = sanitize_title($this->name, "", "folder");
            
            // Update in database
            //error_log("Update slug " . $this->slug);
            global $wpdb;
            $table_name = RML_Core::getInstance()->getTableName();
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET slug=%s WHERE id = %d", $this->slug, $this->id));
        }
        
        return $this->slug;
    }
    
    /**
     * Creates a absolute path. If the absolute path is empty
     * or forced to, it will be updated in the database, too.
     * 
     * @param force Forces to regenerate the absolute path
     * @return string path
     */
    public function absolutePath($force = false) {
        if ($this->absolutePath == "" || $force) {
            $return = array($this->slug());
            $folder = $this;
            while (true) {
                $f = RML_Structure::getInstance()->getFolderByID($folder->parent);
                if ($f !== null) {
                    $folder = $f;
                    $return[] = $folder->slug();
                }else{
                    break;
                }
            }
            $this->absolutePath = implode("/", array_reverse($return));
            
            // Update in database
            //error_log("Update absolute " . $this->absolutePath);
            global $wpdb;
            $table_name = RML_Core::getInstance()->getTableName();
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET absolute=%s WHERE id = %d", $this->absolutePath, $this->id));
        }
        return $this->absolutePath;
    }
    
    /**
     * Creates a absolute path without slugging' the names.
     * 
     * @return string path
     */
    public function absolutePathNormalized($implode = "/") {
        $return = array($this->name);
        $folder = $this;
        while (true) {
            $f = RML_Structure::getInstance()->getFolderByID($folder->parent);
            if ($f !== null) {
                $folder = $f;
                $return[] = $folder->name;
            }else{
                break;
            }
        }
        return implode($implode, array_reverse($return));
    }
    
    /**
     * Checks, if this folder has a children with the name.
     *  
     * @param $slug String Slug or Name of folder
     * @param $isSlug boolean Set it to false, if the slug is not santizied (@see $this->slug())
     * @return boolean true/false
     */
    public function hasChildSlug($slug, $isSlug = true) {
        if (!$isSlug) {
            $slug = sanitize_title($slug, "", "folder");
        }
        
        foreach ($this->children as $value) {
            if ($value->slug() == $slug) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Changes the parent folder of this folder. This function should
     * only be called through the AJAX function wp_ajax_bulk_sort.
     * 
     * The action RML/Structure/Rebuild will update the absolute path of the whole
     * structure in the database. Please call this after setParent()!
     * 
     * @return boolean true = Parent could be changed
     */
    public function setParent($id, $ord = 99, $force = false) {
        if ($force || RML_Structure::getInstance()->isAllowedTo($id, $this->type)) {
            $oldParent = $this->parent;
            
            $this->parent = $id;
            
            global $wpdb;
            
            // Save in database
            $table_name = RML_Core::getInstance()->getTableName();
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET parent=%d, ord=%d WHERE id = %d", $id, $ord, $this->id));
            
            // Reset
            $this->absolutePath = "";
            
            // Update children in parents
            // Update will be processed in action RML/Structure/Rebuild
            
            do_action('RML/Folder/Moved', $this, $id, $ord, $force);
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
     * @return boolean
     */
    public function setName($name) {
        if (strpbrk($name, "\\/?%*:|\"<>") === FALSE && $this->id > 0 && strlen(trim($name)) > 0) {
            if (RML_Structure::getInstance()->hasChildSlug($this->parent, $name, false)) {
                return false;
            }
            
            global $wpdb;
            
            // Reset
            $this->name = $name;
            $this->slug(true);
            $this->updateThisAndChildrensAbsolutePath();
            
            // Save in Database
            $table_name = RML_Core::getInstance()->getTableName();
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET name=%s WHERE id = %d", $name, $this->id));
            
            do_action('RML/Folder/Renamed', $name, $this);
            
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * It iterates all chrildrens of this folder recursivly and
     * updates the absolute path.
     * 
     * @recursive through all children folders
     */
    public function updateThisAndChildrensAbsolutePath() {
        // Update childrens
        if (is_array($this->children) && count($this->children)) {
            foreach ($this->children as $key => $value) {
                $value->updateThisAndChildrensAbsolutePath();
            }
        }
        
        // Update this absolute path
        $this->absolutePath(true);
    }
    
    /**
     * Gets the count of the files in this folder.
     * 
     * @return int
     */
    public function getCnt($forceReload = false) {
        if ($this->cnt === null || $forceReload) {
            $query = new RML_WP_Query_Count(
                apply_filters('RML/Folder/QueryCountArgs', array(
                	'post_status' => 'inherit',
                	'post_type' => 'attachment',
                	'rml_folder' => $this->id
                ))
            );
            if (isset($query->posts[0])) {
                $this->cnt = $query->posts[0];
            }else{
                $this->cnt = 0;
            }
        }
        return $this->cnt;
    }
    
    public function getType() {
        return $this->type;
    }
    
    /**
     * Returns childrens of this folder.
     * 
     * @return array of RML_Folder
     */
    public function getChildrens() {
        return $this->children;
    }
    
    /**
     * Check if folder is a RML_TYPE_...
     * 
     * @param $folder_type (@see ./real-media-library.php for Constant-Types)
     * @return boolean
     */
    public function is($folder_type) {
        return $this->type == $folder_type;
    }
}

?>