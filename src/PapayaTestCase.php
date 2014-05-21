<?php

if (!defined('PAPAYA_INCLUDE_PATH')) {
  if (is_dir(dirname(__FILE__).'/../cms-core')) {
    define('PAPAYA_INCLUDE_PATH', dirname(__FILE__).'/../cms-core/');
  } else {
    define('PAPAYA_INCLUDE_PATH', dirname(__FILE__).str_repeat('/..', 3).'/papaya-lib/');
  }
}

if (!defined('PAPAYA_DB_URI')) {
  define('PAPAYA_DB_URI', '');
}
if (!defined('PAPAYA_DB_TBL_OPTIONS')) {
  define('PAPAYA_DB_TBL_OPTIONS', 'papaya_options');
}

abstract class PapayaTestCase extends PHPUnit_Framework_TestCase {

  /**
  * current temporary directory
  * @var string
  */
  protected $_temporaryDirectory = NULL;
  /**
  * all create temporary directories
  * @var array
  */
  protected $_temporaryDirectories = array();

  /**
   * Papaya Mock Factory instance
   * @var PapayaMocks
   */
  private $_papayaMocks = NULL;


  /**
   * Papaya Dom Fixtures Factory instance
   * @var PapayaDomFixtures
   */
  private $_papayaDomFixtures = NULL;

  /**
  * Make sure that constants are defined, to avoid notices if they
  * are used in declarations.
  *
  * @param $names
  * @return void
  */
  public static function defineConstantDefaults($names) {
    if (!is_array($names)) {
      $names = func_get_args();
    }
    foreach ($names as $name) {
      if (!defined($name)) {
        define($name, NULL);
      }
    }
  }

  /**
   * @param array $paths
   * @param string|array $classMaps
   */
  public static function registerPapayaAutoloader(array $paths = array(), $classMaps = NULL) {
    if (!in_array('PapayaAutoloader::load', spl_autoload_functions())) {
      include_once(PAPAYA_INCLUDE_PATH.'system/Papaya/Autoloader.php');
      spl_autoload_register('PapayaAutoloader::load');
    }
    foreach ($paths as $prefix => $path) {
      if (preg_match('(^/|[a-zA-Z]:[\\\\/])', $path)) {
        PapayaAutoloader::registerPath($prefix, $path);
      } else {
        PapayaAutoloader::registerPath($prefix, PAPAYA_INCLUDE_PATH.$path);
      }
    }
    if (isset($classMaps)) {
      if (!is_array($classMaps)) {
        $classMaps = array($classMaps);
      }
      foreach ($classMaps as $file) {
        if (!preg_match('(^/|[a-zA-Z]:[\\\\/])', $file)) {
          $file = PAPAYA_INCLUDE_PATH.$file;
        }
        PapayaAutoloader::registerClassMap(dirname($file), include($file));
      }
    }
  }

  /**
   * @return PapayaMocks
   */
  public function mockPapaya() {
    if (NULL === $this->_papayaMocks) {
      include_once(dirname(__FILE__).'/Papaya/Mocks.php');
      $this->_papayaMocks = new PapayaMocks($this);
    }
    return $this->_papayaMocks;
  }

  /**
   * @return PapayaDomFixtures
   */
  public function domFixture() {
    if (NULL === $this->_papayaDomFixtures) {
      include_once(dirname(__FILE__).'/Papaya/DomFixtures.php');
      $this->_papayaDomFixtures = new PapayaDomFixtures($this);
    }
    return $this->_papayaDomFixtures;
  }

  /**
   * Use $this->mockPapaya()->application();
   *
   * @deprecated
   * @param array $objects
   * @return PapayaApplication
   */
  public function getMockApplicationObject($objects = array()) {
    return $this->mockPapaya()->application($objects);
  }

  /**
   * Use $this->mockPapaya()->options();
   *
   * @deprecated
   * @param array $options
   * @param array $tables
   * @internal param array $objects
   * @return PapayaConfiguration
   */
  public function getMockConfigurationObject($options = array(), $tables = array()) {
    return $this->mockPapaya()->options($options, $tables);
  }

