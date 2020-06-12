<?php
/**
 * Export meoReader Subscriptions (categories and feeds) as XML.
 *
 * @category    MeoReader
 * @package     Export
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Exporter extends Meomundo_WP {
  
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
   * @var object Archive API object.
   */
  protected $ArchvieAPI;
  
  /**
   * @var array   Plugin options.
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
    
    $this->loadClass( 'MeoReader_Categories' );

    $this->loadClass( 'MeoReader_Feeds' );
    
    $this->loadClass( 'MeoReader_Archive' );

    $this->DB         = $wpdb;
    
    $this->CatAPI     = new MeoReader_Categories( $wpdb, $this->slug );
    
    $this->FeedAPI    = new MeoReader_Feeds( $wpdb, $this->CatAPI, $this->slug );
    
    $this->ArchvieAPI = new MeoReader_Archive( $wpdb );

    $this->options    = get_option( $pluginSlug );

  }
  
  /**
   * Helper: Gather categories and feeds.
   *
   * @return  array Array of all subscriptions (categories and feeds).
   */
  public function getSubsciptionList() {

    $subscriptions      = array();

    /* Get a list (arary) of all categories (via the category API). */
    $categories         = $this->CatAPI->getCategoryList();

    /* Get a list of all feeds that are assigned to each category. */
    foreach( $categories as $category ) {
      
      $subscriptions[]  = array(
        'id'    => $category['id'],
        'name'  => $category['name'],
        'items' => $category['items'],
        'feeds' => $this->FeedAPI->getFeedList( $category['id'] )
      );
      
    }
    
    return $subscriptions;

  }
  
  /**
   * Create content for an XML file (to be exported).
   *
   * @param   array   $data   Array of data to build the XML content.
   * @return  string          XML file content.
   */
  public function createXML( $data ) {
    
    $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
    
    $xml .= '<meoReader publisher="http://www.meomundo.com">' . "\n";

    
    /**
     * Store All archived entries in an array with
     * the main index bein the XML feed's URL.
     */
    $archive = array();
    
    foreach( $data['archive'] as $entry ) {

      $archive[ $entry['feed_xml_url'] ][] = base64_encode( json_encode( $entry ) );

    }

    /* Subscriptions */
    $xml .= " <subscriptions>\n";
    
    foreach( $data['subscriptions'] as $cat ) {
      
      $xml .= ' <category id="' . $cat['id'] . '" name="' . htmlspecialchars( $cat['name'], ENT_QUOTES ) . '">' . "\n";
      
      foreach( $cat['feeds'] as $feed ) {
        
        $xml .= "  <feed>\n";
        $xml .= '   <id>' . $feed['id'] . "</id>\n";
        $xml .= '   <name>' . htmlspecialchars( $feed['name'], ENT_QUOTES ) . "</name>\n";
        $xml .= '   <xmlURL>' . htmlspecialchars( $feed['xml_url'], ENT_QUOTES ) . "</xmlURL>\n";
        $xml .= '   <htmlURL>' . htmlspecialchars( $feed['html_url'], ENT_QUOTES ) . "</htmlURL>\n";
        $xml .= '   <lastBuildDate>' . $feed['last_build_date'] . "</lastBuildDate>\n";
        $xml .= '   <description><![CDATA[' . htmlspecialchars( $feed['description'], ENT_QUOTES ) . "]]></description>\n";
        
        /*
         * Embed the archived elements for this very feed here
         * so it can be installed immediatley with the feed itself!
         */
        if( isset( $archive[ $feed['xml_url'] ] ) ) {
          
          $xml .= "   <archive>\n";
          
          foreach( $archive[ $feed['xml_url'] ] as $archiveEntry ) {
            
            $xml .= '    <entry><![CDATA[' . $archiveEntry . "]]></entry>\n";
            
          }
          
          $xml .= "   </archive>\n";
          
        }
        
        $xml .= "  </feed>\n";
        
      }
      
      $xml .= " </category>\n";

    }
    
    $xml .= " </subscriptions>\n";
    
    $xml .= "</meoReader>\n";
    
    return $xml;    
    
  }
  
  /**
   * Enforce the download of the XML file.
   * The current PHP script has to be stopped in order to serve the data as a file!
   *
   * @param string $xml XML file content.
   */
  public function createXMLDownload( $xml ) {
    
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: Binary'); 
    header('Content-disposition: attachment; filename="MeoReader_Export.xml"'); 

    echo $xml;

    exit;
    
  }


  /**
   * Enforce the download of the OPML file.
   * The current PHP script has to be stopped in order to serve the data as a file!
   *
   * @param string $xml OPML XML file content.
   */
  public function createOPMLDownload( $xml ) {
    
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: Binary'); 
    header('Content-disposition: attachment; filename="MeoReader-Subscriptions.opml"'); 

    echo $xml;

    exit;
    
  }




  /**
   * Create content for an OPML XML file (to be exported).
   *
   * @param   array   $data   Array of data to build the XML content.
   * @return  string          XML file content.
   */
  public function createOPML( $data ) {

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<opml version="2.0">' . "\n";
    $xml .= " <head>\n";
		$xml .= "  <title>MeoReader-Subscriptions.opml</title>\n";
		$xml .= " </head>\n";
		$xml .= " <body>\n";

    foreach( $data['subscriptions'] as $cat ) {
      
      foreach( $cat['feeds'] as $feed ) {

        $xml .= '<outline text="' . htmlspecialchars( $feed['name'], ENT_QUOTES ) . '" description="' . htmlspecialchars( $feed['description'], ENT_QUOTES ) . '" htmlUrl="' . htmlspecialchars( $feed['html_url'], ENT_QUOTES ) . '" language="unknown" title="' . htmlspecialchars( $feed['name'], ENT_QUOTES ) . '" type="rss" version="RSS2" xmlUrl="' . htmlspecialchars( $feed['xml_url'], ENT_QUOTES ) . '" category="' . htmlspecialchars( $cat['name'], ENT_QUOTES ) . '" />' . "\n";

      }

    }
    
    $xml .= " </body>\n";

    $xml .= "</opml>\n";
    
    return $xml;    
    
  }
  
  /**
   * Get the list of ALL archive entries (no pagination).
   *
   * @return array  List of all archive entries.
   */
  public function getArchiveList() {
    
    $archiveEntries = $this->ArchvieAPI->getArchiveEntries( 1, -1 );
    
    return ( false !== $archiveEntries && !empty( $archiveEntries ) && count( $archiveEntries ) > 0 ) ? $archiveEntries : array();

  }
  
}
?>