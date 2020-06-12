<?php
/**
 * MeoReader Constants:
 *
 *  MEOREADER_PATH
 *  MEOREADER_URL
 *  MEOREADER_SLUG
 *  MEOREADER_TBL_CATEGORIES
 *  MEOREADER_TBL_FEEDS
 *  MEOREADER_TBL_ENTRIES
 *  MEOREADER_LANG
 *  MEOREADER_DEVMODE
 *  MEOREADER_MIN
 *
 */

/**
 * URL to the plugin directory.
 */
define( 'MEOREADER_URL', plugins_url() . '/meoReader/' );

/**
 * Plugin handler.
 */
define( 'MEOREADER_SLUG', 'meoreader' );

/**
 * DB table name: categories
 */
define( 'MEOREADER_TBL_CATEGORIES', $wpdb->prefix . 'meoreader_categories' );

/**
 * DB table name: feeds
 */
define( 'MEOREADER_TBL_FEEDS', $wpdb->prefix . 'meoreader_feeds' );

/**
 * DB table name: entries
 */
define( 'MEOREADER_TBL_ENTRIES', $wpdb->prefix . 'meoreader_entries' );

/**
 * DB table name: sessions
 */
define( 'MEOREADER_TBL_SESSIONS', $wpdb->prefix . 'meoreader_sessions' );

/**
 * Dev Mode (true) or Live Mode (false)
 */
$tmp = get_option( 'meoreader' );
  
/* Until set otherwise DEVMODE is TRUE */
if( isset( $tmp['devmode'] ) && $tmp['devmode'] === false ) {
    
  define( 'MEOREADER_DEVMODE', false );
		
  /* Load minified styles and scripts when NOT in dev mode */
	define( 'MEOREADER_MIN', '.min' );

}
else {

  define( 'MEOREADER_DEVMODE', true );

  /* Load normal styles and scripts when IN dev mode */
  define( 'MEOREADER_MIN', '' );

}

unset( $tmp );


/**
 * Not all languages are supported by this plugin. In case the one this WordPress installtion
 * is running on is not being supported (yet), ENGLISH will be used instead!
 */
$meoTemp['supportedLanguages'] = array(
  'en'
);

$meoTemp['lang'] = ( preg_match( '#^([a-z]{2})-#i', get_bloginfo( 'language' ), $match ) ) ? strtolower( $match[1] ) : 'en';

$meoTemp['lang'] = ( in_array( $meoTemp['lang'], $meoTemp['supportedLanguages'] ) ) ? $meoTemp['lang']: 'en';

if( !defined( 'MEOREADER_LANG' ) ) {
  
  define( 'MEOREADER_LANG', $meoTemp['lang'] );
  
}

?>