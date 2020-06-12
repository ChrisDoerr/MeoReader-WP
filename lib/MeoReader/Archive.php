<?php
/**
 * The MeoReader Archive API
 *
 * @category    MeoReader
 * @package     Archive
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Archive {

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
   * Toggle the archive state of an item.
   *
   * All items have an archive attribute.
   * If it is set to 1 it will be treaded as it were move to the archive.
   * It then will not be deleted when deleting entries that are older than {plugin-setting} days, for example.
   * Archive items also only appear on the archive page, not the READER.
   *
   * @param   int   $entryID    ID of the entry whose archive attribute should be toggled from 0 > 1 or 1 > 0.
   * @return  bool              TRUE of the archive state could be changed or FALSE if a technical/db error has occured.
   */
  public function toggleArchiveItem( $entryID ) {
    
    $entryID          = (int) $entryID;

    /* The new state is opposed to the current one */
    $nextArchiveState = ( $this->getArchiveState( $entryID ) == 1 ) ? 0 : 1;
    
    /* Set the entry's new archive state */
    $status           = $this->DB->update( 
      MEOREADER_TBL_ENTRIES,
      array(
        'archive'     => $nextArchiveState
      ),
      array(
        'id'          => $entryID
      ),
      array(
        '%d'
      ),
      array(
        '%d'
      )
    );

    return ( $status === false ) ? false : true;

  }
  
  /**
   * Get the archive state of an entry (by ID).
   *
   * @param   int $entryID    ID of the entry whose archive state (1 or 0) is to be detected.
   * @return  int             Either 0 (meaning it's not an archive item) or 1 (meaning it is an archive item).
   */
  public function getArchiveState( $entryID ) {
    
    $entryID  = (int) $entryID;

    $sql      = $this->DB->prepare( 'SELECT archive FROM ' . MEOREADER_TBL_ENTRIES . ' WHERE id = %d LIMIT 1', $entryID );
    
    $results  = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( isset( $results[0]['archive'] ) && $results[0]['archive'] == 1 ) ? 1 : 0;
    
  }

  /**
   * Get archive entries.
   *
   * @param   int         $pageNr           The current page number will be used to retrieve blocks of data from the database instead of all at once.
   * @param   int         $entriesPerPage   (Optional) Then number of entries that shall be retrieved. Will also be used to get blocks of data instead of all at once. Default is 30.
   * @return  bool|array                    FALSE in case no data was retrieved or a technical/db error has occured or an array of archive entries.
   */
  public function getArchiveEntries( $pageNr, $entriesPerPage = 30 ) {
    
    $pageNr         = (int) $pageNr;
    
    $pageNr         = abs( $pageNr );
    
    if( $pageNr == 0 ) {

      $pageNr       = 1;

    }
    
    $entriesPerPage = (int) $entriesPerPage;

    $offset         = ( $pageNr -1 ) * $entriesPerPage;
    
    $sql            = '   SELECT entries.id,';
    $sql           .= '          entries.guid,';
    $sql           .= '          entries.pub_date,';
    $sql           .= '          entries.description,';
    $sql           .= '          entries.description_prep,';
    $sql           .= '          entries.enclosures,';
    $sql           .= '          entries.link,';
    $sql           .= '          entries.status,';
    $sql           .= '          entries.title,';
    $sql           .= '          entries.archive,';      
    $sql           .= '          feeds.name as feed_name,';
    $sql           .= '          feeds.html_url as feed_html_url,';
    $sql           .= '          feeds.xml_url as feed_xml_url';
    $sql           .= '     FROM ' . MEOREADER_TBL_ENTRIES . ' as entries,';
    $sql           .= '          ' . MEOREADER_TBL_FEEDS . ' as feeds';
    $sql           .= "    WHERE entries.feed_id = feeds.id";
    $sql           .= "      AND entries.archive = '1'";
    $sql           .= ' ORDER BY entries.pub_date DESC';
    
    if( $entriesPerPage > 0 ) {
      
      $sql           .= '    LIMIT ' . $offset . ', ' . $entriesPerPage;
    
    }
    
    $results        = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( $results !== false ) ? $results : false;

  }
  
  /**
   * Count the number of archive entries.
   *
   * @return int Total number of archive elements.
   */
  public function getArchiveTotal() {
    
    $results = $this->DB->get_results( 'SELECT COUNT(*) as archive_total FROM ' . MEOREADER_TBL_ENTRIES . " WHERE archive = '1'", ARRAY_A );
    
    return ( empty( $results ) ) ? 0 : $results[0]['archive_total'];

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

    $data[] = MeoReader_Core::createNonceData( 'meoReader_toggleArchiveItem' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_removeFromArchive' );
    
    return $data;
    
  }

}
?>