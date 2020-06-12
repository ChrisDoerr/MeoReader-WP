<?php
/**
 * WordPress backend ghost page: Feed Checker.
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_FeedChecker extends Meomundo_WP {
  
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
   * @var array plugin options.
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
    
    $this->DB         = $wpdb;
    
    $this->loadClass( 'MeoReader_Templates' );

    $this->loadClass( 'MeoReader_Categories' );

    $this->loadClass( 'MeoReader_Feeds' );

    $this->CatAPI     = new MeoReader_Categories( $wpdb, $pluginSlug );
    
    $this->FeedAPI    = new MeoReader_Feeds( $wpdb, $this->CatAPI, $pluginSlug );
    
    $this->options    = get_option( $pluginSlug );

  }
  
  /**
   * The controller.
   *
   * @return string HTML of the backend page.
   */
  public function controller() {

    $feedURL  = ( isset( $_GET['feedURL'] ) ) ? trim( strip_tags( urldecode( $_GET['feedURL'] ) ) ) : '';
    
    $html = '<div class="wrap" id="meoReader">' . "\n";
    
    $html .=   '<h3>Tools: Feed Checker</h3>';
    
    $html .=  $this->viewForm( $feedURL );

    /**
     * If a URL has been passed fetch it, check it, show details.
     */
    if( $feedURL !== '' ) {
      
      $html .= $this->checkFeed( $feedURL );
      
    }
    
    $html .= "</div>\n";
    
    return $html;

  }
  
  /**
   * View a form so you can check other URLs as well.
   *
   * @param string $feedURL (Optional) A given URL can be used to pre-populate the text input field.
   * @return string The form HTML.
   */
  public function viewForm( $feedURL = '' ) {
    
    $html = '<form name="meoReaderForm_FeedChecker" action="admin.php" method="get">';
    $html .= ' <input type="hidden" name="page" value="meoreader_feedChecker" />' . "\n";
    $html .= ' <label for="meoReaderForm_FeedChecker_URL">Feed URL:</label>';
    $html .= ' <input type="text" class="regular-text" name="feedURL" id="feedURL" value="' . $feedURL . '" />';
    $html .= ' <input type="submit" value="Check Feed" />';
    $html .= '</form>';
    
    if( $feedURL !== '' ) {
      
      $html .= '<p><a href="' . $feedURL . '">' . $feedURL . '</a></p>';
      
    }
   
    return $html;
    
  }
  
  /**
   * Do the actual feed (respectively, URL) checking.
   * Try to parse the data and show certain error - if there are any.
   *
   * @param   string    $feedURL The URL of an RSS (or atom) feed.
   * @return  string    HTML of the results.
   */
  public function checkFeed( $feedURL = '' ) {
    
    if( trim( $feedURL ) === '' ) {
      
      return '';
      
    }

    // Output string
    $html = '';
    
    // Load the meta data of a given feed - if exists.
    $feed = $this->FeedAPI->getFeedByURL( $feedURL );
    
    if( $feed === false ) {
      
      $html .= '<p>This feed does not yet exists in your subscriptions list!</p>' . "\n";
      
    }
    else {
    
      if( isset( $feed['last_build_date'] ) ) {
        
        $html .= '<p><strong>Last Check:</strong> ' . $feed['last_build_date'];

        $html .= ( $this->FeedAPI->feedHasBeenModified( $feedURL, $feed['last_build_date'] ) === false ) ? ' (feed has not been modified since)' : ' (feed has been modified since the last refresh!)';
        
        $html .= "</p>\n";
      
      }
      
    }
    
    
    // Load the feed URL
    $xml = $this->FeedAPI->loadFeed( $feedURL );

    if( isset( $xml['request'] ) && $xml['request'] === false ) {

      return '<p>' . $xml['message'] . '</p>';

    }

    // Get item meta data
    $items = $this->FeedAPI->getItemMeta( $xml );
    
    foreach( $items as $item ) {
      
      $html .= '<table style="margin:2em 0;border:1px solid #000;">';

      foreach( $item as $key => $value ) {
        
        $class = ( $value !== '' ) ? '' : 'error';
        
        $value = preg_replace( '#"http://([^"]*)"#', "<a href=\"http://$1\">$1</a>", $value );
      
        $html .= ' <tr>';
        $html .= '  <th style="text-align:right;padding-right:1em;vertical-align:top;" class="' . $class . '">' . $key . '</th>';
        $html .= '  <td style="padding:0.25em 0;" class="' . $class . '">' . $value . '</td>';
        $html .= ' </tr>';

      }

      if( $this->FeedAPI->entryExists( $item['link'], $item['guid'] ) === true ) {
        
        $html .= '<tr><th colspan="2" class="success">Entry exists in DB</th>';
      
      }
      else {
        
        $pubDate      = date( 'Y-m-d H:i:s', strtotime( $item['pub_date'] ) );

        $olderThan    = date( 'Y-m-d H:i:s', time() - ( $this->options['deleteEntriesOlderThan'] * 60 * 60 * 24 ) );
        
        $itemIsTooOld = ( $pubDate < $olderThan ) ? '- which is okay, since it is older than ' . $this->options['deleteEntriesOlderThan'] . ' days' : 'yet';
        
        $html .= '<tr><th colspan="2" class="error">Entry does not exist ' . $itemIsTooOld . '!</th>';

      }

      $html .= '</table>';
      
    }
    
    return $html;

  }
  
}
?>