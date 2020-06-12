<div class="wrapper">

 <form id="meoReaderForm_AddFeed" action="admin.php?page=<?php echo $Template->slug; ?>_addFeed" method="post" class="meoReader_subscription">
  <input type="hidden" name="meoReaderForm_AddFeed_status" value="1" />
  <input type="hidden" name="meoNonce" value="<?php echo wp_create_nonce( 'meoReader_addFeed' ); ?>" />
  <div>
   <label for="meoReaderForm_AddFeed_url"><?php echo _x( 'Feed URL', 'add feed', 'meoreader' ); ?>:</label>
   <input type="text" name="meoReaderForm_AddFeed_url" id="meoReaderForm_AddFeed_url" class="regular-text ltr" />
   <select name="meoReaderForm_AddFeed_category" id="meoReaderForm_AddFeed_category" size="1">
<?php
foreach( $data['subscriptions'] as $item ) {
  
  echo '    <option value="' . $item['id'] . '"';
  
  if( $item['id'] == 1 ) {
    
    echo ' selected="selected"';
    
  }
  
  echo '>' . $item['name'] . "</option>\n";
  
}
?>
   </select>
   <input type="submit" value="<?php echo _x( 'Add Feed', 'button label', 'meoreader' ); ?>" class="button-secondary" />
  </div>
 </form>

 <div id="meoReader_subscriptionTopBar">
  <ul>
   <li><a href="javascript:void(0);" id="meoReader_btn_deleteAllFeeds" class="button-secondary"><?php echo _x( 'Delete All Feeds', 'button label', 'meoreader' ); ?></a></li>
   <li><a href="javascript:void(0);" id="meoReader_btn_deleteAllCats" class="button-secondary"><?php echo _x( 'Delete All Categories', 'button label', 'meoreader' ); ?></a></li>
  </ul>
 </div>
 
 <div class="clearAll">&nbsp;</div>

</div><!-- end .wrapper -->

<?php
/**
 * List of nonces.
 */
foreach( $data['nonces'] as $nonce ) {
  
  echo '   <input type="hidden" name="' . $nonce['action'] . '" value="' . $nonce['nonce'] . '" id="' . $nonce['action'] . '_nonce" class="meoNonce" />' . "\n";

}
?>

<div id="meoReader_SubscriptionList">

