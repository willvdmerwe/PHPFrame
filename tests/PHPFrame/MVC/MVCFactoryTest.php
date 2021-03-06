<?php
// Include framework if not inculded yet
require_once preg_replace("/tests\/.*/", "src/PHPFrame.php", __FILE__);

class PHPFrame_MVCFactoryTest extends PHPUnit_Framework_TestCase
{
    private $_app, $_factory;

    public function setUp()
    {
        PHPFrame::testMode(true);

        $data_dir = preg_replace("/tests\/.*/", "data", __FILE__);
        PHPFrame::dataDir($data_dir);

        $install_dir = preg_replace("/tests\/.*/", "data/CLI_Tool", __FILE__);
        $home_dir    = PHPFrame_Filesystem::getUserHomeDir();
        $var_dir     = $home_dir.DS.".PHPFrame_CLI_Tool".DS."var";
        $tmp_dir     = $home_dir.DS.".PHPFrame_CLI_Tool".DS."tmp";

        PHPFrame_Filesystem::ensureWritableDir($home_dir.DS.".PHPFrame_CLI_Tool");

        $this->_app = new PHPFrame_Application(array(
            "install_dir" => $install_dir,
            "var_dir"     => $var_dir,
            "tmp_dir"     => $tmp_dir
        ));

        $this->_factory = new PHPFrame_MVCFactory($this->_app);
    }

    public function tearDown()
    {
        $tmp_dir = $this->_app->getTmpDir();
        $app_reg = $tmp_dir.DS."app.reg";

        if (is_file($app_reg)) {
            unlink($app_reg);
        }
        if (is_dir($tmp_dir)) {
            rmdir($tmp_dir);
        }

        $var_dir = $this->_app->getVarDir();
        $app_log = $var_dir.DS."app.log";
        $data_db = $var_dir.DS."data.db";

        if (is_file($app_log)) {
            unlink($app_log);
        }
        if (is_file($data_db)) {
            unlink($data_db);
        }

        // Destroy application
        $this->_app->__destruct();
    }

    public function test_getActionController()
    {
        $controller = $this->_factory->getActionController("app");

        $this->assertType("AppController", $controller);
    }

    public function test_getActionControllerWithClassPrefixFailure()
    {
        $this->_app->classPrefix("SomePrefix_");

        $this->setExpectedException("RuntimeException");
        $controller = $this->_factory->getActionController("app");
    }

    public function test_getActionControllerReflectionException()
    {
        $this->setExpectedException("RuntimeException");

        $controller = $this->_factory->getActionController("apppp");
    }

    public function test_view()
    {
        $view = $this->_factory->view("index");

        $this->assertType("PHPFrame_View", $view);
    }

    public function test_viewPassData()
    {
        $view = $this->_factory->view("index", array("key"=>"value"));
        $data = $view->getData();

        $this->assertType("PHPFrame_View", $view);
        $this->assertType("array", $data);
        $this->assertEquals(1, count($data));
        $this->assertArrayHasKey("key", $data);
        $this->assertEquals("value", $data["key"]);
    }

    public function test_viewPassDataFailure()
    {
        $this->setExpectedException("InvalidArgumentException");

        $view = $this->_factory->view("index", array("value"));
    }

    public function test_getViewHelper()
    {
        $helper = $this->_factory->getViewHelper("cli");
        $this->assertType("CliHelper", $helper);
    }

    public function test_getViewHelperWithClassPrefixFailured()
    {
        $this->_app->classPrefix("SomePrefix_");

        $this->setExpectedException("ReflectionException");

        $helper = $this->_factory->getViewHelper("cli");
    }
}
