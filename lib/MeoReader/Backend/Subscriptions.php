<?php
/**
 * WordPress Backend Page: Subscription Management
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_Subscriptions extends Meomundo_WP {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var object MeoReader_Category object.
   */
  protected $CatAPI;
  
  /**
   * @var object MeoReader_Feed object.
   */
  protected $FeedAPI;
  
  /**
   * @var object MeoReader_Templates object.
   */
  protected $Templates;
  
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
    
    $this->DB       = $wpdb;
    
    $this->loadClass( 'MeoReader_Templates' );

    $this->loadClass( 'MeoReader_Categories' );

    $this->loadClass( 'MeoReader_Feeds' );
    
    $this->loadClass( 'MeoReader_Subscriptions' );

    $this->Templates  = new MeoReader_Templates( $absolutePluginPath, $pluginURL, $pluginSlug );

    $this->CatAPI     = new MeoReader_Categories( $wpdb, $pluginSlug );
    
    $this->FeedAPI    = new MeoReader_Feeds( $wpdb, $this->CatAPI, $pluginSlug );
    
    $this->SubAPI     = new MeoReader_Subscriptions( $this->CatAPI, $this->FeedAPI );

  }
  
  /**
   * The CONTROLLER.
   *
   * @return string HTML page code.
   */
  public function controller() {
    
    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }

    $html = '<h3 class="admin">' . _x( 'Subscription Management', 'headline', 'meoreader' ) . "</h3>\n";
    
    /* Form ADD CATEGORY has been sent. */
    if( isset( $_POST['meoReaderForm_AddCategory_status'] ) && $_POST['meoReaderForm_AddCategory_status'] == 1 ) {
      
      $addCatStatus = $this->addCategory();

      if( $addCatStatus['valid'] == false ) {
        
        /**
         * Only spit out errors.
         * In case everything is fine (or the category already exists) just show the normal page.
         */
        if( isset( $addCatStatus['message'] ) ) {
          
          $html .= '<p class="message error">' . $addCatStatus['message'] . '</p>';
        
        }

      }
      
    }
    
    /**
     * The actual page controlling, based on the GET parameter ACTION.
     */
    if( isset( $_GET['action'] ) ) {
    
      switch( $_GET['action'] ) {

        case 'deleteCategory':

          $actionStatus = $this->deleteCategory();

          break;
        
        case 'renameCategory':

          $actionStatus = $this->renameCategory();

          break;
        
        case 'deleteFeed':
          
          $actionStatus = $this->deleteFeed();
          
          break;

        default:

          $actionStatus = array( 'valid' => false );

          break;

      }

      /**
       * Evaluating the results.
       * Here, spit out messages in case of errors as well as successfull operations
       * to provide a better feedback for the user.
       */
      if( $actionStatus['valid'] == false ) {
        
        if( isset( $actionStatus['message'] ) ) {
          
          $html .= '<p class="message error">' . $actionStatus['message'] . "</p>\n";
        
        }

      }
      else {

        if( isset( $deleteCatStatus['message'] ) ) {
          
          $html .= '<p class="message success">' . $actionStatus['message'] . "</p>\n";
          
        }
      
      }
      
    }
    
    $data = array(
      'subscriptions' => $this->SubAPI->getSubscriptions(),
      'options'       => get_option( $this->slug ),
      'nonces'        => array_merge( $this->CatAPI->getNonces(), $this->FeedAPI->getNonces() )
    );

    /* Generate the HTML page VIEW */
    $html .= $this->Templates->view( 'Backend_Subscriptions', $data );
    
    return $html;
    
  }
  
  
  /** 
   * Action: Add a new category.
   *
   * @requires  string      $_POST['']    Name of the category to be added.
   * @return    bool|array                TRUE in case the category could be added. FALSE in case of a missing/invalid parameter. An ARRAY containing a more detailed error message.
   */
  public function addCategory() {
    
    $catName  = isset( $_POST['meoReaderForm_AddCategory_name'] ) ? trim( strip_tags( $_POST['meoReaderForm_AddCategory_name'] ) )  : '';

    $nonce    = isset( $_POST['meoNonce'] )                       ? $_POST['meoNonce']                                              : '';

    if( $catName == '' || !wp_verify_nonce( $nonce, 'meoReader_addCategory' ) ) {
      
      return array( 'valid' => false );
      
    }
    
    $status = $this->CatAPI->addCategory( $catName );
    
    if( $status == false ) {
      
      return array(
        'valid'   => false,
        'message' => _x( 'Could not create category!', 'error message', 'meoreader' )
      );
    
    }
    else {
      
      return true;
      
    }
    
  }
  
  
  /**
   * Action: Delete a category (based on a category ID).
   *
   * @requires  int   $_GET['itemID']   ID of the category to be deleted.
   * @return    array                   An array containing the TRUE/FALSE state of the operation and maybe some error message.
   */
  public function deleteCategory() {
    
    $catID  = isset( $_GET['itemID'] )    ? (int) $_GET['itemID'] : null;
    
    $nonce  = isset( $_GET['meoNonce'] )  ? $_GET['meoNonce']     : '';

    /* catID = 1 ("Unsorted") maybe renamed but definitely cannot be deleted!! */
    if( null == $catID || $catID < 2  || !wp_verify_nonce( $nonce, 'meoReader_deleteCategory' ) ) {
      
      return array( 'valid' => false );
      
    }
    
    $status = $this->CatAPI->deleteCategory( $catID );
    
    if( $status == true ) {
      
      return array(
        'valid'   => true,
        'message' => _x( 'The category has been deleted.', 'error message', 'meoreader' )
      );
      
    }
    else {
      
      return array(
        'valid'   => false,
        'message' => _x( 'The category could not be deleted!', 'error message', 'meoreader' )
      );
      
    }
    
  }
  
  /**
   * Delete a feed.
   *
   * @requires  int   $_GET['itemID']   ID of the feed to be deleted.
   * @return    array                   An Array containing information about the success of the operation and in case of an error an explanation of what went wrong.
   */
  public function deleteFeed() {
    
    $feedID = isset( $_GET['itemID'] )    ? (int) $_GET['itemID'] : null;

    $nonce  = isset( $_GET['meoNonce'] )  ? $_GET['meoNonce']     : '';

    if( null == $feedID || $feedID < 1 || !wp_verify_nonce( $nonce, 'meoReader_deleteFeed' ) ) {
      
      return array( 'valid' => false );
      
    }
    
    $status = $this->FeedAPI->deleteFeed( $feedID );
    
    if( $status == true ) {
      
      return array(
        'valid'   => true,
        'message' => _x( 'The feed has been deleted.', 'error message', 'meoreader' )
      );
      
    }
    else {
      
      return array(
        'valid'   => false,
        'message' => _x( 'The feed could not be deleted!', 'error message', 'meoreader' )
      );
      
    }
    
  }
  
}
?>