<?php

require_once dirname(__FILE__) . '/Exception.php';

require_once dirname(__FILE__) . '/Utility.php';

class Pingback_Server {
  const RESPONSE_SUCCESS                    = -1;
  const RESPONSE_FAULT_GENERIC              = 0;
  const RESPONSE_FAULT_SOURCE               = 0x0010;
  const RESPONSE_FAULT_SOURCE_LINK          = 0x0011;
  const RESPONSE_FAULT_TARGET               = 0x0020;
  const RESPONSE_FAULT_TARGET_INVALID       = 0x0021;
  const RESPONSE_FAULT_ALREADY_REGISTERED   = 0x0030;
  const RESPONSE_FAULT_ACCESS_DENIED        = 0x0031;
  
  public $responses = array(
    self::RESPONSE_SUCCESS                  => 'Success',
    self::RESPONSE_FAULT_GENERIC            => 'Unknown error.',
    self::RESPONSE_FAULT_SOURCE             => 'The source URI does not exist.',
    self::RESPONSE_FAULT_SOURCE_LINK        => 'The source URI does not contain a link to the target URI, and so cannot be used as a source.',
    self::RESPONSE_FAULT_TARGET             => 'The specified target URI does not exist.',
    self::RESPONSE_FAULT_TARGET_INVALID     => 'The specified target URI cannot be used as a target.',
    self::RESPONSE_FAULT_ALREADY_REGISTERED => 'The pingback has already been registered.',
    self::RESPONSE_FAULT_ACCESS_DENIED      => 'Access denied.'
  );

  protected $_server;
  
  protected $_response;
  
  protected $_request;
  
  protected $_requestSource;
  
  protected $_requestTarget;
  
  protected $_options = array(
    'encoding' => 'utf-8'
  );

  public function __construct($options = array()) {
    $this->_server = xmlrpc_server_create();
    $this->setOptions($options);
    if(!xmlrpc_server_register_method($this->_server, 'pingback.ping', array($this, '_ping'))) {
      throw new Pingback_Exception('Failed to register method to server');
    }
  }
  
  public function __destruct() {
    xmlrpc_server_destroy($this->_server);
  }

  protected function _ping($method, $parameters) {
    list($this->_requestSource, $this->_requestTarget) = $parameters;
    
    $fault = null;

    // is the source argument really an url?
    if(!$fault && !Pingback_Utility::isURL($this->_requestSource)) $fault = self::RESPONSE_FAULT_SOURCE;

    // is the target argument really an url?
    if(!$fault && !Pingback_Utility::isURL($this->_requestTarget)) $fault = self::RESPONSE_FAULT_TARGET;

    // is the target url pingback enabled?
    if(!$fault && !Pingback_Utility::isPingbackEnabled($this->_requestTarget)) $fault = self::RESPONSE_FAULT_TARGET_INVALID;

    // is the source backlinking to the target?
    if(!$fault && !Pingback_Utility::isBacklinking($this->_requestSource, $this->_requestTarget)) $fault = self::RESPONSE_FAULT_SOURCE_LINK;
    
    if($fault !== null) {
      $this->setFault($fault);
      return $this->getFaultAsArray($fault);
    } else {
      $this->setSuccess();
      return $this->getSuccessAsArray();
    }
  }
  
  public function getOption($option) {
    return isset($this->_options[$option]) ? $this->_options[$option] : null;
  }
  
  public function setOption($option, $value) {
    $this->_options[$option] = $value;
  }
  
  public function setOptions($options = array()) {
    foreach($options as $option => $value) {
      $this->setOption($option, $value);
    }
  }

  public function execute($request = null) {
    if($request) {
      $this->_request = $request;
    }
    
    $this->_response = xmlrpc_server_call_method($this->_server, $this->_request, null, array('encoding' => $this->getOption('encoding')));
  }

  public function setResponse($response) {
    $this->_response = $response;
  }

  public function setRequest($request) {
    $this->_request = $request;
  }

  public function getRequest() {
    return $this->_request;
  }

  public function getResponse() {
    return $this->_response;
  }

  public function getSourceURL() {
    return $this->_requestSource;
  }

  public function getTargetURL() {
    return $this->_requestTarget;
  }
  
  public function getFaultAsArray($faultCode) {
    return array(
      'faultCode' => $faultCode,
      'faultString' => $this->responses[$faultCode]
    );
  }
  
  public function getSuccessAsArray() {
    return array($this->responses[self::RESPONSE_SUCCESS]);
  }

  public function setFault($faultCode) {
    $this->_response = xmlrpc_encode($this->getFaultAsArray($faultCode));
  }
  
  public function setSuccess() {
    $this->_response = xmlrpc_encode($this->getSuccessAsArray());
  }

  public function isValid() {
    return !xmlrpc_is_fault($this->_response);
  }
}
