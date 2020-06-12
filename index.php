<?php
/*
Plugin Name:  meoReader
Description:  The meoReader is an RSS reader that allows you to subcribe to RSS feeds, read and manage them through your WordPress backend and even lets you import and export your profiles.
Author:       Chris Doerr
Version:      1.3.0
Author URI:   http://www.meomundo.com/
*/
global $wpdb;


if( !defined( 'MEOREADER_PATH' ) ) {
  
  define( 'MEOREADER_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
  
}

include_once MEOREADER_PATH . 'config.php';

if( !class_exists( 'Meomundo' ) ) {
  
  require_once MEOREADER_PATH . 'lib' . DIRECTORY_SEPARATOR . 'Meomundo.php';
  
}

if( !class_exists( 'Meomundo_WP' ) ) {

  require_once MEOREADER_PATH . 'lib' . DIRECTORY_SEPARATOR . 'Meomundo' . DIRECTORY_SEPARATOR . 'WP.php';

}

require_once MEOREADER_PATH . 'lib' . DIRECTORY_SEPARATOR . 'MeoReader.php';

require_once MEOREADER_PATH . 'lib' . DIRECTORY_SEPARATOR . 'MeoReader' . DIRECTORY_SEPARATOR . 'Core.php';

$MeoReader    = new MeoReader( MEOREADER_PATH, MEOREADER_URL, MEOREADER_SLUG, $wpdb );

load_plugin_textdomain( 'meoreader', false,  dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

unset( $meoTemp );
?>