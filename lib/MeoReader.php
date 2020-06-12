<?php
/**
 * The MeoReader Plugin Class.
 *
 * @category    MeoReader
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader extends Meomundo_WP {
  
  /**
   * @var string Product name (case-sensitive!)
   */
  protected $productName;
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var object WordPress current user.
   */
  protected $User;
  
  /**
   * The constructor.
   *
   * @param string  $absolutePluginPath Absolute path to the plugin directory.
   * @param string  $pluginURL URL to the plugin directory.
   * @param string  $pluginSlug  Plugin handler.
   * @param object  $wpdb WordPress database object.
   */
  public function __construct( $absolutePluginPath, $pluginURL, $pluginSlug, $wpdb ) {
    
    $this->productName  = 'meoReader';

    parent::__construct( $absolutePluginPath, $pluginURL, $pluginSlug );
    
    $this->DB           = $wpdb;
 
    // Installation routines
    register_activation_hook( $absolutePluginPath . 'index.php', array( $this, 'install' ) );

    if( is_admin() ) {
    
      // Register styles and scripts to be used on the plugin's backend pages.
      add_action( 'admin_init', array( $this, 'adminInit' ) );
    
      // Create some menu items for the WordPress backend.
      add_action( 'admin_menu', array( $this, 'registerMenuPages' ) );
    
      /* Remove meoReader menu when current user is no admin and not the "single user" */
      add_filter( 'admin_menu', array( $this, 'removeMenuItems' ), 50 );

      // Handle custom backend Ajax functionality.
      add_action( 'wp_ajax_meoReader', array( $this, 'ajaxController' ) );

      // Workaround for handling uploads
      add_action( 'plugins_loaded', array( &$this, 'internalRedirect' ) );

      /* Only check for updates every 12 hours */
      add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'checkForUpdates' ) );
      
    }
    
  }


  /**
   * Register styles and scripts to be used on the plugin's backend pages.
   */
  public function adminInit() {
    
    wp_register_style( $this->slug . '_styles', $this->url . 'css/backend' . MEOREADER_MIN . '.css', false, true );

    // meoDocs
    wp_register_style( $this->slug . '_meodocsstyles', $this->url . 'css/meoDocs.css', false, true );

    // The enhanced Javascript functions require jQuery to be available!
    wp_register_script( $this->slug . '_meoreader', $this->url . 'js/MeoReader' . MEOREADER_MIN . '.js', array( 'jquery' ), true, true );

    // Dictionary file
    wp_register_script( $this->slug . '_meoreader_dictionary', $this->url . 'js/MeoReader.dictionary.php', array( 'jquery', $this->slug . '_meoreader' ), true, true );

    // The MeoReader box element for showing dialogs or requiring user input to proceed */
    wp_register_script( $this->slug . '_meoreader_box', $this->url . 'js/MeoReader.box' . MEOREADER_MIN . '.js', array( 'jquery', $this->slug . '_meoreader_dictionary' ), true, true );
    
    // Subscription Management (also requires jQuery)
    wp_register_script( $this->slug . '_subscriptions', $this->url . 'js/MeoReader.Subscriptions' . MEOREADER_MIN . '.js', array( 'jquery', $this->slug . '_meoreader_box' ), true, true );
    
    // Audio.JS
    wp_register_script( $this->slug . '_audiojs', $this->url . 'vendors/audiojs/audio.min.js', array( 'jquery' ), true, true );

    // The READER (list of entries)
    wp_register_script( $this->slug . '_reader', $this->url . 'js/MeoReader.Reader' . MEOREADER_MIN . '.js', array( 'jquery', $this->slug . '_meoreader_box' ), true, true );

  }

  /**
   * Register backend pages and create menu items for them in the WordPress backend.
   */
  public function registerMenuPages() {
    
    $cap_read   = 'read';
    
    $cap_admin  = 'manage_options';
    
    // The actual feed reader.
    $pages['reader']['index']                 = add_menu_page( 'meoReader', 'meoReader', $cap_read, $this->slug . '_index', array( $this, 'viewBackend' ) );
    
    // The feed archive.
    $pages['reader']['archive']               = add_submenu_page( $this->slug . '_index', _x( 'Archive', 'menu or page title', 'meoreader' ), _x( 'Archive', 'menu or page title', 'meoreader' ), $cap_read, $this->slug . '_archive', array( $this, 'viewBackend' ) );

    // Manage subscriptions.
    $pages['subscriptions']['subscriptions']  = add_submenu_page( $this->slug . '_index', _x( 'Subscriptions', 'menu or page title', 'meoreader' ), _x( 'Subscriptions', 'menu or page title', 'meoreader' ), $cap_admin, $this->slug . '_subscriptions', array( $this, 'viewBackend' ) );
    
    // Settings page / plugin options page
    $pages['core']['settings']                = add_submenu_page( $this->slug . '_index', _x( 'Settings', 'menu or page title', 'meoreader' ), _x( 'Settings', 'menu or page title', 'meoreader' ), $cap_admin, $this->slug . '_settings', array( $this, 'viewBackend' ) );
    
    // Help/Docs/Manual page
    $pages['doc']['help']                     = add_submenu_page( $this->slug . '_index', '[ ' . _x( 'Help', 'menu or page title', 'meoreader' ) . ' ]', '[ ' . _x( 'Help', 'menu or page title', 'meoreader' ) . ' ]', $cap_read, $this->slug . '_help', array( $this, 'viewBackend' ) );

    /**
     * Also create some ghost pages for handling certain CRUD operations a non-javascript (Ajax) way.
     *
     * By "ghost page" I mean that these pages are properly registered into the WordPress backend but no menu item will be created that links to these pages.
     */
    
    /* Feed Checker Tool */
    $pages['tools']['feedChecker']            = add_submenu_page( null, _x( 'Feed Checker', 'menu or page title', 'meoreader' ), _x( 'Feed Checker', 'menu or page title', 'meoreader' ), $cap_admin, $this->slug . '_feedChecker', array( $this, 'viewBackend' ) );

    /* Subscriptions */
    $pages['subscriptions']['renameCategory'] = add_submenu_page( null, _x( 'Rename Category', 'menu or page title', 'meoreader' ), _x( 'Rename Category', 'menu or page title', 'meoreader' ), $cap_admin, $this->slug . '_renameCategory', array( $this, 'viewBackend' ) );
    
    $pages['subscriptions']['addFeed']        = add_submenu_page( null, _x( 'Add Feed', 'menu or page title', 'meoreader' ), _x( 'Add Feed', 'menu or page title', 'meoreader' ), $cap_admin, $this->slug . '_addFeed', array( $this, 'viewBackend' ) );

    $pages['subscriptions']['moveFeed']       = add_submenu_page( null, _x( 'Move Feed', 'menu or page title', 'meoreader' ), _x( 'Move Feed', 'menu or page title', 'meoreader' ), $cap_admin, $this->slug . '_moveFeed', array( $this, 'viewBackend' ) );
    

    /* Reader (Index) */
    $pages['reader']['viewEntry']             = add_submenu_page( null, _x( 'View Entry', 'menu or page title', 'meoreader' ), _x( 'View Entry', 'menu or page title', 'meoreader' ), $cap_read, $this->slug . '_viewEntry', array( $this, 'viewBackend' ) );

    $pages['reader']['toggleArchiveItem']     = add_submenu_page( null, _x( 'Toggle Archive Item', 'menu or page title', 'meoreader' ), _x( 'Toggle Archive Item', 'menu or page title', 'meoreader' ), $cap_read, $this->slug . '_toggleArchive', array( $this, 'viewBackend' ) );
    
    $pages['reader']['markAllAsRead']         = add_submenu_page( null, _x( 'Mark All As Read', 'menu or page title', 'meoreader' ), _x( 'Mark All As Read', 'menu or page title', 'meoreader' ), $cap_read, $this->slug . '_markAllAsRead', array( $this, 'viewBackend' ) );

    $pages['reader']['reloadFeeds']           = add_submenu_page( null, _x( 'Reload Feeds', 'menu or page title', 'meoreader' ), _x( 'Reload Feeds', 'menu or page title', 'meoreader' ), $cap_read, $this->slug . '_reloadFeeds', array( $this, 'viewBackend' ) );
    
    $pages['reader']['cleanDB']               = add_submenu_page( null, _x( 'Delete Old Entries', 'menu or page title', 'meoreader' ), _x( 'Delete Old Entries', 'menu or page title', 'meoreader' ), $cap_read, $this->slug . '_deleteOldEntries', array( $this, 'viewBackend' ) );
    
    $pages['reader']['search']                = add_submenu_page( null, _x( 'Search meoReader', 'menu or page title', 'meoreader' ), _x( 'Search meoReader', 'menu or page title', 'meoreader' ), $cap_read, $this->slug . '_search', array( $this, 'viewBackend' ) );

    $pages['reader']['createPostFromEntry']   = add_submenu_page( null, _x( 'Create Post From Entry', 'menu or page title', 'meoreader' ), _x( 'Create Post From Entry', 'menu or page title', 'meoreader' ), $cap_read, $this->slug . '_createPostFromEntry', array( $this, 'viewBackend' ) );

    /**
     * Apply the previously registered styles and scripts for the newly registerd pages.
     */

    foreach( $pages['subscriptions'] as $page ) {
      
      add_action( 'admin_print_scripts-' . $page, array( $this, 'loadCoreScripts' ) );

      add_action( 'admin_print_scripts-' . $page, array( $this, 'loadSubscriptionsJS' ) );

      add_action( 'admin_print_styles-' . $page, array( $this, 'loadCoreStyles' ) );
    
    }
    
    foreach( $pages['reader'] as $page ) {
      
      add_action( 'admin_print_scripts-' . $page, array( $this, 'loadCoreScripts' ) );

      add_action( 'admin_print_scripts-' . $page, array( $this, 'loadReaderJS' ) );

      add_action( 'admin_print_styles-' . $page, array( $this, 'loadCoreStyles' ) );
    
    }
    
    foreach( $pages['core'] as $page ) {
      
      add_action( 'admin_print_scripts-' . $page, array( $this, 'loadCoreScripts' ) );

      add_action( 'admin_print_styles-' . $page, array( $this, 'loadCoreStyles' ) );

    }
    
    foreach( $pages['doc'] as $page ) {
      
      add_action( 'admin_print_scripts-' . $page, array( $this, 'loadCoreScripts' ) );

      add_action( 'admin_print_scripts-' . $page, array( $this, 'loadMeoDocsScript' ) );

      add_action( 'admin_print_styles-' . $page, array( $this, 'loadCoreStyles' ) );
      
      add_action( 'admin_print_styles-' . $page, array( $this, 'loadMeoDocsStyles' ) );

    }
    
    foreach( $pages['tools'] as $page ) {

      add_action( 'admin_print_styles-' . $page, array( $this, 'loadCoreStyles' ) );

    }

  }


  /**
   * If the current user is NOT an administrator and NOT the "single user" (according to
   * the plugin settings) then remove the meoReader menu!
   */
  public function removeMenuItems() {

    if( !MeoReader_Core::current_user_can( 'read' ) ) {
    
      $menuSlug = $this->slug . '_index';

      remove_submenu_page( $menuSlug, $this->slug . '_archive' );

      remove_submenu_page( $menuSlug, $this->slug . '_help' );

      remove_menu_page( $menuSlug );
    
    }
  
  }
  
  /**
   * Load core Javascript.
   */
  public function loadCoreScripts() {

    wp_enqueue_script( $this->slug . '_meoreader' );
    
    wp_enqueue_script( $this->slug . '_meoreader_box' );

    wp_enqueue_script( $this->slug . '_meoreader_dictionary' );
    
  }

  /**
   * Load meoDocs Javascript.
   */
  public function loadMeoDocsScript() {

    wp_enqueue_script( $this->slug . '_meodocs' );

  }

  /**
   * Load Javascript only for the subsriptions pages.
   */
  public function loadSubscriptionsJS() {

    wp_enqueue_script( $this->slug . '_subscriptions' );

  }

  /**
   * Load Javascript only for the READER pages.
   */
  public function loadReaderJS() {

    wp_enqueue_script( $this->slug . '_audiojs' );

    wp_enqueue_script( $this->slug . '_reader' );

  }

  /**
   * Properly load some custom CSS.
   */
  public function loadCoreStyles() {
    
    wp_enqueue_style( $this->slug . '_styles' );
    
  }

  /**
   * Load meoDocs CSS.
   */
  public function loadMeoDocsStyles() {
    
    wp_enqueue_style( $this->slug . '_meodocsstyles' );
    
  }
  
  /**
   * View the plugin's backend pages.
   */
  public function viewBackend() {

    MeoReader_Core::setLandmark( __FILE__, __LINE__ );

    // Detect the proper class name for a page.
    $page       = str_replace( $this->slug . '_', '', $_GET['page'] );
    
    $classname  = 'MeoReader_Backend_' . ucfirst( $page );

    if( !$this->loadClass( $classname ) ) {
      
      echo 'Error: Unknown entity!';
      
      return;
      
    }
    
    // Each backend page class applies to a certain, standardized pattern.
    $Backend = new $classname( $this->path, $this->url, $this->slug, $this->DB );
    
    echo '<div class="wrap" id="meoReader" data-url="' . $this->url . '">' . "\n";
    
    echo ' <h2><span>&#187;</span> meo<strong>Reader</strong></h2>' . "\n";

    MeoReader_Core::setLandmark( __FILE__, __LINE__ );

    echo $Backend->controller();
    
    echo "</div>\n";
    
  }
  
  /**
   * Handle custom backend Ajax functionality by using an own Ajax handling class
   * and by appling a MODEL-CONTROLLER-pattern.
   *
   * An additional VIEW-pattern will be handled via some client-side Javascript
   * which will generate HTML according to the data from the Ajax response.
   */
  public function ajaxController() {
    
    // Detect the MODEL
    $method = isset( $_POST['method'] ) ? trim( strip_tags( $_POST['method'] ) ) : '';

    // Load the MODEL class
    $status = $this->loadClass( 'MeoReader_Ajax' );

    // Create MODEL object.
    $Ajax   = new MeoReader_Ajax( $this->path, $this->url, $this->slug, $this->DB );
    
    if( $method == '' || !method_exists( $Ajax, $method ) ) {
      
      $response = array(
        'request' => false,
        'error'   => array(
          'code'    => 34,
          'message' => 'Operation does not exist!'
        )
      );
      
    }
    else {
      
      $response = $Ajax->$method();
      
    }
    
    // Always return JSON formatted data as Ajax response.
    echo json_encode( $response );
    
    exit;
    
  }
  
  /**
   * The intaller.
   * Will be triggered "on plugin activation"(!). Therefore you have to make sure
   * that certain actions will still only be executed ONCE!
   */
  public function install() {
    
    $this->loadClass( 'MeoReader_Installer' );
    
    $Installer = new MeoReader_Installer( $this->slug, $this->DB );
    
    $Installer->install();
    
  }
  
  
  /**
   * Handle "Shadow Pages" if the porper URL has been called.
   */
  public function internalRedirect() {

    if( !isset( $_GET['page'] ) ) {
      
      return;
      
    }

    /* Shadow Page: Blank page (which helps "resetting" previous ajax messages). */
    if( $_GET['page'] === 'meoreader_blank' ) {
      
      echo '';
      
      exit;
      
    }

    /* Shadow Page: Import data into the meoReader database. */
    if( $_GET['page'] === 'meoreader_import' ) {
      
      $this->importData();
      
      exit;
      
    }

    /* Shadow Page: Export data from the meoReader. */
    if( $_GET['page'] === 'meoreader_exportxml' ) {
      
      $this->exportData();

      exit;
      
    }
    
    /* Shadow Page: Export data from the meoReader. */
    if( $_GET['page'] === 'meoreader_exportopml' ) {
      
      $this->exportOPMLData();

      exit;
      
    }

    return;
    
  }

  
  /**
   * Shadow Page: Export data from the meoReader
   */
  public function exportData() {

    $this->loadClass( 'MeoReader_Exporter' );
    
    $Exporter               = new MeoReader_Exporter( $this->path, $this->url, $this->slug, $this->DB );
      
    $data                   = array();
      
    $data['subscriptions']  = $Exporter->getSubsciptionList();
    
    $data['archive']        = $Exporter->getArchiveList();
      
    $data['options']        = get_option( $this->slug );
      
    $Exporter->createXMLDownload( $Exporter->createXML( $data ) );

  }

  /**
   * Shadow Page: Export OPML data from the meoReader
   */
  public function exportOPMLData() {

    $this->loadClass( 'MeoReader_Exporter' );
    
    $Exporter               = new MeoReader_Exporter( $this->path, $this->url, $this->slug, $this->DB );
      
    $data                   = array();
      
    $data['subscriptions']  = $Exporter->getSubsciptionList();
      
    $data['options']        = get_option( $this->slug );
      
    $Exporter->createOPMLDownload( $Exporter->createOPML( $data ) );

  }

  /**
   * Shadow Page: Import data into the meoReader database.
   *
   * The importing architecture allows to import all kinds of
   * custom data structures from various sources into the system.
   *
   * All you have to do is create a class that extends from the MeoReader_Import class
   * and implements the MeoReader_ImportInterface interface, save this class to
   * the /lib/MeoReader/Import/ folder and add the upload form to the subscription page
   * template.
   *
   * For the proper structure of the form take a look at the existing ones, e.g. two hidden fields
   * are required and the class name and id attributes have also to follow a certain convention
   * in order for the Javascript to work!
   */
  public function importData() {
  
    $module       = isset( $_POST['module'] )   ? trim( $_POST['module'] )    : 'MeoReader';

    $fileKey      = isset( $_POST['fileKey'] )  ? trim( $_POST['fileKey'] )   : '';
    
    $nonce        = isset( $_POST['meoNonce'] ) ? (string) $_POST['meoNonce'] : '';
    

    if( !wp_verify_nonce( $nonce, 'meoReader_import' ) ) {
      
      echo json_encode(
        array(
          'request' => false,
          'message' => _x( 'Missing or Invalid Parameter!', 'error message', 'meoreader' )
        )
      );
      
      exit;
      
    }

    $importClass  = 'MeoReader_Import_' . ucfirst( $module );

    $this->loadClass( 'MeoReader_Import' );

    $this->loadInterface( 'MeoReader_ImportInterface' );

    if( $this->loadClass( $importClass ) === false ) {
      
      echo json_encode(
        array(
          'request' => false,
          'message' => 'Unknown Import Module!'
        )
      );
      
      exit;
      
    }

    $Importer     = new $importClass( $this->path, $this->url, $this->slug, $this->DB, $fileKey );
    
    /* Making sure that the import class extends MeoReader_Import and implements (at least) the MeoReader_ImportInterface! */
    if( get_parent_class( $Importer ) !== 'MeoReader_Import' || !in_array( 'MeoReader_ImportInterface', class_implements( $Importer ) ) ) {
      
      echo json_encode(
        array(
          'request' => false,
          'message' => 'Invalid Import Module!'
        )
      );
      
      exit;
      
    }
    
    try {
      
      /* Handle the file upload in your custom import class and return a SimpleXML object. */
      $xml        = $Importer->loadXMLFile();
      
      /**
       * Extract/map the data from that XML object into an standardized meoReader array with the form like this:
       array(
        'subscriptions' => array(
          0 => array(
            'name'  => 'Category A',
            'feeds' => array(
              'feed_1_url',
              'feed_2_url',
              'feed_3_url'
            )
          )
          1 => array(
            'name'  => 'Category B',
            'feeds' => array(
              'feed_4_url',
              'feed_5_url',
              'feed_6_url'
            )
          )
        )
       );
       */
      $data       = $Importer->extractDataFromXML( $xml );

      /* Since the uploaded file is no longer needed it might as well be deleted. */
      $Importer->deleteTempFile();

      $return = array(
        'request' => true,
        'data'    => $data
      );

    }
    catch( Exception $e ) {
      
      $return = array(
        'request' => false,
        'message' => $e->getMessage()
      );
      
    }
    
    /* This JSON string can be picked up via Javascript. */
    echo json_encode( $return );
    
    exit;
    
  }

  /**
   * Check for newer versions of this plugin by calling
   * the remote Meomundo Update Webservice.
   *
   * If there a newer version is availabel hook into the WordPress notification and auto-update system.
   */
  public function checkForUpdates() {
    
    $this->loadClass( 'Meomundo_Updates' );

    $this->loadClass( 'Meomundo_Updates_WP' );

    $Update     = new Meomundo_Updates_WP( $this->productName, 'meoReader/index.php', 'http://updates.meomundo.com/' );

    /* If there is a newer version of this plugin hook into the WordPress automatic updates procedures */
    if( $Update->check( $this->productName, $this->getPluginMeta( 'Version' )  ) ) {

      /* Add plugin update to notification system */
      add_filter( 'pre_set_site_transient_update_plugins', array( $Update, 'addTransient' ) );

      /* Show Update Information ("View Version Details" box) */
      add_filter( 'plugins_api', array( $Update, 'addInfo' ), 10, 3 );

    }
    
    /**
     * When the plugin hasn't been installed yet, there won't be no db tables for this plugin.
     *
     * When the plugin is in the middle of being installed, the function 'is_plugin_active' has not been declared yet.
     * So this little trick can be used to check if the plugin "is about" to be installed.
     *
     * Also, when the plugin is deactivated, do not bother the delete old entries.
     */
    if( function_exists( 'is_plugin_active' ) && is_plugin_active( 'meoReader/index.php' ) ) {
    
      $this->deleteOldEntries();

    }

  }
  
  /**
   * Auto-delete entries that are older than a certain number of days
   * which you have sepcified in the plugin settings.
   */
  public function deleteOldEntries() {
  
    $page = '';
    
    if( isset( $_GET['page'] ) ) {

      $page = $_GET['page'];

    }
    elseif( isset( $_POST['page'] ) ) {

      $page = $_POST['page'];

    }

    /**
     * Do not delete entries immediately when updating the plugin settings.
     * Wait for the next "real" page load.
     */
    if( strtolower( $page ) !== 'meoreader_settings' ) {
    
      $this->loadClass( 'MeoReader_Core' );

      $this->loadClass( 'MeoReader_Entries' );
    
      $EntriesAPI = new MeoReader_Entries( $this->DB );
    
      $EntriesAPI->deleteOldEntries( false, $this->slug );
    
    }
    
  }
  
}
?>