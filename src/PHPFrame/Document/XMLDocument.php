<?php
/**
 * PHPFrame/Document/XMLDocument.php
 * 
 * PHP version 5
 * 
 * @category  PHPFrame
 * @package   Document
 * @author    Luis Montero <luis.montero@e-noise.com>
 * @copyright 2009 E-noise.com Limited
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   SVN: $Id$
 * @link      http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 */

/**
 * XML Document Class
 * 
 * @category PHPFrame
 * @package  Document
 * @author   Luis Montero <luis.montero@e-noise.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 * @since    1.0
 */
class PHPFrame_XMLDocument extends PHPFrame_Document
{
    /**
     * The qualified name of the document type to create. 
     * 
     * @var string
     */
    protected $qualified_name = "xml";
    /**
     * DOM Document Type object
     * 
     * @var DOMDocumentType
     */
    protected $doctype = null;
    /**
     * DOM Document object
     * 
     * @var DOMDocument
     */
    protected $dom = null;
    
    /**
     * Constructor
     * 
     * @access public
     * @return void
     * @uses   DOMImplementation
     * @since  1.0 
     */
    public function __construct($mime="text/xml", $charset=null) 
    {
        // Call parent's constructor to set mime type
        parent::__construct($mime, $charset);
        
        // Acquire DOM object of HTML type
        $this->dom = new DOMDocument("1.0", $this->charset); 
    }
    
    /**
     * Covert object to string
     * 
     * @access public
     * @return string
     * @since  1.0
     */
    public function __toString()
    {
        return $this->indent($this->dom->saveXML().$this->body);
    }
    
    /**
     * Get DOM Document Type object
     * 
     * @access public
     * @return DOMDocumentType
     * @since  1.0
     */
    public function getDocType()
    {
        // Create new doc type object if we don't have one yet
        if (!($this->doctype instanceof DOMDocumentType)) {
             // Create doc type object
            $imp = new DOMImplementation;
            $this->doctype = $imp->createDocumentType($this->qualified_name);
        }
        
        return $this->doctype;
    }
    
    /**
     * Add node/tag
     * 
     * @param DOMNode|null $parent  The parent object to which we want to add the new node.
     * @param string  $name    The name of the new node or tag
     * @param array   $attrs   An assoc array containing attributes key/value pairs.
     * @param string  $content Text content of the node if any
     * 
     * @access public
     * @return DOMNode Returns a reference to the newly created node
     * @since  1.0
     */
    public function addNode($parent=null, $name, $attrs=array(), $content=null)
    {
        $new_node = $this->dom->createElement($name);
        
        if ($parent instanceof DOMNode) {
            $parent->appendChild($new_node);
        } else {
            $this->dom->appendChild($new_node);
        }

        // Add attributes if any
        if (is_array($attrs) && count($attrs) > 0) {
            foreach ($attrs as $key=>$value) {
                $this->addNodeAttr($new_node, $key, $value);
            }
        }
        
        // Add text content if any
        if (!is_null($content)) {
            $this->addNodeContent($new_node, $content);
        }
        
        return $new_node;
    }
    
    /**
     * Add an attribute to a given node
     * 
     * @param DOMNode $node       The node we want to add the attributes to.
     * @param string  $attr_name  The attribute name
     * @param string  $attr_value The value for the attribute if any.
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function addNodeAttr(DOMNode $node, $attr_name, $attr_value)
    {
        // Create attribute
        $attr = $this->dom->createAttribute($attr_name);
        
        // Add attribute value
        $value = $this->dom->createTextNode($attr_value);
        $attr->appendChild($value);
        
        // Append attribute to node
        $node->appendChild($attr);
    }
    
    /**
     * Add content to given node
     * 
     * @param DOMNode $node The node where to add the content text.
     * @param string  $str  The text to add to the node
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function addNodeContent(DOMNode $node, $str)
    {
        $text_node = $this->dom->createTextNode($str);
        $node->appendChild($text_node);
    }
    
    /**
     * Render view and store in document's body
     * 
     * This method is invoked by the views and renders the ouput data in the
     * document specific format.
     * 
     * @param PHPFrame_View $view The view object to process.
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function renderView(PHPFrame_View $view){}
    
    /**
     * Method used to render Row Collections in this document
     * 
     * @param PHPFrame_Collection
     * 
     * @access public
     * @return string
     * @since  1.0
     */
    public function renderCollection(PHPFrame_Collection $collection)
    {
        $str = "FIX ME!!!: ".get_class($this)."::renderRowCollection().";
        
        return $str;
    }
    
    /**
     * Indent
     * 
     * This method is used to turn inline XML into human-readable text
     * 
     * @param string $response The XML as string.
     * 
     * @return string $response Human readable XML.
     * 
     * @access protected
     * @since  1.0
     */
    protected function indent($response)
    {
        $return_array = explode('>',$response);
        $depth        = -1;
        
        for($i = 0; $i < count($return_array) - 1; $i++) {
            if(strpos($return_array[$i],"\n")!==false){
                $return_array[$i] = trim($return_array[$i]);
            }
                    
            $end_tag = strpos($return_array[$i], "</");
            
            if($end_tag!==false) {
                if ($end_tag != 0) {
                    $return_array[$i] = $this->padding($depth) . $return_array[$i];
                    $depth--; 
                    $return_array[$i] = str_replace("</","\r\n".$this->padding($depth)."</",$return_array[$i]);
                } else {
                    $depth--;
                    $return_array[$i] = $this->padding($depth) . $return_array[$i];
                }
                $depth--;
            } else {
                $return_array[$i] = $this->padding($depth) . $return_array[$i];
            }
            
            $return_array[$i] = $return_array[$i] . ">\r\n";
            $depth++;
        }
        
        $response = implode($return_array);
        return $response;
    }
    
    /**
     * Padding
     * 
     * This generates padding with specified depth
     * 
     * @param int $depth
     * 
     * @access protected
     * @return string
     * @since  1.0
     */
    protected function padding($depth)
    {
        $padding = '';
        
        for ($tabs = 0; $tabs < $depth; $tabs++) {
            $padding .= '  ';
        }
        
        return $padding;    
    }
}
