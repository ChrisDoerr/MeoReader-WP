<?php
/**
 * Favicon Handler
 *
 * @category    MeoReader
 * @package     Favicons
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Favicons {
  
  /**
   * @var bool $curlIsEnabled TRUE if the cURL extension is (installed and) enabled.
   */
  protected $curlIsEnabled;
  
  /**
   * @var bool $urlFOpenEnabled TRUE if allow_url_fopen is set to true in the php.ini configuration.
   */
  protected $urlFOpenEnabled;

  /**
   * @var string $path Absolute path to the /favicons/ plugin directors.
   */
  protected $path;

  /**
   * @var string $url Absolute URL to the /favicons/ plugin directory.
   */
  protected $url;


  /**
   * The constructor.
   *
   * @param string  $path   Absolute path to the plugin directory.
   * @param string  $url    Absolute URL to the plugin directory.
   */
  public function __construct( $path, $url ) {
    
    $this->path             = $path . 'favicons' . DIRECTORY_SEPARATOR;
    
    $this->url              = $url . 'favicons/';

    $this->urlFOpenEnabled  = ( ini_get( 'allow_url_fopen' ) == 1 )                     ? true : false;
    
    $this->curlIsEnabled    = ( in_array( 'curl', get_loaded_extensions() ) !== false ) ? true : false;

  }


  /**
   * Download an external image (favicon) to the server (in this case the /favicons/ plugin directory.
   *
   * @param string        $url              URL of the external image file.
   */
  public function downloadFavicon( $url ) {

    $url                = trim( strip_tags( $url ) );
    
    $filename           = md5( strtolower( $url ) );
    
    $file               = $this->path . $filename . '.png';
    
    $fileURL            = $this->url . $filename . '.png';

    if( $url == ''
        || ( $this->urlFOpenEnabled == false && $this->curlIsEnabled == false )
    ) {
      
      return false; // @todo clearer error handling | error message
      
    }
    
    // Let Google detect the favicon.
    $url = 'https://www.google.com/s2/favicons?domain=' . urlencode( $url );

    // The preferred approach is using cURL
    if( $this->curlIsEnabled == true ) {

      return $this->downloadImage_curl( $url, $file, $fileURL );
      
    }

    // If cURL is not available use fopen instead
    if( $this->urlFOpenEnabled == true ) {
      
      return $this->downloadImage_fopen( $url, $file, $fileURL );
      
    }
    
  }

  /**
   * Download an external image by using cURL
   * By making this function 'protected' you don't (necessarily) have to validate the parameters again.
   *
   * @param   string      $url        URL of the external image file.
   * @param   string      $file       Absolute path to the targeted filename.
   * @param   string      $fileURL    Absolute URL to the targeted filename.
   * @return  mixed                   (String) URL in case the file could be downloaded or FALSE if that was not the case.
   */
  protected function downloadImage_curl( $url, $file, $fileURL ) {

    /* Workaround: Let Google handle the favicon detection. */
    $ch         = curl_init( $url );

    curl_setopt( $ch, CURLOPT_HEADER, 0 );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );    /* Google requires SSL */
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );    /* Google requires SSL */
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );   /* 10 seconds, hardcoded, timeout */

    $rawdata    = curl_exec( $ch );

    curl_close( $ch );
    
    if( $fp     = fopen( $file, 'w' ) ) {

      fwrite( $fp, $rawdata );

      fclose( $fp );
    
      return $fileURL;
    
    }
    else {
      
      return false;
      
    }
    
  }

  /**
   * Download an external image by using fopen, respectively the short form wrappers file_get_contents and file_put_contents.
   * By making this function 'protected' you don't (necessarily) have to validate the parameters again.
   *
   * @param   string      $url        URL of the external image file.
   * @param   string      $filename   Absolute path to the targeted filename.
   * @param   string      $fileURL    Absolute URL to the targeted filename.
   * @return  mixed                   (String) URL in case the file could be downloaded or FALSE if that was not the case.
   */
  protected function downloadImage_fopen( $url, $filename, $fileURL ) {
    
    /* Workaround: Let Google handle the favicon detection. */
    if( $file = @file_get_contents( $url ) ) {
      
      if( @file_put_contents( $filename, $file ) ) {
      
        chmod( $filename, 0666);
        
        return $fileURL;
        
      }
      else {
        
        return false;
        
      }
      
    }
    else {
      
      return false;
      
    }
    
  }
  
}

?>