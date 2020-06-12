<?php
/**
 * WordPress Backend Page: Plugin Settings
 *
 * @category    MeoReader
 * @package     Plugin Backend Pages
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Backend_Settings extends Meomundo_WP {
  
  /**
   * @var int   Max PHP execution time according to the server settings, respectively 1 below that number.
   */
  protected $maxTimeout;
  
  /**
   * @var array List of all the states a newly create post is allowed to have (in the context of this plugin).
   */
  protected $postStatus;
  
  /**
   * The constructor.
   *
   * @param string  $absolutePluginPath   Absolute path to the plugin directory.
   * @param string  $pluginURL            URL to the plugin directory.
   * @param string  $pluginSlug           Plugin handler.
   */
  public function __construct( $absolutePluginPath, $pluginURL, $pluginSlug ) {
    
    parent::__construct( $absolutePluginPath, $pluginURL, $pluginSlug );

    $this->maxTimeout = (int) ini_get( 'max_execution_time' );

    $this->maxTimeout--;
  
    $this->postStatus = array(
      'publish',
      'pending',
      'draft',
      'private'
    );

  }
  
  /**
   * The CONTROLLER.
   *
   * @return string HTML page code.
   */
  public function controller() {
    
    /* Check user privileges. */
    if( !MeoReader_Core::current_user_can( 'admin' ) ) {

      return '<p class="message error">' . _x( 'You are not allowed to do that!', 'error message', 'meoreader' ) . "</p>\n";

    }

    $html = '<h3 class="admin">' . _x( 'Settings', 'headline', 'meoreader' ) . "</h3>\n";
    
    $formHasBeenSent  = ( isset( $_POST['MeoReaderSettings_formStatus'] ) && $_POST['MeoReaderSettings_formStatus'] == 1 );
    
    $nonce  	        = isset( $_POST['MeoReader_Nonce'] ) ? $_POST['MeoReader_Nonce'] : '';

    /**
     * If the settings form has been send, the nonce could be verified and the user is allowed to save options
     * do some validation + save the settings.
     */
    if( $formHasBeenSent && wp_verify_nonce( $nonce, 'meoReader_saveSettings' ) && current_user_can( 'manage_options' ) ) {
    
      $result = $this->saveFormData( $this->validateFormData() );
      
      if( $result == true ) {
        
      $html .= '<p class="message success">' . _x( 'Settings have been saved', 'error message', 'meoreader' ) . "</p>\n";

      }
      else {
        
        $html .= '<p class="message error">' . _x( 'The Settings could not be saved!', 'error message', 'meoreader' ) . "</p>\n";
        
      }

    }

    $html .= $this->viewForm();
    
    return $html;
    
  }
  
  /**
   * Saving the settings data.
   *
   * The WordPress function updata_option() also returns FALSE when no change has been made
   * to the data: old data = new data. Ans since this method should only return FALSE
   * if there was a technical problem and also TRUE if there was nothing to update (which
   * I think makes more sense) you have to test for that case!
   *
   * @param array $newData    Array of settings data to be saved as plugin options.
   * @return bool 	          TRUE if the data could be saved or FALSE if that was not the case.
   */
  public function saveFormData( $newData = array() ) {
    
    $oldData  = get_option( $this->slug );
    
    $newData  = $this->normalize( $newData );
    
    $status   = ( $oldData !== $newData ) ? update_option( $this->slug, $newData ) : true;
    
    return ( $status !== false ) ? true : false;
    
  }
  
  /**
   * Validate the settings form data.
   *
   * @requires  int   $_POST['']    Number of entries to be shown per page in the READER (index) as well as the archive.
   * @requires  int   $_POST['']    Delete entries that are older than this number of days to keep your database performance up.
   * @return    array               The validated settings form data.
   */
  public function validateFormData() {

    $data                           = array();

    $data['entriesPerPage']         = ( isset( $_POST['MeoReaderSettings_entriesPerPage'] ) && !empty( $_POST['MeoReaderSettings_entriesPerPage'] ) ) ? (int) $_POST['MeoReaderSettings_entriesPerPage']  : 30;

    $data['deleteEntriesOlderThan'] = ( isset( $_POST['MeoReaderSettings_olderThan'] )      && !empty( $_POST['MeoReaderSettings_olderThan'] ) )      ? (int) $_POST['MeoReaderSettings_olderThan']       : 31;
    
    /* At least keep entries of the past 24 hours!! */
    if( $data['deleteEntriesOlderThan'] < 1 ) {
      
      $data['deleteEntriesOlderThan'] = 1;
      
    }
    
    $data['timeout']                = ( isset( $_POST['MeoReaderSettings_timeout'] )        && !empty( $_POST['MeoReaderSettings_timeout'] ) )        ? (int) $_POST['MeoReaderSettings_timeout']         : 30;
    
    $data['timeout']                = ( $data['timeout'] > $this->maxTimeout ) ? $this->maxTimeout : $data['timeout'];

    $data['showGoogleImporter']     = ( isset( $_POST['MeoReaderSettings_allowGoogleImport'] ) )                                                      ? true                                              : false; 
    
    $data['userID']                 = ( isset( $_POST['MeoReaderSettings_userID'] )         && !empty( $_POST['MeoReaderSettings_userID'] ) )         ? (int) $_POST['MeoReaderSettings_userID']          : 0;

    $data['currentCatTab']          = ( isset( $_POST['MeoReaderSettings_currentTab'] ) && $_POST['MeoReaderSettings_currentTab'] > 0 )               ? (int) $_POST['MeoReaderSettings_currentTab']      : 0;

    $data['postStatus']             = ( isset( $_POST['MeoReaderSettings_postStatus'] ) && in_array( $_POST['MeoReaderSettings_postStatus'], $this->postStatus ) )  ? $_POST['MeoReaderSettings_postStatus']  : 'draft';

    $data['twitter']                = ( isset( $_POST['MeoReaderSettings_twitter'] )  && !empty( $_POST['MeoReaderSettings_twitter'] ) )              ? strip_tags( $_POST['MeoReaderSettings_twitter'] )    : '';

    $data['postEditor']             = ( isset( $_POST['MeoReaderSettings_postEditor'] ) )                                                             ? true                                              : false;

    $data['userCanPublish']         = ( isset( $_POST['MeoReaderSettings_userCanPublish'] ) )                                                     ? true                                              : false;
    
    $data['autopublish']            = ( isset( $_POST['MeoReaderSettings_autopublish'] ) )                                                        ? true                                              : false;

    $data['anonymousLinks']         = ( isset( $_POST['MeoReaderSettings_anonymousLinks'] ) )                                                     ? true                                              : false;

    $data['audioplayer']            = ( isset( $_POST['MeoReaderSettings_audioplayer'] ) )                                                        ? true                                              : false;

    $data['purify']                 = ( isset( $_POST['MeoReaderSettings_purify'] ) )                                                             ? true                                              : false;

    return $data;
    
  }
  
  /**
   * Normalize settings array.
   * normalize != validation!
   *
   * @param array $data Form data.
   * @return array Normalized settings data.
   */
  public function normalize( $data = array() ) {
    
    $data     = (array) $data;
    
    $defaults = array(
      'entriesPerPage'          => 30,
      'deleteEntriesOlderThan'  => 31,
      'timeout'                 => $this->maxTimeout,
      'showGoogleImporter'      => false,   // The default is FALSE because importing Google Reader data REQUIRES PHP's ZIP functionality to be enabled!
      'currentCatTab'           => 0,
      'userID'                  => 0,
      'twitter'                 => '',
      'postStatus'              => 'draft',
      'postEditor'              => true,
      'userCanPublish'          => false,
      'anonymousLinks'          => false,
      'audioplayer'             => false,
      'purify'                  => true,
      'autopublish'             => false
    );
    
    return array_merge( $defaults, $data );
    
  }
  
  /**
   * View the settings form.
   *
   * @return string HTML formatted settings form.
   */
  public function viewForm() {
    
    /* Normalize options to match with default values where necessary. */
    $data = $this->normalize( get_option( $this->slug ) );

    /* Get a list of all users to select one single user that, besides admins, is allowed to READ */
    $users    = get_users(
      array(
        'fields'  => array(
          'ID',
          'display_name'
        ),
        'orderby' => 'display_name'
      )
    );
    
    $NullUser               = new stdClass();

    $NullUser->ID           = 0;

    $NullUser->display_name = _x( 'No single user allowed', 'settings form', 'meoreader' );

    $users[] = $NullUser;

    $html = '';
    
    $html .= '<form action="admin.php?page=meoreader_settings" method="post" id="MeoReaderSettingsForm">' . "\n";
    $html .= ' <input type="hidden" name="MeoReaderSettings_formStatus" value="1" />' . "\n";
    $html .= ' <input type="hidden" name="MeoReader_Nonce" value="' . wp_create_nonce( 'meoReader_saveSettings' ) . '" />' . "\n";
    $html .= ' <input type="hidden" name="MeoReaderSettings_currentTab" value="' . $data['currentCatTab'] . '" />' . "\n";

    /* Entries per page */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <label for="MeoReaderSettings_entriesPerPage">' . _x( 'Entries per page', 'settings form', 'meoreader' ) . ":</label>\n";
    $html .= '  <input type="text" id="MeoReaderSettings_entriesPerPage" name="MeoReaderSettings_entriesPerPage" value="' . strip_tags( $data['entriesPerPage'] ) . '" />' . "\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";
    
    /* Older than */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <label for="MeoReaderSettings_olderThan">' . _x( 'Delete entries older than', 'settings form', 'meoreader' ) . ":</label>\n";
    $html .= '  <input type="text" id="MeoReaderSettings_olderThan" name="MeoReaderSettings_olderThan" value="' . strip_tags( $data['deleteEntriesOlderThan'] ) . '" /> <span>' . _x( 'Days', 'settings form', 'meoreader' ) . ".</span>\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";

    /* Timeout */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <label for="MeoReaderSettings_timeout">' . _x( 'Timeout', 'settings form', 'meoreader' ) . ":</label>\n";
    $html .= '  <input type="text" id="MeoReaderSettings_timeout" name="MeoReaderSettings_timeout" value="' . strip_tags( $data['timeout'] ) . '" /> <span>' . _x( 'Seconds to wait for a server response when fetching a feed', 'settings form', 'meoreader' ) . ". <sup>1</sup></span>\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";

    /* Single User */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <label for="MeoReaderSettings_userID">' . _x( 'Allow Single User', 'settings form', 'meoreader' ) . ":</label>\n";
    
    $html .= '  <select id="MeoReaderSettings_userID" name="MeoReaderSettings_userID" size="1">' . "\n";

    foreach( $users as $user ) {
      
      $userID = (int) $user->ID;

      $html .= '    <option value="' . $userID . '"';

      if( $userID === $data['userID'] ) {
        
        $html .= ' selected="selected" ';
        
      }
      
      $html .= '>' . strip_tags( $user->display_name ) . "</option>\n";
      
    }
    
    $html .= " </select>\n";
    
    $html .= '<span>' . _x( 'to use <strong>the reader</strong> but not to manage subscriptions. This should not be an admininistrator!', 'settings form', 'meoreader' ) . "</span>\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";


    /* Twitter Handle */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <label for="MeoReaderSettings_twitter">' . _x( 'Twitter Handle', 'settings form', 'meoreader' ) . " @</label>\n";
    $html .= '  <input type="text" id="MeoReaderSettings_twitter" name="MeoReaderSettings_twitter" value="' . strip_tags( $data['twitter'] ) . '" /> <span>' . _x( 'Your username on Twitter', 'settings form', 'meoreader' ) . ' <span class="description">(' . _x( 'optional', 'settings form', 'meoreader' ) . ")</span>\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";

    /* Create Post From Entry: Post status*/
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <label for="MeoReaderSettings_postStatus">' . _x( 'Post Status', 'settings form', 'meoreader' ) . ":</label>\n";
    
    $html .= '  <select id="MeoReaderSettings_postStatus" name="MeoReaderSettings_postStatus" size="1">' . "\n";

    foreach( $this->postStatus as $postStatus ) {
      
      $html .= '    <option value="' . $postStatus . '"';

      if( $postStatus === $data['postStatus'] ) {
        
        $html .= ' selected="selected" ';
        
      }
      
      $html .= '>' . $postStatus . "</option>\n";
      
    }
    
    $html .= " </select>\n";
    
    $html .= '<span>' . _x( 'Status of posts that have been created from an entry.', 'settings form', 'meoreader' ) . "</span>\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";

    /* Switch to Post Editor */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <div class="meoReaderForm_left"><input type="checkbox" id="MeoReaderSettings_postEditor" name="MeoReaderSettings_postEditor" value="1"';
    
    if( $data['postEditor'] === true ) {
      
      $html .= ' checked="checked"';
      
    }
    
    $html .= ' /></div>' . "\n";
    $html .= '  <div class="meoReaderForm_right"><label for="MeoReaderSettings_postEditor">' . _x( 'Switch to the editor when creating a post from an entry', 'settings form', 'meoreader' ) . '.</label></div>' . "\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";


    /* Allow "single user" to create posts from entries */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <div class="meoReaderForm_left"><input type="checkbox" id="MeoReaderSettings_userCanPublish" name="MeoReaderSettings_userCanPublish" value="1"';
    
    if( $data['userCanPublish'] === true ) {
      
      $html .= ' checked="checked"';
      
    }
    
    $html .= ' /></div>' . "\n";
    $html .= '  <div class="meoReaderForm_right"><label for="MeoReaderSettings_userCanPublish">' . _x( 'Allow <i>single user</i> to create posts from entries', 'settings form', 'meoreader' ) . '.</label></div>' . "\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";

    /**
     * Auto-publish all posts immediately
     */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <div class="meoReaderForm_left"><input type="checkbox" id="MeoReaderSettings_autopublish" name="MeoReaderSettings_autopublish" value="1"';
    
    if( $data['autopublish'] === true ) {
      
      $html .= ' checked="checked"';
      
    }
    
    $html .= ' /></div>' . "\n";
    $html .= '  <div class="meoReaderForm_right"><label for="MeoReaderSettings_autopublish">' . _x( 'Auto-publish all entries as blog posts', 'settings form', 'meoreader' ) . '.</label></div>' . "\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";


    /* Google Reader Import */
    if( !function_exists( 'zip_open' ) ) {
      
      $html .= ' <div class="formItem"><p>' . _x( 'If you want to import data from the Google Reader you need to enable the PHP function <i>zip_open</i> in your PHP.ini file first!', 'settings form', 'meoreader' ) . '</p></div>';

    }
    else {
    
      $html .= ' <div class="formItem">' . "\n";
      $html .= '  <div class="meoReaderForm_left"><input type="checkbox" id="MeoReaderSettings_allowGoogleImport" name="MeoReaderSettings_allowGoogleImport" value="1"';
    
      if( $data['showGoogleImporter'] === true ) {
      
        $html .= ' checked="checked"';
      
      }
    
      $html .= ' /></div>' . "\n";
      $html .= '  <div class="meoReaderForm_right"><label for="MeoReaderSettings_allowGoogleImport">' . _x( 'Show the <strong>Import Google Reader</strong> form on the <i>subscriptions</i> page', 'settings form', 'meoreader' ) . '.</label></div>' . "\n";
      $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
      $html .= " </div>\n";
    
    }




    /* Anonymize outgoing links http://anonym.to/? */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <div class="meoReaderForm_left"><input type="checkbox" id="MeoReaderSettings_anonymousLinks" name="MeoReaderSettings_anonymousLinks" value="1"';
    
    if( $data['anonymousLinks'] === true ) {
      
      $html .= ' checked="checked"';
      
    }

    $html .= ' /></div>' . "\n";
    $html .= '  <div class="meoReaderForm_right"><label for="MeoReaderSettings_anonymousLinks">' . _x( 'Use http://anonym.to service for outgoing links', 'settings form', 'meoreader' ) . '.</label></div>' . "\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";


    /* Show audioplayer "audio.js" for attached audio files (enclosures) */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <div class="meoReaderForm_left"><input type="checkbox" id="MeoReaderSettings_audioplayer" name="MeoReaderSettings_audioplayer" value="1"';
    
    if( $data['audioplayer'] === true ) {
      
      $html .= ' checked="checked"';
      
    }

    $html .= ' /></div>' . "\n";
    $html .= '  <div class="meoReaderForm_right"><label for="MeoReaderSettings_audioplayer">' . _x( 'Show audio player for attached audio files', 'settings form', 'meoreader' ) . '. <sup>2</sup></label></div>' . "\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";


    /* Use HTMLPurfier lib to secure HTML output, eg against XSS attack vectors */
    $html .= ' <div class="formItem">' . "\n";
    $html .= '  <div class="meoReaderForm_left"><input type="checkbox" id="MeoReaderSettings_purify" name="MeoReaderSettings_purify" value="1"';
    
    if( $data['purify'] === true ) {
      
      $html .= ' checked="checked"';
      
    }

    $html .= ' /></div>' . "\n";
    $html .= '  <div class="meoReaderForm_right"><label for="MeoReaderSettings_purify">' . _x( 'Secure HTML output against certain XSS attack vectors', 'settings form', 'meoreader' ) . '. <sup>3</sup></label></div>' . "\n";
    $html .= '  <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= " </div>\n";

    /**
     * READONLY CronJob URL
     */

    $cronToken = get_option( 'meoreader_crontoken' );
    
    if( $cronToken !== false && trim( $cronToken ) !== '' ) {

      $html .= '<div class="formItem">' . "\n";
      $html .= ' <div class="meoReaderForm_left"><label>CronJob URL:</label></div>' . "\n";
      $html .= ' <div class="meoReaderForm_right"><span style="background-color:#FFF;padding:0.5em 1em;">' . $this->url . 'cron.php?token=' . $cronToken . '</span></div>';
      $html .= ' <div class="clearAll">&nbsp;</div>' . "\n";
      $html .= "</div>\n";
    
    }

    /* Submit form */
    $html .= '<div class="formItem">' . "\n";
    $html .= ' <label>&nbsp;</label>' . "\n";
    $html .= ' <p><input type="submit" class="button-primary" value="' . _x( 'Save Changes', 'button label', 'meoreader' ) . '" /></p>' . "\n";
    $html .= ' <div class="clearAll">&nbsp;</div>' . "\n";
    $html .= "</div>\n";
  
    $html .= "</form>\n";
    
    /* Footnote(s) */
    $html .= '<p><sup>1</sup> ' . str_replace( '%maxTime%', ini_get( 'max_execution_time' ), _x( 'Has to be <strong>less than %maxTime%</strong> according to your server settings.', 'settings form', 'meoreader' ) ) . "</p>\n";
    $html .= '<p><sup>2</sup> <strong>Audio.js</strong> by <a href="http://kolber.github.io/audiojs/" target="_blank">Anthony Kolber</a>, under <a href="http://www.opensource.org/licenses/mit-license.php" target="_blank">MIT License</a>.</p>' . "\n";
    $html .= '<p><sup>3</sup> ' . _x( 'Securing HTML output makes a lot of sense but can be pretty slow! You have to make a choice here: Security or Performance', 'setting_form', 'meoreader' ) . ".\n";
    
    return $html;
    
  }
  
}
?>