<?php
/**
 * The MeoReader Feeds API
 *
 * @category    MeoReader
 * @package     Feeds
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Feeds {
  
  /**
   * @var object WordPress database object.
   */
  protected $DB;
  
  /**
   * @var object MeoReader_Categories object for handling the categories.
   */
  protected $CatAPI;
  
  /**
   * @var string $slug Plugin handler.
   */
  protected $slug;
  
  /**
   * @var array $options Plugin options.
   */
  protected $options;
  
  /**
   * @var string $loadExternalFile Method name for loading external files.
   */
  protected $loadExternalFile;

  /**
   * @var array Array of nonces and actions for this class.
   */
  protected $nonceData;
    
  /**
   * The constructor.
   *
   * @param object                $wpdb         WordPress database object.
   * @param MeoReader_Categories  $CatAPI       MeoReader_Categories object.
   * @param string                $pluginSlug   Plugin handler.
   */
  public function __construct( $wpdb, MeoReader_Categories $CatAPI, $pluginSlug ) {

    $this->DB               = $wpdb;
    
    $this->CatAPI           = $CatAPI;
    
    $this->slug             = $pluginSlug;
    
    $this->options          = get_option( $pluginSlug );

    $this->loadExternalFile = $this->setLoadingMethod();

    $this->nonceData        = $this->createNonces();

  }


  /**
   * Detect existing and preferred PHP function for loading external files.
   *
   * @return bool|string  FALSE in case none of the preferred functions are available or the proper METHOD name for implementing the preferred PHP function.
   */
  public function setLoadingMethod() {
    
    /* Using cURL (if available) is the prefered method to load external files. */
    if( function_exists( 'curl_version' ) ) {
      
      return 'loadExternalFile_viaCurl';
      
    }
    /* If cURL is not available use fopen, respectively file_get_conents. */
    else if( ini_get('allow_url_fopen') ) {
      
      return 'loadExternalFile_viaFopen';
      
    }
    /* If both are extensions are not availabel you cannot load external files! */
    else {
      
      return false;
      
    }

  }

  
  /**
   * Get a list of all feeds for a given category (either by ID or by name) or simply all.
   *
   * @param string|int    $cat  (Optional) Either a category ID or a category name or nothing which then will return all feeds, no matter what category they are assigned to.
   * @return bool|array         FALSE in case the list could not be assembled or an array containing the feed list. Note: When there are no feeds but technically the DB query was successful an (empty) array will be returned!
   */
  public function getFeedList( $cat = null ) {
  
    $catID        = ( is_numeric( $cat ) ) ? $cat : $this->CatAPI->getCategoryByName( $cat );
    
    if( $catID == false ) {
      
      return false;
      
    }
    
    $sql          = $this->DB->prepare( 'SELECT id, name, description, xml_url, html_url, last_build_date FROM ' . MEOREADER_TBL_FEEDS . ' WHERE cat_id = %d ORDER BY name ASC', $catID );
    
    $results      = $this->DB->get_results( $sql, ARRAY_A );

    /* @question is it even worth doing a typecasting here? */
    return ( $results === false ) ? false : array_map( create_function( "\$arr", "\$arr['id'] = (int) \$arr['id']; return \$arr;" ), $results );
    
  }

  /**
   * Get a list of all feeds, no matter which category.
   *
   * @return bool|array FALSE in case of a technical/DB error or an array list of all feeds.
   */
  public function getListOfAllFeeds() {

    $sql          = 'SELECT id, name, description, xml_url, html_url, last_build_date FROM ' . MEOREADER_TBL_FEEDS . ' ORDER BY name ASC';
    
    $results      = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( empty ($results ) ) ? false : $results;

  }
  
  
  /**
   * Add a feed to you subscriptions (if it does not already exists).
   *
   * @param string          $feedURL  URL of the RSS feed.
   * @param int             $catID    Category ID.
   * @return bool|int|array           FALSE in case the feed could not be added, or the ID of the newly added feed, or TRUE if you are already subscribing to it.
   */
  public function addFeed( $feedURL, $catID ) {

    $catID    = (int) $catID;

    $feedURL  = trim( $feedURL );

    /* Check if the feed already exists in your list. */
    if( $this->feedExists( $feedURL ) ) {
          
      return array(
        'request' => true,
        'message' => _x( 'You are already subscribing to this feed', 'error message', 'meoreader' )
      );
      
    }

    /* Load the external (RSS feed) file. */
    $xml = $this->loadFeed( $feedURL );

    if( is_array( $xml ) ) {
      
      return $xml;
      
    }
    
    /* Parse the meta data. */
    $feedMeta = $this->parseMetaData(
      $xml,
      array(
        'xml_url' => $feedURL,
        'cat_id'  => $catID
      )
    );

    /* Insert feed into the database */
    $insertStatus = $this->insertFeed( $feedMeta );

    if( $insertStatus !== false ) {
      
      $this->addEntries( $xml, $insertStatus );

      return $insertStatus; // aka new feed ID
      
    }
    else {
      
      return false;
      
    }
    
  }
  
  
  /**
   * Parse/extract certain meta data from an XML feed object.
   *
   * This will be the method to handle all kinds of RSS types.
   *
   * @param   object  $xml        SimpleXML object.
   * @param   array   $moreData   An array whose key/value data will be added to the meta data.
   * @return  array               Array containing the meta data.
   */
  public function parseMetaData( $xml, $moreData = array() ) {
    
    $feedMeta                     = $moreData;

    $feedMeta['title']            = isset( $xml->channel->title)          ? trim( strip_tags( (string) $xml->channel->title ) )        : '';
    
    if( $feedMeta['title'] == '' && isset( $xml->title ) ) {

      $feedMeta['title']          = trim( strip_tags( $xml->title ) );

    }

    /* There are many ways to implement homepage links in RSS */
    $feedMeta['html_url']         = '';
    
    if( isset( $xml->channel->link ) ) {
      $feedMeta['html_url'] = (string) $xml->channel->link;
    }
    elseif( isset( $xml->link ) ) {
      $feedMeta['html_url'] = (string) $xml->link;
    }
    

    /* There are many ways to implement dates in RSS. */
    $feedMeta['last_build_date']  = '';

    if( isset( $xml->channel->lastBuildDate ) ) {

      $feedMeta['last_build_date']  = (string) $xml->channel->lastBuildDate;

    }
    elseif( isset( $xml->pubDate ) ) {

      $feedMeta['last_build_date']  = (string) $xml->pubDate;

    }
    elseif( isset( $xml->updated ) ) {

      $feedMeta['last_build_date']  = (string) $xml->updated;

    }

    $feedMeta['description']      = isset( $xml->channel->description )   ? trim( strip_tags( (string) $xml->channel->description ) )  : '';
    
    return $feedMeta;

  }
  
  
  /**
   * Insert a new feed and therefore "subscribe" to it.
   *
   * @param array $feedMeta An array containing all of the required meta data of a feed.
   * @returns int|bool FALSE if the set could not be inserterd into the database or the (int) ID of the new record.
   */
  public function insertFeed( $feedMeta ) {
    
    $defaults = array(
      'title'           => '',
      'html_url'        => '',
      'xml_url'         => '',
      'last_build_date' => '',
      'description'     => '',
      'cat_id'          => 1
    );
    
    $feedMeta = array_merge( $defaults, $feedMeta );
    
    $status   = $this->DB->insert( 
      MEOREADER_TBL_FEEDS,
      array(
        'cat_id'          => $feedMeta['cat_id'],
        'xml_url'         => $feedMeta['xml_url'],
        'html_url'        => $feedMeta['html_url'],
        'description'     => $feedMeta['description'],
        'last_build_date' => date( 'Y-m-d H:i:s', strtotime( $feedMeta['last_build_date'] ) ),
        'name'            => $feedMeta['title']
      ),
      array(
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s'
      )
    );
    
    return ( $status == true ) ? $this->DB->insert_id : false;
        
  }


  /**
   * Add an entrie to the database.
   * "Entries" are the single items (like blog posts, for example) that are listed in an RSS feed.
   *
   * Since it's not considered an error when an entry already exists in the database the insertion
   * process will simply move on to the next item. As a result, no return value will be returned!
   *
   * @param   object    $xml      A SimpleXML object, representing an XML file.
   * @param   int       $feedID   ID of a feed.
   */
  public function addEntries( $xml, $feedID ) {

    $items        = $this->getItemMeta( $xml );

    /**
     * Insert each entry separately - if it doesn't already exists, of course.
     */
    foreach( $items as $item ) {
      
      if( !$this->entryExists( $item['link'], $item['guid'] ) ) {

        $pubDateStamp   = strtotime( (string) $item['pub_date'] );

        $olderThanStamp = ( time() - ( $this->options['deleteEntriesOlderThan'] * 24 * 60 * 60 ) );

        if( $pubDateStamp < $olderThanStamp ) {
          
          continue;
          
        }

        $status = $this->DB->insert( 
          MEOREADER_TBL_ENTRIES,
          array(
            'feed_id'           => $feedID,
            'pub_date'          => date( 'Y-m-d H:i:s', $pubDateStamp ),
            'description'       => MeoReader_Core::htmLawed_content( $item['description'] ),
            'description_prep'  => MeoReader_Core::purifyContent( $item['description'] ),
            'enclosures'        => $item['enclosures'],
            'link'              => $item['link'],
            'title'             => $item['title'],
            'status'            => 'unread',
            'guid'              => $item['guid']
          ),
          array(
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
          )
        );
        
        /* Update the feed's "last build date" */
        $this->DB->update( 
          MEOREADER_TBL_FEEDS,
          array( 'last_build_date' => date( 'Y-m-d H:i:s', time() ) ),
          array( 'id' => $feedID ),
          array( '%s' ),
          array( '%d' )
        );
        
        /* (Optional) Auto-publish new entries */
        if( isset( $this->options['autopublish'] ) && $this->options['autopublish'] === true ) {
          
          // Each entry should generate a unique post ID to avoid duplicatation in case of auto-posting entries.
          $postEntryID    = md5( strtolower( trim( $item['link'] . $item['guid'] ) ) );
          
          // Avoid duplicate post creation by checking the postEntryID
          if( !$this->entryPostExits( $postEntryID ) ) {
            
            $this->autoCreatePostFromEntry( $item['title'], $item['description'], $item['link'], $this->getFeedName( $feedID ), $postEntryID );
            
          }

        }
      
      }
      
    }
    
  }
  
  
  /**
   * Check if a given post entry ID already exists in the post_meta db table.
   *
   *
   * @param   string  $postEntryID    Post entry ID.
   * @return  bool                    TRUE if the ID already exists or FALSE if that is not the case.
   */
  public function entryPostExits( $postEntryID ) {
    
    global $wpdb;
    
    $sql    = 'SELECT meta_id FROM ' . $wpdb->prefix . "postmeta WHERE `meta_key` = 'postEntryID' AND `meta_value` = '" . $postEntryID . "' LIMIT 1";
    
    $result = $wpdb->get_results( $sql, ARRAY_A );

    return ( empty( $result ) || $result === false ) ? false : true;
    
  }


  /**
   * Auto-Create and publish a post (title + content) from a given feed entry.
   *
   * @param   string    $title          Post/entry title.
   * @param   string    $content        Post/entry content/description.
   * @param   string    $link           Link to the original (external) post.
   * @param   string    $feedName       Name of the feed this post/entry belongs to.
   * @param   string    $postStatus     (Optional) can be 'publish' (=default), 'pending', or 'draft.
   * @param   string    $postEntryID    Unique post/entry ID to avoid duplicate postings.
   */
  public function autoCreatePostFromEntry( $title, $content, $link, $feedName, $postEntryID, $postStatus = 'publish' ) {

    global $current_user;
    
    $postStatus       = strtolower( $postStatus );
    
    $postStatus       = ( preg_match( '#^(publish|pending|draft)$#', $postStatus ) ) ? $postSatus : 'publish';
    
    /* Create the new post */
    $newPost  = array(
      'post_title'    => $title,
      'post_content'  => '<p>' . _x( 'by', 'label', 'meoreader' ) . ' <a href="' . $link . '">' . $feedName . "</a></p>\n" . MeoReader_Core::purifyContent( $content ),
      'post_status'   => 'publish',
      'post_author'   => $current_user->ID
    );

    $newPostID        = wp_insert_post( $newPost );
    
    if( $newPostID > 0 ) {
      
      $status = add_post_meta( $newPostID, 'postEntryID', $postEntryID, true );
      
    }
    
  }

  
  /**
   * Extract/Detect items/entries with all their meta data from an RSS or Atom feed.
   *
   * @param   object  $xml    A SimpleXML object, representing an XML file.
   * @return  array           An array of all the entries that could be found and all their meta data.
   */
  public function getItemMeta( $xml ) {
    
    $maxItems = 30; // @todo settings

    /* RSS */
    if( isset( $xml->channel ) ) {

      /**
       * Create an array for better handling the data.
       */
      foreach( $xml->channel->item as $item ) {

        /* If an entry contains an enclosure tree, create a serialized array out of it. */
        $enclosures = isset( $item->enclosure )         ? $this->createEnclosureData( $item->enclosure )              : '';
        
        $link       = isset( $item->link )              ? trim( strip_tags( (string) $item->link ) )                  : '';

        /* If there is no LINK tag, check if the GUID can be used as a (perma)link */
        if( $link === '' && isset( $item->guid->attributes()->isPermaLink ) && strtolower( (string) $item->guid->attributes()->isPermaLink ) === 'true' ) {

          $link = (string) $item->guid;

        }

        /* Maybe there's an atom:link element? */
        if( $link === '' ) {
      
          foreach( $item->children("atom", true)->link as $linkItem ) {

            $tmp = $linkItem->attributes();

            /**
             * PODLOVE.org is an awesome project about podcast publishing.
             * It uses some thought-through but non-standard ways for certain things.
             */
            if( isset( $tmp['rel'] ) && $tmp['rel'] == 'http://podlove.org/deep-link' ) {
    
              $link = (string) $tmp['href'];
    
              break;
          
            }

          }
      
        }

        
        // Description
        $description = isset( $item->content ) ? (string) $item->content : '';

        // Or maybe <content:encoded> element instead (not RSS spec complient but anyways..)
        if( $description == '' ) {

          $description = isset( $item->children( 'content', true )->encoded ) ? (string) $item->children( 'content', true )->encoded : '';
          
        }

        
        if( $description === '' ) {
          
          $description = isset( $item->description )  ? (string) $item->description : '';
        
        }
        
        // Or mabye iTunes summary
        if( $description === '' ) {
          
          $description = isset( $item->children( 'itunes', true )->summary ) ? (string) $item->children( 'itunes', true )->summary : '';
          
        }
        
        // Always Clear Content Float
        if( $description !== '' ) {
          
          $description .= '<div class="clearAll">&nbsp;</div>' . "\n";
          
        }
        
        // GUID
        $guid = ( isset( $item->guid ) )  ? (string) $item->guid : '';


        $tmp = array(
          'title'             => isset( $item->title )        ? trim( strip_tags( (string) $item->title ) )                 : '',
          'link'              => $link,
          'pub_date'          => isset( $item->pubDate )      ? date( 'Y-m-d H:i:s', strtotime( (string) $item->pubDate ) ) : '',
          'description'       => $description,
          'enclosures'        => $enclosures,
          'guid'              => $guid
        );

        $items[] = $tmp;
        
        if( count( $items ) === $maxItems ) {
          break;
        }

      }
    
    }
    /* ATOM */
    elseif( isset( $xml->entry ) ) {

      /**
       * Create an array for better handling the data.
       */
      foreach( $xml->entry as $item ) {

        /* If an entry contains an enclosure tree, create a serialized array out of it. */
        $enclosures = isset( $item->enclosure )         ? $this->createEnclosureData( $item->enclosure )                : '';
        
        /* Description can either be a summary or even better, a content element */
        /* summary elements by containn CDATA wrapped content. If so: filer! */
        $description = '';
        
        if( isset( $item->summary ) ) {
          $description = (string) $item->summary;
        }

        if( isset( $item->content ) ) {
          $description = (string) $item->content;
        }
        

        // Always Clear Content Float
        if( $description !== '' ) {
          
          $description .= '<div class="clearAll">&nbsp;</div>' . "\n";
          
        }

        /* Entry Date */
        $pubDate = '';
        
        if( isset( $item->published ) ) {
          $pubDate = date( 'Y-m-d H:i:s', strtotime( (string) $item->published ) );
        }
        
        if( isset( $item->updated ) ) {
          $pubDate = date( 'Y-m-d H:i:s', strtotime( (string) $item->updated ) );
        }
        
        /* GUID */
        $guid = isset( $item->id  ) ? (string) $item->id  : '';

        $items[] = array(
          'title'             => isset( $item->title )        ? trim( strip_tags( (string) $item->title ) )                   : '',
          'link'              => isset( $item->link['href'] ) ? trim( strip_tags( (string) $item->link['href'] ) )            : '',
          'pub_date'          => $pubDate,
          'description'       => $description,
          'enclosures'        => $enclosures,
          'guid'              => $guid
        );

        if( count( $items ) === $maxItems ) {
          break;
        }

      }
    
    }
    else {
      
      return array();
      
    }
    
    return $items;

  }
  
  
  /**
   * (Potentially) Create an enclosure data set.
   *
   * In the context of an RSS feed "enclosures" are usually certain types of media.
   * In the case of a podcast feed this might be audio files (respectively URLs) but in terms of
   * specs this could be anything from a PDF to a video etc...
   *
   * And since there can be multiple (types of) enclosures as well as none,
   * create an array of all detected enclosures and serialize it for storing them in the db.
   *
   * @param   array   $enclosures   An array containing an XML object - the RSS enclosure tree.
   * @return  string                A serialized array of enclosure data.
   */
  public function createEnclosureData( $enclosures = array() ) {

    if( empty( $enclosures ) ) {

      return '';

    }
    
    $data   = array();

    foreach( $enclosures as $enclosure ) {
          
      $url      = isset( $enclosure->attributes()->url )  ? (string) $enclosure->attributes()->url  : '';
          
      $type     = isset( $enclosure->attributes()->type ) ? (string) $enclosure->attributes()->type : '';
      
      $filename = '';
      
      /* Also get rid of possible URL parameters and anchor fragments when showing only the filename. */
      if( $url !== '' ) {
        
        $tmp      = parse_url( $url );
      
        if( isset( $tmp['query'] ) ) {
          
          $filename = str_replace( '?' . $tmp['query'], '', basename( $url ) );
        
        }
      
        if( isset( $tmp['fragment'] ) ) {
          
          $filename = str_replace( '#' . $tmp['fragment'], '', $filename );

        }
      
        $filename = ( $url !== '' ) ? basename( $url ) : '';
      
      }

      $data[] = array(
        'url'       => $url,
        'type'      => $type,
        'filename'  => $filename
      );

    }
    
    return serialize( $data );

  }
  
  /**
   * Check wether an entry (respectively it's URL) already exists in the database.
   *
   * The problem is that there is only a best practive to provice a (unique) link.
   * But that's not a requirement. At least 99% of RSS generators provide either a link
   * or a guid for each entry and so it makes sense to potentially check both.
   *
   * @param   string    $link       URL the enty is linking to for viewing the original post.
   * @param   string    $guid       (Optional) GUID of an entry.
   * @param   bool      $returnID   If TRUE returns the ID of an existing entry.
   * @return  bool|int              TRUE if the entry already exists or FALSE in case it does not. Or the INT ID of the existing entry.
   */
  public function entryExists( $link, $guid = '', $returnID = false ) {
    
    $link = trim( $link );

    $guid = trim( $guid );

    if( $link === '' && $guid === '' ) {
      
      return true;
      
    }

    /* Only check for existing links */
    if( $guid == '' ) {
    
      $sql    = $this->DB->prepare( 'SELECT id FROM ' . MEOREADER_TBL_ENTRIES . ' WHERE link = %s LIMIT 1', $link );
    
    }
    /* Check links as well as the guid for existence. */
    else {

      if( $link !== '' ) {
        
        $sql    = $this->DB->prepare( 'SELECT id FROM ' . MEOREADER_TBL_ENTRIES . ' WHERE link = %s AND guid = %s LIMIT 1', $link, $guid );
      
      }
      else {
        
        $sql    = $this->DB->prepare( 'SELECT id FROM ' . MEOREADER_TBL_ENTRIES . ' WHERE guid = %s LIMIT 1', $guid );
      
      }

    }

    $results  = $this->DB->get_results( $sql, ARRAY_A );

    if( $returnID === true ) {
      
      return ( empty( $results ) ) ? false : $results[0]['id'];
    
    }
    else {

      return ( empty( $results ) ) ? false : true;
    
    }
    
  }
  
  /**
   * Delete a specific feed.
   *
   * @param   int   $feedID   ID of the feed that shall be deleted.
   * @return  bool            TRUE if the feed (and all its assigned entries) have been deleted or FALSE if that was not possible.
   */
  public function deleteFeed( $feedID ) {
    
    $feedID = (int) $feedID;
    
    /**
     * Before actually deleting a feed, delete all entries that are currently assigned to it to this feed.
     */
    $sql      = $this->DB->prepare( 'DELETE FROM ' . MEOREADER_TBL_ENTRIES . ' WHERE feed_id = %d', $feedID );

    $result   = $this->DB->query( $sql );

    /**
     * Now you can delete the feed itself.
     */
    $sql      = $this->DB->prepare( 'DELETE FROM ' . MEOREADER_TBL_FEEDS . ' WHERE id = %d LIMIT 1', $feedID );
    
    $result   = $this->DB->query( $sql );
    
    // @todo How to behave if deleting all entries already failed?! Or even worth: The entries are deleted but not the feed itself?!
    return ( $result == true ) ? true : false;
    
  }
  
  /**
   * Move a feed to another category.
   *
   * @param   int   $feedID   ID of the feed that shall be moved.
   * @param   int   $catID    ID of the category this feed shall be moved to.
   * @return  bool            TRUE if the feed could be moved or FALSE if that was not the case.
   */
  public function moveFeed( $feedID, $catID ) {
    
    $feedID = (int) $feedID;
    
    $catID  = (int) $catID;
    
    if( !$this->CatAPI->categoryExists( $catID ) || !$this->feedExists( $feedID ) ) {
      
      return false;
      
    }

    /**
     * In case the "new" category is actually the "old" category, still run the db update
     * since there is no harm done.
     */
    $status = $this->DB->update(
      MEOREADER_TBL_FEEDS,
      array(
        'cat_id'  => $catID
      ),
      array(
        'id'      => $feedID
      ),
      array(
        '%d'
      ),
      array(
        '%d'
      )
    );
    
    return ( $status == false ) ? false : true;    
    
  }
  
  /**
   * Check wether a secific feed (by ID or by name) arealy exists.
   *
   * @param   int|string  $feed   Either (int) ID or (string) name of the feed that might potentially already exist.
   * @return  bool                TRUE if the feed ID exists or FALSE if it does not.
   */
  public function feedExists( $feed ) {
    
    /* $feed is feed ID */
    if( is_numeric( $feed  ) ) {

      $feedID = (int) $feed;
    
      $sql    = $this->DB->prepare( 'SELECT id FROM ' . MEOREADER_TBL_FEEDS . ' WHERE id = %d LIMIT 1', $feedID );
    
      $result = $this->DB->get_results( $sql, ARRAY_A );
    
      return ( empty( $result ) ) ? false : true;    

    }
    /* $feed is feed_URL */
    else {
    
      $feedURL = (string) $feed;

      /**
       * If a given URL does not end like a filename but like a directory (or permalink)
       * and yet the last character is not a (URL) directory separator
       * check your subscriptions for a forward-slash-ending URL as well an "open" URL.
       */
      if( !preg_match( '#\.[a-z]{3}$#i', $feedURL ) && substr( $feedURL, -1 ) !== '/' ) {
      
        $sql  = $this->DB->prepare( "SELECT `id` FROM `" . MEOREADER_TBL_FEEDS . "` WHERE `xml_url` = %s OR `xml_url` = %s ", $feedURL, $feedURL . '/' );

      }
      else {

        $sql  = $this->DB->prepare( "SELECT `id` FROM `" . MEOREADER_TBL_FEEDS . "` WHERE `xml_url` = %s ", $feedURL );

      }
    
      $sql    .= ' LIMIT 1;';

      $result  = $this->DB->get_results( $sql, ARRAY_A );

      return ( empty( $result ) ) ? false : true;

    }
    
  }


  /**
   * Get the name of a feed for a given feed ID.
   *
   * @param   int           $feedID   ID of the feed whose name shall be returned.
   * @return  bool|string             FALSE in case there is no feed with the given ID OR the name of the feed.
   */
  public function getFeedName( $feedID ) {
    
    $feedID = (int) $feedID;
    
    $sql    = $this->DB->prepare( 'SELECT name FROM ' . MEOREADER_TBL_FEEDS . ' WHERE id = %d LIMIT 1', $feedID );
    
    $result = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( empty( $result ) ) ? false : $result[0]['name'];
    
  }
  

  /**
   * Get a list of all feed - including their meta data.
   *
   * @return bool|array FALSE in case of a technical/db error or an array of all feeds.
   */
  public function getAllFeeds() {
    
    $sql      = 'SELECT id, name, description, xml_url, html_url, last_build_date FROM ' . MEOREADER_TBL_FEEDS . ' ORDER BY name ASC';
    
    $results  = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( empty( $results ) ) ? false : $results;
    
  }
  
  
  /**
   * Load an external feed and return an XML object for further processing/parsing.
   *
   * @param   string        $feedURL    A string containing the URL of the RSS feed.
   * @return  array|object              Returns either an error array or an SimpleXML object.
   */
  public function loadFeed( $feedURL ) {
    
    $args       = $this->validate_loadFeedArguments( $feedURL, $this->options['timeout'] );

    /* URLs have to begin with http:// or https:// */
    if( !preg_match( '#^(http|https)://#i', $args['feedURL'] ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Invalid or Missing Feed URL', 'error message', 'meoreader' )
      );
      
    }
    
    /* If your system does not allow you to load external files quit here */
    if( $this->loadExternalFile === false ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Your system does not allow you to load external files like RSS feeds!', 'error message', 'meoreader' )
      );

    }

    $method     = $this->loadExternalFile;

    $xmlString  = $this->$method( $args['feedURL'], $args['timeout'] );

    if( is_array( $xmlString ) ) {
      
      return array(
        'request' => false,
        'message' => isset( $xmlString['message'] ) ? $xmlString['message'] : '',
        'status'  => isset( $xmlString['status'] )  ? $xmlString['status']  : 0
      );
      
    }

    /* Charset/Encoding detection - if possible */
    $encoding = ( preg_match( '#encoding="([^"]+)"#uim', $xmlString, $match ) ) ? strtoupper( $match[1] ) : 'UTF-8';

