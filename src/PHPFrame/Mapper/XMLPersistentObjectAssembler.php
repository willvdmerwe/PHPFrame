<?php
/**
 * PHPFrame/Mapper/XMLPersistentObjectAssembler.php
 * 
 * PHP version 5
 * 
 * @category  PHPFrame
 * @package   Mapper
 * @author    Lupo Montero <lupo@e-noise.com>
 * @copyright 2010 The PHPFrame Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://github.com/PHPFrame/PHPFrame
 */

/**
 * XML Domain Object Assembler Class
 * 
 * @category PHPFrame
 * @package  Mapper
 * @author   Lupo Montero <lupo@e-noise.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://github.com/PHPFrame/PHPFrame
 * @since    1.0
 * @ignore
 */
class PHPFrame_XMLPersistentObjectAssembler 
    extends PHPFrame_PersistentObjectAssembler
{
    private $_path_info = null;
    private $_file_info = null;
    
    /**
     * Constructor
     * 
     * @param PHPFrame_PersistenceFactory $factory Instance of persistence 
     *                                             factory to be used with the 
     *                                             assembler.
     * @param string                      $path    Path to directory where to 
     *                                             store the XML file.
     * 
     * @return void
     * @since  1.0
     */
    public function __construct(PHPFrame_PersistenceFactory $factory, $path)
    {
        parent::__construct($factory);
        
        // Make sure the directory is writable
        PHPFrame_Filesystem::ensureWritableDir($path);
        
        // Create FileInfo object for dir path
        $this->_path_info = new SplFileInfo($path);
        
        // Build full path to XML file
        $file_name = $this->_path_info->getRealPath();
        $file_name .= DS.$this->factory->getTableName().".xml";
        
        // Create FileInfo object for XML file
        // Create XML file if it doesnt exist
        if (!is_file($file_name)) {
            $file_obj = new SplFileObject($file_name, "w");
            $this->_file_info = $file_obj->getFileInfo();
        } else {
            $this->_file_info = new SplFileInfo($file_name);
        }
    }
    
    /**
     * Find a persistent object using an IdObject
     * 
     * @param int|PHPFrame_IdObject $id_or_id_obj Either a numeric id or an 
     *                                            instance of IdObject.
     * 
     * @return PHPFrame_PersistentObject
     * @since  1.0
     */
    public function findOne($id_or_id_obj)
    {
        if (is_int($id_or_id_obj)) {
            $id = $id_or_id_obj;
            
        } elseif ($id_or_id_obj instanceof PHPFrame_IdObject) {
            $msg = "XMLIdObject not implemented!!!.";
            throw new RuntimeException($msg);
            
        } else {
            $msg = "Wrong argument type. ";
            $msg .= get_class($this)."::findOne() expected only argument to be";
            $msg .= " of type PHPFrame_IdObject or integer.";
            throw new InvalidArgumentException($msg);
        }
        
        foreach ($this->find() as $obj) {
            if ($obj->id() == $id) {
                return $obj;
            }
        }
    }
    
    /**
     * Find a collection of persistent objects using an IdObject
     * 
     * @param PHPFrame_IdObject $id_obj Instance of {@link PHPFrame_IdObject}.
     * 
     * @return PHPFrame_PersistentObjectCollection
     * @since  1.0
     */
    public function find(PHPFrame_IdObject $id_obj=null)
    {
        $file_contents = file_get_contents($this->_file_info->getRealPath());
        if (!empty($file_contents)) {
            $raw_tmp = PHPFrame_XMLSerialiser::unserialise($file_contents);
        } else {
            $raw_tmp = array();
        }
        
        $raw = array();
        
        if (key_exists($this->factory->getTargetClass(), $raw_tmp)) {
            $raw = $raw_tmp[$this->factory->getTargetClass()];
        } else {
            $raw = $raw_tmp;
        }
        
        $raw_array_obj = new PHPFrame_Array($raw);
        if ($raw_array_obj->isAssoc()) {
            $raw = array($raw);
        }
        
        // Create collection object
        return $this->factory->getCollection($raw);
    }
    
    /**
     * Persist persistent object
     * 
     * @param PHPFrame_PersistentObject $obj The persistent object we want to 
     *                                       store with the mapper.
     * 
     * @return void
     * @since  1.0
     */
    public function insert(PHPFrame_PersistentObject $obj)
    {
        $obj->validateAll();
        
        // Get current collection
        $collection = $this->find();
        
        // Update modified time
        $obj->mtime(time());
        
        // Prepare new elements (insert)
        if ($obj->id() <= 0) {
            $obj->id($this->_getNewId());
            $obj->ctime(time());
            
            // Add new element to collection
            $collection->addElement($obj);
        
        } else {
            // Prepare existing elements (update)
            foreach ($collection as $item) {
                if ($item->id() == $obj->id()) {
                    $item = iterator_to_array($obj);
                }
            }
        }
        
        // Open the file in "write" mode
        $file_obj = $this->_file_info->openFile("w");
        $file_obj->fwrite($this->_serializeCollection($collection));
        
        $obj->markClean();
    }
    
    /**
     * Delete persistent object from the database
     * 
     * @param int|PHPFrame_PersistentObject $id_or_obj Either a numeric id or 
     *                                                 an instance of the 
     *                                                 persistence object.
     * 
     * @return void
     * @since  1.0
     */
    public function delete($id_or_obj)
    {
        if (!$id_or_obj instanceof PHPFrame_PersistentObject) {
            $obj = $this->findOne((int) $id_or_obj);
        } else {
            $obj = $id_or_obj;
        }
        
        // Get current collection
        $collection = $this->find();
        $collection->removeElement($obj);
        
        // Open the file in "write" mode
        $file_obj = $this->_file_info->openFile("w");
        $file_obj->fwrite($this->_serializeCollection($collection));
        
        $obj->markClean();
    }
    
    /**
     * Serialise collection as an XML string
     * 
     * @param PHPFrame_PersistentObjectCollection $collection Instance of 
     *                                                        collection object 
     *                                                        to serialise.
     * 
     * @return string
     * @since  1.0
     */
    private function _serializeCollection(
        PHPFrame_PersistentObjectCollection $collection
    ) {
        // Flatten collection object to array
        $array = array();
        foreach ($collection as $item) {
            $array[get_class($item)][] = iterator_to_array($item);
        }
        
        return PHPFrame_XMLSerialiser::serialise($array, "collection");
    }
    
    /**
     * Get new id based on highest in current collection
     * 
     * @return int
     * @since  1.0
     */
    private function _getNewId()
    {
        $newid = 0;
        
        $collection = $this->find();
        
        foreach ($collection as $item) {
            if ($item->id() > $newid) {
                $newid = $item->id();
            }
        }
        
        return ($newid+1);
    }
}
