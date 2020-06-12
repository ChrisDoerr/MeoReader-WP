<?php
/**
 * Bootstrapping WordPress
 */
$root = realpath( dirname( __FILE__ ) . '/../../../../' );
$root = str_replace( '/', DIRECTORY_SEPARATOR, $root ) . DIRECTORY_SEPARATOR;

include_once $root . 'wp-load.php';

/**
 * Provide a dictionary for the JS strings.
 */
?>
/*global MeoReader*/
MeoReader.i18n.dictionary = {

  /* MeoReader.box */
  'btn_Okay'                        : '<?php echo _x( 'Okay', 'JS MeoReader Box', 'meoreader' ); ?>',
  'btn_Cancel'                      : '<?php echo _x( 'Cancel', 'JS MeoReader Box', 'meoreader' ); ?>',
  'btn_Close'                       : '<?php echo _x( 'Close', 'JS MeoReader Box', 'meoreader' ); ?>',

  /* MeoReader.Import */
  'Importing Data'                  : '<?php echo _x( 'Importing Data', 'JS MeoReader Import', 'meoreader' ); ?>',
  'Depending on how many feeds you have, this might take a while' : '<?php echo _x( 'This might take a while depending on how many feeds you have.', 'JS MeoReader Import', 'meoreader' ); ?>',
  'Progress'                        : '<?php echo _x( 'Progress', 'JS MeoReader Import', 'meoreader' ); ?>',
  'of'                              : '<?php echo _x( 'of', 'JS MeoReader Import', 'meoreader' ); ?>',
  'Abort'                           : '<?php echo _x( 'Abort', 'JS MeoReader Import', 'meoreader' ); ?>',
  'The following feed(s) could not be added'                      : '<?php echo _x( 'The following feed(s) could not be added', 'JS MeoReader Import', 'meoreader' ); ?>',
  
  /* MeoReader.Reader */
  'Add to Archive'                  : '<?php echo _x( 'Add to Archive', 'JS MeoReader Reader', 'meoreader' ); ?>',
  'No entries'                      : '<?php echo _x( 'No entries', 'JS MeoReader Reader', 'meoreader' ); ?>',
  'Update Error'                    : '<?php echo _x( 'Update Error', 'JS MeoReader Reader', 'meoreader' ); ?>',
  'The following feeds could not be refreshed'                    : '<?php echo _x( 'The following feeds could not be refreshed', 'JS MeoReader Reader', 'meoreader' ); ?>',
  'Error'                           : '<?php echo _x( 'Error', 'JS MeoReader Reader', 'meoreader' ); ?>',
  'Refreshing Feeds'                : '<?php echo _x( 'Refreshing Feeds', 'JS MeoReader Reader', 'meoreader' ); ?>',
  'Post has been created'           : '<?php echo _x( 'Post has been created', 'JS MeoReader Reader', 'meoreader' ); ?>',
  'Post could not be created!'      : '<?php echo _x( 'Post could not be created!', 'error message', 'meoreader' ); ?>',
  'Create Post From Entry'          : '<?php echo _x( 'Create Post From Entry', 'entry toolbar', 'meoreader' ); ?>',
  'Share this on Facebook'          : '<?php echo _x( 'Share this on Facebook', 'entry toolbar', 'meoreader' ); ?>',
  'Share this on Twitter'           : '<?php echo _x( 'Share this on Twitter', 'entry toolbar', 'meoreader' ); ?>',

  /* MeoReader.Subscriptions */
  'Move'                            : '<?php echo _x( 'Move', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'btn_Move'                        : '<?php echo _x( 'Move', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'to category'                     : '<?php echo _x( 'to category', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Move Feed'                       : '<?php echo _x( 'Move Feed', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Confirm Deletion'                : '<?php echo _x( 'Confirm Deletion', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Are you sure you want to delete this %type%'                   : '<?php echo _x( 'Are you sure you want to delete this %type%', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'category'                        : '<?php echo _x( 'category', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'feed'                            : '<?php echo _x( 'feed', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Delete this category'            : '<?php echo _x( 'Delete this category', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'delete'                          : '<?php echo _x( 'delete', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'check'                           : '<?php echo _x( 'check', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Yes'                             : '<?php echo _x( 'Yes', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Rename this category'            : '<?php echo _x( 'Rename this category', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'rename'                          : '<?php echo _x( 'rename', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Delete this feed'                : '<?php echo _x( 'Delete this feed', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Move this feed into another category'                          : '<?php echo _x( 'Move this feed into another category', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'move'                            : '<?php echo _x( 'move', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Currently there are no feeds assigned to that category'        : '<?php echo _x( 'Currently there are no feeds assigned to that catgory', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Expand/Collapse All Categories'  : '<?php echo _x( 'Expand/Collapse All Categories', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'The feed has an invaid URL'      : '<?php echo _x( 'The feed has an invaid URL', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Toggle Read/Unread Status'       : '<?php echo _x( 'Toggle Read/Unread Status', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Confirm Delete All Cats'         : '<?php echo _x( 'Are you sure you want to delete <strong>all categories</strong>?', 'JS MeoReader Subscriptions', 'meoreader' ); ?>',
  'Confirm Delete All Feeds'        : '<?php echo _x( 'Are you sure you want to delete <strong>all feeds</strong>?', 'JS MeoReader Subscriptions', 'meoreader' ); ?>'

};

<?php
/* Also make the plugin settings available */
$pluginSettings = get_option( 'meoreader' );
?>
MeoReader.settings = <?php echo json_encode( $pluginSettings ); ?>;

MeoReader.settings.url = '<?php echo WP_PLUGIN_URL . '/meoReader/'; ?>';
