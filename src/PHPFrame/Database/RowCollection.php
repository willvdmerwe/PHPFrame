<?php
/**
 * PHPFrame/Database/RowCollection.php
 * 
 * PHP version 5
 * 
 * @category   MVC_Framework
 * @package    PHPFrame
 * @subpackage Database
 * @author     Luis Montero <luis.montero@e-noise.com>
 * @copyright  2009 E-noise.com Limited
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version    SVN: $Id$
 * @link       http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 * @since      1.0
 */

/**
 * Row Collection Class
 * 
 * @category   MVC_Framework
 * @package    PHPFrame
 * @subpackage Database
 * @author     Luis Montero <luis.montero@e-noise.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 * @see        Iterator
 * @since      1.0
 */
class PHPFrame_Database_RowCollection implements Iterator
{
    /**
     * A reference to the DB connection to use for fetching rows
     * 
     * @var PHPFrame_Database
     */
    private $_db=null;
    /**
     * An id object held internally to load rows from db
     * 
     * @var PHPFrame_Database_IdObject
     */
    private $_id_obj=null;
    /**
     * The total number of entries for selected table. This includes entries that
     * fall outside the current subset/page when using limits.
     * 
     * @var int
     */
    private $_total=null;
    /**
     * The rows that make up the collection
     * 
     * @var array
     */
    private $_rows=array();
    /**
     * A pointer used to iterate through the rows array
     * 
     * @var int
     */
    private $_pos=0;
    
    /**
     * Constructor
     * 
     * @param array             $options An array with initialisation options.
     * @param PHPFrame_Database $db      Optionally use an alternative database 
     *                                   to the default one provided by 
     *                                   PHPFrame::DB() as defined in config 
     *                                   class.
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function __construct($options=null, PHPFrame_Database $db=null) {
        // Handle options
        
        // If no database object is passed we use default connection
        if ($db instanceof PHPFrame_Database) {
            $this->_db = $db;
        } else {
            $this->_db = PHPFrame::DB();
        }
        
        // Acquire id object used to handle SQL query
        $this->_id_obj = new PHPFrame_Database_IdObject($options);
    }
    
    /**
     * Get available options
     * 
     * @access public
     * @return array
     * @since  1.0
     */
    public function getOptions()
    {
        // Return options from Id Object
        return $this->_id_obj->getOptions();
    }
    
    /**
     * Convert row collection to string
     * 
     * @access public
     * @return string
     * @since  1.0
     */
    public function __toString()
    {
        $str = "";
        
        for ($i=0; $i<count($this->_rows); $i++) {
            // Add table headings
            if ($i == 0) {
                $fields = $this->_id_obj->getFields();
                // If using "*" we get all fields in table from row object
                if (is_array($fields) && $fields[0] == "*") {
                    $fields = $this->_rows[$i]->getKeys();
                }
                
                foreach ($fields as $key) {
                    $str .= PHPFrame_Base_String::fixLength($key, 16)."\t";
                }
                $str .= "\n";
            }
            
            $str .= $this->_rows[$i]->toString(false)."\n";
        }
        
        return $str;
    }
    
    /**
     * Set the fields array used in select statement
     * 
     * @param string|array $fields a string or array of strings with field names
     * 
     * @access public
     * @return PHPFrame_Database_IdObject
     * @since  1.0
     */
    public function select($fields)
    {
        $this->_id_obj->select($fields);
        
        return $this;
    }
    
    /**
     * Set the table from which to select rows
     * 
     * @param string $table A string with the table name
     * 
     * @access public
     * @return PHPFrame_Database_IdObject
     * @since  1.0
     */
    public function from($table)
    {
        $this->_id_obj->from($table);
        
        return $this;
    }
    
    /**
     * Add a join clause to the select statement
     * 
     * @param sting $join A join statement
     * 
     * @access public
     * @return PHPFrame_Database_IdObject
     * @since  1.0
     */
    public function join($join)
    {
        $this->_id_obj->join($join);
        
        return $this;
    }
    
    /**
     * Add "where" condition
     * 
     * @param string $left
     * @param string $operator
     * @param string $right
     * 
     * @access public
     * @return PHPFrame_Database_IdObject
     * @since  1.0
     */
    public function where($left, $operator, $right)
    {
        $this->_id_obj->where($left, $operator, $right);
        
        return $this;
    }
    
    /**
     * Set group by clause
     * 
     * @param string $column The column name to group by
     * 
     * @access public
     * @return PHPFrame_Database_IdObject
     * @since  1.0
     */
    public function groupby($column)
    {
        $this->_id_obj->groupby($column);
        
        return $this;
    }
    
    /**
     * Set order by clause
     * 
     * @param string $column    The column name to order by
     * @param string $direction The order direction (either ASC or DESC)
     * 
     * @access public
     * @return PHPFrame_Database_IdObject
     * @since  1.0
     */
    public function orderby($column, $direction=null)
    {
        $this->_id_obj->orderby($column, $direction);
        
        return $this;
    }
    
    /**
     * Set order direction
     * 
     * @param string $column    The column name to order by
     * @param string $direction The order direction (either ASC or DESC)
     * 
     * @access public
     * @return PHPFrame_Database_IdObject
     * @since  1.0
     */
    public function orderdir($direction)
    {
        $this->_id_obj->orderdir($direction);
        
        return $this;
    }
    
