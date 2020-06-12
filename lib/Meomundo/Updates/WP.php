<?php
/**
 * Handle plugin updates for plugins that are hosted by meomundo.com
 * via the meomundo update webservice.
 *
 * @category    MeoLib
 * @package     WordPress
 * @subpackage  Updates
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class Meomundo_Updates_WP extends Meomundo_Updates {
  
  /**
   * @var string $productName Product name, case-sensitive!
   */
  protected $productName;
  /**
   * @var string $wpPluginSlug The WordPress plugin slug which is the relative path to the "plugin file" (the one with the plugin header) from /plugins/ on (e.g. 'meoDummy/index.php' ).
   */
  protected $wpPluginSlug;
  
  /**
   * The constructor.
   *
   * @param string  $productName    Product name, case-sensitive!
   * @param string  $wpPluginSlug   The WordPress plugin slug which is the relative path to the "plugin file" (the one with the plugin header) from /plugins/ on (e.g. 'meoDummy/index.php' ).
   * @param string  $serviceURL     URL to the meomundo update webservice.
   */
  public function __construct( $productName, $wpPluginSlug, $serviceURL ) {
    
    parent::__construct( $serviceURL );
    
    $this->productName  = $productName;

    $this->wpPluginSlug = $wpPluginSlug;
    
  }
  
  /**
   * Add plugin update to the WordPress notification system
   * + provide data the automatic-update-process of WordPress.
   *
   * @param   object  $transient    The plugin transient provided by the WordPress API!
   * @return  object                The transient with the newly added plugin update data.
   */
  public function addTransient( $transient ) {

    $Obj                = new stdClass();

    $Obj->slug          = $this->wpPluginSlug;

    $Obj->new_version   = $this->getUpdate( 'version' );

    $Obj->package       = $this->getUpdate( 'file' );

/*
    $transient->checked[ $this->wpPluginSlug ]   = $Obj->new_version;
*/

    $transient->response[ $this->wpPluginSlug ]  = $Obj;

    return $transient;
  
  }
  
  /**
   * Show more information about the new update.
   *
   * @param   bool        $false    Provided by the WordPress API!
   * @param   string      $action   Provided by the WordPress API!
   * @param   object      $args     Provided by the WordPress API!
   * @return  bool|object           FALSE in case this is not about your plugin or an object containing all the necessary information to be shown.
   */
  public function addInfo( $false, $action, $args ) {

    if( $args->slug === $this->wpPluginSlug ) {  
 
      $updateMeta         = (array) $this->getUpdate( 'meta' );

      $requires           = ( isset( $updateMeta['requires'] ) ) ? $updateMeta['requires'] : '3.0';

      $changelog          = $this->getUpdate( 'changelog' );

      $Obj                = new stdClass();

      $Obj->slug          = $this->wpPluginSlug;

      $Obj->plugin_name   = $this->productName;

      $Obj->new_version   = $this->getUpdate( 'version' );

      $Obj->requires      = $requires;

      $Obj->author        = '<a href="http://www.meomundo.com/" target="_blank">meomundo.com</a>';

      $Obj->last_updated  = date( 'Y-m-d', $this->getUpdate( 'releaseDate' ) );

      $Obj->download_link = $this->getUpdate( 'file' );

      if( $changelog !== false ) {
        
        $Obj->sections = array(
          'changelog' => $this->generateChangelogHTML( (array) $changelog )
        );
      
      }

      return $Obj;

    }

    return false;

  }
  
  /**
   * Generate an unordered HTML list from the changelog array.
   *
   * @param   array   $changelog    A one dimensional array where each element is a changelog entry.
   * @return  string                An unorderd HTML list of the changelog information.
   */
  protected function generateChangelogHTML( $changelog ) {
    
    $changelog = (array) $changelog;

    $html = '<ul>';

    foreach( $changelog as $item ) {

      $html .= '<li>' . $item . '</li>';

    }

    $html .= '</ul>';
    
    return $html;

  }
      
}
?>