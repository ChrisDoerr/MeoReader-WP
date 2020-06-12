<?php
/**
 * Plugin Installer/Uninstaller Class
 *
 * @category    MeoReader
 * @package     Installler
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 *
 */
class MeoReader_Installer {
  
  /**
   * @var string Plugin handler.
   */
  protected $slug;
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;


  /** 
   * The constrcutor.
   *
   * @param string  $pluginSlug   Plugin handler.
   * @param object  $wpdb         WordPress database object.
   */
  public function __construct( $pluginSlug, $wpdb ) {
    
    $this->slug       = $pluginSlug;
    
    $this->DB         = $wpdb;

  }
  
  /**
   * Installaer Routine(s)
   */
  public function install() {

    require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'upgrade.php';
    
    $meoReader_tbl_categories = MEOREADER_TBL_CATEGORIES;

    $meoReader_tbl_feeds      = MEOREADER_TBL_FEEDS;

    $meoReader_tbl_entries    = MEOREADER_TBL_ENTRIES;
    
    $meoReader_tbl_sessions   = MEOREADER_TBL_SESSIONS;
    
    /* Copied from schema.php */
    $charset_collate          = '';

    if( !empty( $this->DB->charset ) ) {
      
      $charset_collate = "DEFAULT CHARACTER SET {$this->DB->charset}";
    
    }

    if( !empty( $this->DB->collate) ) {
		
      $charset_collate .= " COLLATE {$this->DB->collate}";
    
    }

    /**
     * Database table creation.
     */
    $create_cateogories =<<<CATS
    CREATE TABLE {$meoReader_tbl_categories} (
    id tinyint(5) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(128) NOT NULL,
    meta TEXT NOT NULL,
    PRIMARY KEY  (id)
    ){$charset_collate};
CATS;

    $create_feeds =<<<FEEDS
    CREATE TABLE {$meoReader_tbl_feeds} (
    id smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    cat_id tinyint(3) unsigned NOT NULL,
    xml_url varchar(128) NOT NULL,
    html_url varchar(128) NOT NULL,
    description text NOT NULL,
    last_build_date datetime NOT NULL,
    name varchar(128) NOT NULL,
    meta TEXT NOT NULL,
    session_id int(10) unsigned NOT NULL,
    PRIMARY KEY  (id)
    ){$charset_collate};
FEEDS;

    $create_entries =<<<ENTRIES
    CREATE TABLE {$meoReader_tbl_entries} (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    feed_id smallint(5) unsigned NOT NULL,
    pub_date datetime NOT NULL,
    description text NOT NULL,
    description_prep text NOT NULL,
    enclosures text NOT NULL,
    link text NOT NULL,
    title varchar(255) NOT NULL,
    status varchar(32) NOT NULL,
    archive tinyint(4) NOT NULL DEFAULT '0',
    guid varchar(128) NOT NULL,
    META TEXT NOT NULL,
    PRIMARY KEY  (id)
    ){$charset_collate};
ENTRIES;


    $create_sessions =<<<SESSIONS
    CREATE TABLE {$meoReader_tbl_sessions} (
    id smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    status varchar(16) NOT NULL,
    start datetime NOT NULL,
    end datetime NOT NULL,
    PRIMARY KEY  (id)
    ){$charset_collate};
SESSIONS;


    /**
     * Inser inital set of data (if not exist)
     */
    $insert_cats =<<<INSERT
    INSERT INTO {$meoReader_tbl_categories} ( id, name )
         SELECT * FROM ( SELECT 1, 'Unsorted' ) AS tmp
                  WHERE NOT EXISTS (
                              SELECT id
                                FROM {$meoReader_tbl_categories}
                               WHERE id = 1
         );
INSERT;
    
    /**
     * Using the WordPress function dbDelta which makes it easier
     * to also update table structures.
     */
    dbDelta( $create_cateogories );

    dbDelta( $create_feeds );

    dbDelta( $create_entries );

    dbDelta( $create_sessions );

    
    /* Initial data (here: category 'Unsorted' ) */
    $this->DB->query( $insert_cats );
    
    /**
     * Plugin settings/options
     */
    $maxTimeout = (int) ini_get( 'max_execution_time' );

    $defaults = array(
      'entriesPerPage'          => 30,
      'deleteEntriesOlderThan'  => 31,
      'timeout'                 => ( $maxTimeout -1 ),
      'showGoogleImporter'      => false,   // The default is FALSE because importing Google Reader data REQUIRES PHP's ZIP functionality to be enabled!
      'currentCatTab'           => 0,
      'userID'                  => 0,
      'twitter'                 => '',
      'postStatus'              => 'draft',
      'postEditor'              => true,
      'userCanPublish'          => false,
      'anonymousLinks'          => false,
      'audioplayer'             => false,   // Default is FALSE because some browser (like IE) may have problems with audio.js!
      'purify'                  => true     // Secure HTML output to prevent certain kinds of XSS attack vectors via the HTMLPurifier lib
    );


    $options  = get_option( $this->slug );
    
    $merged   = is_array( $options ) ? array_merge( $defaults, $options ) : $defaults;

    update_option( $this->slug, $merged );
  
    
    $token = get_option( 'meoreader_crontoken' );
    
    if( $token === false ) {
      update_option( 'meoreader_crontoken', md5( time() . NONCE_SALT ) );
    }
    
    /* Create the /favicons/ directory if it does not exists (required since version 1.0.0) */
    $faviconsDir =  MEOREADER_PATH . 'favicons' . DIRECTORY_SEPARATOR;

    if( !is_dir( $faviconsDir ) ) {

        @mkdir( $faviconsDir, 0775 );
    }

  }
  
  /**
   * The uninstaller routine(s)
   * Leave no trace behind...
   */
  public function uninstall() {
    
    /**
     * Delete custom database tables.
     */
    $sql = 'DROP TABLE IF EXISTS ' . MEOREADER_TBL_CATEGORIES . ', ' . MEOREADER_TBL_FEEDS . ', ' . MEOREADER_TBL_ENTRIES . ', ' . MEOREADER_TBL_SESSIONS;
    
    $this->DB->query( $sql );
    
    /**
     * Delete plugin options.
     */
    delete_option( $this->slug );
    
    delete_option( 'meoreader_crontoken' );
    
    /**
     * Empty the /favicons/ directory.
     */
    $files = glob( MEOREADER_PATH . 'favicons' . DIRECTORY_SEPARATOR . '*' );

    foreach( $files as $file ){
      
      if( is_file( $file ) ) {
        
        unlink( $file );
      
      }
    
    }

  }
  
}
?>