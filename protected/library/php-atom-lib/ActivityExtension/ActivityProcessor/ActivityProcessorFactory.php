<?php

require_once 'ActivityProcessorBasic.php';
require_once 'DefaultObjectProcessor.php';
require_once 'ArticleObjectProcessor.php';
require_once 'AudioObjectProcessor.php';
require_once 'StatusObjectProcessor.php';
require_once 'PhotoObjectProcessor.php';
require_once 'BookmarkObjectProcessor.php';
require_once 'VideoObjectProcessor.php';

class ProcessorFactoryException extends Exception {
	
}

class ActivityProcessorFactory {
	const DEFAULT_PROCESSOR	= 'default';
	
	private static $_instance;
	protected $_processorTable;
	
	private function __construct() {
		$this->_processorTable = array (self::DEFAULT_PROCESSOR => array('processor' => 'DefaultObjectProcessor', 'level' => 0));
	}
	
	/**
	 * 
	 * @return ActivityProcessorFactory
	 */
	public static function getInstance() {
		if (!self::$_instance) {
			self::$_instance = new ActivityProcessorFactory();
		}
		
		return self::$_instance;
	}
	
	public function registerProcessor($objectType, $processor, $level) {
		if (isset($this->_processorTable[$objectType])) {
			throw new ProcessorFactoryException('Object Type ' . $objectType . 'has already been registered!!!');
		}
		else {
			$this->_processorTable[$objectType] = array('processor' => $processor, 'level' => $level);
		}
	}
	
	public function getProcessor(ActivityObjectExtension $object, $defaultType=null) {

		$objectType = 'default';
		
		if (count($object->objectType) == 0 && $defaultType !== null) {
			$object->addObjectType($defaultType);
		}
		
		$processor	= null;
		$level		= 0;
		foreach ($object->objectType as $type) {
			if (isset($this->_processorTable[$type->value])) {
				if ($level <= $this->_processorTable[$type->value]['level']) {
					$processor	= $this->_processorTable[$type->value];
					$objectType	= $type->value;
					$level = $processor['level']+1;
				}
			}
			else {
				if ($level == $this->_processorTable[self::DEFAULT_PROCESSOR]['level']) {
					$processor = $this->_processorTable[self::DEFAULT_PROCESSOR];
				}
			}
		}
		$result = $processor['processor'];
		
		return new $result($object, $objectType);
	}
}