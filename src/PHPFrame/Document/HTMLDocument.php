<?php
/**
 * PHPFrame/Document/HTMLDocument.php
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
 * HTML Document Class
 * 
 * @category PHPFrame
 * @package  Document
 * @author   Luis Montero <luis.montero@e-noise.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 * @see      PHPFrame_Document
 * @since    1.0
 */
class PHPFrame_HTMLDocument extends PHPFrame_XMLDocument
{
    /**
     * The qualified name of the document type to create. 
     * 
     * @var string
     */
    protected $qualified_name = "html";
    /**
     * Pathway object
     * 
     * @var PHPFrame_Pathway
     */
    private $_pathway = null;
    
    /**
     * Constructor
     * 
     * @access public
     * @return void
     * @uses   DOMImplementation, PHPFrame_URI, PHPFrame_Pathway
     * @since  1.0 
     */
    public function __construct($mime="text/html", $charset=null) 
    {
        // Call parent's constructor to set mime type
        parent::__construct($mime, $charset);
        
        // Acquire DOM object of HTML type
        $imp = new DOMImplementation;
        $this->dom = $imp->createDocument(null, 
                                           $this->qualified_name, 
                                           $this->getDocType()); 
        
        // Get root node
        $html_node = $this->dom->getElementsByTagName("html")->item(0);
        
        // Add head
        $head_node = $this->addNode($html_node, "head");
        // Add body
        $this->addNode($html_node, "body", null, "{content}");
        
        // Add meta tags
        $this->addMetaTag("generator", "PHPFrame");
        $this->addMetaTag(null, $this->mime_type."; charset=".$this->charset, "Content-Type");
        
        // Add base url
        $uri = new PHPFrame_URI();
        $this->addNode($head_node, "base", array("href"=>$uri->getBase()));
        
        // Acquire a pathway object used by this document
        $this->_pathway = new PHPFrame_Pathway();
    }
    
    /**
     * Convert object to string
     * 
     * @access public
     * @return string
     * @since  1.0
     */
    public function __toString()
    {
        // Add title tag in head node 
        $head_node = $this->dom->getElementsByTagName("head")->item(0);
        $this->addNode($head_node, "title", null, $this->getTitle());
        
        // Render DOM Document as HTML string
        $html = $this->indent($this->dom->saveHTML());
        
        // Add body
        $html = str_replace("{content}", $this->body, $html);
        
        return $html;
    }
    
    public function setBody($str)
    {
        $this->body = (string) $str;
    }
    
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Render view and store in document's body
     * 
     * This method is invoked by the views and renders the ouput data in the
     * document specific format.
     * 
     * @param PHPFrame_View $view        The view object to process.
     * @param bool              $apply_theme Boolean to insicate whether we want to apply 
     *                                       the overall theme or not.
     * 
     * @access public
     * @return void
     * @since  1.0
     * @todo It is very important to check path used for require_once call for security.
     */
    public function render(PHPFrame_View $view, $apply_theme=true) 
    {
        $tmpl_path = PHPFRAME_INSTALL_DIR.DS."src";
        $tmpl_path .= DS."views".DS.$view->getName().".php";
        
        if (is_file($tmpl_path)) {
            // Start buffering
            ob_start();
            // set view data as local array
            $data = $view->getData();
            // Include template file
            require_once $tmpl_path;
            // save buffer in body property
            $this->body = ob_get_contents();
            // clean output buffer
            ob_end_clean();
        } else {
            throw new RuntimeException("Layout template file ".$tmpl_path." not found.");
        }
        
        if ($apply_theme) {
            $this->_applyTheme($view);
        } else {
            // we dont need to wrap the controller output in the overall template
            // so we just prepend the sytem events and return
            $sys_events = $this->renderPartial('sysevents');
            $this->body = $sys_events.$this->body;
            return;
        }
    }
    
