<?php
/**
 * The MeoReader Ajax Handler
 *
 * Handle all incoming Ajax requests for this plugin with this class
 * by acting as man-in-the-middle between the request and making the proper API calls.
 * Also provide all answeres as JSON object string!
 *
 * @category    MeoReader
 * @package     Ajax
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Ajax extends Meomundo_WP {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;

  /**
   * @var object  Category API (model) object.
   */
  protected $CatAPI;
  
  /**
   * @var object  Feed API (model) object.
   */
  protected $FeedAPI;
  
  /**
   * @var object  Entry API (model) object.
   */
  protected $EntryAPI;
  
  /**
   * @var object  Subscription API (model) object.
   */
  protected $SubAPI;

  /**
   * @var object  Archive API (model) object.
   */
  protected $ArchiveAPI;

  /**
   * @var array   Plugin options.
   */
  protected $options;
  
  /**
   * The constructor.
   *
   * @param string  $absolutePluginPath   Absolute path to the plugin directory.
   * @param string  $pluginURL            URL to the plugin directory.
   * @param string  $pluginSlug           Plugin handler.
   * @param object  $wpdb                 WordPress database object.
   */
  public function __construct( $absolutePluginPath, $pluginURL, $pluginSlug, $wpdb ) {
    
    parent::__construct( $absolutePluginPath, $pluginURL, $pluginSlug );
    
    $this->loadClass( 'MeoReader_Categories' );

    $this->loadClass( 'MeoReader_Feeds' );

    $this->loadClass( 'MeoReader_Entries' );

    $this->loadClass( 'MeoReader_Subscriptions' );

    $this->loadClass( 'MeoReader_Archive' );

    $this->DB         = $wpdb;
    
    $this->CatAPI     = new MeoReader_Categories( $wpdb, $this->slug );
    
    $this->FeedAPI    = new MeoReader_Feeds( $wpdb, $this->CatAPI, $this->slug );
    
    $this->EntryAPI   = new MeoReader_Entries( $wpdb );
    
    $this->SubAPI     = new MeoReader_Subscriptions( $this->CatAPI, $this->FeedAPI );

    $this->ArchiveAPI = new MeoReader_Archive( $wpdb );
    
    $this->options    = get_option( $pluginSlug );

  }

  /**
   * Add a feed to a given category.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires bool   $_POST['trueIfExists']    (Optional) If a feed already exists, return TRUE as if it was newly added.
   * @requires string $_POST['feedURL']         Full URL of the feed to be added.
   * @requires int    $_POST['categoryID']      ID of the category the feed shall be assigned to.
   * @return  array                             An array containing the validity of the request (true/false) and in case of an error a message.
   */
  public function addFeed() {
    
    $trueIfExists   = ( isset( $_POST['trueIfExists'] ) && $_POST['trueIfExists'] === 'true' ) ? true : false;
    
    $feedURL        = isset( $_POST['feedURL'] )  ? trim( strip_tags( $_POST['feedURL'] ) ) : '';
    
    $categoryID     = isset( $_POST['catID'] )    ? (int) $_POST['catID']                   : 1;
    
    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }
    
    if( $feedURL == '' ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }
    
    /**
     * Let the feed API do the actual operation.
     */
    $result = $this->FeedAPI->addFeed( $feedURL, $categoryID );
    
    if( isset( $result['request'] ) && $result['request'] === false ) {
      
      return $result;
      
    }

    if( is_numeric( $result ) || $trueIfExists === true ) {
      
      return array(
        'request' => true
      );
      
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Feed could not be added!', 'error message', 'meoreader' )
      );
    
    }
    
  }


  /**
   * Delete a category (by a given ID).
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int   $_POST['catID']   Category ID. Default is 1 (= "Unsorted").
   * @return    array                   An array containing the validity of the request (true/false) and in case of an error a message.
   */
  public function deleteCategory() {
    
    $categoryID = isset( $_POST['catID'] ) ? (int) $_POST['catID'] : 1;

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    /**
     * Category ID = 1 = "Unsorted" and cannot be deleted!
     */
    if( $categoryID < 2 ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }
    
    /**
     * Let the category API do the actual operation.
     */
    $result = $this->CatAPI->deleteCategory( $categoryID );
    
    if( $result == true ) {
      
      return array(
        'request' => true
      );
      
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Category could not be added!', 'error message', 'meoreader' )
      );

    }
    
  }

  /**
   * Delete a feed (by a given ID)
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int   $_POST['feedID']  Feed ID. Default is 1.
   * @return    array                   An array containing the validity of the request (true/false) and in case of an error a message.
   */
  public function deleteFeed() {
    
    $feedID = isset( $_POST['feedID'] ) ? (int) $_POST['feedID'] : 1;

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }
    
    $URL = $this->FeedAPI->getHomepageURLByID( $feedID );
    
    /**
     * Let the feed API do the actual operation.
     */
    $result = $this->FeedAPI->deleteFeed( $feedID );
    
    if( $result == true ) {
      
      /**
       * Also delete the favicon for this feed.
       */
      if( trim( $URL ) !== '' ) {
        
        $filename   = md5( strtolower( $URL ) );
        
        $file       = MEOREADER_PATH . 'favicons' . DIRECTORY_SEPARATOR . $filename . '.png';
        
        if( file_exists( $file ) ) {
          
          @unlink( $file );
          
        }
        
      }
      
      return array(
        'request' => true
      );
      
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Category could not be added!', 'error message', 'meoreader' )
      );

    }
    
  }

  /**
   * Rename a category.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int     $_POST['catFrom']     ID of the category to be renamed.
   * @requires  string  $_POST['catTo']       The new name of the category.
   * @requires  string  $_POST['meoNonce']    Nonce to verify user action.
   * @return    array                         An array containing the validity of the request (true/false) and in case of an error a message.
   */
  public function renameCategory() {
  
    $from   = ( isset( $_POST['catFrom'] ) )  ? (int) $_POST['catFrom']                   : 1;
    
    $to     = ( isset( $_POST['catTo'] ) )    ? trim( strip_tags( $_POST['catTo'] ) )     : '';
    
    $nonce  = ( isset( $_POST['meoNonce'] ) ) ? (string) $_POST['meoNonce']               : '';

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    if( $from < 1 || $to == '' || !wp_verify_nonce( $nonce, 'meoReader_renameCategory' ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }
  
    /**
     * Let the category API do the actual operation.
     */
    $result = $this->CatAPI->renameCategory( $from, $to );
    
    if( $result === true ) {
      
      return array(
        'request' => true
      );
      
    }
    elseif( is_numeric( $result ) ) {

      return array(
        'request' => false,
        'message' => _x( 'New category name already exists!', 'error message', 'meoreader' )
      );

    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Category could not be renamed!', 'error message', 'meoreader' )
      );

    }
    
  }

  /**
   * Get the complete subscription list (including categories and feeds).
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @return array An array containing the validity of the request (true/false), the raw-data subscription list and in case of an error a message.
   */
  public function getSubscriptionList() {

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    return array(
      'request' => true,
      'data'    => $this->SubAPI->getSubscriptions()
    );

  }
  
  
  /**
   * Get the complete subscription list (including categories and feeds).
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @return array An array containing the validity of the request (true/false) and in case of an error, a message.
   */
  public function getCategoryList() {

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

     /**
     * Let the category API do the actual operation.
     */
    $categories = $this->CatAPI->getCategoryList();
    
    if( $categories !== false ) {
      
      return array(
        'request'     => true,
        'categories'  => $categories
      );
      
    }
    else {
    
      return array(
        'request' => false,
        'message' => _x( 'Could not get category list!', 'error message', 'meoreader' )
      );
    
    }

  }
  

  /**
   * Move a feed to a (maybe) new category.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int     $_POST['feedID']      ID of the feed to be moved.
   * @requires  int     $_POST['catID']       ID of the category this feed should be moves to.
   * @requires  string  $_POST['meoNonce']    Nonce to verify user action.
   * @return    array                         An array containing the validity of the request (true/false) and in case of an error, a message.
   */
  public function moveFeed() {
    
    $feedID = isset( $_POST['feedID'] )   ? (int) $_POST['feedID']      : null;

    $catID  = isset( $_POST['catID'] )    ? (int) $_POST['catID']       : 1;

    $nonce  = isset( $_POST['meoNonce'] ) ? (string) $_POST['meoNonce'] : '';

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    if( null == $feedID || !wp_verify_nonce( $nonce, 'meoReader_moveFeed' ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }

    /**
     * Let the feed API do the actual operation.
     */
    $status = $this->FeedAPI->moveFeed( $feedID, $catID );
    
    if( $status !== false ) {
      
      return array(
        'request' => true
      );
      
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Could not move the feed!', 'error message', 'meoreader' )
      );
      
    }
    
  }


  /**
   * General Setter.
   *
   * @param string  $key      Name of the member variable to be set.
   * @param string  $value    Value of the newly set member variable.
   */
  public function set( $key, $value ) {
    
    $this->$key = $value;
    
  }


  /**
   * Mark a single entry as READ
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int     $_POST['entryID']     The ID of the entry to be marked as READ.
   * @requires  string  $_POST['meoNonce']    Nonce to verify user action.
   * @return    array                         An array containing the validity of the request (true/false) and in case of an error, a message.
   */
  public function markEntryAsRead() {
    
    $entryID  = isset( $_POST['entryID'] )  ? (int) $_POST['entryID']     : null;

    $nonce    = isset( $_POST['meoNonce'] ) ? (string) $_POST['meoNonce'] : '';

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

/*
    if( null == $entryID || !wp_verify_nonce( $nonce, 'meoReader_markEntryAsRead' ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }
*/

    /**
     * Let the feed API do the actual operation.
     */
    $status = $this->EntryAPI->markEntryAsRead( $entryID );
    
    if( $status !== false ) {
      
      return array(
        'request' => true
      );
      
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Could not mark entry as READ!', 'error message', 'meoreader' )
      );
      
    }
  
  }



  /**
   * Mark a single entry as UNREAD
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int     $_POST['entryID']     The ID of the entry to be marked as UNREAD.
   * @requires  string  $_POST['meoNonce']    Nonce to verify user action.
   * @return    array                         An array containing the validity of the request (true/false) and in case of an error, a message.
   */
  public function markEntryAsUnRead() {
    
    $entryID  = isset( $_POST['entryID'] )  ? (int) $_POST['entryID']     : null;

    $nonce    = isset( $_POST['meoNonce'] ) ? (string) $_POST['meoNonce'] : '';

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

/*
    if( null == $entryID || !wp_verify_nonce( $nonce, 'meoReader_markEntryAsRead' ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }
*/

    /**
     * Let the feed API do the actual operation.
     */
    $status = $this->EntryAPI->markEntryAsUnRead( $entryID );
    
    if( $status !== false ) {
      
      return array(
        'request' => true
      );
      
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Could not mark entry as READ!', 'error message', 'meoreader' )
      );
      
    }
  
  }


  /**
   * Marking all entries as READ
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  string  $_POST['meoNonce']    Nonce to verify user action.
   * @return    array                     	  An array containing the validity of the request (true/false) and in case of an error, a message.
   */
  public function markAllEntriesAsRead() {

    $nonce    = isset( $_POST['meoNonce'] ) ? (string) $_POST['meoNonce'] : '';

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    if( !wp_verify_nonce( $nonce, 'meoReader_markAllAsRead' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
    
    }
    
    /**
     * Let the feed API do the actual operation.
     */
    $status = $this->EntryAPI->markAllAsRead();
    
    if( $status !== false ) {
      
      return array(
        'request' => true
      );
      
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Could not mark all entries as READ!', 'error message', 'meoreader' )
      );
      
    }
  
  }


  /**
   * Marking all entries as READ
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int   $_POST['pageNr']    Current page number - will be used as offset.
   * @requires  int   $_POST['catID']     ID of the currently shown category.
   * @return    array                     An array containing the validity of the request (true/false) and in case of an error, a message.
   */
  public function getEntryList() {
    
    $pageNr = isset( $_POST['pageNr'] ) ? (int) $_POST['pageNr']  : 1;
    
    $catID  = isset( $_POST['catID'] )  ? (int) $_POST['catID']   : 0;

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }
    
    /**
     * Let the feed API do the actual operation.
     */
    $status = $this->EntryAPI->getEntriesByCategory( $catID, $pageNr, $this->options['entriesPerPage'] );

    if( $status !== false ) {
      
      $dimEntries = count( $status );
      
      for( $i = 0; $i < $dimEntries; $i++ ) {
        
        if( isset( $status[$i]['enclosures'] ) && $status[$i]['enclosures'] !== '' ) {
          
          $status[$i]['enclosures'] = unserialize( $status[$i]['enclosures'] );
          
        }
        
      }
      
      $user = 'noone';
      
      if( MeoReader_Core::current_user_can( 'read' ) ) {
        
        $user = 'singleUser';
        
      }
      
      if( MeoReader_Core::current_user_can( 'admin' ) ) {
      
        $user = 'master';
      
      }
      
      return array(
        'request'         => true,
        'entries'         => $status,
        'dateformat'      => get_option('date_format'),
        'timeformat'      => get_option('time_format'),
        'twitter'         => $this->options['twitter'],
        'user'            => $user,
        'userCanPublish'  => $this->options['userCanPublish']
      );
      
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Could not get list of entries!', 'error message', 'meoreader' )
      );
      
    }    
    
  }

  /**
   * Count unread entries: All AS WELL AS for a given category.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int   $_POST['catID']   ID of the category to be counted separately.
   * @return    array                   An array containing information about the validity of the request (true/false) and both total values (all/category).
   */
  public function countUnreadEntries() {

    $catID  = isset( $_POST['catID'] ) ? (int) $_POST['catID'] : 0;

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }
    
    return array(
      'request' => true, /* which is only set to stay in the applied Ajax response pattern */
      'totals'  => $this->EntryAPI->countUnreadEntries( $catID )
    );
    
  }
  
  /**
   * Toggle an entry's archive state (1 = archive element, 0 = not an archive element).
   * Archive elements, for example, won't be deleted even if they are older than the plugin options allow.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int     $_POST['entryID']     ID of the entry whose archive state shall be toggled.
   * @requires  string  $_POST['meoNonce']    Nonce to verify user action.
   * @return    array                         An array containing information about the validity of the request (true/false) and maybe an error message in case a technical error has occured.
   */
  public function toggleEntryArchiveState() {
    
    $entryID  = isset( $_POST['entryID'] )  ? (int) $_POST['entryID']     : 0;

    $nonce    = isset( $_POST['meoNonce'] ) ? (string) $_POST['meoNonce'] : '';

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    if( !wp_verify_nonce( $nonce, 'meoReader_toggleArchiveItem' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
    
    }
    
    $status   = $this->ArchiveAPI->toggleArchiveItem( $entryID );
    
    if( $status === true ) {

      return array(
        'request' => true
      );

    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( "Could not toggle entry's archive state!", 'error message', 'meoreader' )
      );
      
    }
    
  }
  

  /**
   * Get all archive elements.
   * Use the current page number for retrieving blocks of data instead of all results at once.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int   $_POST['pageNr']    The current page number of the archive VIEW.
   * @return    array                     An array containing information about the validity of the request (true/false) and maybe an error message in case a technical error has occured.
   */
  public function getArchiveList() {
    
    $pageNr   = isset( $_POST['pageNr'] ) ? (int) $_POST['pageNr'] : 1;

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    $entries  = $this->ArchiveAPI->getArchiveEntries( $pageNr, $this->options['entriesPerPage'] );

    if( $entries === false ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Cannot get archive elements!', 'error message', 'meoreader' )
      );
      
    }
    else {

      $dimEntries = count( $entries );
      
      for( $i = 0; $i < $dimEntries; $i++ ) {
        
        if( isset( $entries[$i]['enclosures'] ) && $entries[$i]['enclosures'] !== '' ) {
          
          $entries[$i]['enclosures'] = unserialize( $entries[$i]['enclosures'] );
          
        }
        
      }

      return array(
        'request' => true,
        'entries' => $entries
      );
      
    }
    
  }
  
  
  /**
   * Gather a list of all feeds, no matter of the category.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @return  array   An array containing information about the validity of the request (true/false), the list of all feeds, and maybe an error message in case a technical error has occured.
   */
  public function getListOfAllFeeds() {
    
    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    $feeds = $this->FeedAPI->getListOfAllFeeds();
    
    if( $feeds === false ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Seems like there are no feeds!', 'error message', 'meoreader' )
      );
      
    }
    else {
      
      return array(
        'request' => true,
        'feeds'   => $feeds
      );
      
    }
    
  }
  
  
  /**
   * Update/Refresh a given feed (by its ID).
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int     $_POST['feedID']      ID of the feed whose items/entries shall be indexed.
   * @requires  string  $_POST['meoNonce']    Nonce to verify user action.
   * @return    array                         An array containing information about the validity of the request (true/false) and maybe an error message in case a technical error has occured.
   */
  public function updateFeed() {
    
    $feedID   = isset( $_POST['feedID'] )   ? (int) $_POST['feedID']      : 0;

    $nonce    = isset( $_POST['meoNonce'] ) ? (string) $_POST['meoNonce'] : '';
    
    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    if( $feedID < 1 || !wp_verify_nonce( $nonce, 'meoReader_updateFeed' ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }
    
    $status = $this->FeedAPI->updateFeed( $feedID );
    
    if( $status === true ) {
      
      return array(
        'request' => true
      );
      
    }
    else {
      
      return $status;
      
    }
    
  }

  
  /**
   * Add a category by its name.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  string  $_POST['catName']     Name of the (new) category.
   * @requires  string  $_POST['meoNonce']    Nonce to verify user action.
   * @return    array                         An array containing information about the validity of the request (true/false) and maybe an error message in case a technical error has occured.
   */
  public function addCategory() {

// @todo gar nicht in MeoReader.Subscriptions.js implementiert?! Warum nicht?
    
    $catName  = isset( $_POST['catName'] )  ? trim( strip_tags( $_POST['catName'] ) ) : '';

    $nonce    = isset( $_POST['meoNonce'] ) ? (string) $_POST['meoNonce']             : '';

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    if( $catName == '' || !wp_verify_nonce( $nonce, 'meoReader_addCategory' ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }
    
    $response = $this->CatAPI->addCategory( $catName );
    
    if( $response !== false ) {
      
      return array(
        'request' => true,
        'catID'   => $response
      );
      
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Could not add category!', 'error message', 'meoreader' )
      );
      
    }
    
  }
  
  /**
   * Set a category ID as "currently selected" (in the select list at the top of the subscriptions page).
   * This can be used for remembering the selection on the next page visit.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int     $_POST['catID']       Name of the (new) category.
   * @requires  string  $_POST['meoNonce']    Nonce to verify user action.
   * @return    array                         An array containing information about the validity of the request (true/false) and maybe an error message in case a technical error has occured.
   */
  public function setCategoryAsCurrentTab() {
    
    $catID  = isset( $_POST['catID'] )    ? (int) $_POST['catID']       : null;

    $nonce  = isset( $_POST['meoNonce'] ) ? (string) $_POST['meoNonce'] : '';
    
    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    if( null === $catID || !wp_verify_nonce( $nonce, 'meoReader_setCategoryAsCurrentTab' ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }

    $status = $this->CatAPI->setCategoryAsCurrentTab( $catID );
    
    if( $status === true ) {
      
      return array(
        'request' => true
      );
    
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Could not set category as current tab!', 'error message', 'meoreader' )
      );
      
    }

  }
    
  /**
   * Get the ID of the currently selected cateogry.
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @return  array This request will always be TRUE but the array also contains the requested category ID.
   */
  public function getCurrentCatTab() {
      
    return array(
      'request' => true,
      'catID'   => $this->CatAPI->getCurrentCatTab()
    );
    
  }
  
  
  /**
   * Calculate the total number of pages (for a given category).
   *
   * Since this is an Ajax handler, the paramters are not passed as arguments but in a POST request.
   *
   * @requires  int   $_POST['catID']   ID of the category whose total number of pages shall be calculated.
   * @return    array                   This request will always be TRUE but the array also contains the total number of pages - which will always be 1 or higher!
   */
  public function getTotalNumberOfPages() {

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }

    $catID                = isset( $_POST['catID'] ) ? (int) $_POST['catID'] : 1;
  
    $totalNumberOfEntries = $this->EntryAPI->getTotalNumberOfEntries( $catID );
    
    $totalNumberOfPages   = $this->EntryAPI->getTotalNumberOfPages( $this->slug, $totalNumberOfEntries );

    if( $totalNumberOfPages < 1 ) {
      $totalNumberOfPages = 1;
    }
    
    return array(
      'request'     => true,
      'totalPages'  => $totalNumberOfPages
    );
    
  }
  
  
  /**
   * Create a new post from a given entry.
   *
   * @requires  int $_POST['entryID']   ID of the entry a new post should be created for.
   * @return    array                   An array containing information about the validity of the request (true/false) and maybe an error message in case a technical error has occured.
   */
  public function createPostFromEntry() {
    
    /* Check user previliges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }
    
    $entryID = isset( $_POST['entryID'] ) ? (int) $_POST['entryID'] : 0;
    
    $status = $this->EntryAPI->createPostFromEntry( $entryID, $this->options['postStatus'] );
    
    if( $status === false ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Post could not be created!', 'error message', 'meoreader' )
      );
      
    }
    else {
      
      return array(
        'request'     => true,
        'postID'      => (int) $status,
        'postEditor'  => $this->options['postEditor']
      );
      
    }
    
  }
  
  /**
   * Toggle the timerange option 'timerange': today <=> all time.
   *
   * @return true Always true since no actual operation takes place.
   */
  public function toggleTimerange() {
    
    $options = get_option('meoreader');
    
    /* Switch the current state */
    $options['timerange'] = ( isset( $options['timerange'] ) && $options['timerange'] === 'today' ) ? 'alltime' : 'today';
    
    update_option( 'meoreader', $options );
    
    return array(
      'request' => true
    );
    
  }
  

  /**
   * Delete all categories (besides "Unsorted") and move all feeds to "Unsorted"
   *
   * @return array Array containing information about the success of the requested action.
   */
  public function deleteAllCategories() {

    /* Check user previliges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }
    
    $status = $this->CatAPI->deleteAllCategories();

    return array(
      'request' => true
    );
    
  }

  /**
   * Delete all feeds
   *
   * @return array Array containing information about the success of the requested action.
   */
  public function deleteAllFeeds() {
    
    /* Check user previliges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return array(
        'request' => false,
        'message' => _x( 'You are not allowed to do that!', 'error message', 'meoreader' )
      );

    }
    
    $status = $this->FeedAPI->deleteAllFeeds();

    return array(
      'request' => true
    );
    
  }
  
  
  /**
   * Add entries to the archive (when importing data!).
   *
   * @return array Array containing information about the success of the requested action.
   */
  public function addArchiveEntries() {

    if( !isset( $_POST['entries'] ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
      );
      
    }

    $status = $this->FeedAPI->addArchiveEntries( $_POST['entries'] );
    
    if( $status === true ) {

      return array(
        'request' => true
      );
    
    }
    else {
      
      return array(
        'request' => false,
        'message' => _x( 'Could not add achive entry', 'error message', 'meoreader' )
      );
      
    }
    
  }

}
?>