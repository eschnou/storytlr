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
 * @package    PHPExcel_Writer
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.6.3, 2008-08-25
 */


/** PHPExcel_IWriter */
require_once 'PHPExcel/Writer/IWriter.php';

/** PHPExcel_Cell */
require_once 'PHPExcel/Cell.php';

/** PHPExcel_RichText */
require_once 'PHPExcel/RichText.php';

/** PHPExcel_Shared_String */
require_once 'PHPExcel/Shared/String.php';


/**
 * PHPExcel_Writer_CSV
 *
 * @category   PHPExcel
 * @package    PHPExcel_Writer
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Writer_CSV implements PHPExcel_Writer_IWriter {
	/**
	 * PHPExcel object
	 *
	 * @var PHPExcel
	 */
	private $_phpExcel;
	
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
	 * Sheet index to write
	 * 
	 * @var int
	 */
	private $_sheetIndex;
	
	/**
	 * Pre-calculate formulas
	 *
	 * @var boolean
	 */
	private $_preCalculateFormulas = true;
	
	/**
	 * Create a new PHPExcel_Writer_CSV
	 *
	 * @param 	PHPExcel	$phpExcel	PHPExcel object
	 */
	public function __construct(PHPExcel $phpExcel) {
		$this->_phpExcel 	= $phpExcel;
		$this->_delimiter 	= ',';
		$this->_enclosure 	= '"';
		$this->_lineEnding 	= PHP_EOL;
		$this->_sheetIndex 	= 0;
	}
	
	/**
	 * Save PHPExcel to file
	 *
	 * @param 	string 		$pFileName
	 * @throws 	Exception
	 */	
	public function save($pFilename = null) {
		// Fetch sheet
		$sheet = $this->_phpExcel->getSheet($this->_sheetIndex);
		
		// Open file
		$fileHandle = fopen($pFilename, 'w');
		if ($fileHandle === false) {
			throw new Exception("Could not open file $pFilename for writing.");
		}
		
		// Get cell collection
		$cellCollection = $sheet->getCellCollection();
		
		// Get column count
		$colCount = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
		
		// Loop trough cells
		$currentRow = -1;
		$rowData = array();
		foreach ($cellCollection as $cell) {					
			if ($currentRow != $cell->getRow()) {
				// End previous row?
				if ($currentRow != -1) {
					$this->_writeLine($fileHandle, $rowData);
				}

				// Set current row
				$currentRow = $cell->getRow();
			
				// Start a new row
				$rowData = array();
				for ($i = 0; $i < $colCount; $i++) {
					$rowData[$i] = '';
				}
			}
					
			// Copy cell
			$column = PHPExcel_Cell::columnIndexFromString($cell->getColumn()) - 1;
			if ($cell->getValue() instanceof PHPExcel_RichText) {
				$rowData[$column] = $cell->getValue()->getPlainText();
			} else {
				if ($this->_preCalculateFormulas) {
					$rowData[$column] = PHPExcel_Style_NumberFormat::toFormattedString(
						$cell->getCalculatedValue(),
						$sheet->getstyle( $cell->getCoordinate() )->getNumberFormat()->getFormatCode()
					);
				} else {
					$rowData[$column] = PHPExcel_Style_NumberFormat::toFormattedString(
						$cell->getValue(),
						$sheet->getstyle( $cell->getCoordinate() )->getNumberFormat()->getFormatCode()
					);
				}
			}
		}
		
		// End last row?
		if ($currentRow != -1) {
			$this->_writeLine($fileHandle, $rowData);
		}
				
		// Close file
		fclose($fileHandle);
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
			$pValue = null;
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
	
	/**
	 * Write line to CSV file
	 * 
	 * @param	mixed	$pFileHandle	PHP filehandle
	 * @param	array	$pValues		Array containing values in a row
	 * @throws	Exception
	 */
	private function _writeLine($pFileHandle = null, $pValues = null) {
		if (!is_null($pFileHandle) && is_array($pValues)) {
			// No leading delimiter
			$writeDelimiter = false;
			
			// Build the line
			$line = '';
			
			foreach ($pValues as $element) {
				// Decode UTF8 data
				if (PHPExcel_Shared_String::IsUTF8($element)) {
					$element = utf8_decode($element);
				}
				
				// Escape enclosures
				$element = str_replace($this->_enclosure, $this->_enclosure . $this->_enclosure, $element);

				// Add delimiter
				if ($writeDelimiter) {
					$line .= $this->_delimiter;
				} else {
					$writeDelimiter = true;
				}
				
				// Add enclosed string
				$line .= $this->_enclosure . $element . $this->_enclosure;
			}
			
			// Add line ending
			$line .= $this->_lineEnding;
			
			// Write to file
			fwrite($pFileHandle, $line);
		} else {
			throw new Exception("Invalid parameters passed.");
		}
	}

    /**
     * Get Pre-Calculate Formulas
     *
     * @return boolean
     */
    public function getPreCalculateFormulas() {
    	return $this->_preCalculateFormulas;
    }
    
    /**
     * Set Pre-Calculate Formulas
     *
     * @param boolean $pValue	Pre-Calculate Formulas?
     */
    public function setPreCalculateFormulas($pValue = true) {
    	$this->_preCalculateFormulas = $pValue;
    }
}
