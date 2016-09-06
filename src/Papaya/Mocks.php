<?php
PapayaTestCase::defineConstantDefaults(
  'PAPAYA_DB_TBL_AUTHOPTIONS',
  'PAPAYA_DB_TBL_AUTHUSER',
  'PAPAYA_DB_TBL_AUTHGROUPS',
  'PAPAYA_DB_TBL_AUTHLINK',
  'PAPAYA_DB_TBL_AUTHPERM',
  'PAPAYA_DB_TBL_AUTHMODPERMS',
  'PAPAYA_DB_TBL_AUTHMODPERMLINKS',
  'PAPAYA_DB_TBL_SURFER'
);

class PapayaMocks {

  /**
   * @var PapayaTestCase
   */
  private $_testCase;

  public function __construct(PapayaTestCase $testCase) {
    $this->_testCase = $testCase;
  }


  /*********************
   * $papaya
   ********************/

  public function application(array $objects = array()) {
    $testCase = $this->_testCase;
    $values = array();
    foreach ($objects as $identifier => $object) {
      $name = strToLower($identifier);
      $values[$name] = $object;
    }
    if (empty($values['options'])) {
      $values['options'] = $this->options();
    }
    if (empty($values['request'])) {
      $values['request'] = $this->request();
    }
    if (empty($values['references'])) {
      $values['references'] = $this->references();
    }
    $testCase->{'context_application_objects'.spl_object_hash($this)} = $values;

    $application = $testCase->getMock('PapayaApplication');
    $application
      ->expects($testCase->any())
      ->method('__isset')
      ->will($testCase->returnCallback(array($this, 'callbackApplicationHasObject')));
    $application
      ->expects($testCase->any())
      ->method('__get')
      ->will($testCase->returnCallback(array($this, 'callbackApplicationGetObject')));
    $application
      ->expects($testCase->any())
      ->method('__call')
      ->will($testCase->returnCallback(array($this, 'callbackApplicationGetObject')));
    $application
      ->expects($testCase->any())
      ->method('hasObject')
      ->will($testCase->returnCallback(array($this, 'callbackApplicationHasObject')));
    $application
      ->expects($testCase->any())
      ->method('getObject')
      ->will($testCase->returnCallback(array($this, 'callbackApplicationGetObject')));
    return $application;
  }

