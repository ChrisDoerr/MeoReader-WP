<?php
/**
 * WordPress backend ghost page: Reload feed/Refresh entry list.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_ReloadFeeds extends Meomundo_WP {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;

  /**
   * @var object MeoReader_Categories object.
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
   * @return string HTML page code.
   */
  public function controller() {

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }

    $html = '<h3>' . _x( 'Reload Feeds', 'headline', 'meoreader' ) . "</h3>\n";

    $html .= $this->reloadFeeds();

    $html .= '<p>&#171; <a href="admin.php?page=meoreader_index&amp;pageNr=1">' . _x( 'Back to the Index', 'backlink', 'meoreader' ) . "</a></p>\n";
    
    return $html;
    
  }
  
  /**
   * Call the feed API to do the actual operation.
   *
   * @return string HTML formatted error or success message.
   */
  public function reloadFeeds() {
    
    $nonce = isset( $_GET['meoNonce'] ) ? $_GET['meoNonce'] : '';
    
    if( !wp_verify_nonce( $nonce, 'meoReader_updateFeed' ) ) {
      
      return '<p class="message error">' . _x( "You're not allowed to do that!", 'error message', 'meoreader' ) . "</p>\n";      
      
    }
    
    /* Get a list of ALL feeds (no matter what category). */
    $feeds = $this->FeedAPI->getAllFeeds();

    if( $feeds === false ) {

      return '<p class="message error">' . _x( 'There are no feeds to fetch!', 'error message', 'meoreader' ) . "</p>\n";

    }
    
    /* Add each item of each feed - if it's not already in the database */
    foreach( $feeds as $feed ) {

      $xml = $this->FeedAPI->loadFeed( $feed['xml_url'] );

      if( is_array( $xml ) ) {

        return $xml;

      }

      $status = $this->FeedAPI->addEntries( $xml, $feed['id'] );
      
      if( $status === false ) {
      
        return '<p class="message error">' . _x( 'Could not reload (all) feeds - Possibly due a PHP timeout! Please try again with Javascript enabled!', 'error message', 'meoreader' ) . "</p>\n";
    
      }

    }

    return '<p class="message success">' . _x( 'Feeds have been reloaded', 'error message', 'meoreader' ) . "</p>\n";
    
  }
  
}
?>