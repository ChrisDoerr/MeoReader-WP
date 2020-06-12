<?php
/**
 * WordPress backend ghost page: Mark all entries as READ.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_MarkAllAsRead extends Meomundo_WP {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var object MeoReader_Entries object.
   */
  protected $EntryAPI;
  
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
    
    $this->loadClass( 'MeoReader_Entries' );

    $this->EntryAPI    = new MeoReader_Entries( $wpdb );
    
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

    $pageNr = ( isset( $_GET['pageNr'] ) ) ? (int) $_GET['pageNr'] : 1;
    
    $html = '<h3>' . _x( 'Mark All Entries as <i>Read</i>', 'headline', 'meoreader' ) . "</h3>\n";
    
    $html .= $this->markAllEntriesAsRead();
    
    $html .= '<p>&#171; <a href="admin.php?page=meoreader_index&amp;pageNr=' . $pageNr . '">' . _x( 'Back to the Index', 'backlink', 'meoreader' ) . "</a></p>\n";
    
    return $html;
    
  }
  
  /**
   * Call the API to do the action operation.
   *
   * @return string HTML formatted error or success message.
   */
  public function markAllEntriesAsRead() {
    
    $nonce  = isset( $_GET['meoNonce'] ) ? $_GET['meoNonce'] : '';
    
    if( !wp_verify_nonce( $nonce, 'meoReader_markAllAsRead' ) ) {

      return '<p class="message error">' . _x( "You're not allowed to do that!", 'error message', 'meoreader' ) . "</p>n";

    }
    
    $status = $this->EntryAPI->markAllAsRead();
    
    if( $status === false ) {
      
      return '<p class="message error">' . _x( 'Could not mark all entries as <i>read</i>', 'error message', 'meoreader' ) . "</p>n";
      
    }
    else {
      
      return '<p class="message success">' . _x( 'All entries have been marked as <i>read</i>', 'error message', 'meoreader' ) . "</p>\n";
      
    }
    
  }
  
}
?>