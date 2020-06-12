<?php
/**
 * The MeoReader Entries API
 *
 * @category    MeoReader
 * @package     Categories
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Entries {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var array Array of nonces and actions for this class.
   */
  protected $nonceData;
  
  /**
   * The constructor.
   *
   * @param object  $wpdb WordPress database object.
   */
  public function __construct( $wpdb ) {
    
    $this->DB         = $wpdb;
    
    $this->nonceData  = $this->createNonces();
        
  }

  /**
   * Get all entries for a given (or all) category/-ies.
   *
   * @param   int         $catID            The category ID.
   * @param   int         $pageNr           The current page number - will be used for getting only certain blocks from the database.
   * @param   int         $entriesPerPage   (Optional) The number of entries that shall be retrieved in one call. The default is 30.
   * @return  bool|array                    FALSE if a technical error occured or an array of all the entries for this DB call.
   */
  public function getEntriesByCategory( $catID, $pageNr, $entriesPerPage = 30 ) {

    $catID          = (int) $catID;

    $catID          = abs( $catID );
    
    $pageNr         = (int) $pageNr;
    
    $pageNr         = abs( $pageNr );
    
    $entriesPerPage = (int) $entriesPerPage;
    
    $entriesPerPage = abs( $entriesPerPage );
    
    $offset         = ( $pageNr -1 ) * $entriesPerPage;
    
    $options        = get_option('meoreader');
    
    $todayAllTime   = ( isset( $options['timerange'] ) && $options['timerange'] === 'today' ) ? 'today' : 'alltime';
    
    if( $catID < 1 ) {

      $sql  = '   SELECT entries.id,';
      $sql .= '          entries.pub_date,';
      $sql .= '          entries.description,';
      $sql .= '          entries.description_prep,';
      $sql .= '          entries.enclosures,';
      $sql .= '          entries.link,';
      $sql .= '          entries.status,';
      $sql .= '          entries.title,';
      $sql .= '          entries.archive,';      
      $sql .= '          feeds.name as feed_name,';
      $sql .= '          feeds.html_url as feed_html_url';
      $sql .= '     FROM ' . MEOREADER_TBL_ENTRIES . ' as entries,';
      $sql .= '          ' . MEOREADER_TBL_FEEDS . ' as feeds';
      $sql .= "    WHERE entries.feed_id = feeds.id";
      $sql .= "      AND entries.status IN( 'read', 'unread' )";
      $sql .= "      AND entries.archive = '0'";

      if( $todayAllTime === 'today' ) {
				$sql .= "        AND DATE( entries.pub_date ) >= CURDATE() ";
      }

      $sql .= ' ORDER BY entries.pub_date DESC';
      $sql .= '    LIMIT ' . $offset . ', ' . $entriesPerPage;
      
    }
    else {
      
      $sql  = '   SELECT entries.id,';
      $sql .= '          entries.pub_date,';
      $sql .= '          entries.description,';
      $sql .= '          entries.description_prep,';
      $sql .= '          entries.enclosures,';
      $sql .= '          entries.link,';
      $sql .= '          entries.title,';
      $sql .= '          entries.archive,';      
      $sql .= '          entries.status,';
      $sql .= '          feeds.name as feed_name,';
      $sql .= '          feeds.html_url as feed_html_url';
      $sql .= '     FROM ' . MEOREADER_TBL_ENTRIES . ' as entries,';
      $sql .= '          ' . MEOREADER_TBL_FEEDS . ' as feeds';
      $sql .= "    WHERE feeds.cat_id = %d";
      $sql .= '      AND entries.feed_id = feeds.id';
      $sql .= "      AND entries.status IN( 'read', 'unread' )";
      $sql .= "      AND entries.archive = '0'";
      
      if( $todayAllTime === 'today' ) {
        $sql .= "        AND DATE( entries.pub_date ) >= CURDATE() ";
      }

      $sql .= ' ORDER BY entries.pub_date DESC';
      $sql .= '    LIMIT ' . $offset . ', ' . $entriesPerPage;
      
      $sql = $this->DB->prepare( $sql, $catID );
      
    }
    
    $results = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( empty( $results ) ) ? false : $results;

//    return ( empty( $results ) ) ? false : MeoReader_Core::purifyResultsHTML( $results );
    
  }
  
  /**
   * Count all UNREAD entries of a given category (by its ID).
   *
   * @param   int   $catID    Category ID.
   * @return  array           An array containing the overall number of unread items and the unread items of the given category as well as the category ID.
   */
  public function countUnreadEntries( $catID ) {

    $catID        = (int) $catID;
    
    $catID        = abs( $catID );

    $totalAll     = $this->countAllUnreadEntries();

    /* If catID = 0 count the overall total */
    $totalCat     = $totalAll;

    if( $catID > 0 ) {
      
      $totalCat   = $this->countUnreadEntriesByCategory( $catID );

    }

    return array(
      'all'       => $totalAll,
      'category'  => $totalCat,
      'catID'     => $catID
    );
     
  }
  
  
  /**
   * Count ALL unread entries.
   *
   * @return int Overall number of unread entries.
   */
  public function countAllUnreadEntries() {

    $sql    = '   SELECT COUNT(*) as total';
    $sql   .= '     FROM ' . MEOREADER_TBL_ENTRIES;
    $sql   .= "    WHERE status = 'unread'";
          
    $result = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( isset( $result[0]['total'] ) ) ? (int) $result[0]['total'] : 0;

  }
  
  /**
   * Count all unread entries of a given category (by ID).
   *
   * @param   int $catID    ID of the category whose entries shall be counted.
   * @return  int           Number of unread entries of a category.
   */
  public function countUnreadEntriesByCategory( $catID ) {
    
    $catID  = (int) $catID;

    $sql    = '   SELECT COUNT(*) as total';
    $sql   .= '     FROM ' . MEOREADER_TBL_ENTRIES . ' as entries,';
    $sql   .= '          ' . MEOREADER_TBL_FEEDS . ' as feeds';
    $sql   .= "    WHERE feeds.cat_id = %d";
    $sql   .= '      AND entries.feed_id = feeds.id';
    $sql   .= "      AND entries.status = 'unread'";

    $sql    = $this->DB->prepare( $sql, $catID );

    $result = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( isset( $result[0]['total'] ) ) ? (int) $result[0]['total'] : 0;

  }
  
  /**
   * Get the overall number of entries of the total of a given category.
   *
   * @param   int $catID    Category ID. If ID is < 0 the overall number of entries will be counted.
   * @return  int           The total number of entries, either ALL or of a given category.
   */
  public function getTotalNumberOfEntries( $catID ) {
    
    $catID    = (int) $catID;
    
    $option   = get_option( 'meoreader' );
    
    /* Count ALL entries */
    if( $catID < 1 ) {
    
      $sql    = '   SELECT COUNT(*) as total';
      $sql   .= '     FROM ' . MEOREADER_TBL_ENTRIES . ' as entries,';
      $sql   .= '          ' . MEOREADER_TBL_FEEDS . ' as feeds';
      $sql   .= "    WHERE entries.feed_id = feeds.id";
      $sql   .= "      AND entries.status IN ( 'read', 'unread' )";
      
      if( isset( $option['timerange'] ) && $option['timerange'] === 'today' ) {
        
        $sql .= "        AND DATE( entries.pub_date ) >= CURDATE() ";
        
      }
          
    }
    /* Count all entries of a given category */
    else {

      $sql    = '   SELECT COUNT(*) as total';
      $sql   .= '     FROM ' . MEOREADER_TBL_ENTRIES . ' as entries,';
      $sql   .= '          ' . MEOREADER_TBL_FEEDS . ' as feeds';
      $sql   .= "    WHERE feeds.cat_id = %d";
      $sql   .= '      AND entries.feed_id = feeds.id';
      $sql   .= "      AND entries.status IN ( 'read', 'unread' )";
      
      if( isset( $option['timerange'] ) && $option['timerange'] === 'today' ) {
        
        $sql .= "        AND DATE( entries.pub_date ) >= CURDATE() ";
        
      }

      $sql    = $this->DB->prepare( $sql, $catID );

    }
    
    $results  = $this->DB->get_results( $sql, ARRAY_A );

    return ( isset( $results[0]['total'] ) ) ? (int) $results[0]['total'] : 0;
    
  }
  
  /**
   * Get an entry by its ID.
   *
   * @param   int   $entryID    The entry ID.
   * @return  array             An array containing information about the validity of the request and the entry data itself.
   */
  public function getEntryById( $entryID ) {
    
    $entryID = (int) $entryID;
    
    $sql      = '     SELECT entry.id,';
    $sql     .= '            entry.pub_date,';
    $sql     .= '            entry.description,';
    $sql     .= '            entry.enclosures,';
    $sql     .= '            entry.link,';
    $sql     .= '            entry.title,';
    $sql     .= '            feed.name as feed_name,';
    $sql     .= '            feed.html_url as feed_html_url';
    $sql     .= '       FROM ' . MEOREADER_TBL_ENTRIES . ' as entry,';
    $sql     .= '            ' . MEOREADER_TBL_FEEDS . ' as feed';
    $sql     .= '      WHERE entry.id = %d';
    $sql     .= '        AND entry.feed_id = feed.id';
    $sql     .= '      LIMIT 1';
    
    $sql      = $this->DB->prepare( $sql, $entryID );
    
    $results  = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( empty( $results ) ) ? array( 'request' => false ) : array( 'request' => true, 'entry'  => $results[0] );
    
  }
  
  /**
   * Mark a single entry as READ.
   *
   * @param   int   $entryID    The entry ID to be set as READ.
   * @return  bool              TRUE in case the entry could be marked as READ or FALSE if a technical/db error has occured.
   */
  public function markEntryAsRead( $entryID ) {
    
    $entryID  = (int) $entryID;

    $status   = $this->DB->update(
      MEOREADER_TBL_ENTRIES,
      array(
        'status'  => 'read'
      ),
      array(
        'id'      => $entryID
      ),
      array(
        '%s'
      ),
      array(
        '%d'
      )
    );

    return ( $status !== false ) ? true : false;

  }

  /**
   * Mark a single entry as UNREAD.
   *
   * @param   int   $entryID    The entry ID to be set as UNREAD.
   * @return  bool              TRUE in case the entry could be marked as READ or FALSE if a technical/db error has occured.
   */
  public function markEntryAsUnRead( $entryID ) {
    
    $entryID  = (int) $entryID;

    $status   = $this->DB->update(
      MEOREADER_TBL_ENTRIES,
      array(
        'status'  => 'unread'
      ),
      array(
        'id'      => $entryID
      ),
      array(
        '%s'
      ),
      array(
        '%d'
      )
    );

    return ( $status !== false ) ? true : false;

  }
  

  
  /**
   * Mark all entries as READ.
   *
   * @return bool TRUE in case all entries could be marked as READ or FALSE in case a technical/db error has occured.
   */
  public function markAllAsRead() {
    
    $status = $this->DB->query( 'UPDATE ' . MEOREADER_TBL_ENTRIES . " SET `status` = 'read'" );
    
    return ( $status === false ) ? false : true;
    
  }
  

  
  /**
   * Delete entries that are older than X number of days.
   * This should keep your database (relatively) performant - I hope.
   *
   * @param     int     $olderThan    (Optinal) Number of days after that entries will be deleted. If no value is set, the plugin options will be used instead.
   * @param     string  $slug         (Optinal) Plugin slug - will be used to get the plugin options.
   * @return    bool                  TRUE in case the entries could be deleted or there were no entries older than X days. FALSE in case a technical/db error has occured.
   */
  public function deleteOldEntries( $olderThan = false, $slug = '' ) {
    
    if( $olderThan === false ) {
      
      $options    = get_option( $slug );
      
      $olderThan  = ( $options !== false && isset( $options['deleteEntriesOlderThan'] ) ) ? (int) $options['deleteEntriesOlderThan'] : 30;
      
    }
    
    $olderThan = (int) $olderThan;

    /* At least keep entries of the last 24 hours!! */
    if( $olderThan < 1 ) {

      $olderThan = 1;

    }
    
    /* Get the formatted date of the oldest entry that is allowed, so to speak. */
    $olderThan_date = $this->convertOlderThan( $olderThan );

    /* Delete entries that are older than X days BUT do not delete archive elements!! */
    $sql            = $this->DB->prepare( 'DELETE FROM ' . MEOREADER_TBL_ENTRIES . ' WHERE pub_date < %s AND archive <> 1' , $olderThan_date );

    $status         = $this->DB->query( $sql );
    
    return ( $status === false ) ? false : true;
    
  }

  /**
   * Check if there even are entries that are older than X days.
   * Archive element do not count!!
   *
   * @param int $olderThan Number of days after that entries will be deleted.
   * @return bool TRUE if there are entries that are older than X days or FALSE if that's not the case.
   */
  public function oldEntriesExist( $olderThan ) {
    
    /* Get the formatted date of the oldest entry that is allowed, so to speak. */
    $olderThan_date = $this->convertOlderThan( $olderThan );
    
    $sql            = $this->DB->prepare( 'SELECT id FROM ' . MEOREADER_TBL_ENTRIES . ' WHERE pub_date < %s AND archive <> 1', $olderThan_date );
    
    $results        = $this->DB->get_results( $sql );
    
    return ( empty( $results ) ) ? false : true;    
    
  }

  /**
   * Convert number of "older than" days into a properly formatted date string.
   *
   * @param int $days Number of days.
   * @return string Properly formatted date string of the X-days-before-now.
   */
  protected function convertOlderThan( $days ) {
    
    $days = (int) $days;

    $days = abs( $days );
    
    $now  = time();
    
    $old  = $now - ( $days * 24 * 60 * 60 );
    
    return date( 'Y-m-d H:i:s', $old );

  }
  
  /**
   * Search entry descriptions and entry titles for a given query.
   *
   * @param   string      $query    Search query.
   * @return  bool|array            FALSE if there were no matches found or an array of all the matching entries.
   */
  public function searchEntries( $query ) {
    
    $query    = trim( strip_tags( $query ) );
    
    /* Don't even bother looking up the database if there the query is empty! */
    if( $query == '' ) {
      
      return false;
      
    }
    
    $prepared = $this->DB->prepare( '%s', strtolower( $query ) );
    
    $prepared = str_replace( "'", '%', $prepared );

    $sql      = '   SELECT entry.id,';
    $sql     .= '          entry.feed_id,';
    $sql     .= '          entry.pub_date,';
    $sql     .= '          entry.description,';
    $sql     .= '          entry.title,';
    $sql     .= '          entry.status,';
    $sql     .= '          entry.archive,';
    $sql     .= '          entry.link,';
    $sql     .= '          entry.enclosures,';
    $sql     .= '          feed.name as feed_name,';
    $sql     .= '          feed.xml_url as feed_xml_url,';
    $sql     .= '          feed.html_url as feed_html_url';
    $sql     .= '     FROM ' . MEOREADER_TBL_ENTRIES . ' as entry,';
    $sql     .= '          ' . MEOREADER_TBL_FEEDS . ' as feed';
    $sql     .= "    WHERE ( entry.description LIKE '" . $prepared . "'"; // search: entry description
    $sql     .= "       OR entry.title LIKE '" . $prepared . "'";         // search: entry title
    $sql     .= "       OR feed.name LIKE '" . $prepared . "' )";         // search: feed name
    $sql     .= '      AND entry.feed_id = feed.id';
    $sql     .= ' GROUP BY entry.id';
    $sql     .= ' ORDER BY entry.pub_date DESC';

    $results  = $this->DB->get_results( $sql, ARRAY_A );

    return ( empty( $results ) ) ? false : $results;
    
  }

  
  /**
   * Calculate the total number of pages.
   *
   * @param   string  $slug           Plugin handler.
   * @param   int     $totalEntries   Total number of entries (per category or overall).
   * @return  int                     Total number of pages.
   */
  public function getTotalNumberOfPages( $slug, $totalEntries ) {
    
    $totalEntries   = (int) $totalEntries;
    
    if( $totalEntries < 1 ) {
      
      return 1;
      
    }
    
    $options        = get_option( $slug );

    $entriesPerPage = is_numeric( $options['entriesPerPage'] ) ? (int) $options['entriesPerPage'] : 30;

    $entriesPerPage = ( $entriesPerPage > 0 ) ? $entriesPerPage : 30;

    return (int) ceil( $totalEntries / $entriesPerPage );
    
  }


  
  /**
   * Get the array of nonce data for this class.
   *
   * @return array  Array of the nonces and actions.
   */
  public function getNonces() {
    
    return $this->nonceData;
    
  }
  
  /**
   * Create nonces and actions for this class.
   * 
   * Nonces will mostly only be implemented for actions that require WRITING permissions.
   * READING permissions are not as critical in the context of this plugin.
   *
   * @return array  Array of the nonces and actions.
   */
  public function createNonces() {
    
    $data   = array();

    $data[] = MeoReader_Core::createNonceData( 'meoReader_markEntryAsRead' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_markAllAsRead' );
    
    $data[] = MeoReader_Core::createNonceData( 'meoReader_toggleSingleReadState' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_deleteOldEntries' );
    
    return $data;
    
  }
  
  
  /**
   * Create a post (title + content) from a given feed entry.
   *
   * @param   int       $entryID 	    ID of the feed entry the new post shall be created from.
   * @param   string    $postStatus   (Optional) Can either be 'publish', 'draft', 'pending' or 'private'. Default is 'draft'.
   * @return  bool|int                The ID of the newly created post or FALSE in case the post could not be created for some reason.
   */
  public function createPostFromEntry( $entryID, $postStatus = '' ) {

    global $current_user;
    
    $entryID    = (int) $entryID;
    
    $postStatus = ( $postStatus === '' ) ? 'draft' : strip_tags( $postStatus );
    
    $entry      = $this->getEntryById( $entryID );
    
    if( $entry['request'] === true ) {
      
      /* Create the new post */
      $newPost  = array(
        'post_title'    => $entry['entry']['title'],
        'post_content'  => '<p>' . _x( 'by', 'label', 'meoreader' ) . ' <a href="' . $entry['entry']['link'] . '">' . $entry['entry']['feed_name'] . "</a></p>\n" . $entry['entry']['description'],
        'post_status'   => $postStatus,
        'post_author'   => $current_user->ID,
      );

      $newPostID = wp_insert_post( $newPost );
      
      return ( is_numeric( $newPostID ) && $newPostID > 0 ) ? (int) $newPostID : false;

    }
    else {
      
      return false;
      
    }
    
  }

}
?>