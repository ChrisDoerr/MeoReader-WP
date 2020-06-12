<?php
/**
 * Basic WordPress class to build on when developing themes and plugins.
 *
 * @category    MeoLib
 * @package     WordPress
 * @copyright   Copyright (c) 2012 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 *
 */
class Meomundo_WP extends Meomundo {

  /**
   * @var string $url URL to the theme or plugin directory.
   */
  protected $url;
  
  /**
   * @var string slug Unique theme or plugin slug. Will, for example, be used to store options or handle backend pages.
   */
  protected $slug;
  
  /**
   * The constructor.
   *
   * @param string $absolutePath Absolute path to the theme or plugin directory.
   * @param string $themeOrPluginURL URL to the theme or plugin directory.
   * @param string $slug Unique theme or plugin slug. Will, for example, be used to store options or handle backend pages.
   */
  public function __construct( $absolutePath, $themeOrPluginURL, $slug ) {
    
    $this->url      = $themeOrPluginURL;

    $this->slug     = $slug;

    parent::__construct( $absolutePath );
    
  }

  /**
   * Helper: Escape or strip "evil" code from a given string.
   *
   * @param string $string String that shall be prepared.
   * @param boolean $slash (Optional) TRUE for adding slashes, FALSE for removing slashes or leaving this one out for neither of those operations.
   * @return string Prepared and hopefully more secured string.
   */
  public function prepareString( $string, $slash = null ) {

    $string = (string) $string;
    
    $string = strip_tags( $string );

    $string = htmlspecialchars( $string, ENT_QUOTES );

    if( $slash === true ) {
      
      $string = addslashes( $string );
    
    }
    elseif( $slash === false ) {
      
      $string = stripslashes( $string );
      
    }
    
    return $string;
    
  }
  

  /**
   * Check if NOW is within the timeframe for plugin updates.
   *
   * @param   int   $hours    Timeframe for not checking for new updates. WordPress is checking every 12 hours by default so this number has to be a mulitple of 12!! If that is not the case, the plugin default, 24, will be used!
   * @param   bool  $dev      (Optional) In dev mode you don't want to wait that long until you can test your code. So if $dev is set to TRUE this method will always return FALSE!
   * @return  bool            TRUE if NOW is within the timeframe or FALSE if the timeframe has passed.
   */
  public function withinPluginUpdateTimeframe( $hours = 12, $dev = false ) {
  
    $dev        = ( isset( $dev ) && $dev === true ) ? true : false;
    
    if( $dev === true ) {
      
      return false;
      
    }
    
    $hours      = (int) $hours;
  
    $hours      = abs( $hours );

    if( $hours % 12 !== 0 ) {
    
      $hours    = 24;

    }

    $timeframe  = 60 * 60 * $hours;

    $timezone   = get_option('timezone_string');

    if( $timezone === false || trim( $timezone ) === '' ) {
  
      $timezone = ini_get( 'date.timezone' );
  
    }

    $DateTime   = ( $timezone !== false && ( $timezone ) !== '' ) ? new DateTime( null, new DateTimeZone( $timezone ) ) : new DateTime();

    $lastUpdate = get_site_transient( 'update_plugins' );

    return ( ( $lastUpdate->last_checked + $timeframe ) > $DateTime->getTimestamp() ) ? true : false;
  
  }

  /**
   * Get plugin meta data like 'Version', 'Author', etc.
   * This data is coming from the plugin "core" file (where the WP plugin comment header is declared).
   * Typically, this is in an index.php file. If you're using another file to do that you can specify that.
   *
   * @param   string  $key          Key of the meta data to be retrieved.
   * @param   string  $pluginFile   (Optional) Set the file where your plugin comment header is declared. Default is "index.php". So you only have to set this parameter when you're not using index.php in your plugin directory!
   * @return  mixed                 FALSE in case the pluginFile or the key does not exists. Otherwise the value of this plugin meta data will be returned.
   */
  public function getPluginMeta( $key, $pluginFile = '' ) {
    
    $pluginFile = ( $pluginFile === '' ) ? 'index.php' : $pluginFile;
    
    $pluginFile = $this->path . $pluginFile;

    if( !file_exists( $pluginFile ) ) {
      
      return false;
      
    }

    $pluginData = get_file_data( $pluginFile, array( 'Version' => 'Version' ), 'plugin' );
    
    return ( isset( $pluginData[ $key ] ) ) ? $pluginData[ $key ] : false;

  }
  
}
?>