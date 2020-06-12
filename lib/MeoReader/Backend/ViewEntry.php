<?php
/**
 * WordPress Backend Page: View a single entry.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_ViewEntry extends Meomundo_WP {

  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  
  /**
   * @var object MeoReader_Entries object.
   */
  protected $EntryAPI;
  
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

    $this->loadClass( 'MeoReader_Entries' );
    
    $this->Templates  = new MeoReader_Templates( $absolutePluginPath, $pluginURL, $pluginSlug );

    $this->EntryAPI   = new MeoReader_Entries( $wpdb );
    
  }
  
  /**
   * The controller.
   *
   * @requires  int     $_GET['itemID']   ID of the entry to be shown.
   * @requires  int     $_GET['pageNr']   Current page number to be used to return to this very page.
   * @requires  string  $_GET['ref']      Handle of the page the user is coming from (index or archive).
   * @return    string                    HTML formatted entry.
   */
  public function controller() {
    
    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }

    $html     = '';
    
    $entryID  = isset( $_GET['itemID'] )  ? (int) $_GET['itemID']                             : 0;
    
    $pageNr   = isset( $_GET['pageNr'] )  ? (int) $_GET['pageNr']                             : 1;
    
    $ref      = isset( $_GET['ref'] )     ? trim( strip_tags( strtolower( $_GET['ref'] ) ) )  : 'index';
    
    if( $entryID == 0 ) {
      
      return '<p class="message error">' . _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' ) . "</p>\n";
      
    }

    $data           = $this->EntryAPI->getEntryById( $entryID );
    
    if( $data['request'] === true ) {
      
      $this->EntryAPI->markEntryAsRead( $entryID );
      
    }
    
    $data['pageNr'] = $pageNr;
    
    $data['ref']    = $ref;
    
    $data['options'] = get_option( $this->slug );
    
    $html .= $this->Templates->view( 'Backend_ViewEntry', $data );
    
    return $html;
    
  }

}
?>