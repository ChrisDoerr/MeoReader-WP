<?php
/**
 * WordPress backend ghost page: Add a feed.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_AddFeed extends Meomundo_WP {
  
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
   * @requires string $_POST['meoReaderForm_AddFeed_url']       The feed URL.
   * @requires string $_POST['meoReaderForm_AddFeed_category']  The category ID.
   * @requires string $_POST['meoNonce']                        Nonce to verify user action.
   * @return string HTML page code.
   */
  public function controller() {

    $feedURL  = isset( $_POST['meoReaderForm_AddFeed_url'] )      ? trim( strip_tags( $_POST['meoReaderForm_AddFeed_url'] ) ) : '';
    
    $catID    = isset( $_POST['meoReaderForm_AddFeed_category'] ) ? (int) $_POST['meoReaderForm_AddFeed_category']            : null;
    
    $nonce    = isset( $_POST['meoNonce'] )                       ? $_POST['meoNonce']                                        : '';

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }

    if( null == $catID || $catID < 1 || $feedURL == '' || !preg_match( '#^http(s)?:\/\/#i', $feedURL ) ) {
      
      return '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";
      
    }
    
    $html       = "";

    // FORM: Add Feed
    if( isset( $_POST['meoReaderForm_AddFeed_status'] ) && $_POST['meoReaderForm_AddFeed_status'] == 1 && wp_verify_nonce( $nonce, 'meoReader_addFeed' ) ) {
      
      $addFeedStatus = $this->addFeed( $feedURL, $catID );

      if( $addFeedStatus['request'] == false ) {
        
        if( isset( $addFeedStatus['message'] ) ) {
          
          $html .= '<p class="message error">' . $addFeedStatus['message'] . '</p>';
        
          $html .= '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";

          return $html;

        }

      }
      else {
        
        $html .= '<p class="message success">' . $addFeedStatus['message'] . '</p>';
        
        $html .= '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";
        
        return $html;
        
      }
      
    }
    else {
      
      return '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";

    }
    
  }
  
  /**
   * Add a feed and also immediately add all its entries to the database.
   *
   * @param   string  $feedURL    URL of the new RSS feed.
   * @param   int     $catID      ID of the category this new feed should be assigned to.
   * @return  array               An array validating the operation and in case of an error also contains an explanation of what went wrong.
   */
  public function addFeed( $feedURL, $catID ) {
    
    /* Use the feed API for doing the actual operation */
    $status = $this->FeedAPI->addFeed( $feedURL, $catID );

    /* There has been a techical/db problem. */
    if( $status == false || isset( $status['request'] ) && ( $status['request'] === false && $status['message'] === '' ) ) {
      
      return array(
        'request'   => false,
        'message'   => _x( 'Feed could not be added!', 'error message', 'meoreader' )
      );
      
    }
    /* The new feed already exists. */
    elseif( isset( $status['request'] ) && $status['request'] == false ) {
      
      return array(
        'request' => false,
        'message' => _x( 'You are already subscribing to this feed', 'error message', 'meoreader' )
      );
    
    }
    /* Everything is fine. The feed could be added. */
    else {
      
      return array(
        'request' => true,
        'message' => _x( 'The feed has been added.', 'error message', 'meoreader' )
      );
      
    }
    
  }
  
}
?>