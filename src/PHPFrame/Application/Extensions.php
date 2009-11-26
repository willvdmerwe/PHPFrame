<?php
/**
 * PHPFrame/Application/Extensions.php
 * 
 * PHP version 5
 * 
 * @category  PHPFrame
 * @package   Application
 * @author    Luis Montero <luis.montero@e-noise.com>
 * @copyright 2009 The PHPFrame Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   SVN: $Id$
 * @link      http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 */

/**
 * Extensions abstract class
 * 
 * @category PHPFrame
 * @package  Application
 * @author   Luis Montero <luis.montero@e-noise.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 * @since    1.0
 */
abstract class PHPFrame_Extensions implements IteratorAggregate
{
    /**
     * A mapper object used to store and retrieve extension data.
     *
     * @var PHPFrame_PersistentObjectCollection
     */
    private $_mapper;
    /**
     * A collection object holding data about installed extensions.
     *
     * @var PHPFrame_PersistentObjectCollection
     */
    private $_extensions;
    
    /**
     * Constructor.
     * 
     * @param PHPFrame_Mapper $mapper Mapper object used to persist the ACL 
     *                                objects.
     * 
     * @return void
     * @since  1.0
     */
    public function __construct(PHPFrame_Mapper $mapper) 
    {
        // Get installed features from file
        $this->_mapper = $mapper;
        
        $this->_extensions = $this->_mapper->find();
    }
    
    /**
     * Implementation of IteratorAggregate interface.
     * 
     * @return Iterator
     * @since  1.0
     */
    public function getIterator()
    {
        return $this->_extensions;
    }
    
    /**
     * Get extension info by name
     * 
     * @param string $name The feature name.
     * 
     * @return array
     * @since  1.0
     */
    final public function getInfo($name) 
    {
        foreach ($this->_extensions as $extension) {
            if ($extension->getName() == $name) {
                return $extension;
            }
        }
        
        $msg = "Feature '".$name."' is not installed";
        throw new RuntimeException($msg);
    }
    
    /**
     * This methods tests whether the specified extension is installed and 
     * enabled.
     *
     * @param string $name The extension name to check (ie: dashboard, user, 
     *                     projects, ...).
     * 
     * @return bool
     * @since  1.0
     */
    final public function isEnabled($name) 
    {
        foreach ($this->_extensions as $extension) {
            if ($extension->getName() == $name && $extension->isEnabled()) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check whether a given extension is installed.
     * 
     * @param string $name The extension name to check (ie: dashboard, user, 
     *                     projects, ...).
     *                     
     * @return bool
     * @since  1.0
     */
    final public function isInstalled($name)
    {
        foreach ($this->_extensions as $extension) {
            if ($extension->getName() == $name && $extension->isInstalled()) {
                return true;
            }
        }
        
        return false;
    }
}
