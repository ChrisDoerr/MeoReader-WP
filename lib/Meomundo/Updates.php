<?php
/**
 * Handle updates for meomundo products, hosted by meomundo.com
 * via the meomundo update webservice.
 *
 * @category    MeoLib
 * @package     Updates
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class Meomundo_Updates {
  
  /**
   * @var string $apiVersion The API version is required when using the meomundo update webservice.
   */
  protected $apiVersion;
  
  /**
   * @var string $serviceURL URL of the meomundo update web service.
   */
  protected $serviceURL;
  
  /**
   * @var array $update Array containing all kinds of information about the latest version of a requested product.
   */
  protected $update;
  
  /**
   * The constructor.
   *
   * @param string $serviceURL URL of the meomundo update web service.
   */
  public function __construct( $serviceURL ) {
    
    $this->serviceURL = $serviceURL;
    
    if( substr( $this->serviceURL, -1 ) !== '/' ) {
      
      $this->serviceURL .= '/';
      
    }
    
    $this->apiVersion = '1.0';
    
  }
  
  /**
   * Set a custom API version of the meomundo web service.
   *
   * @param   string  $apiVersion   The API version is required when using the meomundo update webservice.
   * @return  bool                  TRUE if at least the version pattern was valid, FALSE if that was not the case.
   */
  public function setApiVersion( $apiVersion ) {
    
    if( preg_match( '#^[0-9]{1,2}\.[0-9]{1,2}$#', $apiVersion ) ) {

      $this->apiVersion = $apiVersion;
      
      return true;
      
    }
    else {
      
      return false;
      
    }
    
  }
  
  /**
   * Check if a given version string is a valid "two-dot" string.
   *
   * @param   string  $version    Version string, following the pattern {fullVersion}.{newFeature(s)}.{bugFix(es)}
   * @return  bool                TRUE if the version pattern matches or FALSE if it does not.
   */
  public function isValidVersion( $version ) {

    return ( preg_match( '#^[0-9]+\.[0-9]+\.[0-9]+$#', $version ) ) ? true : false;
  
  }

  /**
   * Check the meomundo web service if there is a newer versions of a given product.
   *
   * @param   string  $product    Product name.
   * @param   string  $version    "Two-dot" notation version number.
   * @return  bool                TRUE if there is a newer version for a given product or FALSE if an error of any kind has occured or there simply is no update for this product.
   */
  public function check( $product, $version ) {

    if( !$this->isValidVersion( $version ) ) {
      
      return false;
      
    }

    /* Get the latest version (via the web service) */
    $latestVersion = $this->getLatestProductVersion( $product );

    if( $latestVersion === false ) {
      
      return false;
      
    }

    /* Do the actual comparison. */
    if( version_compare( $version, $latestVersion->version, '<' ) ) {
      
      $this->update = (array) $latestVersion;

      return true;
      
    }
    else {
      
      return false;
      
    }
    
  }
  
  /**
   * Normalize a product name.
   *
   * @param   string  $product    Product name.
   * @return  string              The prepared and normalized product name.
   */
  public function normalizeProductName( $product ) {
    
    $product    = (string) $product;
    
    $product    = trim( strip_tags( $product ) );

    return $product;

  }
  
  /**
   * Get the "latest" data (like the version number, the file URL, etc. ) for a given product.
   *
   * @param   string      $product    Product name/slug.
   * @return  bool|array              FALSE in case some kind of error has occured or an array of the "latest" product information.
   */
  public function getLatestProductVersion( $product ) {
  
    $product    = $this->normalizeProductName( $product );
  
    /* Build the proper URL for making a REST request to the meomundo update web service. */
    $target     = $this->serviceURL . $this->apiVersion . '/' . $product . '/';

    /* The answer will be a JSON encoded string that need to be converted back into a PHP array. */
    $response   = json_decode( $this->makeRequest( $target ) );

    /* If the request was valid, return the "latest product" information. */
    if( $response->request === true ) {
      
      return $response;
      
    }
    else {
      
      return false;
      
    }
  
  }    

  /**
   * Make the actual REST request to the web service.
   *
   * @param   string  $url    RESTFUL API call.
   * @return  string          A JSON string containing information about the validity of the request and the data itself (or in case the request was invalid, an error message).
   */
  public function makeRequest( $url ) {

    /* @todo more in-depth error handling including TIMEOUT */
    $data = @file_get_contents( $url );

    if( $data === false ) {
      
      return json_encode(
        array(
          'request' => false,
          'message' => 'Webservice not found!'
        )
      );
      
    }
    else {
      
      return $data;
      
    }
    
  }
  
  /**
   * Getter: Get custom product information provided by the update web service.
   *
   * @param   string  $key    Array key of the product information - according to the update web service.
   * @return  string          The product information (value).
   */
  public function getUpdate( $key ) {
    
    return ( isset( $this->update[ $key ] ) ) ? $this->update[ $key ] : '';
    
  }

  /**
   * Check wether the download of a certain product update requires an authentification key.
   *
   * @return bool TRUE if a auth key is required for downloading the latest product file or FALSE if that is not the case.
   */
  public function updateRequiresAuthentification() {
    
    return ( isset( $this->update['auth'] ) && $this->update['auth'] === true ) ? true : false;
    
  }

}
?>