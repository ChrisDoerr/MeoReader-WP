<?php
MeoReader_Core::setLandmark( __FILE__, __LINE__ );

$currentPage    = $data['pageNr'];

$unreadItems    = $data['total']['unread']['category'] . ' / ' . $data['total']['unread']['all'];

if( isset( $data['options']['timerange'] ) && $data['options']['timerange'] === 'today' ) {

  $todayAlltime = _x( 'All Time', 'tab title', 'meoreader' );
  
  $todayAlltimeClass= 'today';

}
else {

  $todayAlltime = _x( 'Today', 'tab title', 'meoreader' );

  $todayAlltimeClass= '';

}


/**
 * Tab_Cats
 */
if( count( $data['categories'] ) > 0 ) {
  
  $tab_cats = "       <ul>\n";
  
  $cats   = array();
  
  /* First item is always the "current category" */
  $cats[] = $data['currentCategory'];

  /* If the current category is not "All Categories" add this "none-category", always as second item in the list! */
  if( $data['currentCategory']['id'] !== 0 ) {
    
    $cats[] = array(
      'id'    => 0,
      'name'  => _x( 'All Categories', 'tab', 'meoreader' )
    );
    
  }

  /* Now the rest of the categories to the list. */
  foreach( $data['categories'] as $cat ) {
    
    if( $cat['id'] !== $data['currentCategory']['id'] ) {
      
      $cats[] = $cat;
      
    }
    
  }

  /* Sort the categories in an alphabetical order */
  usort( $cats, create_function( '$a,$b','if( $a["name"] == $b["name"] ) { return 0; } return ( $a["name"] < $b["name"] ) ? -1 : 1;' ) );

  /* Only now you can build the HTML list properly */
  foreach( $cats as $cat ) {

    /* No need to show the current item twice! */
    if( $cat['id'] !== $data['currentCategory']['id'] ) {

      $tab_cats .= '        <li><a href="admin.php?page=meoreader_index&amp;action=viewCategory&amp;catID=' . $cat['id'] . '" data-cid="' . $cat['id'] . '">' . $cat['name'] . '</a></li>' . "\n";

    }

  }
  
  $tab_cats .= "       </ul>\n";

}

MeoReader_Core::setLandmark( __FILE__, __LINE__ );

?>

  <div id="MeoReader_Reader">

   <div id="MeoReader_Reader_TopBar">
  
    <div id="MeoReader_Reader_MastNav">
     <ul>
      <li class="tab_reload first"><span class="icon">&nbsp;</span><a href="admin.php?page=meoreader_reloadFeeds&amp;meoNonce=<?php echo wp_create_nonce( 'meoReader_updateFeed' ); ?>" title="<?php echo _x( 'Check for new entries', 'tab title', 'meoreader' ); ?>"><?php echo _x( 'Refresh', 'tab', 'meoreader' ); ?></a></li>
      <li class="tab_category"><a href="admin.php?page=meoreader_index&amp;action=viewCategory&amp;catID=<?php echo $data['currentCategory']['id']; ?>" data-cid="<?php echo $data['currentCategory']['id']; ?>"><?php echo ( $data['currentCategory']['name'] !== false ) ? $data['currentCategory']['name'] : _x( 'All Categories', 'tab title', 'meoreader' ); ?></a>
<?php
echo $tab_cats;
?>
      </li>
      <li class="tab_unread"><a href="javascript:void(0);" class="none" title="<?php echo _x( 'Unread Entries (Current Category / All)', 'tab title', 'meoreader' ); ?>">(<?php echo $unreadItems; ?>)</a></li>
<?php
/* Show the "Mark All As Read" button even when there are no unread items any more. */
echo '      <li class="tab_markAsRead"><a href="admin.php?page=meoreader_markAllAsRead&amp;pageNr=' . $data['currentPage'] . '&amp;meoNonce=' . wp_create_nonce( 'meoReader_markAllAsRead' )  . '">' . _x( 'Mark All as Read', 'tab', 'meoreader' ) . "</a></li>\n";
?>

      <li class="tab_today"><a href="javascript:void(0);" class="<?php echo $todayAlltimeClass; ?>" data-labelToday="<?php echo _x( 'Today', 'tab title', 'meoreader' ); ?>" data-labelAlltime="<?php echo _x( 'All Time', 'tab title', 'meoreader' ); ?>"><?php echo $todayAlltime; ?></a></li>

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

<?php
/**
 * List of nonces.
 */
