<?php
/**
 * WordPress backend ghost page: Move a feed to another category.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_MoveFeed extends Meomundo_WP {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var object MeoReader_Category object.
   */
  protected $CatAPI;
  
  /**
   * @var object MeoReader_Feeds object.
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
    
    $this->DB         = $wpdb;
    
    $this->loadClass( 'MeoReader_Templates' );

    $this->loadClass( 'MeoReader_Categories' );

    $this->loadClass( 'MeoReader_Feeds' );

    $this->Templates  = new MeoReader_Templates( $absolutePluginPath, $pluginURL, $pluginSlug );

    $this->CatAPI     = new MeoReader_Categories( $wpdb, $pluginSlug );
    
    $this->FeedAPI    = new MeoReader_Feeds( $wpdb, $this->CatAPI, $pluginSlug );

  }
  
  /**
   * The CONTROLLER.
   *
   * @requires  int     $_POST['itemID']                           ID of the feed to be moved to another category.
   * @requires  int     $_POST['meoReaderForm_moveFeed_newCatID']  ID if the category the feed shall be moved to.
   * @return    string                                             HTML page code.
   */
  public function controller() {

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }
    $feedID = isset( $_GET['itemID'] )                            ? (int) $_GET['itemID']                           : null;

    $catID  = isset( $_POST['meoReaderForm_moveFeed_newCatID'] )  ? (int) $_POST['meoReaderForm_moveFeed_newCatID'] : null;

    if( null == $feedID || $feedID < 1 ) {
      
      $html .= '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";
      
      return $html;
      
    }
    
    $html       = "";

    /* The form "Move Feed" has been sent. */
    if( isset( $_POST['meoReaderForm_MoveFeed_status'] ) && $_POST['meoReaderForm_MoveFeed_status'] == 1 ) {
      
      $nonce = isset( $_POST['meoNonce'] ) ? $_POST['meoNonce'] : '';

      if( !wp_verify_nonce( $nonce, 'meoReader_moveFeed' ) ) {
        
        $html .= '<p class="message error">' . _x( "You're not allowed to do that!", 'error message', 'meoreader' ) . '</p>';
        
        $html .= '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";

        return $html;
        
      }
      
      $moveFeedStatus = $this->moveFeed( $feedID, $catID );

      if( $moveFeedStatus['valid'] == false ) {
        
        if( isset( $moveFeedStatus['message'] ) ) {
          
          $html .= '<p class="message error">' . $moveFeedStatus['message'] . '</p>';
        
          $html .= '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";
        
          return $html;

        }

      }
      else {
        
        $html .= '<p class="message success">' . $moveFeedStatus['message'] . '</p>';
        
        $html .= '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";
        
        return $html;
        
      }
      
    }
    // If the form has not been sent yet, show IT!
    else {
      
      $feedName   = $this->FeedAPI->getFeedName( $feedID );

      $categories = $this->CatAPI->getCategoryList();

      // Generate the (HTML) VIEW for this action.
      $html      .= $this->Templates->view(
        'Backend_moveFeed',
        array(
          'feedID'      => $feedID,
          'catID'       => $catID,
          'feedName'    => $feedName,
          'categories'  => $categories
        )
      );
      
      return $html;

    }
    
  }
  
  /**
   * Let the feed model handle the moving and prepare the result so it can
   * be evaluated properly on this page.
   * 
   * @param   int     $feedID   ID of the feed that shall be moved to another category.
   * @param   int     $catID    ID of the category this feed shall be moved to.
   * @return  array             An array containing information about the success of the operation and in case of an error an explanation of what went wrong.
   */
  public function moveFeed( $feedID, $catID ) {
    
    $status = $this->FeedAPI->moveFeed( $feedID, $catID );

    /* There has been a techical problem. */
    if( $status == false ) {
      
      return array(
        'valid'   => false,
        'message' => _x( 'The feed could not be moved!', 'error message', 'meoreader' )
      );
      
    }
    /* Everything is fine, the feed could be moved. */
    else {
      
      return array(
        'valid'   => true,
        'message' => _x( 'The feed has been moved.', 'error message', 'meoreader' )
      );
      
    }
    
  }
  
}
?>