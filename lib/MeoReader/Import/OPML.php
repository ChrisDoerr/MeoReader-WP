<?php
/**
 * Import OPML subscriptions.
 *
 * @category    MeoReader
 * @package     Import
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Import_OPML extends MeoReader_Import implements MeoReader_ImportInterface {

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

    $xml = @simplexml_load_file( $_FILES[ $this->fileKey ]['tmp_name'] );

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
    
    /* Nested like the Google Reader Takeout */
    if( isset( $xml->body->outline[0]->outline ) ) {
      
      $data = $this->extractNestedDataFromXML( $xml );
      
      return $data;
      
    }
    else {

      /* Feeds */
      foreach( $xml->body->outline as $feed ) {

        /* Only get the feed (xml) URL. Get the rest of the meta data when fetching the feed for the first time */
        $feedURL              = isset( $feed->attributes()->xmlUrl )    ? trim( (string) $feed->attributes()->xmlUrl ) : '';
      
        $category             = isset( $feed->attributes()->category )  ? trim( (string) $feed->attributes()->category ) : 'Unsorted';

        $data[ $category ][]  = $feedURL;
      
      }

      /* Remodelling the data structure according the internal import standard */
      $newData = array();
    
      foreach( $data as $cat => $feeds ) {
      
        $newData[] = array(
          'name'  => $cat,
          'feeds' => $feeds
        );
      
      }
    
      return $newData;
    
    }

  }


  /**
   * Sometimes OPML is nested like the one from the Google takeout
   *
   * @param   object  $xml    SimpleXML object representation of the OPML file.
   * @return  array           Extracted data (categories and subscriptions).
   */
  public function extractNestedDataFromXML( $xml ) {
    
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