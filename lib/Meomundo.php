<?php
/**
 * Core class for the MeoLib to provice a basic architectural structure and functionality,
 * including a class loader method.
 *
 * @category    MeoLib
 * @copyright   Copyright (c) 2012 http://www.meomundo.com
 * @author      Christian Doerr <doerr@meomundo.com>
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 *
 */
class Meomundo {
  
  /**
   * @var string Absolute path to this library.
   */
  protected $path;
  
  /**
   * The Constructor.
   *
   * @param string Absolute path to this library.
   */
  public function __construct( $absolutePath ) {

    $this->path     = $absolutePath;
    
  }


  /**
   * Simple getter.
   *
   * @param string $key Name of the member variable whose value is being requested.
   * @return mixed If the requested member variable exists it value will be returned. Otherwise NULL will be returned.
   */
  public function get( $key ) {
    
    if( isset( $this->$key ) ) {
      
      return $this->$key;
      
    }
    else {
    
      return null;
    
    }
    
  }


  /**
   * Simple setter.
   * Will overwrite old values or create a new member variable if it doesn't already exist.
   *
   * @param string $key Name of the member variable whose value shall be set.
   * @param mixed $value The value of the member variable to be set.
   */
  public function set( $key, $value ) {
    
    $this->$key = $value;

  }


  /**
   * Custom updater.
   * Will only set new values to already existing member variables!
   *
   * @param string $key Name of the member variable whose value shall be updated.
   * @param mixed $value The NEW value of the member variable to be set.
   * @return boolean TRUE if the member variable exists and the value could be set. FALSE is the member variable $key does not exist.
   */
  public function update( $key, $value ) {
    
    if( isset( $this->$key ) ) {
      
      $this->$key = $value;
      
      return true;
      
    }

    return false;
    
  }
  
  
  /**
   * Custom caller.
   * Mainly for making it easier to unittest private and protected methods.
   *
   * @param string $method Method name to be called.
   * @param mixed $args Arguments to be passed to the method.
   * @return mixed. NULL in case the method does not exists. If it does, the method's return value will be returned.
   */
  public function call( $method, $args ) {
    
    if( method_exists( $this, $method ) ) {
      
      return $this->$method( $args );
      
    }
    else {
      
      return null;
      
    }
    
  }

  
  /**
   * Custom class loader.
   * Will load a Zend-like class file structure based on the absolute path, defined in the member variable $path.
   *
   * @param string $classname Name of the class to be loaded.
   * @return boolean TRUE if either the class already is defined or the classfile could be loaded and it actually contains the requested class declaration. FALSE if either the class file does not exist or it doesn't contain the class that has been requested.
   */
  public function loadClass( $classname ) {

    if( $this->classExists( $classname ) ) {
      
      return true;
    
    }
    else {

      $path = $this->path;

      if( strtolower( substr( $path, -4 ) ) !== 'lib' . DIRECTORY_SEPARATOR && strtolower( substr( $path, -3 ) ) !== 'lib' ) {
        
        $path .= 'lib' . DIRECTORY_SEPARATOR;

      }

      $file = $path . str_replace( '_', DIRECTORY_SEPARATOR, $classname ) . '.php';

      if( file_exists( $file ) ) {
        
        include_once $file;

      }
      else {

        return false;

      }
      
    }

    /**
     * Simply including the classfile doesn't make sense to me since the goal is to load a class not a file.
     * Always make sure the file does contain the class declaration!
     */
    return $this->classExists( $classname );

  }
  
  /**
   * Check wether a class(name) has been declared.
   *
   * @param string $classname
   * @return boolean
   */
  public function classExists( $classname ) {
    
    return class_exists( $classname );
    
  }
  

  /**
   * Custom interface loader.
   * Will load a Zend-like interface file structure based on the absolute path, defined in the member variable $path.
   *
   * @param string $interfaceName Name of the interface to be loaded.
   * @return boolean TRUE if either the interface already is defined or the interface file could be loaded and it actually contains the requested interface declaration. FALSE if either the interface file does not exist or it doesn't contain the interface that has been requested.
   */
  public function loadInterface( $interfaceName ) {

    if( $this->interfaceExists( $interfaceName ) ) {
      
      return true;
    
    }
    else {

      $path = $this->path;

      if( strtolower( substr( $path, -4 ) ) !== 'lib' . DIRECTORY_SEPARATOR && strtolower( substr( $path, -3 ) ) !== 'lib' ) {
        
        $path .= 'lib' . DIRECTORY_SEPARATOR;

      }

      $file = $path . str_replace( '_', DIRECTORY_SEPARATOR, $interfaceName ) . '.php';

      if( file_exists( $file ) ) {
        
        include_once $file;

      }
      else {
      
        return false;

      }
      
    }

    /**
     * Simply including the classfile doesn't make sense to me since the goal is to load a class not a file.
     * Always make sure the file does contain the class declaration!
     */
    return $this->interfaceExists( $interfaceName );

  }
  
  /**
   * Check wether a interface(name) has been declared.
   *
   * @param string $interfaceName
   * @return boolean
   */
  public function interfaceExists( $interfaceName ) {
    
    return interface_exists( $interfaceName );
    
  }
  
}
?>