<?php
foreach( $data['subscriptions'] as $item ) {
  
  echo '<h4 class="category" data-noi="' . $item['items'] . '" data-cid="' . $item['id'] . '" data-title="' . $item['name'] . '"><label>' . $item['name'] . ' (' . $item['items'] . ')</label><span>';
  
  // cat ID = 1 ("Unsorted") cannot be deleted but renamed!
  if( $item['id'] !== 1 ) {
    
    echo '<a href="admin.php?page=' . $Template->page . '&amp;action=deleteCategory&amp;itemID=' . $item['id'] . '&amp;meoNonce=' . wp_create_nonce( 'meoReader_deleteCategory' ) . '" class="delete" title="' . _x( 'Delete this category', 'tab title', 'meoreader' ) . '">' . _x( 'delete', 'tab', 'meoreader' ) . '</a> | ';
  
  }
  
  echo '<a href="admin.php?page=' . $Template->slug .'_renameCategory&amp;itemID=' . $item['id'] . '" title="' . _x( 'Rename this category', 'tab title', 'meoreader' ) . '" class="renameCategory">' . _x( 'rename', 'tab', 'meoreader' ) . '</a></span></h4>' . "\n";
  
  echo '<div class="entries">' . "\n";
  
  if( count( $item['feeds'] ) > 0 ) {
    
    foreach( $item['feeds'] as $feed ) {

      echo '<div class="feedItem" data-fid="' . $feed['id'] . '" data-name="' . $feed['name'] . '">';
      echo ' <div class="name">' . $feed['name'] . '<span><a href="admin.php?page=' . $Template->page . '&amp;action=deleteFeed&amp;itemID=' . $feed['id'] . '&amp;meoNonce=' . wp_create_nonce( 'meoReader_deleteFeed' ) . '" class="delete" title="' . _x( 'Delete this feed', 'tab title', 'meoreader' ) . '">' . _x( 'delete', 'tab', 'meoreader' ) . '</a> | <a href="admin.php?page=' . $Template->slug . '_feedChecker&amp;feedURL=' . urldecode( $feed['xml_url'] ) . '">' . _x( 'check', 'tab', 'meoreader' ) . '</a> | <a href="admin.php?page=' . $Template->slug . '_moveFeed&amp;itemID=' . $feed['id'] . '&amp;meoNonce=' . wp_create_nonce( 'meoReader_moveFeed' ) . '" class="move" title="' . _x( 'Move this feed into another category', 'tab title', 'meoreader' ) . '">' . _x( 'move', 'tab', 'meoreader' ) . '</a></span></div>' . "\n";
      echo ' <div class="htmlUrl">';
      
      if( trim( $feed['html_url'] ) !== '' ) {
      
        echo '<a href="' . $feed['html_url'] . '" class="www" target="_blank" title="homepage">' . $feed['html_url'] . '</a> &ensp; &ensp; ';
      
      }
      
      echo '<a href="' . $feed['xml_url'] . '" class="rss" target="_blank" title="rss feed">' . $feed['xml_url'] . "</a></div>\n";
      
      if( trim( $feed['description'] ) !== '' ) {

        echo ' <div class="description">' . $feed['description'] . "</div>\n";
      
      }

      echo "</div>\n";
      
    }
    
  }
  else {

    echo '<div class="feedItem"><i>' . _x( 'Currently there are no feeds assigned to that catgory', 'error message', 'meoreader' ) . ".</i></div>\n";

  }
  
  echo '<div class="clearAll">&nbsp;</div><p>&nbsp;</p>' . "\n";
  
  echo "</div>\n"; // .entries
  
}
?>

</div><!-- end #meoReader_SubscriptionList -->

<div class="meoReaderForm_wrapper">
 <form id="meoReaderForm_AddCategory" action="admin.php?page=<?php echo $Template->page; ?>" method="post" class="meoReader_subscription">
  <input type="hidden" name="meoReaderForm_AddCategory_status" value="1" />
  <input type="hidden" name="meoNonce" value="<?php echo wp_create_nonce( 'meoReader_addCategory' ); ?>" />
  <div>
   <label for="meoReaderForm_AddCategory_name"><?php echo _x( 'Category Name', 'add category form', 'meoreader' ); ?>:</label>
   <input type="text" name="meoReaderForm_AddCategory_name" id="meoReaderForm_AddCategory_name" class="regular-text ltr" />
   <input type="submit" value="<?php echo _x( 'Add Category', 'button label', 'meoreader' ); ?>" class="button-secondary" />
   <div class="clearAll">&nbsp;</div>
  </div>
 </form>
</div>

<p id="meoReader_toggleImportExportView"><a href="javascript:void(0);" id="meoReader_toggleImportExport" class="button-secondary">[ + ] Import / Export</a></p>

