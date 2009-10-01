<?php
/**
 * PHPFrame/Filter/Validator.php
 * 
 * PHP version 5
 * 
 * @category  PHPFrame
 * @package   Filter
 * @author    Luis Montero <luis.montero@e-noise.com>
 * @copyright 2009 E-noise.com Limited
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   SVN: $Id$
 * @link      http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 */

/**
 * Validator class
 * 
 * @category PHPFrame
 * @package  Filter
 * @author   Luis Montero <luis.montero@e-noise.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 * @since    1.0
 */
class PHPFrame_Validator
{
	/**
     * An array used to store field names and their filters
     * 
     * @var array
     */
	private $_filters = array();
	/**
     * The original values
     * 
     * @var array
     */
    private $_original_values = array();
    /**
     * The filtered values
     * 
     * @var array
     */
    private $_filtered_values = array();
	/**
	 * Array used to store messages
	 * 
	 * @var array
	 */
	private $_messages = array();
	/**
	 * Boolean indicating whether we want validator to throw exceptions
	 * 
	 * @var bool
	 */
	private $_throw_exceptions = false;
	/**
	 * Default exception class used when not specified by filter
	 * 
	 * @var string
	 */
    private $_exception_class = "Exception";
	
    /**
     * Constructor
     * 
     * @access public
     * @return void
     * @since  1.0
     */
	public function __construct()
	{
		//...
	}
	
	/**
	 * Set a filter for a given field name in the validator
	 * 
	 * @param string          $field_name
	 * @param PHPFrame_Filter $filter
	 * 
	 * @access public
     * @return void
     * @since  1.0
	 */
	public function setFilter($field_name, PHPFrame_Filter $filter)
	{
	    if (!is_string($field_name) || strlen($field_name) < 1) {
            $msg  = get_class($this)."::setFilter() expects argument ";
            $msg .= "\$name to be of type string and not empty and got value ";
            $msg .= "'".$field_name."' of type ".gettype($field_name);
            throw new InvalidArgumentException($msg);
        }
        
		$this->_filters[$field_name] = $filter;
	}
	
	/**
     * Get filter for a given field in the validator
     * 
     * @param string $field_name
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function getFilter($field_name)
    {
        if (!is_string($field_name) || strlen($field_name) < 1) {
            $msg  = get_class($this)."::getFilter() expects argument ";
            $msg .= "\$name to be of type string and not empty and got value ";
            $msg .= "'".$field_name."' of type ".gettype($field_name);
            throw new InvalidArgumentException($msg);
        } elseif (!isset($this->_filters[$field_name])) {
            return null;
        }
        
        return $this->_filters[$field_name];
    }
	
    /**
     * Set whether or not the validator should throw exceptions
     * 
     * @param bool $bool
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function throwExceptions($bool)
    {
        if (!is_bool($bool)) {
            $msg  = get_class($this)."::throwExceptions() expected argument ";
            $msg .= "\$bool to be of type 'bool' and got '".gettype($bool)."'";
            throw new InvalidArgumentException($msg);
        }
        
        $this->_throw_exceptions = $bool;
    }
    
    /**
     * Set default exception class
     * 
     * @param string $str The exception class name
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function setExceptionClass($str)
    {
        if (!is_string($str)) {
            $msg  = get_class($this)."::setExceptionClass() expected argument ";
            $msg .= "\$str to be of type 'string' and got '".gettype($str)."'";
            throw new InvalidArgumentException($msg);
        }
        
        $this->_exception_class = $str;
    }
    
    /**
     * Check whether a givan value is valid with the current validator state
     * 
     * @param mixed $value The value to validate
     * 
     * @access public
     * @return bool
     * @since  1.0
     */
    public function isValid($value)
    {
    	$this->_original_value  = $value;
    	
    	if (!$this->sanitise($this->getOriginalValue())) {
    	    return false;
    	}
    	
        if (!$this->filter($this->getSanitisedValue())) {
            return false;
        }
    	
        return true;
    }
    
    /**
     * Validate a value for a single field in validator
     * 
     * @param string $field_name
     * @param mixed  $value
     * 
     * @access public
     * @return bool TRUE on success and FALSE on failure
     * @since  1.0
     */
    public function validate($field_name, $value)
    {
        if (!is_string($field_name)) {
            $msg  = get_class($this)."::validate() expected argument ";
            $msg .= "\$field_name to be of type 'string' and got '";
            $msg .= gettype($field_name)."'";
            throw new InvalidArgumentException($msg);
        }
        
    	if (!isset($this->_filters[$field_name])) {
    		$msg  = "No filter has been set for field '".$field_name."'.";
            throw new UnexpectedValueException($msg);
    	}
    	
    	$filter = $this->_filters[$field_name];
    	
        if ($filter instanceof PHPFrame_BoolFilter) {
            $null_on_failure = $filter->getOption("null_on_failure");
        } else {
            $null_on_failure = false;
        }
            
        $this->_filtered_values[$field_name] = $filter->process($value);
            
        if (
             count($filter->getMessages()) > 0
             && (
                ($this->_filtered_values[$field_name] === false && !$null_on_failure)
                || (is_null($this->_filtered_values[$field_name]) && $null_on_failure)
            )
        ) {
            $last_message = end($filter->getMessages());
            $this->fail($last_message[0], $last_message[1]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate all fields and return
     * 
     * @param array $assoc An associative array containing the field names and 
     *                     the values to process.
     * 
     * @access public
     * @return mixed The filtered array or FALSE on failure
     * @since  1.0
     */
	public function validateAll(array $assoc)
	{
	    
        
        return true;
	}
	
    /**
     * Get original value
     * 
     * @param string $field_name
     * 
     * @access public
     * @return mixed
     * @since  1.0
     */
    public function getOriginalValue($field_name)
    {
        return $this->_original_values[$field_name];
    }
    
    /**
     * Get filtered value
     * 
     * @param string $field_name
     * 
     * @access public
     * @return mixed
     * @since  1.0
     */
    public function getFilteredValue($field_name)
    {
        return $this->_filtered_values[$field_name];
    }
	
	/**
	 * Get messages array
	 * 
	 * @access public
	 * @return array
	 * @since  1.0
	 */
	public function getMessages()
	{
		return $this->_messages;
	}
	
	/**
	 * Notify failure
	 * 
	 * @param string $str             The failure message
	 * @param string $exception_class [Optional] Specialised exception class
	 * 
	 * @access public
     * @return void
     * @since  1.0
	 */
    protected function fail($str, $exception_class=null)
    {
        if (!is_string($str)) {
            $msg  = get_class($this)."::fail() expected argument \$str ";
            $msg .= "to be of type 'string' and got '".gettype($str)."'";
            throw new InvalidArgumentException($msg);
        }
        
        if (is_null($exception_class)) {
            $exception_class = $this->_exception_class;
        }
        
        $this->_messages[] = array($str, $exception_class);
        
        if ($this->_throw_exceptions) {
            throw new $exception_class($str);
        }
    }
}