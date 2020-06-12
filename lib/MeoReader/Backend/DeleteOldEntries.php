<?php
/**
 * WordPress backend ghost page: Delete old entries.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_DeleteOldEntries extends Meomundo_WP {
  
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

    $this->EntryAPI   = new MeoReader_Entries( $wpdb );

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

    $options = get_option( $this->slug );
    
    $olderThan = ( isset( $options['deleteEntriesOlderThan'] ) ) ? (int) $options['deleteEntriesOlderThan'] : 30;

    $html = '<h3>' . _x( 'Delete Old Entries', 'headline', 'meoreader' ) . "</h3>\n";
    
    $html .= $this->deleteOldEntries( $olderThan );

    $html .= '<p>&#171; <a href="admin.php?page=meoreader_index&amp;pageNr=1">' . _x( 'Back to the Index', 'backlink', 'meoreader' ) . "</a></p>\n";

    return $html;
    
  }
  

  /**
   * Call the API to delete entries that are older than X days.
   *
   * @param   int     $olderThan    Entries that are older than this number of days will be deleted - if they are not archive elements, that is.
   * @return  string                An HTML formatted error or success message.
   */
  public function deleteOldEntries( $olderThan ) {

    $olderThan  = (int) $olderThan;
    
    $olderThan  = abs( $olderThan );
    
    $nonce      = isset( $_GET['meoNonce'] ) ? $_GET['meoNonce'] : '';
    
    if( !wp_verify_nonce( $nonce, 'meoReader_deleteOldEntries' ) ) {
      
      return '<p class="message error">' . _x( "You're not allowed to do that!", 'error message', 'meoreader' ) . "</p>\n";
      
    }
    
    $status = $this->EntryAPI->deleteOldEntries( $olderThan );
    
    if( $status === false ) {
      
      return '<p class="message error">' . _x( 'Could not delete old entries!', 'error message', 'meoreader' ) . "</p>\n";
    
    }
    else {
      
      return '<p class="message success">' . _x( 'Old entries have been deleted.', 'error message', 'meoreader' ) . "</p>\n";
      
    }
    
  }
  
}
?>