<?php
require_once 'ThreadingNS.php';
require_once 'ThreadingEntryExtension.php';

class ThreadingExtensionException extends Exception { }

class ThreadingExtensionFactory implements IAtomExtensionFactory {

	public function adapt(SimpleXMLElement $atomNode) {
		switch ($atomNode->getName()) {
			case AtomNS::ENTRY_ELEMENT:
				return new ThreadingEntryExtension($atomNode);
				break;
			default:
				throw new ExtensionFactoryException('No Adaptor Available for '.$atomNode->getName().' element!');
		}
	}
	
	public function getNamespace() {
		return ThreadingNS::NS;
	}
}