//    $dom = new DOMDocument( '1.0', $encoding );
    $dom = new DOMDocument( '1.0', $encoding );

    $dom->recover = TRUE;

    $dom->loadXML( $xmlString );

    $test = (string) $dom->saveXML();

    // Create XML Object
    $xml          = @simplexml_load_string( $test );
    
    if( $xml === false ) {

      return array(
        'request' => false,
        'message' => _x( 'Could not create an XML object from the feed. Maybe the feed is broken or not well-formed!', 'error message', 'meoreader' )
      );
      
    }
    else {
      
      return $xml;
      
    }
    
  }
  
  
  /**
   * Normalize the pair of arguments $feedURL and $timeout.
   *
   * @param   string  $feedURL    The URL of the targeted feed.
   * @param   int     $timeout    Number of seconds until a waiting connection will be quit.
   * @return  array               An array of the normalized data.
   */
  public function validate_loadFeedArguments( $feedURL, $timeout ) {
    
    $feedURL = trim( strip_tags( $feedURL ) );
    
    $timeout = (int) $timeout;
    
    $timeout = abs( $timeout );
    
    return array(
      'feedURL' => $feedURL,
      'timeout' => $timeout
    );

  }
  
  /**
   * Load an external file via cURL.
   *
   * @param   string  $feedURL    URL of the RSS feed to be loaded.
   * @param   int     $timeout    Number of seconds until a waiting connection will be quit.
   * @return  mixed               A string of the loaded file in case everything went fine. Or an error array containing either an error message or the HTTP status code of the bad request.
   */
  public function loadExternalFile_viaCurl( $feedURL, $timeout ) {
    
    $args           = $this->validate_loadFeedArguments( $feedURL, $timeout );

    $ch             = curl_init();

    curl_setopt( $ch, CURLOPT_URL, $args['feedURL'] );

    curl_setopt( $ch, CURLOPT_TIMEOUT, $args['timeout'] );

    /*  Also follow HTTP redirects (like 301s and 302s). */
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    
    /* If URL should be connected to via SSL */
    if( preg_match( '#^https://#i', $feedURL ) ) {
    
      curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
      curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
    
    }

    /* The content */
    $data           = curl_exec( $ch );

    $httpStatusCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

    $curlError      = curl_errno( $ch );

    curl_close( $ch );

    /**
     * Timeout Error.
     * This could also mean that there are too many redirects that
     * cannot all be followed within the timeout timeframe.
     */
    if( $curlError == 28 ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Timout error. Cannot reach targeted server!', 'error message', 'meoreader' )
      );
      
    }

    /* If everything is fine return a string containing the plain (XML) text. */
    if( $httpStatusCode == 200 ) {
      
      return $data;
      
    }
    // @changelog 2014-10-02 19:30
    elseif( $httpStatusCode === 301 || $httpStatusCode === 302 ) {
      
      $ch = curl_init($feedURL);
      
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      
      $data = $this->curl_exec_follow($ch);
      
      $httpStatusCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
      
      $curlError      = curl_errno( $ch );
      
      curl_close($ch);
      
      if( $httpStatusCode === 200 ) {
        return $data;
      }
      else {
        return array('request'=>false,'status'=>$httpStatusCode);
      }
      
    }
    /* If not, return an array containing the HTTP status code. */
    else {
      
      return array(
        'request' => false,
        'status'  => $httpStatusCode
      );
      
    }

  }


  /**
   * Load an external file via fOpen, respectively the simplified verision, file_get_contents()
   *
   * @param   string  $feedURL    URL of the RSS feed to be loaded.
   * @param   int     $timeout    Number of seconds until a waiting connection will be quit.
   * @return  mixed               A string of the loaded file in case everything went fine. Or an error array containing either an error message or the HTTP status code of the bad request.
   */
  public function loadExternalFile_viaFopen( $feedURL, $timeout ) {

    $args           = $this->validate_loadFeedArguments( $feedURL, $timeout );

    $httpStatusCode = 0;
    
    /* Try reading the external file */
    $data =  file_get_contents(
      $args['feedURL'],
      false,
      stream_context_create(
        array(
          'timeout' => $args['timeout']
        )
      )
    );
    
    /* In case of a technical */
    if( $data === false ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Cannot read feed due some technical error.', 'error message', 'meoreader' )
      );

    }
    
    /* Detect the "final" HTTP status code */
    foreach( $http_response_header as $key => $value ) {
      
      if( preg_match( '#^http\/[0-9]\.[0-9] ([0-9]{1,3})#i', $value, $match ) ) {
        
        $httpStatusCode = (int) $match[1];
        
      }
      
    }
    
    /**
     * If the HTTP status code 200 (OK) could be found (even as final redirect)
     * return a string containing the plain (XML) text.
     */
    if( $httpStatusCode == 200 ) {
      
      return $data;
      
    }
    /**
     * There seem to be all kinds of potential error but no final 200 OK.
     * Therefore return an error.
     */
    else {
    
      return array(
        'request' => false,
        'status'  => $httpStatusCode
      );
   
    }

  }
  
  
  /**
   * Update/Refresh a feed - by its ID.
   *
   * @param   int         $feedID   ID of the feed to be refetched.
   * @return  bool|array            TRUE if the feed could be updated or an array containing information about the failure of the operation.
   */
  public function updateFeed( $feedID ) {
    
    $feedID = (int) $feedID;

    /* Get the feed for the given feed ID. */
    $feed = $this->getFeedById( $feedID );

    if( $feed === false ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Cannot find Feed!', 'error message', 'meoreader' )
      );
      
    }

    /**
     * If method is cURL perform a HTTP Head Request first and only update the file if it has been modified since the last check!
     * In case the feed has been updated this would be a reasonable overhead.
     * In case the feed has NOT been updated since the laste refresh this will be a performance boos for you and will save
     * the feed provider some uneccessary traffic.
     */
    if( ( $this->loadExternalFile === 'loadExternalFile_viaCurl' ) && $this->feedHasBeenModified( $feed['xml_url'], $feed['last_build_date'] ) === false ) {
      
      return true;
      
    }

    /* Load the feed. */
    $xml    = $this->loadFeed( $feed['xml_url'] );

    if( is_array( $xml ) ) {
      
      return array(
        'request' => false,
        'message' => _x( 'Cannot load Feed from URL', 'error message', 'meoreader' )
      );
      
    }
    
    /* Add entries of the newly fetched feed - if they're not older than {pluginSettings} days. */
    $status = $this->addEntries( $xml, $feedID );
    
    return true;
    
  }


  /**
   * Helper: Get all the meta data of a single feed by its ID
   *
   * @param   int $feedID   The numerical feed ID.
   * @return  array|bool    An array with all the db fieds as array keys. Or FALSE in case the feed (ID) could not be found.
   */
  public function getFeedByID( $feedID ) {
    
    $feedID   = (int) $feedID;
    
    $sql      = $this->DB->prepare( "SELECT * FROM `" . MEOREADER_TBL_FEEDS . "` WHERE `id` = %d LIMIT 1", $feedID );
    
    $results  = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( empty( $results ) || !isset( $results[0] ) ) ? false : $results[0];
    
  }


  /**
   * Check if a feed has even been modified since the last refresh.
   * Therefore perfom a HTTP Head Request - which at this point is only available
   * if cURL is enabled in your environment.
   *
   * @param   string  $feedURL      The feed URL.
   * @param   string  $lastUpdate   The UNIX timestamp of the last time the feed has been refreshed.
   * @return                        TRUE if no "last modified" meta data has been found or the feed has been modified. FALSE if the feed has not been modified since.
   */
  public function feedHasBeenModified( $feedURL, $lastUpdate ) {
    
    $timeout  = isset( $this->options['timeout'] ) ? (int) $this->options['timeout'] : 15;
    
    /* The CURL Request */
    $ch             = curl_init();

    curl_setopt( $ch, CURLOPT_URL, $feedURL );
    
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    /* Also follow HTTP redirects (like 301s and 302s). */
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

    curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );

    /* If URL should be connected to via SSL */
    if( preg_match( '#^https://#i', $feedURL ) ) {
    
      curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
      curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
    
    }

    /* Setting up the HEAD Request */
    curl_setopt( $ch, CURLOPT_NOBODY, true );

    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'HEAD' );

    curl_setopt( $ch, CURLOPT_HEADER, 1 );


    /* The Answer */
    $data           = curl_exec( $ch );
    
    $httpStatusCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

    $curlError      = curl_errno( $ch );

    curl_close( $ch );

    
    /* Check the dates */

    $checkLastModified  = preg_match( '#last-modified:([^\n]*)\n#im', $data, $match );

    if( $checkLastModified !== false && isset( $match[1] ) ) {
  
      $lastModified     = trim( $match[1] );

      /* Detect the time zone */
      $timezone 	      = trim( strrchr( $lastModified, " " ) );
  
      if( !preg_match( '#[a-z]#i', $timezone ) ) {
    
        return false;
      }
  
      /* Create UNIX timestamp from the last_modified_date */
      $lastModDate      = strtotime( $lastModified );

      $Date             = new DateTime( $lastModified, new DateTimeZone( $timezone ) );
      
      /* Convert datetime to UNIX timestamp */
      if( !preg_match( '#^[0-9]+$#', $lastUpdate ) ) {
        
        $lastUpdate = strtotime( $lastUpdate );
        
      }

      return ( $lastUpdate < $Date->getTimestamp() ) ? true : false;
  
    }

    return true;

  }

  /**
   * Get a feed with all its meta data by a given URL
   *
   * @param   string      $feedURL    Feed URL
   * @return  array|bool              FALSE if the URL does not exist (yet) or an array containing all the meta data.
   */
  public function getFeedByURL( $feedURL ) {
    
    $feedURL  = $this->DB->prepare( '%s', $feedURL );
    
    $sql      = 'SELECT * FROM ' . MEOREADER_TBL_FEEDS . " WHERE xml_url = " . $feedURL . " LIMIT 1";
    
    $results  = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( $results === false || !isset( $results[0] ) ) ? false : $results[0];
    
  }

  
  /**
   * Helper: Get the feed URL for a given feed ID.
   *
   * @param   int         $feedID   ID of the feed whose URL is of interest.
   * @return  bool|string           FALSE in case of a technical/DB error or the URL of the feed.
   */
  public function getFeedURLByID( $feedID ) {
    
    $feedID   = (int) $feedID;
    
    $sql      = $this->DB->prepare( 'SELECT xml_url as url FROM ' . MEOREADER_TBL_FEEDS . ' WHERE id = %d LIMIT 1', $feedID );
    
    $results  = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( empty( $results ) ) ? false : $results[0]['url'];

  }

  /**
   * Helper: Get the homepage URL for a given feed ID.
   *
   * @param   int         $feedID   ID of the feed whose URL is of interest.
   * @return  bool|string           FALSE in case of a technical/DB error or the URL of the feed.
   */
  public function getHomepageURLByID( $feedID ) {
    
    $feedID   = (int) $feedID;
    
    $sql      = $this->DB->prepare( 'SELECT html_url as url FROM ' . MEOREADER_TBL_FEEDS . ' WHERE id = %d LIMIT 1', $feedID );
    
    $results  = $this->DB->get_results( $sql, ARRAY_A );
    
    return ( empty( $results ) ) ? false : $results[0]['url'];

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

    $data[] = MeoReader_Core::createNonceData( 'meoReader_addFeed' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_addEntries' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_deleteFeed' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_moveFeed' );

    $data[] = MeoReader_Core::createNonceData( 'meoReader_updateFeed' );
    
    return $data;
    
  }

  
  /**
   * Delete all feeds at once.
   * 
   * @return bool FALSE in case there was a problem with detecting the feeds or TRUE if the feeds could be deleted.
   */
  public function deleteAllFeeds() {
    
    $feeds = $this->getListOfAllFeeds();
    
    if( $feeds === false ) {
      
      return false;
      
    }
    
    foreach( $feeds as $feed ) {
      
      $this->deleteFeed( $feed['id'] );

    }
    
    return true;
    
  }

  /**
   * Add archive entries or in case an entry allready exists
   * make sure it's archive state is set to 1.
   *
   * @param   array $entries    List of entries that shall be added to the archive.
   * @return  bool              Always TRUE - at least unti I can think of a nice way for handling errors.
   */
  public function addArchiveEntries( $entries ) {

    foreach( $entries as $entry ) {
      
      $base     = base64_decode( $entry );

      $data     = json_decode( $base, true );

      $entryID  = $this->entryExists( $data['link'], $data['guid'], true );

      /* If the entry does not exist yet, add it to the DB */
      if( $entryID === false ) {

        $feed = $this->getFeedByURL( $data['feed_xml_url' ] );
        
        if( $feed == false ) {
          
          continue;
          
        }


        /**
         * I'm not sure yet why this is...
         * But it seems that when an archive entry is older than
         * what is set in the settings,
         * the base encoded entry has to be unwrapper again!
         */
        if( !is_array( $entry ) ) {
  
          $entry = base64_decode( $entry );
  
          $entry = json_decode( $entry, true );

        }

        /* Use the title as metric of the meta data for this entry really exists! */
        if( !isset( $entry['title'] ) ) {
    
          return false; // @todo or true?!
    
        }


        $status = $this->DB->insert( 
          MEOREADER_TBL_ENTRIES,
          array(
            'feed_id'           => $feed['id'],
            'pub_date'          => $entry['pub_date'],
            'description'       => $entry['description'],
            'description_prep'  => $entry['description_prep'],
            'enclosures'        => $entry['enclosures'],
            'link'              => $entry['link'],
            'title'             => $entry['title'],
            'status'            => 'read',
            'guid'              => $entry['guid'],
            'archive'           => 1
          ),
          array(
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d'
          )
        );

      }
      /**
       * If the entry already exists (e.g. because it has been added when the feed has been added)
       * make sure it's marked as an "archive" entry!
       */
      else {
      
        $status = $this->DB->update( 
          MEOREADER_TBL_ENTRIES,
          array(
            'archive' => 1,
            'status'  => 'read'
          ),
          array( 'id' => $entryID ),
          array( '%d', '%s' ),
          array( '%d' )
        );

      }

    }
    
    return true;

  }



  // @changelog 2014-10-02 19:30
  // http://slopjong.de/2012/03/31/curl-follow-locations-with-safe_mode-enabled-or-open_basedir-set/
  public function curl_exec_follow( $ch, &$maxredirect = null ) {
    
    $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0";

    curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );

    $mr = $maxredirect === null ? 5 : intval($maxredirect);

    if (filter_var(ini_get('open_basedir'), FILTER_VALIDATE_BOOLEAN) === false
      && filter_var(ini_get('safe_mode'), FILTER_VALIDATE_BOOLEAN) === false
    ) {

      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
      curl_setopt($ch, CURLOPT_MAXREDIRS, $mr );
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    }
    else {
    
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

      if ($mr > 0) {
      
        $original_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        $newurl       = $original_url;
      
        $rch          = curl_copy_handle($ch);
      
        curl_setopt($rch, CURLOPT_HEADER, true);
        curl_setopt($rch, CURLOPT_NOBODY, true);
        curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
        
        do {
        
          curl_setopt($rch, CURLOPT_URL, $newurl);
        
          $header = curl_exec($rch);
        
          if (curl_errno($rch)) {
            
            $code = 0;
        
          }
          else {
          
            $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
            
            if ($code == 301 || $code == 302) {
            
              preg_match('/Location:(.*?)\n/i', $header, $matches);
              
              $newurl = trim(array_pop($matches));
            
              // if no scheme is present then the new url is a
              // relative path and thus needs some extra care
              if(!preg_match("/^https?:/i", $newurl)){
                
                $newurl = $original_url . $newurl;
              }

            }
            else {
            
              $code = 0;
            
            }
          
          }
        
        } while ($code && --$mr);
      
        curl_close($rch);
      
        if (!$mr) {
        
          if ($maxredirect === null)
            trigger_error('Too many redirects.', E_USER_WARNING);
    
          else
            $maxredirect = 0;
        
          return false;
    
        }
    
        curl_setopt($ch, CURLOPT_URL, $newurl);
    
      }
    
    }
    
    return curl_exec($ch);
  
  }

}
?>