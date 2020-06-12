<?php
/**
 * WordPress backend ghost page: Showing search Results.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_Search extends Meomundo_WP {
  
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

    $this->loadClass( 'MeoReader_Categories' );

    $this->loadClass( 'MeoReader_Feeds' );

    $this->loadClass( 'MeoReader_Entries' );

    $this->Templates  = new MeoReader_Templates( $absolutePluginPath, $pluginURL, $pluginSlug );

    $this->CatAPI     = new MeoReader_Categories( $wpdb, $pluginSlug );
    
    $this->FeedAPI    = new MeoReader_Feeds( $wpdb, $this->CatAPI, $pluginSlug );

    $this->EntryAPI   = new MeoReader_Entries( $wpdb, $this->CatAPI );

  }
  
  /**
   * The CONTROLLER.
   *
   * @requires  string  $_POST['meoReader_query']     The search query.
   * @requires  int     $_POST['meoReader_pageNr']    The current page number - which will be used to retrieve blocks of matching entries instead of all matching entries at once.
   * @return    string                                HTML page code.
   */
  public function controller() {

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }

    $query  = isset( $_POST['meoReader_query'] )  ? trim( strip_tags( $_POST['meoReader_query'] ) ) : '';
    
    $pageNr = isset( $_POST['meoReader_pageNr'] ) ? (int) $_POST['meoReader_pageNr']                : 1;
    
    $pageNr = abs( $pageNr );
   
    $html = '<h3>' . _x( 'Search Results', 'headline', 'meoreader' ) . "</h3>\n";
    
    /* Call the API to do the actual operation */
    $results = $this->EntryAPI->searchEntries( $query );
    
    $html .= $this->Templates->view(
      'Backend_Search',
      array(
        'entries' => $results,
        'query'   => $query,
        'total'   => count( $results ),
        'pageNr'  => $pageNr
      )
    );

    return $html;

  }
  
}
?>