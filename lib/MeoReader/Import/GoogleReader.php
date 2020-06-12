<?php
/**
 * Import Google Reader subscriptions (via Google Takeout ZIP).
 *
 * @category    MeoReader
 * @package     Import
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Import_GoogleReader extends MeoReader_Import implements MeoReader_ImportInterface {

  /**
   * The constructor.
   *
   * @param string  $absolutePluginPath   Absolute path to the plugin directory.
   * @param string  $pluginURL            URL to the plugin directory.
   * @param string  $pluginSlug           Plugin handler.
   * @param object  $wpdb                 WordPress database object.
   * @param string                        Array key to access the uploaded file via $_FILES[ $fileKey ].
   */
  public function __construct( $absolutePluginPath, $pluginURL, $pluginSlug, $wpdb, $fileKey ) {
    
    parent::__construct( $absolutePluginPath, $pluginURL, $pluginSlug, $wpdb, $fileKey );

  }  

  /**
   * Look for and then extract the subscription XML file and return a SimpleXML object.
   *
   * @throws Exception  If the PHP ZIP functionality is not awailable.
   * @throws Exception  If the required XML file was not contained in the ZIP file.
   * @return object     SimpleXML object representing the Google Takeout data.
   */
  public function loadXMLFile() {

    if( !isset( $_FILES[ $this->fileKey ] ) || $_FILES[ $this->fileKey ]['name'] == '' ) {
      
      throw new Exception( 'No file to be uploaded!' );
      
    }
    

    /* It's kinda late to check here. But the PHP zip_... functions are absolutely required. So be sure they are available! */
    if( !function_exists( 'zip_open' ) ) {
      
      throw new Exception( 'You need to enable the PHP function <i>zip_open</i> in your PHP.ini file!' );

    }

    /* Open the zip file */
    if( $zip = zip_open( $_FILES[ $this->fileKey ]['tmp_name'] ) ) {
    
      $targetFile = '';
    
      $content    = '';

      /* Get through the list of all the containing files and look out for the subscriptions XML file. */
      while( $entry = zip_read( $zip ) ) {
    
        $tmp =  zip_entry_name( $entry );

        if( preg_match( '#subscriptions\.xml$#i', $tmp ) ) {
      
          $handle   = zip_entry_open( $zip, $entry, "r");

          $content  = zip_entry_read( $entry, zip_entry_filesize( $entry ) );
        
        }

      }
      
      /* The XML file could not be found */
      if( $content == '' ) {
        
        throw new Exception( 'Could not find Google Reader subscription list!' );
        
      }

      zip_close( $zip );

    }
    else {
      
      throw new Exception( 'Cannot open ZIP file' );
      
    }
    
    /* Create a SimpleXML object from the content of the XML file. */
    $xml = simplexml_load_string( $content );
    
    if( $xml === false ) {
      
      throw new Exception( 'Could not process XML file!' );
      
    }
    
    return $xml;
    
  }
  

  /**
   * Map the XML content into an "standardized" array that can be handled by this plugin's importer.
   *
   * @param   object  $xml    SimpleXML object.
   * @return  array           An array that contains the complete subscription list (categories and feeds).
   */
  public function extractDataFromXML( $xml ) {
    
    $data = array();
    
    /* Cagegories */
    foreach( $xml->body->outline as $subscription ) {
      
      $feeds = array();
      
      /* Feeds */
      foreach( $subscription->outline as $feed ) {
        
        /* Only get the feed (xml) URL. Get the rest of the meta data when fetching the feed for the first time */
        $feeds[] = isset( $feed->attributes()->xmlUrl ) ? (string) $feed->attributes()->xmlUrl : '';
        
      }
      
      $data[] = array(
        'name'    => (string) $subscription['title'],
        'feeds'   => $feeds
                
      );
      
    }
    
    return $data;
    
  }
  
}
?>