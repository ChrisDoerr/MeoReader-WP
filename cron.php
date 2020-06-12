<?php
/**
 * Stand-alone "Feed Updates"
 * to be used via cronjob.
 *
 * !! Take a look into the plugin settings and the documentation for
 *    how to implement the cronjob PROPERLY !!!
 */

/**
 * Bootstrap WordPress CMS functionality
 */
$root = realpath( dirname( __FILE__ ) . '/../../../' );

$root = str_replace( '/', DIRECTORY_SEPARATOR, $root ) . DIRECTORY_SEPARATOR;

include_once $root . 'wp-load.php';


/**
 * Deny direct access to this file.
 * Only run the job when the proper token has been passed!
 *
 * @todo update: add token option
 *
 * DEMO URL:  http://webtest/dev/wordpress/mobile/wp-content/plugins/meoReader/cron.php?token=d4baeb273bb292e0ec47d2be48d42c3a
 */
$cronToken = get_option( 'meoreader_crontoken' );

if( !isset( $_GET['token'] ) || ( $_GET['token'] !== $cronToken ) ) {

  die( 'Access Denied!' );

}

$now = current_time( 'mysql' );


/**
 * Bootstrapping the application
 */
global $wpdb;

$tmp        = array(
  'path'  => dirname( __FILE__ ) . DIRECTORY_SEPARATOR,
  'url'   => plugins_url() . '/Ohrinsel-PodManager/',
  'slug'  => 'meoreader'
);


if( !class_exists( 'Meomundo' ) ) {
  require_once $tmp['path'] . 'lib' . DIRECTORY_SEPARATOR . 'Meomundo.php';
}
if( !class_exists( 'Meomundo_WP' ) ) {
  require_once $tmp['path'] . 'lib' . DIRECTORY_SEPARATOR . 'Meomundo' . DIRECTORY_SEPARATOR . 'WP.php';
}

/* Meomundo WordPress object: Will be used to load classes and/or interfaces */
$Meomundo_WP    = new Meomundo_WP( $tmp['path'], $tmp['url'], $tmp['slug'] );


$Meomundo_WP->loadClass( 'MeoReader_Sessions' );
$SessionAPI     = new MeoReader_Sessions( $wpdb );


$Meomundo_WP->loadClass( 'MeoReader_Categories' );
$CatAPI = new MeoReader_Categories( $wpdb, $tmp['slug'] );

$Meomundo_WP->loadClass( 'MeoReader_Feeds' );
$FeedAPI = new MeoReader_Feeds( $wpdb, $CatAPI, $tmp['slug'] );



$currentSession = $SessionAPI->getCurrentSession();



$maxRuntime   = time() + ( (int) ini_get('max_execution_time') ) -10; // 10 seconds buffer before the script stops!

while( time() < $maxRuntime ) {

  // @todo error handling $currentSession['request'] true/false
  $nextItem       = $SessionAPI->getNextItem( $currentSession['session_id'] );

  if( null === $nextItem || empty( $nextItem ) ) {
  
    $SessionAPI->closeSession( $currentSession['session_id'] );
  
    die( 'SESSION CLOSED' );
  
    exit;
  
  }
  else {
  
		$tmp    = $FeedAPI->updateFeed( $nextItem['id'] );

    $status = $SessionAPI->updateFeed( $nextItem['id'], $currentSession['session_id'] );

  }
  
}

header( 'Location: ' . $tmp['url'] . basename( __FILE__ ) . '?token=' . $cronToken );

exit;

?>