foreach( $data['nonces'] as $nonce ) {
  
  echo '   <input type="hidden" name="' . $nonce['action'] . '" value="' . $nonce['nonce'] . '" id="' . $nonce['action'] . '_nonce" class="meoNonce" />' . "\n";

}
?>

   <div id="MeoReader_Reader_List" data-pagenr="<?php echo $data['pageNr']; ?>">
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


    /* When -for whatever reason- there is no URL don't create an "empty" link but a js blank instead */
    $iframeFree = preg_replace( '#<iframe#im', '<div class="meoReader_placeholderWrap"><p>Show External Content</p><div class="meoReader_placeholder"><!--[MeoReader]<iframe', $content );

    $iframeFree = preg_replace( '#</iframe>#im', '</iframe>[/MeoReader]--></div></div>', $iframeFree );

    $enclosures = unserialize( $entry['enclosures'] );

    $media      = '';

    if( $enclosures !== false ) {

      $media = '<ul class="MeoReader_Reader_media">' . "\n";

      foreach( $enclosures as $enclosure ) {
  
        $typeClass = ( preg_match( '#([^/]+)/#i', $enclosure['type'], $match ) ) ? $match[1] : 'default';
  
        $media .= ' <li class="' . $typeClass . '"><a href="' . $enclosure['url'] . '" title="' . $enclosure['url'] . '">' . $enclosure['filename']. '</a>';
        
        if( isset( $data['options']['audioplayer'] ) && $data['options']['audioplayer'] === true && strtolower( $typeClass ) === 'audio' ) {
          
          $media .= '</li><li><audio src="' . $enclosure['url'] . '" preload="none"></audio>';

        }
        
        $media .= "</li>\n";
  
      }

      $media .= "</ul>\n\n";

    }

    $entries .= '    <div class="MeoReader_Reader_item ' . $entry['status'] . '" data-entryID="' . $entry['id'] . '">' . "\n";
    $entries .= '     <table class="MeoReader_Reader_item_listView">' . "\n";
    $entries .= '      <tr>';

    $entries .= '       <td class="MeoReader_Reader_action"><a href="admin.php?page=meoreader_toggleArchive&amp;itemID=' . $entry['id'] . '&amp;pageNr=' . $data['pageNr'] . '&amp;meoNonce=' . wp_create_nonce( 'meoReader_toggleArchiveItem' ) . '" data-entryID="' . $entry['id'] . '" class="action archive false" title="' . _x( 'Add to Archive', 'tab title', 'meoreader' ) . '">A</a> ';
    $entries .= '<a href="admin.php?page=meoreader_toggleRead&amp;itemID=' . $entry['id'] . '&amp;pageNr=' . $data['pageNr'] . '&amp;meoNonce=' . wp_create_nonce( 'meoReader_toggleRead' ) . '" data-entryID="' . $entry['id'] . '" class="action ' . $entry['status'] . '" title="' . _x( 'Toggle Read/Unread Status', 'tab title', 'meoreader' ) . '">R</a></td>' . "\n";

    $entries .= '       <td class="MeoReader_Reader_feedName">';

    $entries .= '<img src="' . $data['meta']['url'] . 'favicons.php?url=' . urlencode( $entry['feed_html_url'] ) . '" alt="" class="favicon" /> ';
    $entries .= $Template->shortenText( $entry['feed_name'], 30 ) . '</td>' . "\n";

    $entries .= '       <td class="MeoReader_Reader_entryTitle"><a href="admin.php?page=meoreader_viewEntry&amp;itemID=' . $entry['id'] . '&amp;pageNr=' . $data['pageNr'] . '">' . $title . '</a></td>' . "\n";
    $entries .= '       <td class="MeoReader_Reader_entryDateTime">' . $Template->shortenDate( $entry['pub_date'] ) . '</td>' . "\n";
    $entries .= '      </tr>';
    $entries .= '      </table><!-- end .MeoReader_Reader_item_listView -->' . "\n";
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

      $entries .= '     <h4>' . _x( 'by', 'label', 'meoreader' ) . ' <a href="' . $entry['feed_html_url'] . '">' . $entry['feed_name'] . '</a></h4>' . "\n";

    }
    else {

      $entries .= '     <h4>' . _x( 'by', 'label', 'meoreader' ) . $entry['feed_name'] . "</h4>\n";

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

    $entries .= '      <a href="http://twitter.com/share?text=' . urlencode( $title ) . '&amp;url=' . urlencode( $entry['link'] ) . $via . '" class="button-secondary meoReader_shareEntryOnTwitter"><span>t</span> ' . _x( 'Share this on Twitter', 'entry toolbar', 'meoreader' ) . "</a>\n";
    $entries .= '      <a href="http://www.facebook.com/sharer.php?s=100&amp;p%5Burl%5D=' . urlencode( $entry['link'] ) . '&amp;p%5Btitle%5D=' . urlencode( $title ) . '" class="button-secondary meoReader_shareEntryOnFacebook"><span>fb</span> ' . _x( 'Share this on Facebook', 'entry toolbar', 'meoreader' ) . "</a>\n";
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
if( $data['total']['pages'] > 1 ) {

  echo '<div class="prevNext">' . "\n";

  if( $currentPage > 1 ) {

    echo ' <div class="next">&#171; <a href="admin.php?page=meoreader_index&amp;pageNr=' . ( $currentPage -1 ) . '">' . _x( 'Newer Entries', 'prevnext', 'meoreader' ) . "</a></div>\n";

  }

  if( $currentPage < $data['total']['pages'] ) {

    echo ' <div class="prev"><a href="admin.php?page=meoreader_index&amp;pageNr=' . ( $currentPage +1 ) . '">' . _x( 'Older Entries', 'prevnext', 'meoreader' ) . "</a> &#187;</div>\n";

  }
  
  echo ' <div class="clearAll">&nbsp;</div>' . "\n";
  echo "</div>\n";
  
}
?>

  </div>
