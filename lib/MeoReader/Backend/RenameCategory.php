<?php
/**
 * WordPress backend ghost page: Rename a Category
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_RenameCategory extends Meomundo_WP {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var object MeoReader_Category object.
   */
  protected $CatAPI;
  
  /**
   * @var object MeoReader_Templates object.
   */
  protected $Templates;
  
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
    
    $this->DB       = $wpdb;
    
    $this->loadClass( 'MeoReader_Templates' );

    $this->loadClass( 'MeoReader_Categories' );

    $this->Templates  = new MeoReader_Templates( $absolutePluginPath, $pluginURL, $pluginSlug );

    $this->CatAPI     = new MeoReader_Categories( $wpdb, $pluginSlug );
    
  }
  
  /**
   * The CONTROLLER.
   *
   * @return string HTML page code.
   */
  public function controller() {
    
    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }

    /**
     * Renaming is based on a categories ID so make sure it's been passed properly.
     * In this Javascript-free fallback the parameter needs to be passed via GET.
     */
    $catID      = ( isset( $_GET['itemID'] ) ) ? (int) $_GET['itemID'] : null;
    
    // Since this parameter is required only show a back link to the subscription page if it is missing.
    if( null == $catID || $catID < 1 ) {
      
      $html .= '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";
      
      return $html;
      
    }
    
    /* The "old" category name. */
    $catName 	  = $this->CatAPI->getCategoryByID( $catID );
    
    /* The (potentially) "new" category name. */
    $newCatName = isset( $_POST['meoReaderForm_RenameCategory_newCategoryName'] ) ? trim( strip_tags( $_POST['meoReaderForm_RenameCategory_newCategoryName'] ) ) : '';

    $html       = "";

    /* The form 'Rename Category' has been sent. */
    if( isset( $_POST['meoReaderForm_renameCategory_status'] ) && $_POST['meoReaderForm_renameCategory_status'] == 1 ) {
      
      $nonce = isset( $_POST['meoNonce'] ) ? $_POST['meoNonce'] : '';
      
      if( !wp_verify_nonce( $nonce, 'meoReader_renameCategory' ) ) {

        return '<p class="message error">' . _x( "You're not allowed to do that!", 'error message', 'meoreader' ) . '</p>';

      }
      
      /* Renaming the category */
      $renameCatStatus = $this->renameCategory( $catID, $newCatName );

      if( $renameCatStatus['valid'] == false ) {
        
        if( isset( $renameCatStatus['message'] ) ) {
          
          $html .= '<p class="message error">' . $renameCatStatus['message'] . '</p>';
        
          $html .= '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";
          
          return $html;

        }

      }
      else {

        $html .= '<p class="message success">' . $renameCatStatus['message'] . '</p>';
        
        $html .= '<p class="back">&#171; <a href="admin.php?page=' . $this->slug . '_subscriptions">' . _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ) . "</a></p>\n";
        
        return $html;
        
      }
      
    }
    
    /* Generate the (HTML) VIEW for this action. */
    $html .= $this->Templates->view(
      'Backend_RenameCategory',
      array(
        'catID'   => $catID,
        'oldName' => $catName,
        'newName' => $newCatName
      )
    );
    
    return $html;
    
  }
  
  /**
   * Handle the actual renaming by calling the Category API and
   * preparing the results for a proper evaluation.
   *
   * @param   int     $catID        ID of the category to be renamed.
   * @param   string  $newCatName   New name of the category.
   * @return  array                 An array containing information about the validity of the operation (and in case of an error, a message).
   */
  public function renameCategory( $catID, $newCatName ) {
    
    $nonce = isset( $_POST['meoNonce'] ) ? $_POST['meoNonce'] : '';
    
    if( !wp_verify_nonce( $nonce, 'meoReader_renameCategory' ) ) {

      return array(
        'valid'       => false,
        'message'     => _x( "You're not allowed to do that!", 'error message', 'meoreader' )
      );

    }
    
    if( $this->CatAPI->renameCategory( $catID, $newCatName ) === false ) {
      
      return array(
        'valid'       => false,
        'message'     => _x( 'The category could not be renamed!', 'error message', 'meoreader' ),
        'catID'       => (int) $catID,
        'newCatName'  => trim( strip_tags( $newCatName ) )
      );
      
    }
    else {
      
      return array(
        'valid'   => true,
        'message' => _x( 'The category has been renamed.', 'error message', 'meoreader' )
      );
      
    }
    
  }
  
}
?>