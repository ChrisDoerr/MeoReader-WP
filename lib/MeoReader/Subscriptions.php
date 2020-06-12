<?php
/**
 * The MeoReader Subscriptions API
 *
 * @category    MeoReader
 * @package     Subscriptions
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
class MeoReader_Subscriptions {
  
  /**
   * @var object MeoReader_Category object.
   */
  protected $CatAPI;
  
  /**
   * @var object MeoReader_Feed object.
   */
  protected $FeedAPI;
  
  /**
   * The constructor.
   *
   * @param MeoReader_Categories  $CatAPI     Category API object.
   * @param MeoReader_Feeds       $FeedAPI    Feed API object.
   */
  public function __construct( $CatAPI, $FeedAPI ) {
    
    $this->CatAPI     = $CatAPI;
    
    $this->FeedAPI    = $FeedAPI;
  
  }
    

  /**
   * Get a list of all subscriptions (categories + their feeds).
   *
    @return array   An array containing all subscriptions (categories + their feeds).
   */
  public function getSubscriptions() {
    
    $subscriptions  = array();
    
    $categories     = $this->CatAPI->getCategoryList();
    
    foreach( $categories as $category ) {
      
      $subscriptions[]  = array(
        'id'    => $category['id'],
        'name'  => $category['name'],
        'items' => $category['items'],
        'feeds' => $this->FeedAPI->getFeedList( $category['id'] )
      );
      
    }
    
    return $subscriptions;
    
  }

}
?>