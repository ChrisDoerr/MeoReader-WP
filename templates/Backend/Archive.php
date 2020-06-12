<?php

$currentPage    = $data['pageNr'];

?>

  <div id="MeoReader_Reader">

<?php
/**
 * List of nonces.
 */
foreach( $data['nonces'] as $nonce ) {
  
  echo '   <input type="hidden" name="' . $nonce['action'] . '" value="' . $nonce['nonce'] . '" id="' . $nonce['action'] . '_nonce" class="meoNonce" />' . "\n";

}
?>



   <div id="MeoReader_Reader_TopBar">
  
    <div id="MeoReader_Reader_MastNav">
     <ul>
      <li class="tab_search">
       <form id="MeoReader_Reader_SearchForm" action="admin.php?page=meoreader_search" method="post">
        <input type="hidden" name="meoReader_pageNr" value="<?php echo $data['pageNr']; ?>" />
        <input type="text" name="meoReader_query" id="meoReader_query" value="" />
        <input type="submit" value="<?php echo _x( 'Search', 'button label', 'meoreader' ); ?>" />
       </form>
      </li>
     </ul>
    </div>

    <div class="clearAll">&nbsp;</div>
   
   </div>


   <div id="MeoReader_Reader_List" class="archive">
<?php
if( false !== $data['entries'] && count( $data['entries'] ) > 0 ) {
  
  $entries = '';
  
  foreach( $data['entries'] as $entry ) {

    /**
     * Content sanatization
     */
    // HTMLPurifier: Better but slower
    if(
         isset( $data['options']['purify'] )
      && $data['options']['purify'] === true
      && isset( $entry['description_prep'] )
      && !empty( $entry['description_prep'] )
    ) {
      $content = $entry['description_prep'];
    }
    // htmLawed: Not as safe but faster
    else {

      $content = $entry['description'];

    }
    
    /**
     * Title sanatization
     * htmLawed is good enough here since basically nothing is allowed inside the title.
     */
    $title = MeoReader_Core::htmLawed_title( $entry['title'] );
    

    $iframeFree   = preg_replace( '#<iframe#im', '<div class="meoReader_placeholderWrap"><p>Show External Content</p><div class="meoReader_placeholder"><!--[MeoReader]<iframe', $content );

    $iframeFree   = preg_replace( '#</iframe>#im', '</iframe>[/MeoReader]--></div></div>', $iframeFree );

    $enclosures   = unserialize( $entry['enclosures'] );

    $media        = '';

    if( $enclosures !== false ) {

      $media = '<ul class="MeoReader_Reader_media">' . "\n";

      foreach( $enclosures as $enclosure ) {
  
        $typeClass = ( preg_match( '#([^/]+)/#i', $enclosure['type'], $match ) ) ? $match[1] : 'default';
  
        $media .= ' <li class="' . $typeClass . '"><a href="' . $enclosure['url'] . '" title="' . $enclosure['url'] . '">' . $enclosure['filename']. '</a>';
        
        if( isset( $data['options']['audioplayer'] ) && $data['options']['audioplayer'] === true && strtolower( $typeClass ) === 'audio' ) {
          
          $media .= '</li><li><audio src="' . $enclosure['url'] . '" preload="none" />';
        }
        
        $media .= "</li>\n";
  
      }

      $media .= "</ul>\n\n";

    }
  
    $entries .= '    <div class="MeoReader_Reader_item ' . $entry['status'] . '" data-entryID="' . $entry['id'] . '">' . "\n";
    $entries .= '     <table class="MeoReader_Reader_item_listView">' . "\n";
    $entries .= '      <tr>';
    $entries .= '       <td class="MeoReader_Reader_action"><a href="admin.php?page=meoreader_toggleArchive&amp;itemID=' . $entry['id'] . '&amp;pageNr=' . $data['pageNr'] . '&amp;ref=archive&amp;meoNonce=' . wp_create_nonce( 'meoReader_toggleArchiveItem' ) . '" data-entryID="' . $entry['id'] . '" class="action archive true" title="Remove from Archive">A</a></td>' . "\n";
    $entries .= '       <td class="MeoReader_Reader_feedName">';

    $entries .= '<img src="' . $data['meta']['url'] . 'favicons.php?url=' . urlencode( $entry['feed_html_url'] ) . '" alt="" class="favicon" /> ';
    $entries .= $Template->shortenText( $entry['feed_name'], 30 ) . '</td>' . "\n";

    $entries .= '       <td class="MeoReader_Reader_entryTitle"><a href="admin.php?page=meoreader_viewEntry&amp;itemID=' . $entry['id'] . '&amp;pageNr=' . $data['pageNr'] . '&amp;ref=archive">' . $title . '</a></td>' . "\n";
    $entries .= '       <td class="MeoReader_Reader_entryDateTime">' . $Template->shortenDate( $entry['pub_date'] ) . '</td>' . "\n";
    $entries .= '      </tr>';
    $entries .= '     </table><!-- end .MeoReader_Reader_item_listView -->' . "\n";
    $entries .= '    </div><!-- end .MeoReader_Reader_item -->' . "\n";
    
    $entries .= '    <div class="MeoReader_Reader_content" data-entryID="' . $entry['id']  . '" id="entryID' . $entry['id'] . '">' . "\n";

    /* Don't show empty links! */
    if( isset( $entry['link'] ) && !empty( $entry['link'] ) ) {
      
      $entries .= '     <h3><a href="' . $entry['link'] . '">' . $title . '</a></h3>' . "\n";
    
    }
    else {
      $entries .= '     <h3>' . $title . "</h3>\n";
    }

    /* Don't show empty links! */
    if( isset( $entry['feed_html_url'] ) && !empty( $entry['feed_html_url'] ) ) {

      $entries .= '     <h4>by <a href="' . $entry['feed_html_url'] . '">' . $entry['feed_name'] . '</a></h4>' . "\n";

    }
    else {

      $entries .= '     <h4>by ' . $entry['feed_name'] . "</h4>\n";

    }

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

    $entries .= '      <a href="http://twitter.com/share?text=' . urlencode( $title ) . '&url=' . urlencode( $entry['link'] ) . $via . '" class="button-secondary meoReader_shareEntryOnTwitter"><span>t</span> ' . _x( 'Share this on Twitter', 'entry toolbar', 'meoreader' ) . "</a>\n";
    $entries .= '      <a href="http://www.facebook.com/sharer.php?s=100&amp;p[url]=' . urlencode( $entry['link'] ) . '&amp;p[title]=' . urlencode( $title ) . '" class="button-secondary meoReader_shareEntryOnFacebook"><span>fb</span> ' . _x( 'Share this on Facebook', 'entry toolbar', 'meoreader' ) . "</a>\n";
    $entries .= '     </div>' . "\n";
    
    $entries .= '    </div>' . "\n";
    $entries .= "\n\n";
    
    $iframeFree = '';
    
    $media      = '';
  
  }
  
  echo $entries;
  
}
else {

  echo '<p><i>' . _x( 'No entries', 'error message', 'meoreader' ) . "</i></p>\n";

}
?>
   </div>
   
<?php
/**
 * Prev/Next Navigation
 */
if( $data['totalPages'] > 1 ) {

  echo '<div class="prevNext">' . "\n";

  if( $currentPage > 1 ) {

    echo ' <div class="next">&#171; <a href="admin.php?page=meoreader_archive&amp;pageNr=' . ( $currentPage -1 ) . '">' . _x( 'Newer Entries', 'prevnext', 'meoreader' ) . "</a></div>\n";

  }

  if( $currentPage < $data['totalPages'] ) {

    echo ' <div class="prev"><a href="admin.php?page=meoreader_archive&amp;pageNr=' . ( $currentPage +1 ) . '">' . _x( 'Older Entries', 'prevnext', 'meoreader' ) . "</a> &#187;</div>\n";

  }
  
  echo ' <div class="clearAll">&nbsp;</div>' . "\n";
  echo "</div>\n";
  
}
?>   
   
  </div>
