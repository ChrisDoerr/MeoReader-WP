<?php
/**
 * The MeoReader Import Core
 *
 * Extend your custom import classes from this one!
 * (And also implement the MeoReader_ImportInterface interface)
 *
 * @category    MeoReader
 * @package     Import
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Import extends Meomundo_WP {

  /**
   * @var object WordPress database object.
   */
  protected $DB;

  /**
   * @var object  Category API (model) object.
   */
  protected $CatAPI;
  
  /**
   * @var object  Feed API (model) object.
   */
  protected $FeedAPI;
    
  /**
   * @var array   Plugin options.
   */
  protected $options;
  
  /**
   * @var string  Array key to access the uploaded file via $_FILES[ $fileKey ].
   */
  protected $fileKey;

  /**
   * @var array Array of nonces and actions for this class.
   */
  protected $nonceData;

  /**
   * The constructor.
   *
   * @param string  $absolutePluginPath   Absolute path to the plugin directory.
   * @param string  $pluginURL            URL to the plugin directory.
   * @param string  $pluginSlug           Plugin handler.
   * @param object  $wpdb                 WordPress database object.
   * @param string  $fileKey              Array key to access the uploaded file via $_FILES[ $fileKey ]
   */
  public function __construct( $absolutePluginPath, $pluginURL, $pluginSlug, $wpdb, $fileKey ) {
    
    parent::__construct( $absolutePluginPath, $pluginURL, $pluginSlug );
    
    $this->loadClass( 'MeoReader_Categories' );

    $this->loadClass( 'MeoReader_Feeds' );

    $this->loadClass( 'MeoReader_Entries' );

    $this->DB         = $wpdb;
    
    $this->CatAPI     = new MeoReader_Categories( $wpdb, $pluginSlug );
    
    $this->FeedAPI    = new MeoReader_Feeds( $wpdb, $this->CatAPI, $this->slug );
    
    $this->options    = get_option( $pluginSlug );
    
    $this->fileKey    = $fileKey;

    $this->nonceData  = $this->createNonces();

  }
  
  /**
   * Import the data into the database.
   * This method should rarely be used since it's very likely that you will run into a PHP execution timeout!
   * Better use the Ajax stacking approach instead!
   *
   * @param   array $data   An array containing all subscription data (categories and feeds) in a meoReader importer format.
   * @return  bool          The stack will simply move on to its next element if an item or category cannot be importet. So no error handling is being provided here and this method will always return TRUE.
   */
  final public function importData( $data ) {
    
    foreach( $data as $category ) {

      $catID = $this->CatAPI->addCategory( $category['name'] );
      
      foreach( $category['feeds'] as $feed ) {
        
        $this->FeedAPI->addFeed( $feed, $catID );
        
      }
      
    }
    
    return true;
    
  }

  /**
   * Delete the uploaded file since it is no longer of any use.
   */
  final public function deleteTempFile() {

    unset( $_FILES[ $this->fileKey ]['tmp_name'], $_FILES[ $this->fileKey ] );

  }

  
  /**
   * Get the array of nonce data for this class.
   *
   * @return array  Array of the nonces and actions.
   */
  final public function getNonces() {
    
    return $this->nonceData;
    
  }
  
  /**
   * Create nonces and actions for this class.
   * 
   * Nonces will mostly only be implemented for actions that require WRITING permissions.
   * READING permissions are not as critical in the context of this plugin.
   *
   * @return array  Array of the nonces and actions.
   */
  final public function createNonces() {
    
    $data   = array();

    $data[] = MeoReader_Core::createNonceData( 'meoReader_importData' );
    
    return $data;
    
  }

  
  /**
   * Validate an XML file agains an XSD XML schema.
   *
   * @param   string  $xmlFile      Absolute path to the XML file to be validated.
   * @param   string  $xmlSchema    Absolute path to the XSD file to validate against.
   * @return  bool                  TRUE if the XML file could be validated or FALSE if one of the two files does not exist or the XML file could not be validated.
   */
  public function xmlSchemeIsValid( $xmlFile, $xmlSchema ) {
    
    if( !file_exists( $xmlFile ) || !file_exists( $xmlSchema ) ) {
      
      return false;
      
    }

    /**
     * Don't throw any E_WARNING if the file cannot be read, contains a broken DOM structure,
     * or cannot be validated against the meoReader XML schema.
     */
    libxml_use_internal_errors( true );

    /* Use DOMDOCUMENT to validate the uploaded XML against the meoReader XML schema */
    $validDOM = new DOMDocument();

    if( !$validDOM->load( $xmlFile ) || !$validDOM->schemaValidate( $xmlSchema ) ) {

      return false;
      
    }

    unset( $validDOM );
    
    return true;
    
  }  

}
?>