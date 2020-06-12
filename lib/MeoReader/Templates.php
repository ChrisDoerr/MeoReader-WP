<?php
/**
 * Basic Template Class.
 *
 * @category    MeoReader
 * @package     Templates
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 *
 */
class MeoReader_Templates extends Meomundo_WP {
  
  /**
   * @var string Current WordPress backend page slug.
   */
  protected $page;
  
  /**
   * The constructor.
   *
   * @param string  $absolutePluginPath   Absolute path to the plugin directory.
   * @param string  $pluginURL            URL to the plugin directory.
   * @param string  $pluginSlug           Plugin handler.
   */
  public function __construct( $absolutePluginPath, $pluginURL, $pluginSlug ) {
    
    parent::__construct( $absolutePluginPath, $pluginURL, $pluginSlug );
    
  }
  
  /**
   * Load and VIEW a template file while providing $data coming from the MODEL.
   * There should be no business logic necessary inside a VIEW - only create the
   * HTML based on the given DATA!
   *
   * @param   string  $templateName   Name of the template file to be included.
   * @param   array   $data           This array will have to contain all the data that has been collected by the various models (and maybe the controllers).
   * @return  string                  Will return HTML for the page.
   */
  public function view( $templateName, $data = array() ) {

    /**
     * Detect and prodive the current WordPress backend page slug, e.g. for targeting
     * forms, backlinks, etc.
     */
    $this->page   = ( isset( $_GET['page'] ) ) ? trim( strip_tags( $_GET['page'] ) ) : '';
    
    /**
     * It's actually a little scam but it looks more intuitive when you can use $Template->someData
     * inside a template file, don't you think?
     */
    $Template     = $this;

    /**
     * Template files are mapped the same way as class files or interfaces
     * and are located in the /templates/ folder of the plugin's root directory.
     */
    $templateName = str_replace( '_', DIRECTORY_SEPARATOR, $templateName );
    
    $file         = $this->path . 'templates' . DIRECTORY_SEPARATOR . $templateName . '.php';

    if( file_exists( $file ) ) {

      /**
       * In order to be able to execute PHP inside the template files the output buffering will be
       * used to store all "output" in a string ($html). This string can then be returned.
       */
      ob_start();

      include_once( $file );

      $html     = ob_get_contents();
        
      ob_end_clean();
      
      return $html;

    }
    else {

      /**
       * In case a template file cannot be found (does not exist on the filesystem)
       * return the error message as HTML comment so you will not break any other HTML designs!
       */
      return '<!-- meoReader error: Template file not found! -->';
      
    }
    
  }

  /**
   * Helper: Shorten a string to a given number of characters.
   *
   * @param   string  $string   String to be (potentially) shortened.
   * @param   int     $maxLength  Number of character when the string shall be shortened.
   * @param   string  $more       (Optional) In case of a shortened string, $more will be put at the end to show that it has been shortened.
   * @return  string            Shortened and re-formatted date or time.
   */
  public function shortenText( $string, $maxLength, $more = '...' ) {
    
    $length     = strlen( $string);
    
    $maxLength  = (int) $maxLength;
    
    if( $length > $maxLength ) {
      
      $string   = substr( $string, 0, $maxLength ) . $more;
      
    }
    
    return $string;
    
  }
  
  /**
   * Helper: Shorten a given date like this:
   * If DATATIME == TODAY show the timme if DATETIME is older than TODAY show the date.
   * In both cases use the date/time format specified in the WordPress settings.
   *
   * @param   string  $string   Datetime string of the format 'yyyy-mm-dd HH:ii:ss'
   * @return  string            Shortened and re-formatted date or time.
   */
  public function shortenDate( $string ) {

//@todo refactoring with strtotime()
    
    $today        = date( 'Y-m-d', time() );

    /**
     * Extract the single date and time elements from the datetime string.
     */
    $year         = substr( $string, 0, 4 );
    
    $month        = substr( $string, 5, 2 );
    
    $day          = substr( $string, 8, 2 );
    
    $hour         = substr( $string, 11, 2 );
    
    $minute       = substr( $string, 14, 2 );
    
    $second       = substr( $string, 17, 2 );
    
    /**
     * Use TIME and DATE format as set in your WordPress settings.
     */
    $dateFormat   = get_option( 'date_format' );
    
    $timeFormat   = get_option( 'time_format' );

    /**
     * Finally, create a UNIX timestamp for the given datetime string.
     */
    $currentStamp = mktime( $hour, $minute, $second, $month, $day, $year );


    /**
     * If the given datetime string is TODAY only show the (formatted) time.
     */
    if( substr( $string, 0, 10 ) == $today ) {
      
      $date       = date( $timeFormat, $currentStamp );
      
    }
    /**
     * If the given datetime string is ODLER than TODAY show only the (formatted) date
     * (without the time).
     */
    else {

      $date       = date( $dateFormat, $currentStamp );

    }
    
    return $date;
    
  }
  
}
?>