  /**
   * Use $this->mockPapaya()->request();
   *
   * @deprecated
   * @param array $parameters
   * @param string $url
   * @param string $separator
   * @return PapayaRequest
   */
  public function getMockRequestObject($parameters = array(),
                                       $url = 'http://www.test.tld/test.html',
                                       $separator = '[]') {
    return $this->mockPapaya()->request($parameters, $url, $separator);
  }
  /**
  * create a temporary directory for file system functions
  *
  * @return string|bool temporary directory
  */
  public function createTemporaryDirectory() {
    if (function_exists('sys_get_temp_dir')) {
      $baseDirectory = sys_get_temp_dir();
    } elseif (file_exists('/tmp') &&
              is_dir('/tmp') &&
              is_writeable('/tmp')) {
      $baseDirectory = '/tmp';
    } elseif (is_writeable(dirname(__FILE__))) {
      $baseDirectory = dirname(__FILE__);
    } elseif (is_writeable('./')) {
      $baseDirectory = realpath('./');
    } else {
      $this->skipTest('Can not get writeable directory for file system functions.');
    }
    $counter = 0;
    do {
      $rand = substr(base64_encode(rand()), 0, -2);
      if (substr($baseDirectory, -1) == DIRECTORY_SEPARATOR) {
        $temporaryDirectory = $baseDirectory.'testfs.'.$rand;
      } else {
        $temporaryDirectory = $baseDirectory.DIRECTORY_SEPARATOR.'testfs.'.$rand;
      }
    } while (++$counter < 10 &&
             file_exists($temporaryDirectory) &&
             is_dir($temporaryDirectory));
    $this->_temporaryDirectory = $temporaryDirectory;
    if (file_exists($this->_temporaryDirectory) &&
        is_dir($this->_temporaryDirectory)) {
      $directory = $this->_temporaryDirectory;
      $this->_temporaryDirectory = '';
      $this->fail('Test directory "'.$directory.'" did already exists.');
      return FALSE;
    } else {
      $oldMask = umask(0);
      mkdir($this->_temporaryDirectory, 0777, TRUE);
      $this->_temporaryDirectories[] = $this->_temporaryDirectory;
      umask($oldMask);
      return $this->_temporaryDirectory;
    }
  }

  /**
  * remove temporary directory if it exists
  * @return void
  */
  public function removeTemporaryDirectory() {
    if (isset($this->_temporaryDirectories) &&
        is_array($this->_temporaryDirectories)) {
      foreach ($this->_temporaryDirectories as $temporaryDirectory) {
        if (file_exists($temporaryDirectory) &&
            is_dir($temporaryDirectory)) {
          $directoryIterator = new RecursiveDirectoryIterator($temporaryDirectory);
          $fileIterator = new RecursiveIteratorIterator(
            $directoryIterator, RecursiveIteratorIterator::CHILD_FIRST
          );
          foreach ($fileIterator as $file) {
            if ($file->isDir()) {
              if ($file->getBasename() != '.' &&
                  $file->getBasename() != '..') {
                rmdir($file->getPathname());
              }
            } else {
              unlink($file->getPathname());
            }
          }
          rmdir($temporaryDirectory);
        }
      }
    }
  }

  public function getProxy($originalClassName, array $methods = NULL, array $arguments = array(),
                           $proxyClassName = '', $callAutoload = TRUE) {
    include_once(dirname(__FILE__).'/ProxyObject/Generator.php');
    $proxyClass = PapayaProxyObjectGenerator::generate(
      $originalClassName, $methods, $proxyClassName, $callAutoload
    );
    if (!class_exists($proxyClass['proxyClassName'], FALSE)) {
      eval($proxyClass['code']);
    }
    if (empty($arguments)) {
      return new $proxyClass['proxyClassName']();
    } else {
      $proxy = new ReflectionClass($proxyClass['proxyClassName']);
      return $proxy->newInstanceArgs($arguments);
    }
  }

  public function assertAppendedXmlEqualsXmlFragment(
    $expected, PapayaXmlAppendable $control, $message = ''
  ) {
    $actualDom = new PapayaXmlDocument();
    $parent = $actualDom->appendElement('fragment');
    $control->appendTo($parent);
    $this->assertXmlFragmentEqualsXmlFragment(
      $expected,
      $actualDom->documentElement->saveFragment()
    );
  }

  public function assertXmlFragmentEqualsXmlFragment($expected, $actual, $message = '') {
    $this->assertXmlStringEqualsXmlString(
      '<fragment>'.$expected.'</fragment>',
      '<fragment>'.$actual.'</fragment>',
      $message
    );
  }
}