  public function callbackApplicationHasObject($name, $className = '') {
    $testCase = $this->_testCase;
    $values = $testCase->{'context_application_objects'.spl_object_hash($this)};
    $name = strToLower($name);
    if (isset($values[$name])) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * @param $name
   * @return NULL|object
   */
  public function callbackApplicationGetObject($name) {
    $testCase = $this->_testCase;
    $values = $testCase->{'context_application_objects'.spl_object_hash($this)};
    $name = strToLower($name);
    if (isset($values[$name])) {
      return $values[$name];
    }
    return NULL;
  }

  /*********************
   * $papaya->options
   ********************/

  public function options(array $values = array(), array $tables = array()) {
    $testCase = $this->_testCase;
    $testCase->{'context_options_'.spl_object_hash($this)} = $values;
    $testCase->{'context_tables_'.spl_object_hash($this)} = $tables;

    $options = $testCase
      ->getMockBuilder('PapayaConfigurationCms')
      ->disableOriginalConstructor()
      ->getMock();
    $options
      ->expects($testCase->any())
      ->method('offsetGet')
      ->will($testCase->returnCallback(array($this, 'callbackOptionsGet')));
    $options
      ->expects($testCase->any())
      ->method('get')
      ->will($testCase->returnCallback(array($this, 'callbackOptionsGet')));
    $options
      ->expects($testCase->any())
      ->method('getOption')
      ->will($testCase->returnCallback(array($this, 'callbackOptionsGet')));
    return $options;
  }

  public function callbackOptionsGet($name, $default = NULL) {
    $property = 'context_options_'.spl_object_hash($this);
    if (isset($this->_testCase->$property) &&
        is_array($this->_testCase->$property)) {
      $values = $this->_testCase->$property;
      if (isset($values[$name])) {
        return $values[$name];
      }
    }
    return $default;
  }

  public function callbackOptionsGetTableName($name, $usePrefix = TRUE) {
    $property = 'context_options_tables_'.spl_object_hash($this);
    $values = $this->_testCase->$property;
    if ($usePrefix && isset($values['papaya_'.$name])) {
      return $values['papaya_'.$name];
    } elseif (!$usePrefix && isset($values[$name])) {
      return $values[$name];
    } elseif ($usePrefix) {
      return 'papaya_'.$name;
    } else {
      return $name;
    }
  }


  /*********************
   * $papaya->request
   ********************/

  public function request(
    array $parameters = array(), $url = 'http://www.test.tld/test.html', $separator = '[]'
  ) {
    $testCase = $this->_testCase;
    $property = 'context_request_parameters_'.spl_object_hash($this);

    $testCase->$property = new PapayaRequestParameters();
    $testCase->$property->merge($parameters);
    $request = $testCase->getMock('PapayaRequest');
    $request
      ->expects($testCase->any())
      ->method('getUrl')
      ->will($testCase->returnValue(new PapayaUrl($url)));
    $request
      ->expects($testCase->any())
      ->method('getParameterGroupSeparator')
      ->will($testCase->returnValue($separator));
    $request
      ->expects($testCase->any())
      ->method('getBasePath')
      ->will($testCase->returnValue('/'));
    $request
      ->expects($testCase->any())
      ->method('setParameterGroupSeparator')
      ->will($testCase->returnValue($request));
    $request
      ->expects($testCase->any())
      ->method('getParameter')
      ->will($testCase->returnCallback(array($this, 'callbackRequestGetParameter')));
    $request
      ->expects($testCase->any())
      ->method('getParameters')
      ->will($testCase->returnCallback(array($this, 'callbackRequestGetParameters')));
    $request
      ->expects($testCase->any())
      ->method('getParameterGroup')
      ->will($testCase->returnCallback(array($this, 'callbackRequestGetParameterGroup')));
    $request
      ->expects($testCase->any())
      ->method('getMethod')
      ->will($testCase->returnValue('get'));
    return $request;
  }

  public function callbackRequestGetParameter($name, $default = '') {
    $property = 'context_request_parameters_'.spl_object_hash($this);
    return $this->_testCase->$property->get($name, $default);
  }

  public function callbackRequestGetParameters() {
    $property = 'context_request_parameters_'.spl_object_hash($this);
    return $this->_testCase->$property;
  }

  public function callbackRequestGetParameterGroup($name) {
    $property = 'context_request_parameters_'.spl_object_hash($this);
    return $this->_testCase->$property->getGroup($name);
  }

  /*********************
   * $papaya->response
   ********************/

  public function response() {
    return $this->_testCase->getMock('PapayaResponse');
  }

  /*****************************
   * $papaya->administrationUser
   ****************************/

  public function user($isLoggedIn) {
    $user = $this->_testCase->getMock('base_auth');
    $user
      ->expects($this->_testCase->any())
      ->method('isLoggedIn')
      ->will($this->_testCase->returnValue($isLoggedIn));
    return $user;
  }

  /*********************
   * PapayaDatabaseRecord
   ********************/

  public function record(array $data = array(), $className = 'PapayaDatabaseInterfaceRecord') {
    $valueMapExists = array();
    $valueMapIsset = array();
    $valueMapGet = array();
    foreach ($data as $name => $value) {
      $lowerCase = PapayaUtilStringIdentifier::toUnderscoreLower($name);
      $upperCase = PapayaUtilStringIdentifier::toUnderscoreUpper($name);
      $camelCase = PapayaUtilStringIdentifier::toCamelCase($name);
      $valueMapExists[] = array($lowerCase, TRUE);
      $valueMapIsset[] = array($lowerCase, isset($value));
      $valueMapGet[] = array($lowerCase, $value);
      $valueMapExists[] = array($upperCase, TRUE);
      $valueMapIsset[] = array($upperCase, isset($value));
      $valueMapGet[] = array($upperCase, $value);
      if ($lowerCase != $camelCase) {
        $valueMapExists[] = array($camelCase, TRUE);
        $valueMapIsset[] = array($camelCase, isset($value));
        $valueMapGet[] = array($camelCase, $value);
      }
    }
    $record = $this->_testCase->getMock($className);
    $record
      ->expects($this->_testCase->any())
      ->method('offsetExists')
      ->will($this->_testCase->returnValueMap($valueMapExists));
    $record
      ->expects($this->_testCase->any())
      ->method('__isset')
      ->will($this->_testCase->returnValueMap($valueMapIsset));
    $record
      ->expects($this->_testCase->any())
      ->method('offsetGet')
      ->will($this->_testCase->returnValueMap($valueMapGet));
    $record
      ->expects($this->_testCase->any())
      ->method('__get')
      ->will($this->_testCase->returnValueMap($valueMapGet));
    return $record;
  }

  /*********************
   * PapayaUiReference
   ********************/

  /**
   * @param string $url
   * @param null|PapayaUiReference|string $reference or reference class name
   * @return null|PHPUnit_Framework_MockObject_MockObject
   */
  public function reference($url = 'http://www.example.html', $reference = NULL) {
    if (!isset($reference)) {
      $reference = $this->_testCase->getMock('PapayaUiReference');
    } elseif (is_string($reference)) {
      $reference = $this->_testCase->getMock($reference);
    }
    $reference
      ->expects($this->_testCase->any())
      ->method('__toString')
      ->will($this->_testCase->returnValue($url));
    $reference
      ->expects($this->_testCase->any())
      ->method('get')
      ->will($this->_testCase->returnValue($url));
    $reference
      ->expects($this->_testCase->any())
      ->method('getRelative')
      ->will($this->_testCase->returnValue($url));
    return $reference;
  }

  /**************************
   * PapayaUiReferenceFactory
   *************************/

  public function references(array $links = array()) {
    $this->_testCase->{'context_references_factory_mapping'.spl_object_hash($this)} = $links;
    $references = $this->_testCase->getMock('PapayaUiReferenceFactory');
    $references
      ->expects($this->_testCase->any())
      ->method('byString')
      ->will(
        $this->_testCase->returnCallback(array($this, 'callbackGetReferenceForString'))
      );
    return $references;
  }

  public function callbackGetReferenceForString($index) {
    $links = $this->_testCase->{'context_references_factory_mapping'.spl_object_hash($this)};
    if (isset($links[$index])) {
      return $this->reference($links[$index]);
    } else {
      return $this->reference('link:'.$index);
    }
  }
}