<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2008 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel_Shared
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.6.3, 2008-08-25
 */

define('IDENTIFIER_OLE', pack("CCCCCCCC", 0xd0,0xcf,0x11,0xe0,0xa1,0xb1,0x1a,0xe1));
define('IDENTIFIER_BIFF7', pack("CCCCCCCC", 0x09,0x08,0x08,0x00,0x00,0x05,0x05,0x00));
define('IDENTIFIER_BIFF8', pack("CCCCCCCC", 0x09,0x08,0x10,0x00,0x00,0x06,0x05,0x00));
// OpenOffice and Excel 97-2004 for Mac.
define('IDENTIFIER_OOF', pack("CCCCCCCC", 0xfd,0xff,0xff,0xff,0xff,0xff,0xff,0xff));
define('IDENTIFIER_MAC04', pack("CCCCCCCC", 0xfd,0xff,0xff,0xff,0x23,0x00,0x00,0x00));


class PHPExcel_Shared_OLERead {
	public $data = '';

	const NUM_BIG_BLOCK_DEPOT_BLOCKS_POS = 0x2c;
	const SMALL_BLOCK_DEPOT_BLOCK_POS = 0x3c;
	const ROOT_START_BLOCK_POS = 0x30;
	const BIG_BLOCK_SIZE = 0x200;
	const SMALL_BLOCK_SIZE = 0x40;
	const EXTENSION_BLOCK_POS = 0x44;
	const NUM_EXTENSION_BLOCK_POS = 0x48;
	const PROPERTY_STORAGE_BLOCK_SIZE = 0x80;
	const BIG_BLOCK_DEPOT_BLOCKS_POS = 0x4c;
	const SMALL_BLOCK_THRESHOLD = 0x1000;
	// property storage offsets
	const SIZE_OF_NAME_POS = 0x40;
	const TYPE_POS = 0x42;
	const START_BLOCK_POS = 0x74;
	const SIZE_POS = 0x78;
	const IDENTIFIER_OLE = IDENTIFIER_OLE;
	const IDENTIFIER_BIFF7 = IDENTIFIER_BIFF7;
	const IDENTIFIER_BIFF8 = IDENTIFIER_BIFF8;
	// OpenOffice and Excel 97-2004 for Mac.
	const IDENTIFIER_OOF = IDENTIFIER_OOF;
	const IDENTIFIER_MAC04 = IDENTIFIER_MAC04;

	public function read($sFileName)
	{
		// check if file exist and is readable (Darko Miljanovic)
		if(!is_readable($sFileName)) {
			$this->error = 1;
			return false;
		}

		$this->data = file_get_contents($sFileName);
		if (!$this->data) {
			$this->error = 1;
			return false;
		}

		if (substr($this->data, 0, 8) != self::IDENTIFIER_OLE) {
			$this->error = 1;
			return false;
		}

		// Check the start of the first block for a signature found in valid
		// Excel files

		// Incorrectly throws out some valid Excel files. Removing for now.
		/*$identifiers = array(
			IDENTIFIER_BIFF7,
			IDENTIFIER_BIFF8,
			IDENTIFIER_OOF,
			IDENTIFIER_MAC04
		);

		if (!in_array(substr($this->data, 512, 8), $identifiers)) {
			$this->error = 2;
			return false;
		}*/

		$this->numBigBlockDepotBlocks = $this->_GetInt4d($this->data, self::NUM_BIG_BLOCK_DEPOT_BLOCKS_POS);
		$this->sbdStartBlock = $this->_GetInt4d($this->data, self::SMALL_BLOCK_DEPOT_BLOCK_POS);
		$this->rootStartBlock = $this->_GetInt4d($this->data, self::ROOT_START_BLOCK_POS);
		$this->extensionBlock = $this->_GetInt4d($this->data, self::EXTENSION_BLOCK_POS);
		$this->numExtensionBlocks = $this->_GetInt4d($this->data, self::NUM_EXTENSION_BLOCK_POS);

		$bigBlockDepotBlocks = array();
		$pos = self::BIG_BLOCK_DEPOT_BLOCKS_POS;

		$bbdBlocks = $this->numBigBlockDepotBlocks;

		if ($this->numExtensionBlocks != 0) {
			$bbdBlocks = (self::BIG_BLOCK_SIZE - self::BIG_BLOCK_DEPOT_BLOCKS_POS)/4;
		}

		for ($i = 0; $i < $bbdBlocks; $i++) {
			  $bigBlockDepotBlocks[$i] = $this->_GetInt4d($this->data, $pos);
			  $pos += 4;
		}

		for ($j = 0; $j < $this->numExtensionBlocks; $j++) {
			$pos = ($this->extensionBlock + 1) * self::BIG_BLOCK_SIZE;
			$blocksToRead = min($this->numBigBlockDepotBlocks - $bbdBlocks, self::BIG_BLOCK_SIZE / 4 - 1);

			for ($i = $bbdBlocks; $i < $bbdBlocks + $blocksToRead; $i++) {
				$bigBlockDepotBlocks[$i] = $this->_GetInt4d($this->data, $pos);
				$pos += 4;
			}

			$bbdBlocks += $blocksToRead;
			if ($bbdBlocks < $this->numBigBlockDepotBlocks) {
				$this->extensionBlock = $this->_GetInt4d($this->data, $pos);
			}
		}

		$pos = 0;
		$index = 0;
		$this->bigBlockChain = array();

		for ($i = 0; $i < $this->numBigBlockDepotBlocks; $i++) {
			$pos = ($bigBlockDepotBlocks[$i] + 1) * self::BIG_BLOCK_SIZE;

			for ($j = 0 ; $j < self::BIG_BLOCK_SIZE / 4; $j++) {
				$this->bigBlockChain[$index] = $this->_GetInt4d($this->data, $pos);
				$pos += 4 ;
				$index++;
			}
		}

		$pos = 0;
		$index = 0;
		$sbdBlock = $this->sbdStartBlock;
		$this->smallBlockChain = array();

		while ($sbdBlock != -2) {
			$pos = ($sbdBlock + 1) * self::BIG_BLOCK_SIZE;

			for ($j = 0; $j < self::BIG_BLOCK_SIZE / 4; $j++) {
				$this->smallBlockChain[$index] = $this->_GetInt4d($this->data, $pos);
				$pos += 4;
				$index++;
			}

			$sbdBlock = $this->bigBlockChain[$sbdBlock];
		}

		$block = $this->rootStartBlock;
		$pos = 0;
		$this->entry = $this->_readData($block);

		$this->_readPropertySets();

	}

