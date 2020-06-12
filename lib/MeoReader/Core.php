<?php
/**
 * General Helpers/Tools
 *
 * @category    MeoReader
 * @package     Core
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Core {
  
  /**
   * Helper: Create nonce array.
   *
   * @param   string  $action   The action be "nonced" so to speak :)
   * @return  array             Array of nonce data pair.
   */
  public static function createNonceData( $action ) {
    
    if( function_exists( 'wp_create_nonce' ) ) {
    
      return array(
        'action'  => $action,
        'nonce'   => wp_create_nonce( $action )
      );
    
    }
    
  }
  

  /**
   * Secure HTML output against certain XSS attack vectors via the HTMLPurifier library.
   * http://htmlpurifier.org/
   *
   * @param   array $results    An array of entries coming from a database request.
   * @return  array             That same array but -if options have been set- with titles and descriptions being cleaned up.
   */
  public static function purifyResultsHTML( $results ) {
    
    $options = get_option( 'meoreader' );
    
    if( !isset( $options['purify'] ) || $options['purify'] === false ) {
      
      return $results;
      
    }

    $dimResults = count( $results );

    if( is_array( $results ) && $dimResults > 1 ) {
      
      if( !class_exists( 'HTMLPurifier' ) ) {
      
        include_once MEOREADER_PATH . 'vendors' . DIRECTORY_SEPARATOR . 'HTMLPurifier' . DIRECTORY_SEPARATOR . 'HTMLPurifier.standalone.php';
      
      }

      /* Also try to fix broken HTML */
      if( !class_exists( 'HtmlFixer' ) ) {
      
        include_once MEOREADER_PATH . 'vendors' . DIRECTORY_SEPARATOR . 'htmlFixer' . DIRECTORY_SEPARATOR . 'htmlfixer.class.php';
      
      }
      
      $HTMLFixer = new HtmlFixer();

      
      /* Configuration Object */
      $Config = HTMLPurifier_Config::createDefault();
      
      $Config->set( 'HTML.SafeIframe', true );
      
      $Config->set( 'HTML.SafeObject', true );
      
      $Config->set( 'HTML.SafeEmbed', true );
      
      $Config->set( 'Output.FlashCompat', true );
      
      $Config->set( 'URI.SafeIframeRegexp', '%^(http[s]?:)?//(www.youtube(?:-nocookie)?.com/embed/|player.vimeo.com/video/|w.soundcloud.com/player/)%' );
    

      $HTMLPurifier = new HTMLPurifier( $Config );

      /* "Purify" titles and descriptions */
      for( $i = 0; $i < $dimResults; $i++ ) {
      
        $results[ $i ]['title']       = $HTMLPurifier->purify( $results[ $i ]['title'] );
        
        $results[ $i ]['description'] = $HTMLPurifier->purify( $results[ $i ]['description'] );
        
        $results[ $i ]['description'] = $HTMLFixer->getFixedHtml( $results[ $i ]['description'] );
      
      }
    
    }
    
    return $results;

  }
  
  /**
   * Purify content string.
   *
   * @param   string  $content    HTML content to be purified
   * @return  string              Purified HTML string.
   */
  public static function purifyContent( $content ) {

    if( !class_exists( 'HTMLPurifier' ) ) {
      
      include_once MEOREADER_PATH . 'vendors' . DIRECTORY_SEPARATOR . 'HTMLPurifier' . DIRECTORY_SEPARATOR . 'HTMLPurifier.standalone.php';
      
    }
      
    /* Configuration Object */
    $Config = HTMLPurifier_Config::createDefault();
      
    $Config->set( 'HTML.SafeIframe', true );
      
    $Config->set( 'HTML.SafeObject', true );
      
    $Config->set( 'HTML.SafeEmbed', true );
      
    $Config->set( 'Output.FlashCompat', true );
      
    $Config->set( 'URI.SafeIframeRegexp', '%^(http[s]?:)?//(www.youtube(?:-nocookie)?.com/embed/|player.vimeo.com/video/|w.soundcloud.com/player/)%' );
    

    $HTMLPurifier = new HTMLPurifier( $Config );

    return $HTMLPurifier->purify( $content );

  }
  
  /**
   * Check whether the current user is allowed to do something.
   *
   * @param   string  $capability   Can either be "read" or "admin"
   * @return  bool                  TRUE if the current use allowed to do someting or FALSE if he/she is not.
   */
  public static function current_user_can( $capability ) {
    
    global $current_user;
    
    
    if( !isset( $current_user ) ) {
      
      return false;
      
    }

    $capability = strtolower( $capability );
    
    if( $capability !== 'read' ) {
      
      $capability = 'admin';
      
    }
    
    $options = get_option( 'meoreader' );
    
    /* Is Admin */
    if( in_array( 'administrator', $current_user->roles ) ) {
      
      return true;
      
    }
    
    /* Is SingleUser */
    return ( $options['userID'] > 0 && $current_user->ID === $options['userID'] && $capability === 'read' ) ? true : false; 

  }
  
  /**
   * Set a visual landmark (in dev mode) so in case something goes wrong the code stack can be traced.
   * The landmarks themselves are nothing but HTML comments so they only show up in the source code and
   * won't disturb any users.
   *
   * @param string  $file   File to be marked.
   * @param int     $line   Line of code in that file where the landmark has been set.
   */
  public static function setLandmark( $file, $line ) {
    
    if( MEOREADER_DEVMODE === true ) {
    
      $file = str_replace( MEOREADER_PATH, '', $file );
    
      echo '<!-- MeoReader Landmark: ' . $file . ' (' . $line . ') -->' . "\n";
    
    }
    
  }
  

  /**
   * Do at least a basic sanatization of the content.
   * Not nearly as safe as HTMLPurifier but way faster!
   *
   * @param   string  $content    Entry content to be secured.
   * @return  string  	          Sanatized content.
   */
  public static function htmLawed_content( $content ) {
    
    if( !function_exists( 'htmLawed' ) ) {
      
      include_once MEOREADER_PATH . 'vendors' . DIRECTORY_SEPARATOR . 'htmLawed' . DIRECTORY_SEPARATOR . 'htmLawed.php';
      
    }
    
    $content = htmLawed(
      htmlspecialchars_decode( $content ),
      array(
        'safe'           => 1,
        'deny_attribute' => '* -alt -title -src -href',
        'keep_bad'       => 0,
        'comment'        => 1,
        'cdata'          => 1,
        'elements'       => 'div,p,ul,li,a,img,dl,dt,h1,h2,h3,h4,h5,h6,ol,br,table,tr,td,blockquote,pre,ins,del,th,thead,tbody,b,i,strong,em,tt'
      )
    );

    return $content;

  }
  
  /**
   * Do at least a basic sanatization of the title.
   * Not nearly as safe as HTMLPurifier but way faster!
   *
   * In case of the title it's good enough since there is
   * basically nothing allowed but text in a titel.
   *
   * @param   string  $title    Entry title to be sanatized.
   * @return  string            Sanatized string.
   */
  public static function htmLawed_title( $title ) {
    
    if( !function_exists( 'htmLawed' ) ) {
      
      include_once MEOREADER_PATH . 'vendors' . DIRECTORY_SEPARATOR . 'htmLawed' . DIRECTORY_SEPARATOR . 'htmLawed.php';
      
    }
    
    $title = htmlspecialchars_decode( $title );

    $title = htmLawed(
      $title,
      array(
        'deny_attribute'  => '*',
        'elements'        => '-*'
      )
    );

    return $title;

  }
  
}
?>