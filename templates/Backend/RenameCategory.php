<h3 class="admin"><?php echo _x( 'Rename a Category', 'headline', 'meoreader' ); ?></h3>
<form action="admin.php?page=<?php echo $Template->page; ?>&amp;itemID=<?php echo $data['catID']; ?>" method="post" class="meoReader_edit">
 <input type="hidden" name="meoReaderForm_renameCategory_status" value="1" />
 <input type="hidden" name="meoNonce" value="<?php echo wp_create_nonce( 'meoReader_renameCategory' ); ?>" />
 <div>
  <label for="meoReaderForm_RenameCategory_newCategoryName"><?php echo _x( 'Rename', 'rename category', 'meoreader' ); ?> <strong><?php echo $data['oldName']; ?></strong> <?php echo _x( 'to', 'rename category', 'meoreader' ); ?>:</label>
  <input type="text" value="<?php echo $data['newName']; ?>" name="meoReaderForm_RenameCategory_newCategoryName" id="meoReaderForm_RenameCategory_newCategoryName" class="regular-text ltr" />
  <input type="submit" value="<?php echo _x( 'Rename Category', 'button label', 'meoreader' ); ?>" class="button-secondary" />
 </div>
</form>

<p class="back">&#171; <a href="admin.php?page=<?php echo $Template->slug; ?>_subscriptions"><?php echo _x( 'Back to the Subscription Management', 'backlink', 'meoreader' ); ?></a></p>
