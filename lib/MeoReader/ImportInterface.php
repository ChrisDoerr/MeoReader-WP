<?php
/**
 * The MeoReader Import Interface
 *
 * Implement this interface in our custom import classes.
 * (Also extend it from the MeoReader_Import class).
 *
 * @category    MeoReader
 * @package     Import
 * @copyright   Copyright (c) 2013 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */
interface MeoReader_ImportInterface {

  /**
   * Do what's necessaray to extract the data that shall be imported
   * from the uploaded file and create an SimpleXML object out out it.
   *
   * @return object SimpleXML object.
   */
  public function loadXMLFile();

  /**
   * Map the XML content into a "standardized" array that can be handled by the plugin's import handler.
   *
   * @param   object  $xml    SimpleXML object.
   * @return  array           An array that contains the complete subscription list (categories and feeds).
   */
  public function extractDataFromXML( $xml );

}
?>