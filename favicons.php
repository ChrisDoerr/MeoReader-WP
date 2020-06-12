<?php
/**
 * This script shall be used as image source (/favicons.php?url=...)
 *
 * It then checks if there already is a favicon for that URL.
 * If so the image will be patched through.
 * If not, a Google service is being used to get the favicon and download it to your local web space - and then patch it trough.
 *
 * In both cases calling this script returns a binary images file (also with the proper image header being sent)!
 *
 */
$pluginPath   = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

/* The GET parameter 'url' is required! */
$url          = ( isset( $_GET['url'] ) && trim( $_GET['url'] ) !== '' ) ? trim( strip_tags( urldecode( $_GET['url'] ) ) ) : '';

/* For all I know all favicons coming from the Google service will have the PNG format. */
$faviconFile  = md5( strtolower( $url ) ) . '.png';

/* In case there is no favicon or some error had occured use a default icon instead. */
$iconFile     = $pluginPath . 'images/defaultFavicon.png';

/* The MeoReader_Favicons class will handle the Google service and local file storage */
include_once $pluginPath . 'lib' . DIRECTORY_SEPARATOR . 'MeoReader' . DIRECTORY_SEPARATOR . 'Favicons.php';

$Favicons     = new MeoReader_Favicons( $pluginPath, 'http://example.com' ); // The URL is not required since the image files are being passed through

if( file_exists( $pluginPath . 'favicons' . DIRECTORY_SEPARATOR . $faviconFile ) ) {

  $iconFile = $pluginPath . 'favicons' . DIRECTORY_SEPARATOR . $faviconFile;

}
else {

  $status = $Favicons->downloadFavicon( $url );

  if( $status !== false ) {
	
    $iconFile = $pluginPath . 'favicons' . DIRECTORY_SEPARATOR . $faviconFile;
      
  }

}

/* Read the local favicon file and patch it through */
$fp = fopen( $iconFile, 'rb' );

header( "Content-Type: image/png" );
header( "Content-Length: " . filesize( $iconFile ) );

fpassthru( $fp) ;

exit;
?>