<div id="meoReader_ImportExport">

 <div id="meoReader_ExportArea">

  <h3>Export</h3>

  <div class="meoReaderForm_wrapper">
   <form id="meoReaderForm_exportData" action="javascript:void(0);" method="post" class="meoReader_subscription">

    <ul>
     <li>
      <label><a href="admin.php?page=meoreader_exportxml"><?php echo _x( 'meoReader XML file', 'export form', 'meoreader' ); ?></a></label>
      <div class="clearAll">&nbsp;</div>
     </li>
     <li>
      <label><a href="admin.php?page=meoreader_exportopml"><?php echo _x( 'OPML XML file', 'export form', 'meoreader' ); ?></a></label>
      <div class="clearAll">&nbsp;</div>
     </li>
    </ul>

   </form>
  </div>

 </div><!-- end export area -->

 <div id="meoReader_ImportArea">
  
  <h3>Import</h3>

  <div class="meoReaderForm_wrapper">
   <form id="meoReaderForm_importMeoReader" action="admin.php?page=meoreader_import" method="post" class="meoReader_subscription meoReader_import" enctype="multipart/form-data" target="importFrame">
    <input type="hidden" name="meoReaderForm_importMeoReader_status" value="1" />
    <input type="hidden" name="module" value="MeoReader" />
    <input type="hidden" name="meoNonce" value="<?php echo wp_create_nonce( 'meoReader_import' ); ?>" class="meoNonce" />
    <input type="hidden" name="fileKey" value="meoReaderForm_importMeoReader_file" />
    <div>
     <label for="meoReaderForm_importMeoReader_file"><?php echo _x( 'meoReader XML file', 'import form', 'meoreader' ); ?>:</label>
     <input type="file" name="meoReaderForm_importMeoReader_file" id="meoReaderForm_importMeoReader_file" />
     <input type="submit" value="<?php echo _x( 'Import', 'button label', 'meoreader' ); ?>" class="button-secondary" />
    </div>
    <div class="clearAll">&nbsp;</div>
   </form>
  </div>

   <div class="meoReaderForm_wrapper">
    <form id="meoReaderForm_importOPML" action="admin.php?page=meoreader_import" method="post" class="meoReader_subscription meoReader_import" enctype="multipart/form-data" target="importFrame">
     <input type="hidden" name="meoReaderForm_importOPML_status" value="1" />
     <input type="hidden" name="module" value="OPML" />
     <input type="hidden" name="meoNonce" value="<?php echo wp_create_nonce( 'meoReader_import' ); ?>" class="meoNonce" />
     <input type="hidden" name="fileKey" value="meoReaderForm_importOPML_file" />
     <div>
      <label for="meoReaderForm_importOPML_file"><?php echo _x( 'OPML XML file', 'import form', 'meoreader' ); ?>:</label>
      <input type="file" name="meoReaderForm_importOPML_file" id="meoReaderForm_importOPML_file" />
      <input type="submit" value="<?php echo _x( 'Import', 'button label', 'meoreader' ); ?>" class="button-secondary" />
      <div class="clearAll">&nbsp;</div>
     </div>
    </form>
   </div>

<?php
if( $data['options']['showGoogleImporter'] === true ) {
?>
   <div class="meoReaderForm_wrapper">
    <form id="meoReaderForm_importGoogleReader" action="admin.php?page=meoreader_import" method="post" class="meoReader_subscription meoReader_import" enctype="multipart/form-data" target="importFrame">
     <input type="hidden" name="meoReaderForm_importGoogleReader_status" value="1" />
     <input type="hidden" name="module" value="GoogleReader" />
     <input type="hidden" name="meoNonce" value="<?php echo wp_create_nonce( 'meoReader_import' ); ?>" class="meoNonce" />
     <input type="hidden" name="fileKey" value="meoReaderForm_importGoogleReader_file" />
     <div>
      <label for="meoReaderForm_importGoogleReader_file"><?php echo _x( 'Google Reader (Takeout) ZIP file', 'import form', 'meoreader' ); ?>:</label>
      <input type="file" name="meoReaderForm_importGoogleReader_file" id="meoReaderForm_importGoogleReader_file" />
      <input type="submit" value="<?php echo _x( 'Import', 'button label', 'meoreader' ); ?>" class="button-secondary" />
      <div class="clearAll">&nbsp;</div>
     </div>
    </form>
   </div>
<?php
}
?>
 </div><!-- end import area -->

 <iframe name="importFrame" id="importFrame" src="admin.php?page=meoreader_blank"></iframe>

</div><!-- end #meoReader_ImportExport -->