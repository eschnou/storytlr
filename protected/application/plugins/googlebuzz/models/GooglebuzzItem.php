<?php
/*
 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *    Copyright 2010 John Hobbs
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *
 */
class GooglebuzzItem extends SourceItem {

	protected $_prefix 	= 'googlebuzz';

	protected $_preamble = 'Buzz activity: ';

	public function getContent() { return $this->_data['content']; }

	public function getTitle () { return $this->_data['title']; }

	public function getLink() { return $this->_data['link']; }

	public function getType() { return SourceItem::LINK_TYPE; }

	public function getBackup() {
		$item = array();
		$item['SourceID'] = $this->_data['source_id'];
		$item['Title'] = $this->_data['title'];
		$item['Content'] = $this->_data['content'];
		$item['Link'] = $this->_data['link'];
		$item['Published'] = $this->_data['published'];
		return $item;
	}

}