    /**
     * Set limit clause
     * 
     * @param int $limit     The total number of entries we want to limit to
     * @param int $limistart The entry number of the first record in the current page
     * 
     * @access public
     * @return PHPFrame_Database_IdObject
     * @since  1.0
     */
    public function limit($limit, $limistart=null)
    {
        $this->_id_obj->limit($limit, $limistart);
        
        return $this;
    }
    
    /**
     * Set row number of first row in current page
     * 
     * @param int $limistart The entry number of the first record in the current page
     * 
     * @access public
     * @return PHPFrame_Database_IdObject
     * @since  1.0
     */
    public function limistart($limistart)
    {
        $this->_id_obj->limistart($limistart);
 
        return $this;
    }
    
    public function params($key, $value)
    {
        $this->_id_obj->params($key, $value);
 
        return $this;
    }
    
    /**
     * Load rows from database
     * 
     * @param PHPFrame_Database_IdObject $id_object
     * 
     * @access public
     * @return PHPFrame_Database_RowCollection
     * @since  1.0
     */
    public function load(PHPFrame_Database_IdObject $id_object=null)
    {
        if ($id_object instanceof PHPFrame_Database_IdObject) {
            $this->_id_obj = $id_object;
        }
        
        $this->_fetchRows($this->_id_obj);
        $this->_fetchTotalNumRows($this->_id_obj);
        
        return $this;
    }
    
    /**
     * Get rows in collection
     * 
     * @access public
     * @return array
     * @since  1.0
     */
    public function getRows() 
    {
        return $this->_rows;
    }
    
    /**
     * Get fields names for rows
     * 
     * @access public
     * @return array
     * @since  1.0
     */
    public function getKeys()
    {
        if (count($this->_rows) == 0) {
            return null;
        }
        
        return $this->_rows[0]->getKeys();
    }
    
    public function getLimit()
    {
        return $this->_id_obj->getLimit();
    }
    
    public function getLimitstart()
    {
        return $this->_id_obj->getLimitstart();
    }
    
    public function getTotal()
    {
        return $this->_total;
    }
    
    /**
     * Get number of pages
     * 
     * @return int
     * @since  1.0
     */
    public function getPages() 
    {
        if ($this->getLimit() > 0 && $this->getTotal() > 0) {
            // Calculate number of pages
            return (int) ceil($this->getTotal()/$this->getLimit());
        } else {
            return 0;
        }
    }
    
    /**
     * Get current page number
     * 
     * @return int
     * @since  1.0
     */
    public function getCurrentPage() 
    {
        // Calculate current page
        if ($this->getLimit() > 0) {
            return (int) (ceil($this->getLimitstart()/$this->getLimit())+1);
        } else {
            return 0;
        }
    }
    
    /**
     * Get total number of rows in collection
     *   
     * @access public
     * @return int
     * @since  1.0
     */
    public function countRows() 
    {
        return count($this->_rows);
    }
    
    /**
     * Implementation of Iterator::current()
     * 
     * @access public
     * @return PHPFrame_Database_Row
     * @since  1.0
     */
    public function current() 
    {
        return $this->_rows[$this->_pos];
    }
    
    /**
     * Implementation of Iterator::next()
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function next() 
    {
        $this->_pos++;
    }
    
    /**
     * Implementation of Iterator::key()
     *   
     * @access public
     * @return int
     * @since  1.0
     */
    public function key() 
    {
        return $this->_pos;
    }
    
    /**
     * Implementation of Iterator::valid()
     * 
     * @access public
     * @return bool
     * @since  1.0
     */
    public function valid() 
    {
        return ($this->key() < $this->countRows());
    }
    
    /**
     * Implementation of Iterator::rewind()
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function rewind() 
    {
        $this->_pos = 0;
    }
    
    /**
     * Load array of row objects using id object
     * 
     * @param string $id_object The id object used to generate the query.
     *
     * @access private
     * @return void
     * @since  1.0
     */
    private function _fetchRows(PHPFrame_Database_IdObject $id_object) 
    {
        // Cast Id Object to string (this produces a SQL query)
        $sql = (string) $id_object;
        
        // Run SQL query
        $stmt = $this->_db->prepare($sql);
        
        if (!($stmt instanceof PDOStatement)) {
            $msg = "Could not load rows from database.";
            throw new PHPFrame_Exception_Database($msg);
        } else {
            // Fetch associative array
            $stmt->execute($this->_id_obj->getParams());
            //var_dump($this->_id_obj->getParams()); exit;
            $table_name = $this->_id_obj->getTableName();
            while ($array = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row = new PHPFrame_Database_Row($table_name, $this->_db);
                $this->_rows[] = $row->bind($array);
            }
        }
    }
    
    private function _fetchTotalNumRows(PHPFrame_Database_IdObject $id_object)
    {
        // Cast Id Object to string (this produces a SQL query)
        $id_obj_no_limit = clone $id_object;
        $id_obj_no_limit->select("p.id");
        $sql = $id_obj_no_limit->getSQL(false);
        
        // Run SQL query
        $stmt = $this->_db->prepare($sql);
        
        if (!($stmt instanceof PDOStatement)) {
            $msg = "Could not count rows from database.";
            throw new PHPFrame_Exception_Database($msg);
        } else {
            // Fetch row count
            $stmt->execute($this->_id_obj->getParams());
            $this->_total = $stmt->rowCount();
        }
    }
}
