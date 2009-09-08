<?php
/**
 * PHPFrame/Mail/Mailer.php
 * 
 * PHP version 5
 * 
 * @category  PHPFrame
 * @package   Mail
 * @author    Luis Montero <luis.montero@e-noise.com>
 * @copyright 2009 E-noise.com Limited
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   SVN: $Id$
 * @link      http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 */

/**
 * This class wraps around PHPMailer and sets up the mailer using the SMTP details 
 * provided in main config (etc/phpframe.ini).
 * 
 * @category PHPFrame
 * @package  Mail
 * @author   Luis Montero <luis.montero@e-noise.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://code.google.com/p/phpframe/source/browse/#svn/PHPFrame
 * @since    1.0
 */
class PHPFrame_Mailer extends PHPMailer
{
    private $_messageid_sfx=null;
    
    /**
     * Constructor
     * 
     * Initialise some PHPMailer default values
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function __construct() 
    {
        $this->Mailer   = PHPFrame::Config()->get("smtp.mailer");
        $this->Host     = PHPFrame::Config()->get("smtp.host");
        $this->Port     = PHPFrame::Config()->get("smtp.port");
        $this->SMTPAuth = PHPFrame::Config()->get("smtp.auth");
        $this->Username = PHPFrame::Config()->get("smtp.user");
        $this->Password = PHPFrame::Config()->get("smtp.pass");
        $this->From     = PHPFrame::Config()->get("smtp.fromaddress");
        $this->FromName = PHPFrame::Config()->get("smtp.fromname");
        
        // Sets the hostname to use in Message-Id and Received headers and as 
        // default HELO string. If empty, the value returned by SERVER_NAME is used 
        // or 'localhost.localdomain'.
        $this->Hostname = PHPFrame::Config()->get("smtp.host");
    }
    
    /**
     * This method allows to add a suffix to the message id.
     * 
     * This can be very useful when adding data to the message id for processing of 
     * replies. The suffix is added to the the headers in $this->CreateHeader() and 
     * is encoded in base64.
     * 
     * @param string $str
     * 
     * @access public
     * @return void
     * @since  1.0
     */
    public function setMessageIdSuffix($str) 
    {
        $this->_messageid_sfx = (string) $str;
    }
    
    /**
     * Get the message id suffix.
     * 
     * @access public
     * @return string
     * @since  1.0
     */
    public function getMessageIdSuffix() 
    {
        return $this->_messageid_sfx;
    }
    
    /**
     * This method overrides the parent CreateHeader() method.
     * 
     * This method appends the message id suffix encoded in base64.
     * 
     * @access public
     * @return string
     * @since  1.0
     */
    public function CreateHeader() 
    {
        $result = parent::CreateHeader();
        
        if (!is_null($this->_messageid_sfx)) {
            $pattern      = "/Message\-Id\: <([a-zA-Z0-9]+)@/i";
            $replacement  = "Message-Id: <$1-";
            $replacement .= base64_encode($this->_messageid_sfx)."@";
            $result       = preg_replace($pattern, $replacement, $result);
        }
        
        return $result;
    }
}
