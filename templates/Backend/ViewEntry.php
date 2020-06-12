<?php
if( isset( $data['ref'] ) && $data['ref'] == 'archive' ) {

  $backlink  = '<p><a href="admin.php?page=meoreader_archive&amp;pageNr=' . $data['pageNr'] . '" class="button-secondary">&#171; ' . _x( 'Back to the Archive', 'backlink', 'meoreader' ) . '</a>';
  $backlink .= ' &emsp; <a href="admin.php?page=meoreader_toggleArchive&amp;itemID=' . $data['entry']['id'] . '&amp;pageNr=' . $data['pageNr'] . '&amp;ref=archive" class="button-secondary">' . _x( 'Remove from Archive', 'tab', 'meoreader' ) . "</a></p>\n";

}
else {

  $backlink = '<p><a href="admin.php?page=meoreader_index&amp;pageNr=' . $data['pageNr'] . '" class="button-secondary">&#171; ' . _x( 'Back to the Index', 'backlink', 'meoreader' ) . '</a>';
  $backlink .= ' &emsp; <a href="admin.php?page=meoreader_toggleArchive&amp;itemID=' . $data['entry']['id'] . '&amp;pageNr=' . $data['pageNr'] . '&amp;ref=index&amp;meoNonce=' . wp_create_nonce( 'meoReader_toggleArchiveItem' ) . '" class="button-secondary">' . _x( 'Add to Archive', 'tab', 'meoreader' ) . "</a></p>\n";

}

if( $data['request'] === false ) {

  echo '<p><i>' . _x( 'Cannot find entry!', 'error message', 'meoreader' ) . "</i></p>\n";

  echo $backlink;

  return;

}
?>

  <div id="MeoReader_Reader">

   <?php echo $backlink; ?>

   <div id="MeoReader_Reader_viewEntry">

<?php

      /* When -for whatever reason- there is no URL don't create an "empty" link but a js blank instead */
$entryLink    = ( isset( $data['entry']['link'] ) && !empty( $data['entry']['link'] ) )                   ? $data['entry']['link']          : 'javascript:void(0);';

$feedHtmlURL  = ( isset( $data['entry']['feed_html_url'] ) && !empty( $data['entry']['feed_html_url'] ) ) ? $data['entry']['feed_html_url'] : 'javascript:void(0);';
    
$entry        = '';

$entry .= '    <div class="MeoReader_Reader_contentView">' . "\n";

/* Don't show empty links! */
if( isset( $data['entry']['link'] ) && !empty( $data['entry']['link'] ) ) {

  $entry .= '     <h3><a href="' . $data['entry']['link'] . '">' . $data['entry']['title'] . '</a></h3>' . "\n";
    
}
else {

  $entry .= '     <h3>' . $data['entry']['title'] . "</h3>\n";

}


/* Don't show empty links! */
if( isset( $data['entry']['feed_html_url'] ) && !empty( $data['entry']['feed_html_url'] ) ) {

  $entry .= '     <h4>by <a href="' . $data['entry']['feed_html_url'] . '">' . $data['entry']['feed_name'] . '</a></h4>' . "\n";

}
else {

  $entry .= '     <h4>by ' . $data['entry']['feed_name'] . "</h4>\n";

}

$entry .= $data['entry']['description'] . "\n";
$entry .= '    </div>' . "\n";
$entry .= "\n\n";

$enclosures = unserialize( $data['entry']['enclosures'] );

$media      = '';

if( $enclosures !== false ) {

  $media = '<ul class="MeoReader_Reader_media">' . "\n";

  foreach( $enclosures as $enclosure ) {
  
    $typeClass = ( preg_match( '#([^/]+)/#i', $enclosure['type'], $match ) ) ? $match[1] : 'default';
  
    $media .= ' <li class="' . $typeClass . '"><a href="' . $enclosure['url'] . '">' . $enclosure['filename'] . '</a></li>' . "\n";
  
  }

  $media .= "</ul>\n\n";

}

echo $entry;

echo $media;

/* Entry Toolbar */
$via = ( isset( $data['options']['twitter'] ) && $data['options']['twitter'] !== '' ) ? '&amp;via=' . urlencode( $data['options']['twitter'] ) : '';

echo '     <div class="meoReader_entryToolbar" data-entryID="' . $data['entry']['id'] . '">';

$isAdmin              = MeoReader_Core::current_user_can( 'admin' );
    
$singleUserCanPublish =  ( isset( $data['options']['userCanPublish'] ) && $data['options']['userCanPublish'] === true ) ? true : false;

if( $isAdmin === true || $singleUserCanPublish === true ) {
      
  echo '      <a href="http://webtest/dev/wordpress/meoreader/wp-admin/admin.php?page=meoreader_createPostFromEntry&amp;entryID=' . $data['entry']['id'] . '" class="button-secondary meoReader_createPostFromEntry"><span>&nbsp;</span> ' . _x( 'Create Post From Entry', 'entry toolbar', 'meoreader' ) . "</a>\n";

}

echo '      <a href="http://twitter.com/share?text=' . urlencode( $data['entry']['title'] ) . '&url=' . urlencode( $data['entry']['link'] ) . $via . '" class="button-secondary meoReader_shareEntryOnTwitter"><span>t</span> ' . _x( 'Share this on Twitter', 'entry toolbar', 'meoreader' ) . "</a>\n";
echo '      <a href="http://www.facebook.com/sharer.php?s=100&amp;p[url]=' . urlencode( $data['entry']['link'] ) . '&amp;p[title]=' . urlencode( $data['entry']['title'] ) . '" class="button-secondary meoReader_shareEntryOnFacebook"><span>fb</span> ' . _x( 'Share this on Facebook', 'entry toolbar', 'meoreader' ) . "</a>\n";
echo '     </div>' . "\n";


?>
   </div>

   <?php echo $backlink; ?>
   
  </div>
