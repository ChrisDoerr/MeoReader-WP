<?php
/**
 * It is recommended to use a uninstall.php file for uninstalling the plugin. So here we go..
 */
global $wpdb;

if( !defined( 'MEOREADER_PATH' ) ) {
  
  define( 'MEOREADER_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
  
}

/* Load the plugin configuration data. */
include_once MEOREADER_PATH . 'config.php';

/* Load the Installer Class. */
require_once MEOREADER_PATH . 'lib' . DIRECTORY_SEPARATOR . 'MeoReader' . DIRECTORY_SEPARATOR . 'Installer.php';

/* Let the uninstaller class handle the actual cleaning up. */
$Installer = new MeoReader_Installer( MEOREADER_SLUG, $wpdb );

$Installer->uninstall()

?>