    public function renderPartial($name)
    {
        $name = (string) trim($name);
        $path = PHPFRAME_INSTALL_DIR.DS."src".DS."views";
        $path .= DS."partials".DS.$name;
        
        if (!is_file($path)) {
            $path .= ".php";
            if (!is_file($path)) {
                //$msg = "Could not load partial ".$path;
                //throw new RuntimeException($msg);
                return "";
            }
        }
        
        // Start buffering
        ob_start();
        // Include partial file
        require_once $path;
        // save buffer in body property
        $partial = ob_get_contents();
        // clean output buffer
        ob_end_clean();
        
        return $partial;
    }
    
    public function renderPathway(PHPFrame_Pathway $pathway)
    {
        $array = $pathway->toArray();
        
        $html = '<div class="pathway">';
        for ($i=0; $i<count($array); $i++) {
            if ($i>0) {
                $html .= ' &gt;&gt; ';
            }
            $html .= '<span class="pathway_item">';
            if (!empty($array[$i]['url']) && $i < (count($array))-1) {
                $html .= '<a href="'.$array[$i]['url'].'">'.$array[$i]['title'].'</a>';
            } else {
                $html .= $array[$i]['title'];
            }
            $html .= '</span>';
        }
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Method used to render Collections in HTML format
     * 
     * @param PHPFrame_Collection $collection
     * 
     * @access public
     * @return string
     * @since  1.0
     */
    public function renderCollection(
        PHPFrame_Collection $collection, 
        $headings=null
    ) {
        // Build table to display row data
        $html = "<table class=\"data_list\" width=\"100%\">\n";
        
        // Prepare heading array
        if (!is_null($headings) && !is_array($headings)) {
            $msg = "Wrong data type.";
            $msg .= "Headings must be passed as an array.";
            throw new RuntimeException($msg);
        } elseif (is_null($headings)) {
            // If no specified headings we get keys from collection
            $headings = $collection->getKeys();
        }
        
        // Print headings
        $html .= "<thead>\n<tr>\n";
        foreach ($headings as $heading) {
            $html .= "<th>".$heading."</th>\n";
        }
        $html .= "</tr>\n</thead>\n";
        
        // Print tbody
        $html .= "<tbody>\n";
        foreach ($collection as $row) {
            $html .= "<tr>\n";
            foreach ($row->getKeys() as $key) {
                $html .= "<td>".$row->$key."</td>\n";
            }
            $html .= "</tr>\n";
        }
        $html .= "</tbody>\n";
        $html .= "</table>";
        
        return $html;
    }
    
    /**
     * Render HTML filter for collections
     * 
     * This method builds an HTML string with UI filtering elements to be used with
     * row collection objects.
     * 
     * @param PHPFrame_Collection $collection
     * 
     * @access public
     * @return string
     * @since  1.0
     */
    public function renderCollectionFilter(PHPFrame_Collection $collection)
    {
        $html = '<div class="row_collection_filter">';
        
        // Print form to select limit per page
        $html .= '<div class="subset_limit">';
        $html .= '<form name="limitform" id="limitform" method="post">';
        $html .= 'Display Num: ';
        $html .= '<select name="limit" onchange="document.forms[\'limitform\'].submit();">';
        for ($i=25; $i<=100; $i+=25) {
            $html .= '<option value="'.$i.'"';
            if ($collection->getLimit() == $i) {
                $html .= ' selected';
            }
            $html .= '>'.$i.'</option>';
        }
        $html .= '<option value="-1">-- All --</option>';
        $html .= '</select>';
        $html .= '</form>';
        $html .= '</div>';
        
        // Print subset info
        $html .= '<div class="subset_info">';
        $html .= ($collection->getLimitstart()+1);
        $html .= ' - '.($collection->getLimitstart() + count($collection));
        $html .= ' of '.$collection->getTotal();
        $html .= '</div>';
        
        // Print search box
        $html .= '<script language="javascript" type="text/javascript">
                    function submit_filter(reset) {
                        var form = document.forms["listsearchform"];
                        
                        if (reset){
                            form.search.value = "";
                        }
                        
                        form.submit();
                    }
                  </script>';
        
        $html .= '<form action="index.php" id="listsearchform" name="listsearchform" method="post">';
        $html .= '<input type="text" name="search" id="search" value="'.PHPFrame::Request()->get('search').'">';
        $html .= '<button type="button" class="button" onclick="submit_filter(false);">Search</button>';
        $html .= '<button type="button" class="button" onclick="submit_filter(true);">Reset</button>';
        $html .= '<input type="hidden" name="component" value="'.PHPFrame::Request()->getControllerName().'" />';
        $html .= '<input type="hidden" name="action" value="'.PHPFrame::Request()->getAction().'" />';
        $html .= '</form>';
        
        $html .= '</div>';
         
        return $html;
    }
    
    /**
     * Render HTML pagination for collection object
     * 
     * @param PHPFrame_Collection $collection The collection object for
     *                                         which to create the pagination.
     * 
     * @access public
     * @return string
     * @since  1.0
     */
    public function renderPagination(PHPFrame_Collection $collection)
    {
        $html = '';
        
        if ($collection->getPages() <= 1) {
            return $html;
        }
        
        $html .= '<div class="pagination">';
        
        // Print list with prev, next and so on...
        $href = 'index.php?controller='.PHPFrame::Request()->getControllerName();
        $href .= '&amp;action='.PHPFrame::Request()->getAction();
        $href .= '&amp;limit='.$collection->getLimit();
        
        $html .= '<ul>';
        // Start link
        $html .= '<li>';
        if ($collection->getCurrentPage() != 1) {
            $html .= '<a href="'.$href.'&amp;limitstart=0">Start</a>';
        } else {
            $html .= 'Start';
        }
        $html .= '</li>';
        // Prev link
        $html .= '<li>';
        if ($collection->getCurrentPage() != 1) {
            $html .= '<a href="'.$href.'&amp;limitstart='.(($collection->getCurrentPage()-2) * $collection->getLimit()).'">Prev</a>';
        } else {
            $html .= 'Prev';
        }
        $html .= '</li>';
        // Page numbers
        for ($j=0; $j<$collection->getPages(); $j++) {
            $html .= '<li>';
            if ($collection->getCurrentPage() != ($j+1)) {
                $html .= '<a href="'.$href.'&amp;limitstart='.($collection->getLimit() * $j).'">'.($j+1).'</a>';    
            } else {
                $html .= ($j+1);
            }
            $html .= '</li>';
        }
        // Next link
        $html .= '<li>';
        if ($collection->getCurrentPage() != $collection->getPages()) {
            $html .= '<a href="'.$href.'&amp;limitstart='.($collection->getCurrentPage() * $collection->getLimit()).'">Next</a>';    
        } else {
            $html .= 'Next';
        }
        // End link
        $html .= '<li>';
        if ($collection->getCurrentPage() != $collection->getPages()) {
            $html .= '<a href="'.$href.'&amp;limitstart='.(($collection->getPages()-1) * $collection->getLimit()).'">End</a>';    
        } else {
            $html .= 'End';
        }
        $html .= '</li>';
        $html .= '</ul>';
        
        // Print page info
        $html .= 'Page '.$collection->getCurrentPage();
        $html .= ' of '.$collection->getPages();
        
        $html .= "</div>";
        
        return $html;
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
            $publicId = "-//W3C//DTD HTML 4.01//EN";
            $systemId = "http://www.w3.org/TR/html4/strict.dtd";
            $imp = new DOMImplementation;
            $this->doctype = $imp->createDocumentType($this->qualified_name, 
                                                                    $publicId, 
                                                                    $systemId);
        }
        
        return $this->doctype;
    }
    
