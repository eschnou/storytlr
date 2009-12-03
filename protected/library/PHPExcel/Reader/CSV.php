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
 * @package    PHPExcel_Reader
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.6.3, 2008-08-25
 */


/** PHPExcel */
require_once 'PHPExcel.php';

/** PHPExcel_Reader_IReader */
require_once 'PHPExcel/Reader/IReader.php';

/** PHPExcel_Worksheet */
require_once 'PHPExcel/Worksheet.php';

/** PHPExcel_Cell */
require_once 'PHPExcel/Cell.php';


/**
 * PHPExcel_Reader_CSV
 *
 * @category   PHPExcel
 * @package    PHPExcel_Reader
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Reader_CSV implements PHPExcel_Reader_IReader
{
	/**
	 * Delimiter
	 * 
	 * @var string
	 */
	private $_delimiter;
	
	/**
	 * Enclosure
	 * 
	 * @var string
	 */
	private $_enclosure;
	
	/**
	 * Line ending
	 * 
	 * @var string
	 */
	private $_lineEnding;
	
	/**
	 * Sheet index to read
	 * 
	 * @var int
	 */
	private $_sheetIndex;
	
	/**
	 * Create a new PHPExcel_Reader_CSV
	 */
	public function __construct() {
		$this->_delimiter 	= ',';
		$this->_enclosure 	= '"';
		$this->_lineEnding 	= PHP_EOL;
		$this->_sheetIndex 	= 0;
	}
	
	/**
	 * Loads PHPExcel from file
	 *
	 * @param 	string 		$pFilename
	 * @throws 	Exception
	 */	
	public function load($pFilename)
	{
		// Create new PHPExcel
		$objPHPExcel = new PHPExcel();
		
		// Load into this instance
		return $this->loadIntoExisting($pFilename, $objPHPExcel);
	}
	
	/**
	 * Loads PHPExcel from file into PHPExcel instance
	 *
	 * @param 	string 		$pFilename
	 * @param	PHPExcel	$objPHPExcel
	 * @throws 	Exception
	 */	
	public function loadIntoExisting($pFilename, PHPExcel $objPHPExcel)
	{
		// Check if file exists
		if (!file_exists($pFilename)) {
			throw new Exception("Could not open " . $pFilename . " for reading! File does not exist.");
		}
			
		// Create new PHPExcel
		while ($objPHPExcel->getSheetCount() <= $this->_sheetIndex) {
			$objPHPExcel->createSheet();
		}
		$objPHPExcel->setActiveSheetIndex( $this->_sheetIndex );
		
		// Open file
		$fileHandle = fopen($pFilename, 'r');
		if ($fileHandle === false) {
			throw new Exception("Could not open file $pFilename for reading.");
		}
		
		// Loop trough file
		$currentRow = 0;
		$rowData = array();
		while (($rowData = fgetcsv($fileHandle, 0, $this->_delimiter, $this->_enclosure)) !== FALSE) {
			$currentRow++;
			for ($i = 0; $i < count($rowData); $i++) {
				if ($rowData[$i] != '') {
					// Unescape enclosures
					$rowData[$i] = str_replace("\\" . $this->_enclosure, $this->_enclosure, $rowData[$i]);
					$rowData[$i] = str_replace($this->_enclosure . $this->_enclosure, $this->_enclosure, $rowData[$i]);
				
					// Set cell value
					$objPHPExcel->getActiveSheet()->setCellValue(
						PHPExcel_Cell::stringFromColumnIndex($i) . $currentRow, $rowData[$i]
					);
				}
			}
		}

		// Close file
		fclose($fileHandle);
		
		// Return
		return $objPHPExcel;
	}

	/**
	 * Get delimiter
	 * 
	 * @return string
	 */
	public function getDelimiter() {
		return $this->_delimiter;
	}
	
	/**
	 * Set delimiter
	 * 
	 * @param	string	$pValue		Delimiter, defaults to ,
	 */
	public function setDelimiter($pValue = ',') {
		$this->_delimiter = $pValue;
	}
	
	/**
	 * Get enclosure
	 * 
	 * @return string
	 */
	public function getEnclosure() {
		return $this->_enclosure;
	}
	
	/**
	 * Set enclosure
	 * 
	 * @param	string	$pValue		Enclosure, defaults to "
	 */
	public function setEnclosure($pValue = '"') {
		if ($pValue == '') {
			$pValue = '"';
		}
		$this->_enclosure = $pValue;
	}
	
	/**
	 * Get line ending
	 * 
	 * @return string
	 */
	public function getLineEnding() {
		return $this->_lineEnding;
	}
	
	/**
	 * Set line ending
	 * 
	 * @param	string	$pValue		Line ending, defaults to OS line ending (PHP_EOL)
	 */
	public function setLineEnding($pValue = PHP_EOL) {
		$this->_lineEnding = $pValue;
	}
	
	/**
	 * Get sheet index
	 * 
	 * @return int
	 */
	public function getSheetIndex() {
		return $this->_sheetIndex;
	}
	
	/**
	 * Set sheet index
	 * 
	 * @param	int		$pValue		Sheet index
	 */
	public function setSheetIndex($pValue = 0) {
		$this->_sheetIndex = $pValue;
	}
}
