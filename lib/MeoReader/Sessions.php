<?php
class MeoReader_Sessions extends Meomundo_WP {
  
  protected $DB;
  
  protected $tbl_sessions;
  
  protected $tbl_feeds;
  
  public function __construct( $wpdb ) {
    
    $this->DB           = $wpdb;
    
    $this->Request      = $Request;
    
    $this->tbl_sessions = $wpdb->prefix . 'meoreader_sessions';
    
    $this->tbl_feeds    = $wpdb->prefix . 'meoreader_feeds';
    
  }

  public function getCurrentSession() {
    
    $query  = 'SELECT id FROM ' . $this->tbl_sessions . " WHERE status = 'open' LIMIT 1";
    
    $result = (int) $this->DB->get_var( $query );

    if( $result > 0 ) {
      
      return array(
        'request'     => true,
        'session_id'  => $result
      );
      
    }
    else {
      
      return $this->createNewSession();
      
    }
    
  }



  public function closeAllSessions() {

    // @todo also delete session log!!!!??
    $status['closed']  = $this->DB->update(
      $this->tbl_sessions,
      array(
        'status'  => 'closed',
        'end'     => current_time( 'mysql' )
      ),
      array(
        'status'  => 'open'
      )
    );

    $status['zero'] = $this->DB->query( "UPDATE {$this->tbl_feeds} SET session_id = '0' ");

  }

  public function createNewSession() {
    
    $this->closeAllSessions();

    $status = $this->DB->insert(
      $this->tbl_sessions,
      array(
        'status'  => 'open',
        'start'   => current_time( 'mysql' )
      )
    );

    if( $status === false ) {
      
      return array(
        'request' => false,
        'message' => 'Could not create a new session!'
      );
      
    }
    
    $sessionID = $this->DB->insert_id;
    
    return array(
      'request'     => true,
      'session_id' => $sessionID
    );
    
  }


  public function getNextItem( $sessionID ) {
    
    $sessionID  = (int) $sessionID;
    
    $query      = 'SELECT id, xml_url, last_build_date FROM ' . $this->tbl_feeds . ' WHERE session_id = 0 LIMIT 1';
    
    $result     = $this->DB->get_row( $query, ARRAY_A );
    
    return $result;
    
  }
  
  public function updateFeed( $feedID, $sessionID ) {

    $status = $this->DB->update(
      $this->tbl_feeds,
      array(
        'session_id' => $sessionID
      ),
      array( 'id' => $feedID ),
      array( '%d' ),
      array( '%d' )
    );
    
    return ( $status === false ) ? false : true;

  }
  
  
  public function closeSession( $sessionID ) {
    
    $status = $this->DB->update(
      $this->tbl_sessions,
      array(
        'status'  => 'closed',
        'end'     => current_time( 'mysql' )
      ),
      array( 'id' => $sessionID ),
      array( '%s', '%s' ),
      array( '%d' )
    );
    
    return ( $status === false ) ? false : true;    
    
  }
  
}
?>