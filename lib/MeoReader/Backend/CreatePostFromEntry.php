<?php
/**
 * WordPress Backend Page: Create a new post from a given entry.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_CreatePostFromEntry extends Meomundo_WP {
  
  /**
   * @var object MeoReader_Feeds object.
   */
  protected $EntryAPI;
  
  /**
   * @var array Plugin options.
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
    
    $this->loadClass( 'MeoReader_Entries' );

    $this->EntryAPI   = new MeoReader_Entries( $wpdb );
    
    $this->options    = get_option( $pluginSlug );
    
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
    
    $pageNr = ( isset( $_GET['pageNr'] ) && $_GET['pageNr'] > 1 ) ? (int) $_GET['pageNr'] : 1;
    
    $html = '<h3 class="admin">' . _x( 'Create Post From Entry', 'headline', 'meoreader' ) . '</h3>' . "\n";
    
    $html .= $this->createPostFromEntry();
    
    $html .= '<p>&#171; <a href="admin.php?page=meoreader_index&amp;pageNr=' . $pageNr . '">' . _x( 'Back to the Index', 'backlink', 'meoreader' ) . "</a></p>\n";

    return $html;
    
  }

  /**
   * Create a new WordPress post from a given RSS feed entry.
   *
   * @requires  int     $_GET['entryID']    ID of the entry that shall be turned into a blog post.
   * @return    string                      A text message about the failure or success of creating that post.
   */
  public function createPostFromEntry() {
    
    global $current_user;

    if( !MeoReader_Core::current_user_can( 'read' ) ) {
    
      return '<p class="message error">' . _x( "You're not allowed to do that!", 'error message', 'meoreader' ) . "</p>\n"; 

    }
    
    $entryID  = isset( $_GET['entryID'] ) ? (int) $_GET['entryID'] : 0;

    $status   = $this->EntryAPI->createPostFromEntry( $entryID );

    if( $status === false ) {
      
      return '<p class="message error">' . _x( 'Post could not be created!', 'error message', 'meoreader' ) . "</p>\n"; 
      
    }
    else {

      return '<p class="message success">' . _x( 'Post has been created', 'error message', 'meoreader' ) . "</p>\n"; 
    
    }
    
  }

}
?>