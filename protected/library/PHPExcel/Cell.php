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
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.6.3, 2008-08-25
 */


/** PHPExcel_Cell_DataType */
require_once 'PHPExcel/Cell/DataType.php';

/** PHPExcel_Cell_DataValidation */
require_once 'PHPExcel/Cell/DataValidation.php';

/** PHPExcel_Cell_Hyperlink */
require_once 'PHPExcel/Cell/Hyperlink.php';

/** PHPExcel_Worksheet */
require_once 'PHPExcel/Worksheet.php';

/** PHPExcel_Calculation */
require_once 'PHPExcel/Calculation.php';


/**
 * PHPExcel_Cell
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Cell
{
	/**
	 * Column of the cell
	 *
	 * @var string
	 */
	private $_column;
	
	/**
	 * Row of the cell
	 *
	 * @var int
	 */
	private $_row;
	
	/**
	 * Value of the cell
	 *
	 * @var mixed
	 */
	private $_value;
	
	/**
	 * Type of the cell data
	 *
	 * @var string
	 */
	private $_dataType;
	
	/**
	 * Data validation
	 *
	 * @var PHPExcel_Cell_DataValidation
	 */
	private $_dataValidation;
	
	/**
	 * Hyperlink
	 *
	 * @var PHPExcel_Cell_Hyperlink
	 */
	private $_hyperlink;
	
	/**
	 * Parent worksheet
	 *
	 * @var PHPExcel_Worksheet
	 */
	private $_parent;
	   
    /**
     * Create a new Cell
     *
     * @param 	string 				$pColumn
     * @param 	int 				$pRow
     * @param 	mixed 				$pValue
     * @param 	string 				$pDataType
     * @param 	PHPExcel_Worksheet	$pSheet
     * @throws	Exception
     */
    public function __construct($pColumn = 'A', $pRow = 1, $pValue = null, $pDataType = null, PHPExcel_Worksheet $pSheet = null)
    {
    	// Initialise cell coordinate
    	$this->_column = strtoupper($pColumn);
    	$this->_row = $pRow;
    	
    	// Initialise cell value
    	$this->_value = $pValue;
    	
    	// Set datatype?
    	if (!is_null($pDataType)) {
    		$this->_dataType = $pDataType;
    	} else {
			$this->_dataType = PHPExcel_Cell_DataType::dataTypeForValue($pValue);
		}
 	
    	// Set worksheet
    	$this->_parent = $pSheet;
    }
    
    /**
     * Get cell coordinate column
     *
     * @return string
     */
    public function getColumn()
    {
    	return strtoupper($this->_column);
    }
    
    /**
     * Get cell coordinate row
     *
     * @return int
     */
    public function getRow()
    {
    	return $this->_row;
    }
    
    /**
     * Get cell coordinate
     *
     * @return string
     */
    public function getCoordinate()
    {
    	return $this->_column . $this->_row;
    }
    
    /**
     * Get cell value
     *
     * @return mixed
     */
    public function getValue()
    {
    	return $this->_value;
    }
    
    /**
     * Set cell value
     *
     * This clears the cell formula.
     *
     * @param mixed 	$pValue					Value
     * @param bool 		$pUpdateDataType		Update the data type?
     */
    public function setValue($pValue = null, $pUpdateDataType = true)
    {
    	$this->_value = $pValue;

    	if ($pUpdateDataType) {
    		$this->_dataType = PHPExcel_Cell_DataType::dataTypeForValue($pValue);
    	}
    }
    
    /**
     * Set cell value (with explicit data type given)
     *
     * @param mixed 	$pValue			Value
     * @param string	$pDataType		Explicit data type
     */
    public function setValueExplicit($pValue = null, $pDataType = PHPExcel_Cell_DataType::TYPE_STRING)
    {
    	$this->_value 		= $pValue;
   		$this->_dataType 	= $pDataType;
    }
    
    /**
     * Get caluclated cell value
     *
     * @return mixed
     */
    public function getCalculatedValue()
    {
		if (is_null($this->_value) || $this->_value === '') {
			return '';
    	} else if ($this->_dataType != PHPExcel_Cell_DataType::TYPE_FORMULA) {
			return $this->_value;
    	} else {
			return PHPExcel_Calculation::getInstance()->calculate($this);
    	}
    }
    
    /**
     * Get cell data type
     *
     * @return string
     */
    public function getDataType()
    {
    	return $this->_dataType;
    }
    
    /**
     * Set cell data type
     *
     * @param string $pDataType
     */
    public function setDataType($pDataType = PHPExcel_Cell_DataType::TYPE_STRING)
    {
    	$this->_dataType = $pDataType;
    }
    
    /**
     * Has Data validation?
     *
     * @return boolean
     */
    public function hasDataValidation()
    {
    	return !is_null($this->_dataValidation);
    }
    
    /**
     * Get Data validation
     *
     * @return PHPExcel_Cell_DataValidation
     */
    public function getDataValidation()
    {
    	if (is_null($this->_dataValidation)) {
    		$this->_dataValidation = new PHPExcel_Cell_DataValidation($this);
    	}
    	
    	return $this->_dataValidation;
    }
    
    /**
     * Set Data validation
     *
     * @param 	PHPExcel_Cell_DataValidation	$pDataValidation
     * @throws 	Exception
     */
    public function setDataValidation(PHPExcel_Cell_DataValidation $pDataValidation = null)
    {
   		$this->_dataValidation = $pDataValidation;
    	$this->_dataValidation->setParent($this);
    }
    
    /**
     * Has Hyperlink
     *
     * @return boolean
     */
    public function hasHyperlink()
    {
    	return !is_null($this->_hyperlink);
    }
    
    /**
     * Get Hyperlink
     *
     * @return PHPExcel_Cell_Hyperlink
     */
    public function getHyperlink()
    {
    	if (is_null($this->_hyperlink)) {
    		$this->_hyperlink = new PHPExcel_Cell_Hyperlink($this);
    	}
    	
    	return $this->_hyperlink;
    }
    
    /**
     * Set Hyperlink
     *
     * @param 	PHPExcel_Cell_Hyperlink	$pHyperlink
     * @throws 	Exception
     */
    public function setHyperlink(PHPExcel_Cell_Hyperlink $pHyperlink = null)
    {
   		$this->_hyperlink	= $pHyperlink;
    	$this->_hyperlink->setParent($this);
    }
    
    /**
     * Get parent
     *
     * @return PHPExcel_Worksheet
     */
    public function getParent() {
    	return $this->_parent;
    }
    
    /**
     * Re-bind parent
     *
     * @param PHPExcel_Worksheet $parent
     */
    public function rebindParent(PHPExcel_Worksheet $parent) {
		$this->_parent = $parent;
    }
    
	/**
	 * Is cell in a specific range?
	 *
	 * @param 	string 	$pRange		Cell range (e.g. A1:A1)
	 * @return 	boolean
	 */
	public function isInRange($pRange = 'A1:A1')
	{
	    // Uppercase coordinate
    	$pRange = strtoupper($pRange);
    	
   		// Extract range
   		$rangeA 	= '';
   		$rangeB 	= '';
   		if (strpos($pRange, ':') === false) {
   			$rangeA = $pRange;
   			$rangeB = $pRange;
   		} else {
   			list($rangeA, $rangeB) = explode(':', $pRange);
   		}
    		
   		// Calculate range outer borders
   		$rangeStart = PHPExcel_Cell::coordinateFromString($rangeA);
   		$rangeEnd 	= PHPExcel_Cell::coordinateFromString($rangeB);
    		
   		// Translate column into index
   		$rangeStart[0]	= PHPExcel_Cell::columnIndexFromString($rangeStart[0]) - 1;
   		$rangeEnd[0]	= PHPExcel_Cell::columnIndexFromString($rangeEnd[0]) - 1;
   		
   		// Translate properties
		$myColumn		= PHPExcel_Cell::columnIndexFromString($this->getColumn()) - 1;
		$myRow			= $this->getRow();
		
		// Verify if cell is in range
		return (
				($rangeStart[0] <= $myColumn && $rangeEnd[0] >= $myColumn) &&
				($rangeStart[1] <= $myRow && $rangeEnd[1] >= $myRow)
		);
	}
    
    /**
     * Coordinate from string
     *
     * @param 	string 	$pCoordinateString
     * @return 	array 	Array containing column and row (indexes 0 and 1)
     * @throws	Exception
     */
    public static function coordinateFromString($pCoordinateString = 'A1')
    {
    	if (eregi(':', $pCoordinateString)) {
    		throw new Exception('Cell coordinate string can not be a range of cells.');
    	} else if ($pCoordinateString == '') {
    		throw new Exception('Cell coordinate can not be zero-length string.');
    	} else {
	    	// Column
	    	$column = '';
	    	
	    	// Row
	    	$row = '';
	    	
	        // Convert a cell reference
	        if (preg_match("/([$]?[A-Z]+)([$]?\d+)/", $pCoordinateString, $matches)) {
	            list(, $column, $row) = $matches;
	        }
	    	
	    	// Return array
	    	return array($column, $row);
    	}
    }
    
    /**
     * Make string coordinate absolute
     *
     * @param 	string 	$pCoordinateString
     * @return 	string	Absolute coordinate
     * @throws	Exception
     */
    public static function absoluteCoordinate($pCoordinateString = 'A1')
    {
    	if (!eregi(':', $pCoordinateString)) {
	    	// Return value
	    	$returnValue = '';
	    	
	    	// Create absolute coordinate
	    	list($column, $row) = PHPExcel_Cell::coordinateFromString($pCoordinateString);
	    	$returnValue = '$' . $column . '$' . $row;
	    	
	    	// Return
	    	return $returnValue;
    	} else {
    		throw new Exception("Coordinate string should not be a cell range.");
    	}
    }
    
    /**
     * Split range into coordinate strings
     *
     * @param 	string 	$pRange
     * @return 	array	Array containg two coordinate strings
     */
    public static function splitRange($pRange = 'A1:A1')
    {
    	return explode(':', $pRange);
    }
    
	/**
	 * Calculate range dimension
	 *
	 * @param 	string 	$pRange		Cell range (e.g. A1:A1)
	 * @return 	array	Range dimension (width, height)
	 */
	public static function rangeDimension($pRange = 'A1:A1')
	{
	    // Uppercase coordinate
    	$pRange = strtoupper($pRange);
    	
   		// Extract range
   		$rangeA 	= '';
   		$rangeB 	= '';
   		if (strpos($pRange, ':') === false) {
   			$rangeA = $pRange;
   			$rangeB = $pRange;
   		} else {
   			list($rangeA, $rangeB) = explode(':', $pRange);
   		}
    		
   		// Calculate range outer borders
   		$rangeStart = PHPExcel_Cell::coordinateFromString($rangeA);
   		$rangeEnd 	= PHPExcel_Cell::coordinateFromString($rangeB);
    		
   		// Translate column into index
   		$rangeStart[0]	= PHPExcel_Cell::columnIndexFromString($rangeStart[0]);
   		$rangeEnd[0]	= PHPExcel_Cell::columnIndexFromString($rangeEnd[0]);
   		
   		return array( ($rangeEnd[0] - $rangeStart[0] + 1), ($rangeEnd[1] - $rangeStart[1] + 1) );
	}
    
    /**
     * Column index from string
     *
     * @param 	string $pString
     * @return 	int Column index (base 1 !!!)
     * @throws 	Exception
     */
    public static function columnIndexFromString($pString = 'A')
    {
    	// Convert to uppercase
    	$pString = strtoupper($pString);
    	
    	// Convert column to integer
    	if (strlen($pString) == 1) {
    		$result = 0;
    		$result += (ord(substr($pString, 0, 1)) - 65);
    		$result += 1;
    		
    		return $result;
    	} else if (strlen($pString) == 2) {
    		$result = 0;
    		$result += ( (1 + (ord(substr($pString, 0, 1)) - 65) ) * 26);
    		$result += (ord(substr($pString, 1, 2)) - 65);
    		$result += 1;
    		
    		return $result;
    	} else if (strlen($pString) == 3) {
			$result = 0;
			$result += ( (1 + (ord(substr($pString, 0, 1)) - 65) ) * 26 * 26);
			$result += ( (1 + (ord(substr($pString, 1, 1)) - 65) ) * 26);
			$result += (ord(substr($pString, 2, 2)) - 65);
			$result += 1;
			
    		return $result;
    	} else {
    		throw new Exception("Column string index can not be " . (strlen($pString) != 0 ? "longer than 2 characters" : "empty") . ".");
    	}
    }
    
    /**
     * String from columnindex 
     *
     * @param int $pColumnIndex Column index (base 0 !!!)
     * @return string
     */
    public static function stringFromColumnIndex($pColumnIndex = 0)
    {
        // Convert column to string
        $returnValue = '';
        // Determine column string
        if ($pColumnIndex < 26) {
        	$returnValue = chr(65 + $pColumnIndex);
        } else {
        	$iRemainder = (int)($pColumnIndex / 26) -1;
        	$returnValue = PHPExcel_Cell::stringFromColumnIndex( $iRemainder  ).chr(65 + $pColumnIndex%26) ;
        }
        // Return
        return $returnValue;
    }
    
    /**
     * Extract all cell references in range
     *
     * @param 	string 	$pRange		Range (e.g. A1 or A1:A10 or A1:A10 A100:A1000)
     * @return 	array	Array containing single cell references
     */
    public static function extractAllCellReferencesInRange($pRange = 'A1') {
    	// Returnvalue
    	$returnValue = array();
    	
    	// Explode spaces
    	$aExplodeSpaces = explode(' ', str_replace('$', '', strtoupper($pRange)));
    	foreach ($aExplodeSpaces as $explodedSpaces) {
    		// Single cell?
    		if (strpos($explodedSpaces, ':') === false) {
    			$col = 'A';
    			$row = 1;
    			list($col, $row) = PHPExcel_Cell::coordinateFromString($explodedSpaces);

    			if (strlen($col) <= 2) {
    				$returnValue[] = $explodedSpaces;
    			}
			
    			continue;
    		}
    		
    		// Range...
			$rangeStart		= '';
			$rangeEnd		= '';
			$startingCol 	= 0;
			$startingRow 	= 0;
			$endingCol 		= 0;
			$endingRow 		= 0;
										
			list($rangeStart, $rangeEnd) 		= explode(':', $explodedSpaces);
			list($startingCol, $startingRow)	= PHPExcel_Cell::coordinateFromString($rangeStart);
			list($endingCol, $endingRow) 	 	= PHPExcel_Cell::coordinateFromString($rangeEnd);
										
			// Conversions...
			$startingCol 	= PHPExcel_Cell::columnIndexFromString($startingCol) - 1;
			$endingCol 		= PHPExcel_Cell::columnIndexFromString($endingCol) - 1;
											
			// Current data
			$currentCol 	= $startingCol;
			$currentRow 	= $startingRow;
	
			// Loop cells
			while ($currentCol >= $startingCol && $currentCol <= $endingCol) {	
				while ($currentRow >= $startingRow && $currentRow <= $endingRow) {
	    			if (strlen(PHPExcel_Cell::stringFromColumnIndex($currentCol)) <= 2) {
	    				$returnValue[] = PHPExcel_Cell::stringFromColumnIndex($currentCol) . $currentRow;
	    			}
	
					$currentRow++;	
				}
																
				$currentCol++;	
				$currentRow = $startingRow;
			}
    	}
    	
    	// Return value
    	return $returnValue;
    }
	
	/**
	 * Compare 2 cells
	 *
	 * @param 	PHPExcel_Cell	$a	Cell a
	 * @param 	PHPExcel_Cell	$a	Cell b
	 * @return 	int		Result of comparison (always -1 or 1, never zero!)
	 */
	public static function compareCells(PHPExcel_Cell $a, PHPExcel_Cell $b)
	{
		if ($a->_row < $b->_row) {
			return -1;
		} elseif ($a->_row > $b->_row) {
			return 1;
		} elseif (PHPExcel_Cell::columnIndexFromString($a->_column) < PHPExcel_Cell::columnIndexFromString($b->_column)) {
			return -1;
		} else {
			return 1;
		}
	}
	    
	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone() {
		$vars = get_object_vars($this);
		foreach ($vars as $key => $value) {
			if (is_object($value)) {
				$this->$key = clone $value;
			} else {
				$this->$key = $value;
			}
		}
	}
}
