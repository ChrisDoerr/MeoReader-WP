<?php
/**
 * WordPress Backend Page: Archive
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_Archive extends Meomundo_WP {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var object MeoReader_Archive object.
   */
  protected $ArchiveAPI;
  
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

    $this->loadClass( 'MeoReader_Archive' );
    
    $this->Templates  = new MeoReader_Templates( $absolutePluginPath, $pluginURL, $pluginSlug );
    
    $this->EntryAPI   = new MeoReader_Entries( $wpdb );
    
    $this->ArchiveAPI = new MeoReader_Archive( $wpdb );

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
    
    $pageNr               = isset( $_GET['pageNr'] ) ? (int) $_GET['pageNr'] : 1;
    
    $options              = get_option( $this->slug );
    
    $data                 = array();

    $data['entries']      = $this->ArchiveAPI->getArchiveEntries( $pageNr, $options['entriesPerPage'] );
    
    $data['total']        = $this->ArchiveAPI->getArchiveTotal();
    
    $data['pageNr']       = $pageNr;
    
    $data['options']      = $options;

    $data['totalPages']   = $this->EntryAPI->getTotalNumberOfPages( $this->slug, $data['total'] );
    
    $data['nonces']       = array_merge( $this->ArchiveAPI->getNonces(), $this->EntryAPI->getNonces() );
    
    $data['meta']         = array(
      'path'  => $this->path,
      'url'   => $this->url
    );

    $html = '<h3>' . _x( 'Archive', 'headline', 'meoreader' ) . "</h3>\n";

    $html .= $this->Templates->view( 'Backend_Archive', $data );
    
    return $html;
    
  }
  
}
?>