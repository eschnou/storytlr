<?php
require_once 'MediaEntryExtension.php';
require_once 'MediaLinkExtension.php';
require_once 'MediaNS.php';
//require_once 'php-atom-lib/ActivityExtension/ActivityNS.php';

class MediaExtensionException extends Exception { }

class MediaExtensionFactory implements IAtomExtensionFactory {

	public function adapt(SimpleXMLElement $atomNode) {
		switch ($atomNode->getName()) {
			case AtomNS::ENTRY_ELEMENT:
				return new MediaEntryExtension($atomNode, AtomNS::ENTRY_ELEMENT);
				break;
			case ActivityNS::OBJECT_ELEMENT:
				return new MediaEntryExtension($atomNode, ActivityNS::OBJECT_ELEMENT);
				break;
			case AtomNS::LINK_ELEMENT:
				return new MediaLinkExtension($atomNode);
				break;
			default:
				throw new ExtensionFactoryException('No Adaptor Available for '.$atomNode->getName().' element!');
		}
	}
	
	public function getNamespace() {
		return MediaNS::NS;
	}
}