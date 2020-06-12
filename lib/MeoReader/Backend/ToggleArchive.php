<?php
/**
 * WordPress backend ghost page: Toggle archive state of an entry.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_ToggleArchive extends Meomundo_WP {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var object MeoReader_Archive object.
   */
  protected $ArchiveAPI;

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

    $this->loadClass( 'MeoReader_Archive' );

    $this->ArchiveAPI    = new MeoReader_Archive( $wpdb );

  }
  
  /**
   * The CONTROLLER.
   *
   * @requires  int     $_GET['itemID']   ID of the entry to be toggled.
   * @requires  int     $_GET['pageNr']   Current page number to return to this same page.
   * @requires  string  $_GET['ref']      Handle of the page the user is coming from (index or archive).
   * @return    string                    HTML page code.
   */
  public function controller() {

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }

    $entryID  = isset( $_GET['itemID'] ) ? (int) $_GET['itemID'] : 0;
    
    $pageNr   = isset( $_GET['pageNr'] ) ? (int) $_GET['pageNr'] : 1;
    
    $ref      = isset( $_GET['ref'] ) ? trim( strip_tags( strtolower( $_GET['ref'] ) ) ) : 'index';
    
    $html     = '<h3>' . _x( 'Toggle Archive Item', 'headline', 'meoreader' ) . "</h3>\n";

    if( $entryID < 1 ) {
      
      return '<p class="message error">' . _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' ) . "</p>\n";
      
    }
    else {
    
      $html .= $this->toggleEntry( $entryID );
    
    }
    
    if( $ref == 'archive' ) {

      $html .= '<p>&#171; <a href="admin.php?page=meoreader_archive&amp;pageNr=' . $pageNr . '">' . _x( 'Back to the Archive', 'error message', 'meoreader' ) . "</a></p>\n";

    }
    else {
    
      $html .= '<p>&#171; <a href="admin.php?page=meoreader_index&amp;pageNr=' . $pageNr . '">' . _x( 'Back to the Index', 'error message', 'meoreader' ) . "</a></p>\n";
    
    }
    
    return $html;
    
  }
  
  /**
   * Call the API to perform the actual operation.
   *
   * @param   int     $entryID    ID of the entry to be toggled.
   * @return  string              HTML formatted error or success message.
   */
  public function toggleEntry( $entryID ) {
    
    $entryID = (int) $entryID;
    
    $nonce = isset( $_GET['meoNonce'] ) ? $_GET['meoNonce'] : '';
    
    if( !wp_verify_nonce( $nonce, 'meoReader_toggleArchiveItem' ) ) {

      return '<p class="message error">' . _x( "You're not allowed to do that!", 'error message', 'meoreader' ) . "</p>\n";

    }
        
    $status = $this->ArchiveAPI->toggleArchiveItem( $entryID );
    
    if( $status === false ) {
      
      return '<p class="message error">' . _x( 'Could not change archive state for this entry!', 'error message', 'meoreader' ) . "</p>\n";
    
    }
    else {
      
      return '<p class="message success">' . _x( 'Archive state has been changed', 'error message', 'meoreader' ) . "</p>\n";
      
    }
    
  }
  
}
?>