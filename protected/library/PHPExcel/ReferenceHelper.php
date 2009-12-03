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


/** PHPExcel_Worksheet */
require_once 'PHPExcel/Worksheet.php';

/** PHPExcel_Cell */
require_once 'PHPExcel/Cell.php';

/** PHPExcel_Cell_DataType */
require_once 'PHPExcel/Cell/DataType.php';

/** PHPExcel_Style */
require_once 'PHPExcel/Style.php';

/** PHPExcel_Worksheet_Drawing */
require_once 'PHPExcel/Worksheet/Drawing.php';

/** PHPExcel_Calculation_FormulaParser */
require_once 'PHPExcel/Calculation/FormulaParser.php';

/** PHPExcel_Calculation_FormulaToken */
require_once 'PHPExcel/Calculation/FormulaToken.php';


/**
 * PHPExcel_ReferenceHelper (Singleton)
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_ReferenceHelper
{
	/**
	 * Instance of this class
	 *
	 * @var PHPExcel_ReferenceHelper
	 */
	private static $_instance;
	
	/**
	 * Get an instance of this class
	 *
	 * @return PHPExcel_ReferenceHelper
	 */
	public static function getInstance() {
		if (!isset(self::$_instance) || is_null(self::$_instance)) {
			self::$_instance = new PHPExcel_ReferenceHelper();
		}
		
		return self::$_instance;
	}
	
	/**
	 * Create a new PHPExcel_Calculation
	 */
	protected function __construct() {
	}
	
    /**
     * Insert a new column, updating all possible related data
     *
     * @param 	int	$pBefore	Insert before this one
     * @param 	int	$pNumCols	Number of columns to insert
     * @param 	int	$pNumRows	Number of rows to insert
     * @throws 	Exception
     */
    public function insertNewBefore($pBefore = 'A1', $pNumCols = 0, $pNumRows = 0, PHPExcel_Worksheet $pSheet = null) {
		// Get a copy of the cell collection
		/*$aTemp = $pSheet->getCellCollection();
		$aCellCollection = array();
		foreach ($aTemp as $key => $value) {
			$aCellCollection[$key] = clone $value;
		}*/
		$aCellCollection = $pSheet->getCellCollection();
			
    	// Get coordinates of $pBefore
    	$beforeColumn 	= 'A';
    	$beforeRow		= 1;
    	list($beforeColumn, $beforeRow) = PHPExcel_Cell::coordinateFromString( $pBefore );
    		
    		
		// Remove cell styles?
		$highestColumn 	= $pSheet->getHighestColumn();
		$highestRow 	= $pSheet->getHighestRow();

		if ($pNumCols < 0 && PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2 + $pNumCols > 0) {
			for ($i = 1; $i <= $highestRow - 1; $i++) {

				$pSheet->duplicateStyle(
					new PHPExcel_Style(),
					(PHPExcel_Cell::stringFromColumnIndex( PHPExcel_Cell::columnIndexFromString($beforeColumn) - 1 + $pNumCols ) . $i) . ':' . (PHPExcel_Cell::stringFromColumnIndex( PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2 ) . $i)
				);	
					
			}
		}

		if ($pNumRows < 0 && $beforeRow - 1 + $pNumRows > 0) {
			for ($i = PHPExcel_Cell::columnIndexFromString($beforeColumn) - 1; $i <= PHPExcel_Cell::columnIndexFromString($highestColumn) - 1; $i++) {
					
				$pSheet->duplicateStyle(
					new PHPExcel_Style(),
					(PHPExcel_Cell::stringFromColumnIndex($i) . ($beforeRow + $pNumRows)) . ':' . (PHPExcel_Cell::stringFromColumnIndex($i) . ($beforeRow - 1))
				);
					
			}
		}
			
			
   		// Loop trough cells, bottom-up, and change cell coordinates
		while ( ($cell = ($pNumCols < 0 || $pNumRows < 0) ? array_shift($aCellCollection) : array_pop($aCellCollection)) ) {
			// New coordinates
			$newCoordinates = PHPExcel_Cell::stringFromColumnIndex( PHPExcel_Cell::columnIndexFromString($cell->getColumn()) - 1 + $pNumCols ) . ($cell->getRow() + $pNumRows);
								
			// Should the cell be updated?
			if ( 
					(PHPExcel_Cell::columnIndexFromString( $cell->getColumn() ) >= PHPExcel_Cell::columnIndexFromString($beforeColumn)) &&
				 	($cell->getRow() >= $beforeRow)
				 ) {

				// Update cell styles
				$pSheet->duplicateStyle( $pSheet->getStyle($cell->getCoordinate()), $newCoordinates . ':' . $newCoordinates );
				$pSheet->duplicateStyle( $pSheet->getDefaultStyle(), $cell->getCoordinate() . ':' . $cell->getCoordinate() );
					
				// Insert this cell at its new location
				if ($cell->getDataType() == PHPExcel_Cell_DataType::TYPE_FORMULA) {
					// Formula should be adjusted
					$pSheet->setCellValue(
						  $newCoordinates
						, $this->updateFormulaReferences($cell->getValue(), $pBefore, $pNumCols, $pNumRows)
					);
				} else {
					// Formula should not be adjusted
					$pSheet->setCellValue($newCoordinates, $cell->getValue());
				}
				
				// Clear the original cell
				$pSheet->setCellValue($cell->getCoordinate(), '');
			}
		}
			
			
		// Duplicate styles for the newly inserted cells
		$highestColumn 	= $pSheet->getHighestColumn();
		$highestRow 	= $pSheet->getHighestRow();
			
		if ($pNumCols > 0 && PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2 > 0) {
			for ($i = $beforeRow; $i <= $highestRow - 1; $i++) {
				
				// Style
				$pSheet->duplicateStyle(
					$pSheet->getStyle(
						(PHPExcel_Cell::stringFromColumnIndex( PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2 ) . $i)
					),
					($beforeColumn . $i) . ':' . (PHPExcel_Cell::stringFromColumnIndex( PHPExcel_Cell::columnIndexFromString($beforeColumn) - 2 + $pNumCols ) . $i)
				);
				
			}
		}
			
		if ($pNumRows > 0 && $beforeRow - 1 > 0) {
			for ($i = PHPExcel_Cell::columnIndexFromString($beforeColumn) - 1; $i <= PHPExcel_Cell::columnIndexFromString($highestColumn) - 1; $i++) {
					
				// Style
				$pSheet->duplicateStyle(
					$pSheet->getStyle(
						(PHPExcel_Cell::stringFromColumnIndex($i) . ($beforeRow - 1))
					),
					(PHPExcel_Cell::stringFromColumnIndex($i) . $beforeRow) . ':' . (PHPExcel_Cell::stringFromColumnIndex($i) . ($beforeRow - 1 + $pNumRows))
				);
				
			}
		}
	
			
		// Update worksheet: column dimensions
		$aColumnDimensions = array_reverse($pSheet->getColumnDimensions(), true);
		foreach ($aColumnDimensions as $objColumnDimension) {
			$newReference = $this->updateCellReference($objColumnDimension->getColumnIndex() . '1', $pBefore, $pNumCols, $pNumRows);
			list($newReference) = PHPExcel_Cell::coordinateFromString($newReference);
			if ($objColumnDimension->getColumnIndex() != $newReference) {
				$objColumnDimension->setColumnIndex($newReference);
			}
		}
		$pSheet->refreshColumnDimensions();
		
		
		// Update worksheet: row dimensions
		$aRowDimensions = array_reverse($pSheet->getRowDimensions(), true);
		foreach ($aRowDimensions as $objRowDimension) {
			$newReference = $this->updateCellReference('A' . $objRowDimension->getRowIndex(), $pBefore, $pNumCols, $pNumRows);
			list(, $newReference) = PHPExcel_Cell::coordinateFromString($newReference);
			if ($objRowDimension->getRowIndex() != $newReference) {
				$objRowDimension->setRowIndex($newReference);
			}
		}
		$pSheet->refreshRowDimensions();

		$copyDimension = $pSheet->getRowDimension($beforeRow - 1);
		for ($i = $beforeRow; $i <= $beforeRow - 1 + $pNumRows; $i++) {
			$newDimension = $pSheet->getRowDimension($i);
			$newDimension->setRowHeight($copyDimension->getRowHeight());
			$newDimension->setVisible($copyDimension->getVisible());
			$newDimension->setOutlineLevel($copyDimension->getOutlineLevel());
			$newDimension->setCollapsed($copyDimension->getCollapsed());
		}
			
			
		// Update worksheet: breaks
		$aBreaks = array_reverse($pSheet->getBreaks(), true);
		foreach ($aBreaks as $key => $value) {
			$newReference = $this->updateCellReference($key, $pBefore, $pNumCols, $pNumRows);
			if ($key != $newReference) {
				$pSheet->setBreak( $newReference, $value );
				$pSheet->setBreak( $key, PHPExcel_Worksheet::BREAK_NONE );
			}
		}
			
			
		// Update worksheet: merge cells
		$aMergeCells = array_reverse($pSheet->getMergeCells(), true);
		foreach ($aMergeCells as $key => $value) {
			$newReference = $this->updateCellReference($key, $pBefore, $pNumCols, $pNumRows);
			if ($key != $newReference) {
				$pSheet->mergeCells( $newReference );
				$pSheet->unmergeCells( $key );
			}
		}
			
			
		// Update worksheet: protected cells
		$aProtectedCells = array_reverse($pSheet->getProtectedCells(), true);
		foreach ($aProtectedCells as $key => $value) {
			$newReference = $this->updateCellReference($key, $pBefore, $pNumCols, $pNumRows);
			if ($key != $newReference) {
				$pSheet->protectCells( $newReference, $value, true );
				$pSheet->unprotectCells( $key );
			}
		}
			
			
		// Update worksheet: autofilter
		if ($pSheet->getAutoFilter() != '') {
			$pSheet->setAutoFilter( $this->updateCellReference($pSheet->getAutoFilter(), $pBefore, $pNumCols, $pNumRows) );
		}
			
		
		// Update worksheet: freeze pane
		if ($pSheet->getFreezePane() != '') {
			$pSheet->setFreezePane( $this->updateCellReference($pSheet->getFreezePane(), $pBefore, $pNumCols, $pNumRows) );
		}
		

		// Page setup
		if ($pSheet->getPageSetup()->isPrintAreaSet()) {
			$pSheet->getPageSetup()->setPrintArea( $this->updateCellReference($pSheet->getPageSetup()->getPrintArea(), $pBefore, $pNumCols, $pNumRows) );
		}
		
			
		// Update worksheet: drawings
		$aDrawings = $pSheet->getDrawingCollection();
		foreach ($aDrawings as $objDrawing) {
			$newReference = $this->updateCellReference($objDrawing->getCoordinates(), $pBefore, $pNumCols, $pNumRows);
			if ($objDrawing->getCoordinates() != $newReference) {
				$objDrawing->setCoordinates($newReference);
			}
		}
    			
			
		// Update workbook: named ranges
		if (count($pSheet->getParent()->getNamedRanges()) > 0) {
			foreach ($pSheet->getParent()->getNamedRanges() as $namedRange) {
				if ($namedRange->getWorksheet()->getHashCode() == $pSheet->getHashCode()) {
					$namedRange->setRange(
						$this->updateCellReference($namedRange->getRange(), $pBefore, $pNumCols, $pNumRows)
					);
				}
			}
		}
		
		
		// Garbage collect
		$pSheet->garbageCollect();
    }
    	
    /**
     * Update references within formulas
     *
     * @param 	string	$pFormula	Formula to update
     * @param 	int		$pBefore	Insert before this one
     * @param 	int		$pNumCols	Number of columns to insert
     * @param 	int		$pNumRows	Number of rows to insert
     * @return 	string	Updated formula
     * @throws 	Exception
     */
    public function updateFormulaReferences($pFormula = '', $pBefore = 'A1', $pNumCols = 0, $pNumRows = 0) {	
		// Formula stack
		$executableFormulaArray = array();
		
		// Parse formula into a tree of tokens
		$objParser = new PHPExcel_Calculation_FormulaParser($pFormula);
		
		// Loop trough parsed tokens and create an executable formula
		$inFunction = false;
		$token 		= null;
		for ($i = 0; $i < $objParser->getTokenCount(); $i++) {
			$token = $objParser->getToken($i);

			// Is it a cell reference? Not a cell range?
			if ( ($token->getTokenType() == PHPExcel_Calculation_FormulaToken::TOKEN_TYPE_OPERAND) &&
				 ($token->getTokenSubType() == PHPExcel_Calculation_FormulaToken::TOKEN_SUBTYPE_RANGE) ) {
				 	// New cell reference
				 	$newCellReference = $this->updateCellReference($token->getValue(), $pBefore, $pNumCols, $pNumRows);
				 	
				 	// Add adjusted cell coordinate to executable formula array
				 	array_push($executableFormulaArray, $newCellReference);
					 	
				 	continue;
			}
			
			// Is it a subexpression?
			if ($token->getTokenType() == PHPExcel_Calculation_FormulaToken::TOKEN_TYPE_SUBEXPRESSION) {
				// Temporary variable
				$tmp = '';
				switch($token->getTokenSubType()) {
					case PHPExcel_Calculation_FormulaToken::TOKEN_SUBTYPE_START:
						$tmp = '(';
						break;
					case PHPExcel_Calculation_FormulaToken::TOKEN_SUBTYPE_STOP:
						$tmp = ')';
						break;
				}
					
				// Add to executable formula array
				array_push($executableFormulaArray, $tmp);
					 	
				continue;
			}
				
			// Is it a function?
			if ($token->getTokenType() == PHPExcel_Calculation_FormulaToken::TOKEN_TYPE_FUNCTION) {
				// Temporary variable
				$tmp = '';
					
				// Check the function type
				if ($token->getValue() == 'ARRAY' || $token->getValue() == 'ARRAYROW') {
					// An array or an array row...
					$tmp = '(';
				} else {
					// A regular function call...
					switch($token->getTokenSubType()) {
						case PHPExcel_Calculation_FormulaToken::TOKEN_SUBTYPE_START:
					    	$tmp = strtoupper($token->getValue()) . '(';
							$inFunction = true;
							break;
						case PHPExcel_Calculation_FormulaToken::TOKEN_SUBTYPE_STOP:
							$tmp = ')';
							break;
					}
				}
					
				// Add to executable formula array
				array_push($executableFormulaArray, $tmp);
				 	
				continue;
			}
				
			// Is it text?
			if ( ($token->getTokenType() == PHPExcel_Calculation_FormulaToken::TOKEN_TYPE_OPERAND) &&
				 ($token->getTokenSubType() == PHPExcel_Calculation_FormulaToken::TOKEN_SUBTYPE_TEXT) ) {
					 // Add to executable formula array
					 array_push($executableFormulaArray, '"' . $token->getValue() . '"');
					 	
					 continue;
			}
				
			// Is it something else?
			array_push($executableFormulaArray, $token->getValue());
		}
		
		// Return result
		return '=' . implode(' ', $executableFormulaArray);
    }
    
    /**
     * Update cell reference
     *
     * @param 	string	$pCellRange			Cell range
     * @param 	int		$pBefore			Insert before this one
     * @param 	int		$pNumCols			Number of columns to increment
     * @param 	int		$pNumRows			Number of rows to increment
     * @return 	string	Updated cell range
     * @throws 	Exception
     */
    public function updateCellReference($pCellRange = 'A1', $pBefore = 'A1', $pNumCols = 0, $pNumRows = 0) {
		// Is it a range or a single cell?
		if (strpos($pCellRange, ':') === false) {
			// Single cell
			return $this->_updateSingleCellReference($pCellRange, $pBefore, $pNumCols, $pNumRows);
		} else {
			// Range
			return $this->_updateCellRange($pCellRange, $pBefore, $pNumCols, $pNumRows);
		}
    }
    
    /**
     * Update cell range
     *
     * @param 	string	$pCellRange			Cell range
     * @param 	int		$pBefore			Insert before this one
     * @param 	int		$pNumCols			Number of columns to increment
     * @param 	int		$pNumRows			Number of rows to increment
     * @return 	string	Updated cell range
     * @throws 	Exception
     */
    private function _updateCellRange($pCellRange = 'A1:A1', $pBefore = 'A1', $pNumCols = 0, $pNumRows = 0) {
    	if (eregi(':', $pCellRange)) {
			// Range
			$referenceA = '';
			$referenceB = '';
			list($referenceA, $referenceB) = PHPExcel_Cell::splitRange($pCellRange);
				 		
			return $this->_updateSingleCellReference($referenceA, $pBefore, $pNumCols, $pNumRows) . ':' . $this->_updateSingleCellReference($referenceB, $pBefore, $pNumCols, $pNumRows);
    	} else {
    		throw new Exception("Only cell ranges may be passed to this method.");
    	}
    }
	
    /**
     * Update single cell reference
     *
     * @param 	string	$pCellReference		Single cell reference
     * @param 	int		$pBefore			Insert before this one
     * @param 	int		$pNumCols			Number of columns to increment
     * @param 	int		$pNumRows			Number of rows to increment
     * @return 	string	Updated cell reference
     * @throws 	Exception
     */
    private function _updateSingleCellReference($pCellReference = 'A1', $pBefore = 'A1', $pNumCols = 0, $pNumRows = 0) {
    	if (strpos($pCellReference, ':') === false) {
    		// Get coordinates of $pBefore
    		$beforeColumn 	= 'A';
    		$beforeRow		= 1;
    		list($beforeColumn, $beforeRow) = PHPExcel_Cell::coordinateFromString( $pBefore );
    		
			// Get coordinates
			$newColumn 	= 'A';
			$newRow 	= 1;
			list($newColumn, $newRow) = PHPExcel_Cell::coordinateFromString( $pCellReference );
			
			// Verify which parts should be updated
			$updateColumn = (PHPExcel_Cell::columnIndexFromString($newColumn) >= PHPExcel_Cell::columnIndexFromString($beforeColumn))
							&& (strpos($newColumn, '$') === false)
							&& (strpos($beforeColumn, '$') === false);
			
			$updateRow = ($newRow >= $beforeRow)
							&& (strpos($newRow, '$') === false) 
							&& (strpos($beforeRow, '$') === false);
				
			// Create new column reference
			if ($updateColumn) {
				$newColumn 	= PHPExcel_Cell::stringFromColumnIndex( PHPExcel_Cell::columnIndexFromString($newColumn) - 1 + $pNumCols );
			}
			 	
			// Create new row reference
			if ($updateRow) {
				$newRow 	= $newRow + $pNumRows;
			}
				
			// Return new reference
			return $newColumn . $newRow;
    	} else {
    		throw new Exception("Only single cell references may be passed to this method.");
    	}
    }

	/**
	 * __clone implementation. Cloning should not be allowed in a Singleton!
	 *
	 * @throws	Exception
	 */
	public final function __clone() {
		throw new Exception("Cloning a Singleton is not allowed!");
	}
}