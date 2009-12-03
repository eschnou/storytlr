<?php
/*
 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
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

class Themes
{
	public static function getAvailableThemes() {
		if (Zend_Registry::isRegistered("cache")) {
			$cache = Zend_Registry::get("cache");
		} else {
			$cache = false;
		}
		
		if ($cache && ($result = $cache->load('Themes_getAvailableThemes'))) {
			return $result;
		}
		
		$root = Zend_Registry::get("root");
		$dir = dirname($root) . "/themes/";
		$themes = array();
		$files  = array();
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file != "." && $file != ".." && $file != "CVS" && $file != "SVN") {
						$name		= basename($file);
						$files[]	= $name;
					}
				}
				closedir($dh);
			}
		}
		
		sort($files);
		foreach($files as $file) {
				$config 	= "$dir/$file/config.ini";
				
				if (!file_exists($config)) {
					continue; 
				}
				
				$theme			= parse_ini_file($config);
				$theme['name'] 	= $file;
				$themes[$file] 	= $theme; 
		}
		
		if ($cache) {
			$cache->save($themes, 'Themes_getAvailableThemes');
		}
		
		return $themes;
	}
}