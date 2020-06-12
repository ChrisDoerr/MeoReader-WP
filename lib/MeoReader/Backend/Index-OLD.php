<?php
/**
 * WordPress Backend Page: The feed reader index.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_Index extends Meomundo_WP {

  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var object MeoReader_Category object.
   */
  protected $CatAPI;

  /**
   * @var object MeoReader_Entry object.
   */
  protected $EntryAPI;
  
  /**
   * @var object MeoReader_Feeds object.
   */
  protected $FeedAPI;

  /**
   * @var object MeoReader_Archive object.
   */
  protected $ArchiveAPI;

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
    
    $this->loadClass( 'MeoReader_Archive' );

    MeoReader_Core::setLandmark( __FILE__, __LINE__ );

    $this->Templates  = new MeoReader_Templates( $absolutePluginPath, $pluginURL, $pluginSlug );

    MeoReader_Core::setLandmark( __FILE__, __LINE__ );

    $this->CatAPI     = new MeoReader_Categories( $wpdb, $pluginSlug );

    MeoReader_Core::setLandmark( __FILE__, __LINE__ );

    $this->FeedAPI    = new MeoReader_Feeds( $wpdb, $this->CatAPI, $pluginSlug );

    MeoReader_Core::setLandmark( __FILE__, __LINE__ );

    $this->EntryAPI   = new MeoReader_Entries( $wpdb );

    MeoReader_Core::setLandmark( __FILE__, __LINE__ );
    
    $this->ArchiveAPI = new MeoReader_Archive( $wpdb );

  }
  
  /**
   * The CONTROLLER.
   *
   * @return string HTML page code.
   */
  public function controller() {
    
    MeoReader_Core::setLandmark( __FILE__, __LINE__ );

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }

    $html               = "";
    
    $options            = get_option( $this->slug );

    $catID              = ( isset( $_GET['catID'] ) && $_GET['catID'] >= 0 )  ? (int) $_GET['catID'] : -1; 
    
    if( $catID < 0 && isset( $options['currentCatTab'] ) ) {

      $catID = $options['currentCatTab'];
      
    }

    $pageNr             = ( isset( $_GET['pageNr'] ) && $_GET['pageNr'] > 0 ) ? (int) $_GET['pageNr'] : 1;

    $categories         = $this->sortCategories( $this->CatAPI->getCategoryList() );

    $entries            = $this->EntryAPI->getEntriesByCategory( $catID, $pageNr, $options['entriesPerPage'] );

    $unreadItems        = $this->EntryAPI->countUnreadEntries( $catID );
    
    $totalItems         = $this->EntryAPI->getTotalNumberOfEntries( $catID );

    $currentCategory    = $this->CatAPI->getCategoryByID( $catID );

    $oldEntriesExist    = $this->EntryAPI->oldEntriesExist( $options['deleteEntriesOlderThan'] );
    
    $totalNumberOfPages = $this->EntryAPI->getTotalNumberOfPages( $this->slug, $totalItems );
    
    MeoReader_Core::setLandmark( __FILE__, __LINE__ );

    $html       .= $this->Templates->view(
      'Backend_Index',
      array(
        'pageNr'          => $pageNr,
        'categories'      => $categories,
        'entries'         => $entries,
        'total'           => array(
          'unread'        => $unreadItems,
          'hits'          => $totalItems,
          'pages'         => $totalNumberOfPages
        ),
        'currentPage'     => $pageNr,
        'currentCategory' => array(
          'id'            => $catID,
          'name'          => $currentCategory
        ),
        'options'         => $options,
        'ref'             => 'index',
        'oldEntriesExist' => $oldEntriesExist,
        'nonces'          => array_merge( $this->CatAPI->getNonces(), $this->FeedAPI->getNonces(), $this->EntryAPI->getNonces(), $this->ArchiveAPI->getNonces() ),
        'meta'            => array(
          'path'          => $this->path,
          'url'           => $this->url
        )
      )
    );

    MeoReader_Core::setLandmark( __FILE__, __LINE__ );
    
    return $html;
    
  }
  
  /**
   * Sort an array of categories (including their meta data) by name.
   *
   * @param   array $cats   Categories array.
   * @return  array         Array of properly sorted categories.
   */
  public function sortCategories( $cats ) {
    
    $cats = (array) $cats;
    
    usort( $cats, create_function( '$a,$b', 'return ( $a["name"] > $b["name"] ) ? -1 : +1;') );

    return $cats;

  }
  
}
?>