	public function getWorkBook()
	{
		if ($this->props[$this->wrkbook]['size'] < self::SMALL_BLOCK_THRESHOLD){
			$rootdata = $this->_readData($this->props[$this->rootentry]['startBlock']);

			$streamData = '';
			$block = $this->props[$this->wrkbook]['startBlock'];

			$pos = 0;
			while ($block != -2) {
	  			$pos = $block * self::SMALL_BLOCK_SIZE;
				$streamData .= substr($rootdata, $pos, self::SMALL_BLOCK_SIZE);

				$block = $this->smallBlockChain[$block];
			}

			return $streamData;


		} else {
			$numBlocks = $this->props[$this->wrkbook]['size'] / self::BIG_BLOCK_SIZE;
			if ($this->props[$this->wrkbook]['size'] % self::BIG_BLOCK_SIZE != 0) {
				$numBlocks++;
			}

			if ($numBlocks == 0) return '';


			$streamData = '';
			$block = $this->props[$this->wrkbook]['startBlock'];

			$pos = 0;

			while ($block != -2) {
				$pos = ($block + 1) * self::BIG_BLOCK_SIZE;
				$streamData .= substr($this->data, $pos, self::BIG_BLOCK_SIZE);
				$block = $this->bigBlockChain[$block];
			}

			return $streamData;
		}
	}

	public function _GetInt4d($data, $pos)
	{
		// Hacked by Andreas Rehm 2006 to ensure correct result of the <<24 block on 32 and 64bit systems
		$_or_24 = ord($data[$pos+3]);
		if ($_or_24>=128) $_ord_24 = -abs((256-$_or_24) << 24);
		else $_ord_24 = ($_or_24&127) << 24;

		return ord($data[$pos]) | (ord($data[$pos+1]) << 8) | (ord($data[$pos+2]) << 16) | $_ord_24;
	}

	 public function _readData($bl)
	 {
		$block = $bl;
		$pos = 0;
		$data = '';

		while ($block != -2)  {
			$pos = ($block + 1) * self::BIG_BLOCK_SIZE;
			$data = $data.substr($this->data, $pos, self::BIG_BLOCK_SIZE);
			$block = $this->bigBlockChain[$block];
		}
		return $data;
	 }

	public function _readPropertySets()
	{
		$offset = 0;

		while ($offset < strlen($this->entry)) {
			$d = substr($this->entry, $offset, self::PROPERTY_STORAGE_BLOCK_SIZE);

			$nameSize = ord($d[self::SIZE_OF_NAME_POS]) | (ord($d[self::SIZE_OF_NAME_POS+1]) << 8);

			$type = ord($d[self::TYPE_POS]);

			$startBlock = $this->_GetInt4d($d, self::START_BLOCK_POS);
			$size = $this->_GetInt4d($d, self::SIZE_POS);

			$name = '';
			for ($i = 0; $i < $nameSize ; $i++) {
				$name .= $d[$i];
			}

			$name = str_replace("\x00", "", $name);

			$this->props[] = array (
				'name' => $name,
				'type' => $type,
				'startBlock' => $startBlock,
				'size' => $size);

			if (($name == "Workbook") || ($name == "Book") || ($name == "WORKBOOK")) {
				$this->wrkbook = count($this->props) - 1;
			}

			if ($name == "Root Entry" || $name == "ROOT ENTRY") {
				$this->rootentry = count($this->props) - 1;
			}

			$offset += self::PROPERTY_STORAGE_BLOCK_SIZE;
		}

	}
}
