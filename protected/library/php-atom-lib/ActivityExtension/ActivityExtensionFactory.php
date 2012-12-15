<?php
require_once 'ActivityEntryExtension.php';
require_once 'ActivityObjectExtension.php';
require_once 'ActivityNS.php';
require_once 'ActivityProcessor/ActivityProcessorFactory.php';

class ActivityExtensionException extends Exception { }

class ActivityExtensionFactory implements IAtomExtensionFactory {

	public function adapt(SimpleXMLElement $atomNode) {
		switch ($atomNode->getName()) {
			case AtomNS::ENTRY_ELEMENT:
				return new ActivityEntryExtension($atomNode);
				break;
//			case ActivityNS::OBJECT_TYPE_ELEMENT:
//				return new SimpleActivityExtension($atomNode, ActivityNS::OBJECT_TYPE_ELEMENT);
//				break;
//			case ActivityNS::OBJECT_ELEMENT:
//				return new ActivityObjectExtension($atomNode, ActivityNS::OBJECT_ELEMENT);
//				break;
			case AtomNS::AUTHOR_ELEMENT:
				return new ActivityObjectExtension($atomNode, AtomNS::AUTHOR_ELEMENT);
				break;
			default:
				throw new ExtensionFactoryException('No Adaptor Available for '.$atomNode->getName().' element!');
		}
	}
	
	public function getNamespace() {
		return ActivityNS::NS;
	}
}