    /**
     * Add meta tag
     * 
     * @param string $name       This attribute identifies a property name. This 
     *                           specification does not list legal values for this 
     *                           attribute.
     * @param string $content    This attribute specifies a property's value. This 
     *                           specification does not list legal values for this 
     *                           attribute.
     * @param string $http_equiv This attribute may be used in place of the name attribute. 
     *                           HTTP servers use this attribute to gather information for 
     *                           HTTP response message headers.
     * 
     * @return void
     * @since  1.0
     * @todo   This method should check whether the meta tag has already been 
     *         added to avoid printing the same meta tag twice.
     */
    function addMetaTag($name, $content, $http_equiv=null) 
    {
        // Get head node
        $head_node = $this->dom->getElementsByTagName("head")->item(0);
        
        // Creare meta tag node
        $meta_node = $this->addNode($head_node, "meta");
        
        // Add name attribute if any
        if (!is_null($name)) {
            $this->addNodeAttr($meta_node, "name", $name);
        }
        // Add http_equiv attribute if any
        if (!is_null($http_equiv)) {
            $this->addNodeAttr($meta_node, "http_equiv", $http_equiv);
        }
        // Add content attribute
        $this->addNodeAttr($meta_node, "content", $content);
    }
    
    /**
     * Add linked scrip in document head
     * 
     * It takes both relative and absolute values.
     * 
     * @param string $src  The relative or absolute URL to the script source.
     * @param string $type The script type. Default is text/javascript.
     * 
     * @return void
     * @since  1.0
     * @todo   This method should check whether the script has already been 
     *         added to avoid loading the same script twice.
     */
    function addScript($src, $type='text/javascript') 
    {
        // Make source absolute URL
        $this->_makeAbsolute($src);
        
        // Get head node
        $head_node = $this->dom->getElementsByTagName("head")->item(0);
        
        // Create script tag node
        $this->addNode($head_node, "script", array("type"=>$type, "src"=>$src));
    }
    
