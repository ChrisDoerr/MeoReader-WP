<?php
/**
 * The MeoReader Categories API
 *
 * @category    MeoReader
 * @package     Categories
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Categories {
  
  /**
   * @var object    WordPress database object.
   */
  protected $DB;
  
  /**
   * @var string    Plugin handler.
   */
  protected $pluginSlug;

  /**
   * @var array Array of nonces and actions for this class.
   */
  protected $nonceData;

  /**
   * The constructor.
   *
   * @param object  $wpdb         WordPress database object.
   * @param string  $pluginSlug   Plugin handler.
   */
  public function __construct( $wpdb, $pluginSlug ) {
    
    $this->DB         = $wpdb;
    
    $this->slug       = $pluginSlug;
    
    $this->nonceData  = $this->createNonces();

  }

  /**
   * Add a category (if it doesn't already exist
   *
   * @param   string    $catName  Category name.
   * @return  bool|int            FALSE if the category could not be added or (int) the ID the (newly or already existing) category.
   */
  public function addCategory( $catName ) {
    
    $catName  = trim( strip_tags( $catName ) );
    
    /* Missing or Invalid Parameter */
    if( $catName == '' ) {
      
      return false;
      
    }
    
    $catStatus = $this->categoryExists( $catName );
    
    /**
     * There is no need to report an error if the category name already exists.
     * Instead, simply return the catgory ID so you can treat it as if it were newly added.
     */
    if( is_numeric( $catStatus ) ) {
      
      return $catStatus;
      
    }
    
    $status   = $this->DB->insert(
      MEOREADER_TBL_CATEGORIES,
      array(
        'name' => $catName
      ),
      array(
        '%s'
      )
    );
    
    return ( $status == false ) ? false : $this->DB->insert_id;
    
  }
    
  /**
   * Rename an existing category.
   *
   * @param   int       $catIDFrom    ID of the category to be renamed.
   * @param   string    $newCatName   The new category name.
   * @return  bool|int                TRUE if the category could be renamed or FALSE if the category could not be renamed or a category ID of an already existing category with the same name as the new one!
   */
  public function renameCategory( $catIDFrom, $newCatName ) {

    $newCatName = trim( strip_tags( $newCatName ) );
    
    $catIDFrom  = (int) $catIDFrom;

    if( $newCatName == '' || !$this->categoryExists( $catIDFrom ) || $this->categoryExists( $newCatName ) ) {
      
      return false;
      
    }
    
    $status     = $this->DB->update(
      MEOREADER_TBL_CATEGORIES,
      array(
        'name'  => $newCatName
      ),
      array(
        'id'    => $catIDFrom
      ),
      null,
      array(
        '%d'
      )
    );

    return ( $status === false ) ? false : true;

  }
    
  /**
   * Delete a given category, either by name or by ID.
   *
   * Internally this method operated with the category ID.
   * So in case a category NAME will be passed the catID will be detected first before deleting it.
   *
   * @param string|int  $cat Category to be deleted, either the (int) category ID or (string) the category name.
   * @return bool TRUE in case the category could be deleted or FALSE if that was not the case or the ID or name could not be found.
   */
  public function deleteCategory( $cat ) {
    
    /* Either the parameter is already a (int) catID or get the catID for the given (string) category name. */
    $catID    = ( is_numeric( $cat ) ) ? (int) $cat : $this->getCategoryByName( $cat );

    /* The category with the ID 1 CANNOT BE DELETED as this is where feeds from other deleted categories will be assigned to! */
    if( $catID === false || $catID === 1 || $this->moveFeedsToDefaultCategory( $catID ) === false ) {
      
      return false;
      
    }
    
    $sql      = $this->DB->prepare( 'DELETE FROM ' . MEOREADER_TBL_CATEGORIES . ' WHERE id = %d LIMIT 1', $catID );
    
    $result   = $this->DB->query( $sql );
    
    return ( $result == true ) ? true : false;
    
  }
  
  /**
   * Helper: Move all feeds of a given category (by catID) to the default category (id = 1).
   *
   * @param   int   $catID    ID of the category the feeds are currently assigned to.
   * @return  bool            TRUE if the feeds could be moved or FALSE if that was not the case.
   */
  protected function moveFeedsToDefaultCategory( $catID ) {
    
    $catID  = (int) $catID;
    
    $status = $this->DB->update(
      MEOREADER_TBL_FEEDS,
      array(
        'cat_id'  => 1
      ),
      array(
        'cat_id'  => $catID
      ),
      null,
      array(
        '%d'
      )
    );
    
    return ( $status === false ) ? false : true;
    
  }
  

  /**
   * Get a list of all available categories.
   *
   * @param   string      $sortBy   (Optional) Sort the list either by 'name' or 'id'. Default is by 'name'.
   * @return  bool|array            FALSE if no categories could be found or an array containing a list of all catgories with their name and ID each.
   */
  public function getCategoryList( $sortBy = 'name' ) {
    
    $orderBy    = ( strtolower( $sortBy ) == 'id' ) ? 'id' : 'name';

    /* Get the list of all categories while counting all the feeds that are assigned to each single one. */
    $sql  = '   SELECT cats.id,';
    $sql .= '          cats.name,';
    $sql .= '          COUNT( feeds.id ) AS items';
    $sql .= '     FROM ' . MEOREADER_TBL_CATEGORIES . ' AS cats ';
    $sql .= 'LEFT JOIN ' . MEOREADER_TBL_FEEDS . ' AS feeds ';
    $sql .= '       ON ( cats.id = feeds.cat_id )';
    $sql .= ' GROUP BY cats.id';
    $sql .= ' ORDER BY cats.name ASC';

    $results    = $this->DB->get_results( $sql, ARRAY_A );
    
    // @question Is it even worth type casting here?
    //return ( $results === false ) ? false : array_map( create_function( "\$arr", "\$arr['id'] = (int) \$arr['id']; return \$arr;" ), $results );
    return ( $results === false ) ? false : array_map( array( $this, "castIdToInt" ), $results );
    
  }
  
  public function castIdToInt( $a ) {
    if( isset( $a["id"]) ) {
      $a["id"] = (int) $a["id"];
    }
    return $a;
  }
  
  
  /**
   * Helper: Get Category ID by category name
   *
   * @param   string    $catName    Name of the category whose ID is being requested.
   * @return  bool|int              FALSE in case the category could not be found OR the category ID for the given category name.
   */
  public function getCategoryByName( $catName ) {
    
    $sql        = $this->DB->prepare( 'SELECT id FROM ' . MEOREADER_TBL_CATEGORIES . " WHERE name = '%s' LIMIT 1", $catName );
    
    $result     = $this->DB->get_row( $sql, ARRAY_A );

    return ( null !== $result && isset( $result['id'] ) ) ? (int) $result['id'] : false;
    
  }
  

  /**
   * Helper: Get Category name by category ID
   *
   * @param   string    $catID    ID of the category whose name is requested.
   * @return  bool|int            FALSE in case the category could not be found OR the category name for the given category ID.
   */
  public function getCategoryByID( $catID ) {
    
    $catID      = (int) $catID;
    
    $sql        = $this->DB->prepare( 'SELECT name FROM ' . MEOREADER_TBL_CATEGORIES . " WHERE id = '%s' LIMIT 1", $catID );
    
    $result     = $this->DB->get_row( $sql, ARRAY_A );

    return ( null !== $result && isset( $result['name'] ) ) ? trim( strip_tags( $result['name'] ) ) : false;
    
  }

   
  /**
   * Helper: Does Category exist?
   *
   * This method can take category IDs as well as category names as argument!
   *
   * @param   string|int  $cat  Either the category name or a category ID
   * @return  int|bool          FALSE if the category does not exist. Or the (int) ID in case the category exists.
   */
  public function categoryExists( $cat ) {

    /* $cat is the category ID */
    if( is_numeric( $cat ) ) {
      
      $sql      = $this->DB->prepare( 'SELECT id FROM ' . MEOREADER_TBL_CATEGORIES . ' WHERE id = %d LIMIT 1', (int) $cat );
      
    }
    /* $cat id the category name */
    else {
      
      $sql      = $this->DB->prepare( 'SELECT id FROM ' . MEOREADER_TBL_CATEGORIES . " WHERE name = '%s' LIMIT 1", $cat );
      
    }
    
    $result     = $this->DB->get_row( $sql, ARRAY_A );

    return ( null !== $result && isset( $result['id'] ) ) ? (int) $result['id'] : false;
    
  }
  
  /**
   * Store the "currently selected" category (in the READER) in the plugin settings.
   * This setting can then be used to remember the user's selection on his next visit.
   *
   * @param   int   $catID    ID of the category to be set as "currently selected".
   * @return  bool            TRUE if the option could be saved or FALSE if there was a problem.
   */
  public function setCategoryAsCurrentTab( $catID ) {
    
    $catID                    = (int) $catID;
    
    $catID                    = abs( $catID );
    
    $options                  = get_option( $this->slug );
    
    $options['currentCatTab'] = $catID;
    
    $status                   = update_option( $this->slug, $options );
    
    return ( $status === false ) ? false : true;
    
  }
  
  /**
   * Get the "currently selected" category tab item (to be used in the READER).
   *
   * @return  int   The ID of the "currently selected" category item.
   */
  public function getCurrentCatTab() {
    
    $options = get_option( $this->slug );
    
    return $options['currentCatTab'];
    
  }

  /**
   * Get the array of nonce data for this class.
   *
   * @return array  Array of the nonces and actions.
   */
  public function getNonces() {
    
    return $this->nonceData;
    
  }
  
  /**
   * Create nonces and actions for this class.
   * 
   * Nonces will mostly only be implemented for actions that require WRITING permissions.
   * READING permissions are not as critical in the context of this plugin.
   *
   * @return array  Array of the nonces and actions.
   */
  public function createNonces() {
    
    $data   = array();

    $data[] = MeoReader_Core::createNonceData( 'meoReader_addCategory' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_renameCategory' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_deleteCategory' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_setCategoryAsCurrentTab' );

    return $data;
    
  }
  
  
  /**
   * Delete all categories at once.
   * 
   * @return bool FALSE in case there was a problem with detecting the categories or TRUE if the categories could be deleted.
   */
  public function deleteAllCategories() {
    
    $cats = $this->getCategoryList();

    if( $cats === false ) {
      
      return false;
      
    }
    
    foreach( $cats as $cat ) {
      
      if( $cat['id'] > 1 ) {

        $this->deleteCategory( $cat['id'] );
      
      }
      
    }
    
    return true;

  }

}
?>