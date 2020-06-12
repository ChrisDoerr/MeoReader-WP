<?php
/**
 * Import meoReader subscriptions XML.
 *
 * @category    MeoReader
 * @package     Import
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Import_MeoReader extends MeoReader_Import implements MeoReader_ImportInterface {

  /**
   * The constructor.
   *
   * @param string  $absolutePluginPath   Absolute path to the plugin directory.
   * @param string  $pluginURL            URL to the plugin directory.
   * @param string  $pluginSlug           Plugin handler.
   * @param object  $wpdb                 WordPress database object.
   * @param object  $fileKey              Array key to access the uploaded file via $_FILES[ $fileKey ].
   */
  public function __construct( $absolutePluginPath, $pluginURL, $pluginSlug, $wpdb, $fileKey ) {
    
    parent::__construct( $absolutePluginPath, $pluginURL, $pluginSlug, $wpdb, $fileKey );

  }  

  
  /**
   * Create a SimpleXML object from the uploaded XML file.
   *
   * @throws Exception  If an invalid file has been uploaded or none at all.
   * @throws Exception  If the uploaded XML file does not match the required schema.
   * @thrwos Exception  If the SimpleXML object could not be created.
   * @return object     SimpleXML object.
   */
  public function loadXMLFile() {

    if( !isset( $_FILES[ $this->fileKey ] ) || !preg_match( '#\.xml$#i', $_FILES[ $this->fileKey ]['name'] ) ) {
      
      throw new Exception( _x( 'No (valid) file to upload!', 'error message', 'meoreader' ) );
      
    }

/*
    if( !$this->xmlSchemeIsValid( $_FILES[ $this->fileKey ]['tmp_name'], $this->path . 'meoReader.xsd' ) ) {

      throw new Exception( _x( 'Invalid File Schema', 'error message', 'meoreader' ) );
      
    }
*/

    /* Only if the XML of the uploaded file is valid go on and create a SimpleXML object from it. */
    $xml = simplexml_load_file( $_FILES[ $this->fileKey ]['tmp_name'] );
    
    if( $xml === false ) {
      
      throw new Exception( _x( 'Could not process XML file!', 'error message', 'meoreader' ) );
      
    }

    return $xml;

  }

  
  /**
   * Map the XML content into a "standardized" array that can be handled by this plugin's importer.
   *
   * @param   object  $xml    SimpleXML object.
   * @return  array           An array that contains the complete subscription list (categories and feeds).
   */
  public function extractDataFromXML( $xml ) {
    
    $subscriptions  = array();

    $archive        = array();

    /* Categories */
    foreach( $xml->subscriptions->category as $subscription ) {

      $feeds    = array();
      
      $archive  = array();
      
      /* Feeds */
      foreach( $subscription->feed as $feed ) {
        
        if( isset( $feed->archive->entry ) ) {
          
          foreach( $feed->archive->entry as $archiveEntry ) {

/*
            $archive[] = array(
              'pubDate'         => (string) $archiveEntry->pubDate,
              'description'     => (string) $archiveEntry->description,
              'descriptionPrep' => (string) $archiveEntry->descriptionPrep,
              'title'           => (string) $archiveEntry->title,
              'enclosures'      => (string) $archiveEntry->enclosures,
              'feedXmlUrl'      => (string) $archiveEntry->feedXmlUrl
            );
*/

            $archive[] = (string) $archiveEntry;

          }
          
        }

        /* Only get the feed (xml) URL. Get the rest of the meta data when fetching the feed for the first time */
        $feeds[] = isset( $feed->xmlURL ) ? (string) $feed->xmlURL : '';
        
      }
      
      $subscriptions[] = array(
        'name'    => ( isset( $subscription->attributes()->name ) ) ? (string) $subscription->attributes()->name : '',
        'feeds'   => $feeds,
        'archive' => $archive
      );
      
    }

    return $subscriptions;
    
  }

}
?>