    /**
     * Attach external stylesheet
     * 
     * @param string $href The relative or absolute URL to the link source.
     * @param string $type The link type. Default is text/css.
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function addStyleSheet($href, $type='text/css') 
    {
        // Make source absolute URL
        $this->_makeAbsolute($href);
        
        // Get head node
        $head_node = $this->dom->getElementsByTagName("head")->item(0);
        
        // Create script tag node
        $attrs = array("rel"=>"stylesheet", "href"=>$href, "type"=>$type);
        $this->addNode($head_node, "link", $attrs);
    }
    
    /**
     * Apply theme
     * 
     * @param PHPFrame_View $view The view object to process.
     * 
     * @access private
     * @return void
     * @since  1.0
     */
    private function _applyTheme(PHPFrame_View $view) 
    {
        // Add theme stylesheets
        $this->addStyleSheet("themes/".PHPFrame::Config()->get("theme")."/css/styles.css");
        
        // make pathway available in local scope
        $pathway = $view->getPathway();
        
        $component_output = $this->body;
        
        // Set file name to load depending on session auth
        $controller = PHPFrame::Request()->getControllerName();
        if ($controller == "login") {
            $template_filename = 'login.php';
        }
        else {
            $template_filename = 'index.php';
        }
        
        $template_path = PHPFRAME_INSTALL_DIR.DS."public".DS."themes".DS.PHPFrame::Config()->get("theme");
        
        // Start buffering
        ob_start();
        require_once $template_path.DS.$template_filename;
        // save buffer in body
        $this->body = ob_get_contents();
        // clean output buffer
        ob_end_clean();
    }
    
    /**
     * Make path absolute
     * 
     * @param string $path The path we want to make absolute.
     * 
     * @access private
     * @return void
     * @since  1.0
     */
    private function _makeAbsolute(&$path) 
    {
        // Add the document base if a relative path
        if (substr($path, 0, 4) != 'http') {
            $uri = new PHPFrame_URI();
            $path = $uri->getBase().$path;
        }
    }
}
