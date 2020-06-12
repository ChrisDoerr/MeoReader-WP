<?php
$backlink = '<p><a href="admin.php?page=meoreader_index&amp;pageNr="' . $data['pageNr'] . '" class="button-secondary">' . _x( 'Back to the Index', 'backlink', 'meoreader' ) . "</a></p>\n";

$hits = '<p>' . $data['total'] . ' ';

if( $data['total'] == 1 ) {

  $hits .= _x( 'hit', 'search', 'meoreader' );

}
else {
  
  $hits .= _x( 'hits', 'search', 'meoreader' );
 
}

$hits .= ' ' . _x( 'for', 'search', 'meoreader' ) . ': <span class="results">"' . $data['query'] . '"</span></p>' . "\n";



echo '  <div id="MeoReader_Reader" class="meoReader_searchResults">' . "\n";

echo $hits;
 
echo $backlink;

echo '   <div id="MeoReader_Reader_List">' . "\n";

if( false !== $data['entries'] && count( $data['entries'] ) > 0 ) {
  
  $entries = '';
  
  foreach( $data['entries'] as $entry ) {
    
    $iframeFree = preg_replace( '#<iframe#im', '<div class="meoReader_placeholderWrap"><p>Show External Content</p><div class="meoReader_placeholder"><!--[MeoReader]<iframe', $entry['description'] );
    $iframeFree = preg_replace( '#</iframe>#im', '</iframe>[/MeoReader]--></div></div>', $iframeFree );

    $enclosures = unserialize( $entry['enclosures'] );

    $media      = '';

    if( $enclosures !== false ) {

      $media = '<ul class="MeoReader_Reader_media">' . "\n";

      foreach( $enclosures as $enclosure ) {
  
        $typeClass = ( preg_match( '#([^/]+)/#i', $enclosure['type'], $match ) ) ? $match[1] : 'default';
  
        $media .= ' <li class="' . $typeClass . '"><a href="' . $enclosure['url'] . '">' . $enclosure['filename']. '</a></li>' . "\n";
  
      }

      $media .= "</ul>\n\n";

    }
    
    $viewEntryLink = '<a href="admin.php?page=meoreader_viewEntry&amp;itemID=' . $entry['id'] . '">' . $entry['title'] . '</a>';
  
    $entries .= '    <div class="MeoReader_Reader_item ' . $entry['status'] . '" data-entryID="' . $entry['id'] . '">' . "\n";
    $entries .= '     <table class="MeoReader_Reader_item_listView">' . "\n";
    $entries .= '      <tr>';
    $entries .= '       <td class="MeoReader_Reader_action"><a href="javascript:void(0);" class="action archive false">A</a></td>' . "\n";
    $entries .= '       <td class="MeoReader_Reader_feedName">' . $Template->shortenText( $entry['feed_name'], 20 ) . '</td>' . "\n";
    $entries .= '       <td class="MeoReader_Reader_entryTitle">' . $viewEntryLink . '</td>' . "\n";
    $entries .= '       <td class="MeoReader_Reader_entryDateTime">' . $Template->shortenDate( $entry['pub_date'] ) . '</td>' . "\n";
    $entries .= '      </tr>';
    $entries .= '     </table><!-- end .MeoReader_Reader_item_listView -->' . "\n";
    $entries .= '    </div><!-- end .MeoReader_Reader_item -->' . "\n";
    
    $entries .= '    <div class="MeoReader_Reader_content" data-entryID="' . $entry['id']  . '" id="entryID' . $entry['id'] . '">' . "\n";
    $entries .= '     <h3><a href="' . $entry['link'] . '">' . $entry['title'] . '</a></h3>' . "\n";
    $entries .= '     <h4>by <a href="' . $entry['feed_html_url'] . '">' . $entry['feed_name'] . '</a></h4>' . "\n";
    $entries .= $iframeFree . "\n";
    $entries .= $media . "\n";

    /* Entry Toolbar */
    $via = ( isset( $data['options']['twitter'] ) && $data['options']['twitter'] !== '' ) ? '&amp;via=' . urlencode( $data['options']['twitter'] ) : '';

    $entries .= '     <div class="meoReader_entryToolbar" data-entryID="' . $entry['id'] . '">';

    $isAdmin              = MeoReader_Core::current_user_can( 'admin' );
    
    $singleUserCanPublish =  ( isset( $data['options']['userCanPublish'] ) && $data['options']['userCanPublish'] === true ) ? true : false;

    if( $isAdmin === true || $singleUserCanPublish === true ) {
      
      $entries .= '      <a href="javascript:void(0);" class="button-secondary meoReader_createPostFromEntry"><span>&nbsp;</span> ' . _x( 'Create Post From Entry', 'entry toolbar', 'meoreader' ) . "</a>\n";
    
    }

    $entries .= '      <a href="http://twitter.com/share?text=' . urlencode( $entry['title'] ) . '&url=' . urlencode( $entry['link'] ) . $via . '" class="button-secondary meoReader_shareEntryOnTwitter"><span>t</span> ' . _x( 'Share this on Twitter', 'entry toolbar', 'meoreader' ) . "</a>\n";
    $entries .= '      <a href="http://www.facebook.com/sharer.php?s=100&amp;p[url]=' . urlencode( $entry['link'] ) . '&amp;p[title]=' . urlencode( $entry['title'] ) . '" class="button-secondary meoReader_shareEntryOnFacebook"><span>fb</span> ' . _x( 'Share this on Facebook', 'entry toolbar', 'meoreader' ) . "</a>\n";
    $entries .= '     </div>' . "\n";

    $entries .= '    </div>' . "\n";
    $entries .= "\n\n";
    
    $iframeFree = '';
  
  }
  
  echo $entries;
  
  echo $backlink;

}
else {

  echo '<p><i>' . _x( 'No hits', 'error message', 'meoreader' ) . "</i></p>\n";

}
?>
   </div>


  </div>
