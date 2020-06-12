<h3 class="admin"><?php echo _x( 'Move Feed', 'headline', 'meoreader' ); ?></h3>
<form action="admin.php?page=<?php echo $Template->slug; ?>_moveFeed&amp;itemID=<?php echo $data['feedID']; ?>" method="post" class="meoReader_edit">
 <input type="hidden" name="meoReaderForm_MoveFeed_status" value="1" />
 <input type="hidden" name="meoNonce" value="<?php echo wp_create_nonce( 'meoReader_moveFeed' ); ?>" />
 <div>
  <label for="meoReaderForm_moveFeed_newCatID"><?php echo _x( 'Move', 'move feed', 'meoreader' ); ?> <strong><?php echo $data['feedName']; ?></strong> <?php echo _x( 'to', 'move feed', 'meoreader' ); ?>:</label>
  <select name="meoReaderForm_moveFeed_newCatID" id="meoReaderForm_moveFeed_newCatID" size="1">
<?php
foreach( $data['categories'] as $cat ) {

  echo '<option value="' . $cat['id'] . '"';
  
  if( isset( $data['catID'] ) && $data['catID'] == $cat['id'] ) {
    
    echo ' select="select"';
    
  }

  echo '>' . $cat['name'] . "</option>\n";

}
?>
  </select>
  <input type="submit" value="<?php echo _x( 'Move Feed', 'button label', 'meoreader' ); ?>" class="button-secondary" />
 </div>
</form>

<p class="back">&#171; <a href="admin.php?page=<?php echo $Template->slug; ?>_subscriptions"><?php echo _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ); ?></a></p>
