<?php

class ExtensionFactoryException extends Exception { }

interface IAtomExtensionFactory {
	public function adapt(SimpleXMLElement $domObj);
	public function getNamespace();
}

function toAtomDate($timestamp) {
	return date(DATE_ATOM, $timestamp);
}

function toTimestamp($atomDate) { //is it really necessary?
	return strtotime($atomDate);
}