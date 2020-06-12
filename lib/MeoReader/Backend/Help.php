<?php
/**
 * WordPress Backend Page: Help/Docs/Manual
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_Help {
  
  /**
   * @var string  Absolute path to the plugin directory.
   */
  protected $path;
  
  /**
   * @var string  URL to the plugin directory.
   */
  protected $url;
  
  /**
   * The constructor.
   *
   * @param string  $absolutePluginPath   Absolute path to the plugin directory.
   * @param string  $pluginURL            URL to the plugin directory.
   */
  public function __construct( $absolutePluginPath, $pluginURL ) {
    
    $this->path               = $absolutePluginPath;
    
    $this->url                = $pluginURL;
    
  }
  
  /**
   * The CONTROLLER.
   *
   * @return string HTML page code.
   */
  public function controller() {

    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'read' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }
    
    $html = '<h3 class="admin">' . _x( 'Documentation', 'headline', 'meoreader' ) . '</h3>' . "\n";
    
    $html .= $this->loadDocs();
    
    return $html;
    
  }
  
  /**
   * Load the proper doc file (in terms of the language).
   *
   * @return  string          HTML page code.
   */
  public function loadDocs() {
    
    $html   = '';
    
    $file   = $this->path . 'documentation.html';
    
    if( !file_exists( $file ) ) {
      
      return '<p class="message error">Cannot find the documentation file!</p>';
    
    }
    
    $docs       = file_get_contents( $file );
    
    /* Replace certain placeholders. */
    $docs       = str_replace( '[%pluginURL%]', $this->url, $docs );
    
    /* Only the meoDocs block is required */
    $start     = strpos( $docs, '<!--[meoDocs:Start]-->' );

    $end       = strpos( $docs, '<!--[meoDocs:End]--' );
    
    $html      .= substr( $docs, $start, ( $end - $start ) );
    
    /* Remove the header block */
    $html      = preg_replace( '#\^([^\^])*\^#im', '', $html );

/*
@next
$html .= '<p>' . wp_create_nonce('my-nonce') . '</p>';
$nonce = wp_create_nonce( 'meoreader_customAction' );
$html .= ( wp_verify_nonce( $nonce, 'meoreader_customAction' ) ) ? 'YES' : 'NO';
*/

    return $html;

  }

}
?>