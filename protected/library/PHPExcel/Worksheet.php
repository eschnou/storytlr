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


/** PHPExcel */
require_once 'PHPExcel.php';

/** PHPExcel_Cell */
require_once 'PHPExcel/Cell.php';

/** PHPExcel_Cell_DataType */
require_once 'PHPExcel/Cell/DataType.php';

/** PHPExcel_Worksheet_RowDimension */
require_once 'PHPExcel/Worksheet/RowDimension.php';

/** PHPExcel_Worksheet_ColumnDimension */
require_once 'PHPExcel/Worksheet/ColumnDimension.php';

/** PHPExcel_Worksheet_PageSetup */
require_once 'PHPExcel/Worksheet/PageSetup.php';

/** PHPExcel_Worksheet_PageMargins */
require_once 'PHPExcel/Worksheet/PageMargins.php';

/** PHPExcel_Worksheet_HeaderFooter */
require_once 'PHPExcel/Worksheet/HeaderFooter.php';

/** PHPExcel_Worksheet_BaseDrawing */
require_once 'PHPExcel/Worksheet/BaseDrawing.php';

/** PHPExcel_Worksheet_Drawing */
require_once 'PHPExcel/Worksheet/Drawing.php';

/** PHPExcel_Worksheet_HeaderFooterDrawing */
require_once 'PHPExcel/Worksheet/HeaderFooterDrawing.php';

/** PHPExcel_Worksheet_Protection */
require_once 'PHPExcel/Worksheet/Protection.php';

/** PHPExcel_Comment */
require_once 'PHPExcel/Comment.php';

/** PHPExcel_Style */
require_once 'PHPExcel/Style.php';

/** PHPExcel_Style_Fill */
require_once 'PHPExcel/Style/Fill.php';

/** PHPExcel_Style_NumberFormat */
require_once 'PHPExcel/Style/NumberFormat.php';

/** PHPExcel_IComparable */
require_once 'PHPExcel/IComparable.php';

/** PHPExcel_Shared_Font */
require_once 'PHPExcel/Shared/Font.php';

/** PHPExcel_Shared_PasswordHasher */
require_once 'PHPExcel/Shared/PasswordHasher.php';

/** PHPExcel_ReferenceHelper */
require_once 'PHPExcel/ReferenceHelper.php';


/**
 * PHPExcel_Worksheet
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Worksheet implements PHPExcel_IComparable
{
	/* Break types */
	const BREAK_NONE	= 0;
	const BREAK_ROW		= 1;
	const BREAK_COLUMN	= 2;
	
	/**
	 * Parent spreadsheet
	 *
	 * @var PHPExcel
	 */
	private $_parent;
	
	/**
	 * Collection of cells
	 *
	 * @var PHPExcel_Cell[]
	 */
	private $_cellCollection = array();
	
	/**
	 * Collection of row dimensions
	 *
	 * @var PHPExcel_Worksheet_RowDimension[]
	 */
	private $_rowDimensions = array();
	
	/**
	 * Default row dimension
	 *
	 * @var PHPExcel_Worksheet_RowDimension
	 */
	private $_defaultRowDimension = null;
	
	/**
	 * Collection of column dimensions
	 *
	 * @var PHPExcel_Worksheet_ColumnDimension[]
	 */
	private $_columnDimensions = array();
	
	/**
	 * Default column dimension
	 *
	 * @var PHPExcel_Worksheet_ColumnDimension
	 */
	private $_defaultColumnDimension = null;
	
	/**
	 * Collection of drawings
	 *
	 * @var PHPExcel_Worksheet_BaseDrawing[]
	 */
	private $_drawingCollection = null;
	
	/**
	 * Worksheet title
	 *
	 * @var string
	 */
	private $_title;
	
	/**
	 * Page setup
	 *
	 * @var PHPExcel_Worksheet_PageSetup
	 */
	private $_pageSetup;
	
	/**
	 * Page margins
	 *
	 * @var PHPExcel_Worksheet_PageMargins
	 */
	private $_pageMargins;
	
	/**
	 * Page header/footer
	 *
	 * @var PHPExcel_Worksheet_HeaderFooter
	 */
	private $_headerFooter;
	
	/**
	 * Protection
	 *
	 * @var PHPExcel_Worksheet_Protection
	 */
	private $_protection;
	
	/**
	 * Collection of styles
	 *
	 * @var PHPExcel_Style[]
	 */
	private $_styles = array();
	
	/**
	 * Is the current cell collection sorted already?
	 *
	 * @var boolean
	 */
	private $_cellCollectionIsSorted = false;
	
	/**
	 * Collection of breaks
	 *
	 * @var array
	 */
	private $_breaks = array();
	
	/**
	 * Collection of merged cell ranges
	 *
	 * @var array
	 */
	private $_mergeCells = array();
	
	/**
	 * Collection of protected cell ranges
	 *
	 * @var array
	 */
	private $_protectedCells = array();
	
	/**
	 * Autofilter Range
	 * 
	 * @var string
	 */
	private $_autoFilter = '';
	
	/**
	 * Freeze pane
	 * 
	 * @var string
	 */
	private $_freezePane = '';
	
	/**
	 * Show gridlines?
	 *
	 * @var boolean
	 */
	private $_showGridlines = false;
	
	/**
	* Print gridlines?
	*
	* @var boolean
	*/
	private $_printGridlines = false;
	
	/**
	 * Show summary below? (Row/Column outline)
	 *
	 * @var boolean
	 */
	private $_showSummaryBelow = true;
	
	/**
	 * Show summary right? (Row/Column outline)
	 *
	 * @var boolean
	 */
	private $_showSummaryRight = true;
	
	/**
	 * Collection of comments
	 *
	 * @var PHPExcel_Comment[]
	 */
	private $_comments = array();

	/**
	 * Selected cell
	 *
	 * @var string
	 */
	private $_selectedCell = 'A1';
	
	/**
	 * Create a new worksheet
	 *
	 * @param PHPExcel 		$pParent
	 * @param string 		$pTitle
	 */
	public function __construct(PHPExcel $pParent = null, $pTitle = 'Worksheet')
	{
		// Set parent and title
		$this->_parent = $pParent;
		$this->setTitle($pTitle);
		
		// Set page setup
		$this->_pageSetup 			= new PHPExcel_Worksheet_PageSetup();
		
		// Set page margins
		$this->_pageMargins 		= new PHPExcel_Worksheet_PageMargins();
		
		// Set page header/footer
		$this->_headerFooter 		= new PHPExcel_Worksheet_HeaderFooter();
		
    	// Create a default style and a default gray125 style
    	$this->_styles['default'] 	= new PHPExcel_Style();
    	$this->_styles['gray125'] 	= new PHPExcel_Style();
    	$this->_styles['gray125']->getFill()->setFillType(PHPExcel_Style_Fill::FILL_PATTERN_GRAY125);
    	
    	// Drawing collection
    	$this->_drawingCollection 	= new ArrayObject();
    	
    	// Protection
    	$this->_protection			= new PHPExcel_Worksheet_Protection();
    	
    	// Gridlines
    	$this->_showGridlines		= false;
		$this->_printGridlines		= false;
    	
    	// Outline summary
    	$this->_showSummaryBelow	= true;
    	$this->_showSummaryRight	= true;
    	
    	// Default row dimension
    	$this->_defaultRowDimension = new PHPExcel_Worksheet_RowDimension(null);
    	
    	// Default column dimension
    	$this->_defaultColumnDimension = new PHPExcel_Worksheet_ColumnDimension(null);
	}
	
	/**
	 * Get collection of cells
	 *
	 * @return PHPExcel_Cell[]
	 */
	public function getCellCollection()
	{
        // Garbage collect...
        $this->garbageCollect();
        
        // Re-order cell collection?
        if (!$this->_cellCollectionIsSorted) {
        	uasort($this->_cellCollection, array('PHPExcel_Cell', 'compareCells'));
        }

		return $this->_cellCollection;
	}
	
	/**
	 * Get collection of row dimensions
	 *
	 * @return PHPExcel_Worksheet_RowDimension[]
	 */
	public function getRowDimensions()
	{
		return $this->_rowDimensions;
	}
	
	/**
	 * Get default row dimension
	 *
	 * @return PHPExcel_Worksheet_RowDimension
	 */
	public function getDefaultRowDimension()
	{
		return $this->_defaultRowDimension;
	}
	
	/**
	 * Get collection of column dimensions
	 *
	 * @return PHPExcel_Worksheet_ColumnDimension[]
	 */
	public function getColumnDimensions()
	{
		return $this->_columnDimensions;
	}
	
	/**
	 * Get default column dimension
	 *
	 * @return PHPExcel_Worksheet_ColumnDimension
	 */
	public function getDefaultColumnDimension()
	{
		return $this->_defaultColumnDimension;
	}
	
	/**
	 * Get collection of drawings
	 *
	 * @return PHPExcel_Worksheet_BaseDrawing[]
	 */
	public function getDrawingCollection()
	{
		return $this->_drawingCollection;
	}
	
	/**
	 * Refresh column dimensions
	 */
	public function refreshColumnDimensions()
	{
		$currentColumnDimensions = $this->getColumnDimensions();
		$newColumnDimensions = array();

		foreach ($currentColumnDimensions as $objColumnDimension) {
			$newColumnDimensions[$objColumnDimension->getColumnIndex()] = $objColumnDimension;
		}

		$this->_columnDimensions = $newColumnDimensions;
	}
	
	/**
	 * Refresh row dimensions
	 */
	public function refreshRowDimensions()
	{
		$currentRowDimensions = $this->getRowDimensions();
		$newRowDimensions = array();

		foreach ($currentRowDimensions as $objRowDimension) {
			$newRowDimensions[$objRowDimension->getRowIndex()] = $objRowDimension;
		}

		$this->_rowDimensions = $newRowDimensions;
	}
	
    /**
     * Calculate worksheet dimension
     *
     * @return string  String containing the dimension of this worksheet
     */
    public function calculateWorksheetDimension()
    {        
        // Return
        return 'A1' . ':' .  $this->getHighestColumn() . $this->getHighestRow();
    }
    
    /**
     * Calculate widths for auto-size columns
     * 
     * @param  boolean  $calculateMergeCells  Calculate merge cell width
     */
    public function calculateColumnWidths($calculateMergeCells = false)
    {
		$autoSizes = array();
        foreach ($this->getColumnDimensions() as $colDimension) {
			if ($colDimension->getAutoSize()) {
				$autoSizes[$colDimension->getColumnIndex()] = -1;
			}
        }
		
		foreach ($this->getCellCollection() as $cell) {
			if (isset($autoSizes[$cell->getColumn()])) {
				$cellValue = $cell->getCalculatedValue();

				foreach ($this->getMergeCells() as $cells) {
					if ($cell->isInRange($cells) && !$calculateMergeCells) {
						$cellValue = ''; // do not calculate merge cells
					}
				}

				$autoSizes[$cell->getColumn()] = max(
					(float)$autoSizes[$cell->getColumn()],
					(float)PHPExcel_Shared_Font::calculateColumnWidth(
						$this->getStyle($cell->getCoordinate())->getFont()->getSize(),
						false,
						$cellValue
					)
				);
			}
		}
		foreach ($autoSizes as $columnIndex => $width) {
			$this->getColumnDimension($columnIndex)->setWidth($width);
		}
		
    }
    
    /**
     * Get parent
     *
     * @return PHPExcel
     */
    public function getParent() {
    	return $this->_parent;
    }
	
    /**
     * Re-bind parent
     *
     * @param PHPExcel $parent
     */
    public function rebindParent(PHPExcel $parent) {
		$namedRanges = $this->_parent->getNamedRanges();
		foreach ($namedRanges as $namedRange) {
			$parent->addNamedRange($namedRange);
		}
	
		$this->_parent->removeSheetByIndex(
			$this->_parent->getindex($this)
		);
		$this->_parent = $parent;
    }
    
	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->_title;
	}
	
    /**
     * Set title
     *
     * @param string $pValue String containing the dimension of this worksheet
     */
    public function setTitle($pValue = 'Worksheet')
    {
    	// Is this a 'rename' or not?
    	if ($this->getTitle() == $pValue) {
    		return;
    	}
    	
    	// Loop trough all sheets in parent PHPExcel and verify unique names
    	$titleCount	= 0;
    	$aNames 	= $this->getParent()->getSheetNames();
 
		foreach ($aNames as $strName) {
			if ($strName == $pValue || substr($strName, 0, strrpos($strName, ' ')) == $pValue) {
				$titleCount++;
			}
		}

		// Eventually, add a number to the sheet name
		if ($titleCount > 0) {
			$this->setTitle($pValue . ' ' . $titleCount);
			return;
		}
				
		// Set title
        $this->_title = $pValue;
    }
    
    /**
     * Get page setup
     *
     * @return PHPExcel_Worksheet_PageSetup
     */
    public function getPageSetup()
    {
    	return $this->_pageSetup;
    }
    
    /**
     * Set page setup
     *
     * @param PHPExcel_Worksheet_PageSetup	$pValue
     */
    public function setPageSetup(PHPExcel_Worksheet_PageSetup $pValue)
    {
   		$this->_pageSetup = $pValue;
    }
    
    /**
     * Get page margins
     *
     * @return PHPExcel_Worksheet_PageMargins
     */
    public function getPageMargins()
    {
    	return $this->_pageMargins;
    }
    
    /**
     * Set page margins
     *
     * @param PHPExcel_Worksheet_PageMargins	$pValue
     */
    public function setPageMargins(PHPExcel_Worksheet_PageMargins $pValue)
    {
   		$this->_pageMargins = $pValue;
    }
    
    /**
     * Get page header/footer
     *
     * @return PHPExcel_Worksheet_HeaderFooter
     */
    public function getHeaderFooter()
    {
    	return $this->_headerFooter;
    }
    
    /**
     * Set page header/footer
     *
     * @param PHPExcel_Worksheet_HeaderFooter	$pValue
     */
    public function setHeaderFooter(PHPExcel_Worksheet_HeaderFooter $pValue)
    {
    	$this->_headerFooter = $pValue;
    }
    
    /**
     * Get Protection
     *
     * @return PHPExcel_Worksheet_Protection
     */
    public function getProtection()
    {
    	return $this->_protection;
    }
    
    /**
     * Set Protection
     *
     * @param PHPExcel_Worksheet_Protection	$pValue
     */
    public function setProtection(PHPExcel_Worksheet_Protection $pValue)
    {
   		$this->_protection = $pValue;
    }
    
    /**
     * Get highest worksheet column
     *
     * @return string Highest column name
     */
    public function getHighestColumn()
    {
        // Highest column
        $highestColumn = 'A';
               
        // Loop trough cells
        foreach ($this->_cellCollection as $cell) {
        	if (PHPExcel_Cell::columnIndexFromString($highestColumn) < PHPExcel_Cell::columnIndexFromString($cell->getColumn())) {
        		$highestColumn = $cell->getColumn();
        	}
        }
        
        // Loop trough column dimensions
        foreach ($this->_columnDimensions as $dimension) {
        	if ($highestColumn < $dimension->getColumnIndex()) {
        		$highestColumn = $dimension->getColumnIndex();
        	}
        }

        // Return
        return $highestColumn;
    }
    
    /**
     * Get highest worksheet row
     *
     * @return int Highest row number
     */
    public function getHighestRow()
    {       
        // Highest row
        $highestRow = 1;
        
        // Loop trough cells
        foreach ($this->_cellCollection as $cell) {
        	if ($cell->getRow() > $highestRow) {
        		$highestRow = $cell->getRow();
        	}
        }
        
        // Loop trough row dimensions
        foreach ($this->_rowDimensions as $dimension) {
        	if ($highestRow < $dimension->getRowIndex()) {
        		$highestRow = $dimension->getRowIndex();
        	}
        }
        
        // Return
        return $highestRow;
    }
    
    /**
     * Set a cell value
     *
     * @param string 	$pCoordinate	Coordinate of the cell
     * @param mixed 	$pValue			Value of the cell
     */
    public function setCellValue($pCoordinate = 'A1', $pValue = null)
    {
    	// Set value
    	$this->getCell($pCoordinate)->setValue($pValue, true);
    }
    
    /**
     * Set a cell value by using numeric cell coordinates
     *
     * @param string 	$pColumn		Numeric column coordinate of the cell
     * @param string 	$pRow			Numeric row coordinate of the cell
     * @param mixed 	$pValue			Value of the cell
     */
    public function setCellValueByColumnAndRow($pColumn = 0, $pRow = 0, $pValue = null)
    {
    	$this->setCellValue(PHPExcel_Cell::stringFromColumnIndex($pColumn) . $pRow, $pValue);
    }
    
    /**
     * Set a cell value
     *
     * @param string 	$pCoordinate	Coordinate of the cell
     * @param mixed 	$pValue			Value of the cell
     * @param string	$pDataType		Explicit data type
     */
    public function setCellValueExplicit($pCoordinate = 'A1', $pValue = null, $pDataType = PHPExcel_Cell_DataType::TYPE_STRING)
    {
    	// Set value
    	$this->getCell($pCoordinate)->setValueExplicit($pValue, $pDataType);
    }
    
    /**
     * Set a cell value by using numeric cell coordinates
     *
     * @param string 	$pColumn		Numeric column coordinate of the cell
     * @param string 	$pRow			Numeric row coordinate of the cell
     * @param mixed 	$pValue			Value of the cell
     * @param string	$pDataType		Explicit data type
     */
    public function setCellValueExplicitByColumnAndRow($pColumn = 0, $pRow = 0, $pValue = null, $pDataType = PHPExcel_Cell_DataType::TYPE_STRING)
    {
    	$this->setCellValueExplicit(PHPExcel_Cell::stringFromColumnIndex($pColumn) . $pRow, $pValue, $pDataType);
    }
    
    /**
     * Get cell at a specific coordinate
     *
     * @param 	string 			$pCoordinate	Coordinate of the cell
     * @throws 	Exception
     * @return 	PHPExcel_Cell 	Cell that was found
     */
    public function getCell($pCoordinate = 'A1')
    {
    	// Worksheet reference?
		if (strpos($pCoordinate, '!') !== false) {
			$worksheetReference = PHPExcel_Worksheet::extractSheetTitle($pCoordinate, true);
			return $this->getParent()->getSheetByName($worksheetReference[0])->getCell($worksheetReference[1]);
		}
			
		// Named range?
		$namedRange = PHPExcel_NamedRange::resolveRange($pCoordinate, $this);
		if (!is_null($namedRange)) {
			$pCoordinate = $namedRange->getRange();
			if ($this->getHashCode() != $namedRange->getWorksheet()->getHashCode()) {
				if (!$namedRange->getLocalOnly()) {
					return $namedRange->getWorksheet()->getCell($pCoordinate);
				} else {
					throw new Exception('Named range ' . $namedRange->getName() . ' is not accessible from within sheet ' . $this->getTitle());
				}
			}
		}
		
    	// Uppercase coordinate
    	$pCoordinate = strtoupper($pCoordinate);
    	
    	if (eregi(':', $pCoordinate)) {
    		throw new Exception('Cell coordinate can not be a range of cells.');
    	} else if (eregi('\$', $pCoordinate)) {
    		throw new Exception('Cell coordinate must not be absolute.');
    	} else {
	    	// Coordinates
	    	$aCoordinates = PHPExcel_Cell::coordinateFromString($pCoordinate);

	        // Cell exists?
	        if (!isset($this->_cellCollection[ strtoupper($pCoordinate) ])) {
	        	$this->_cellCollection[ strtoupper($pCoordinate) ] = new PHPExcel_Cell($aCoordinates[0], $aCoordinates[1], null, null, $this);
	        	$this->_cellCollectionIsSorted = false;
	        }

	        return $this->_cellCollection[ strtoupper($pCoordinate) ];
    	}
    }
    
    /**
     * Get cell at a specific coordinate by using numeric cell coordinates
     *
     * @param 	string $pColumn		Numeric column coordinate of the cell
     * @param 	string $pRow		Numeric row coordinate of the cell
     * @return 	PHPExcel_Cell 		Cell that was found
     */
    public function getCellByColumnAndRow($pColumn = 0, $pRow = 0)
    {
    	return $this->getCell(PHPExcel_Cell::stringFromColumnIndex($pColumn) . $pRow);
    }
    
    /**
     * Cell at a specific coordinate exists?
     *
     * @param 	string 			$pCoordinate	Coordinate of the cell
     * @throws 	Exception
     * @return 	boolean
     */
    public function cellExists($pCoordinate = 'A1')
    {
    	// Worksheet reference?
		if (strpos($pCoordinate, '!') !== false) {
			$worksheetReference = PHPExcel_Worksheet::extractSheetTitle($pCoordinate, true);
			return $this->getParent()->getSheetByName($worksheetReference[0])->cellExists($worksheetReference[1]);
		}
			
		// Named range?
		$namedRange = PHPExcel_NamedRange::resolveRange($pCoordinate, $this);
		if (!is_null($namedRange)) {
			$pCoordinate = $namedRange->getRange();
			if ($this->getHashCode() != $namedRange->getWorksheet()->getHashCode()) {
				if (!$namedRange->getLocalOnly()) {
					return $namedRange->getWorksheet()->cellExists($pCoordinate);
				} else {
					throw new Exception('Named range ' . $namedRange->getName() . ' is not accessible from within sheet ' . $this->getTitle());
				}
			}
		}
		
    	// Uppercase coordinate
    	$pCoordinate = strtoupper($pCoordinate);
    	
    	if (eregi(':', $pCoordinate)) {
    		throw new Exception('Cell coordinate can not be a range of cells.');
    	} else if (eregi('\$', $pCoordinate)) {
    		throw new Exception('Cell coordinate must not be absolute.');
    	} else {
	    	// Coordinates
	    	$aCoordinates = PHPExcel_Cell::coordinateFromString($pCoordinate);

	        // Cell exists?
	        return isset($this->_cellCollection[ strtoupper($pCoordinate) ]);
    	}
    }
    
    /**
     * Cell at a specific coordinate by using numeric cell coordinates exists?
     *
     * @param 	string $pColumn		Numeric column coordinate of the cell
     * @param 	string $pRow		Numeric row coordinate of the cell
     * @return 	boolean
     */
    public function cellExistsByColumnAndRow($pColumn = 0, $pRow = 0)
    {
    	return $this->cellExists(PHPExcel_Cell::stringFromColumnIndex($pColumn) . $pRow);
    }
    
    /**
     * Get row dimension at a specific row
     *
     * @param int $pRow	Numeric index of the row
     * @return PHPExcel_Worksheet_RowDimension
     */
    public function getRowDimension($pRow = 0)
    {
    	// Found
    	$found = null;
    	
        // Get row dimension
        if (!isset($this->_rowDimensions[$pRow])) {
        	$this->_rowDimensions[$pRow] = new PHPExcel_Worksheet_RowDimension($pRow);
        }
        return $this->_rowDimensions[$pRow];
    }
       
    /**
     * Get column dimension at a specific column
     *
     * @param string $pColumn	String index of the column
     * @return PHPExcel_Worksheet_ColumnDimension
     */
    public function getColumnDimension($pColumn = 'A')
    {
    	// Uppercase coordinate
    	$pColumn = strtoupper($pColumn);
    	
    	// Fetch dimensions
    	if (!isset($this->_columnDimensions[$pColumn])) {
    		$this->_columnDimensions[$pColumn] = new PHPExcel_Worksheet_ColumnDimension($pColumn);
    	}
    	return $this->_columnDimensions[$pColumn];
    }
    
    /**
     * Get column dimension at a specific column by using numeric cell coordinates
     *
     * @param 	string $pColumn		Numeric column coordinate of the cell
     * @param 	string $pRow		Numeric row coordinate of the cell
     * @return 	PHPExcel_Worksheet_ColumnDimension
     */
    public function getColumnDimensionByColumn($pColumn = 0)
    {
        return $this->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($pColumn));
    }
    
    /**
     * Get styles
     *
     * @return PHPExcel_Style[]
     */
    public function getStyles()
    {
    	return $this->_styles;
    }

    /**
     * Get default style
     *
     * @return 	PHPExcel_Style
     * @throws 	Exception
     */
    public function getDefaultStyle()
    {
    	return $this->_styles['default'];
    }
    
    /**
     * Get style for cell
     *
     * @param 	string 	$pCellCoordinate	Cell coordinate to get style for
     * @return 	PHPExcel_Style
     * @throws 	Exception
     */
    public function getStyle($pCellCoordinate = 'A1')
    {
        // Worksheet reference?
		if (strpos($pCellCoordinate, '!') !== false) {
			$worksheetReference = PHPExcel_Worksheet::extractSheetTitle($pCellCoordinate, true);
			return $this->getParent()->getSheetByName($worksheetReference[0])->getStyle($worksheetReference[1]);
		}
		
		// Named range?
		$namedRange = PHPExcel_NamedRange::resolveRange($pCellCoordinate, $this);
		if (!is_null($namedRange)) {
			$pCoordinate = $namedRange->getRange();
			if ($this->getHashCode() != $namedRange->getWorksheet()->getHashCode()) {
				if (!$namedRange->getLocalOnly()) {
					return $namedRange->getWorksheet()->getStyle($pCellCoordinate);
				} else {
					throw new Exception('Named range ' . $namedRange->getName() . ' is not accessible from within sheet ' . $this->getTitle());
				}
			}
		}
		
    	// Uppercase coordinate
    	$pCellCoordinate = strtoupper($pCellCoordinate);
    	
    	if (eregi(':', $pCellCoordinate)) {
    		throw new Exception('Cell coordinate string can not be a range of cells.');
    	} else if (eregi('\$', $pCellCoordinate)) {
    		throw new Exception('Cell coordinate string must not be absolute.');
    	} else if ($pCellCoordinate == '') {
    		throw new Exception('Cell coordinate can not be zero-length string.');
    	} else {
    		// Create a cell for this coordinate.
    		// Reason: When we have an empty cell that has style information,
    		// it should exist for our IWriter
    		$this->getCell($pCellCoordinate);
    		
    		// Check if we already have style information for this cell.
    		// If not, create a new style.
    		if (isset($this->_styles[$pCellCoordinate])) {
    			return $this->_styles[$pCellCoordinate];
    		} else {
    			$newStyle = clone $this->getDefaultStyle();
    			$this->_styles[$pCellCoordinate] = $newStyle;
    			return $newStyle;
    		}
    	}
    }
    
    /**
     * Get style for cell by using numeric cell coordinates
     *
     * @param 	int $pColumn	Numeric column coordinate of the cell
     * @param 	int $pRow		Numeric row coordinate of the cell
     * @return 	PHPExcel_Style
     */
    public function getStyleByColumnAndRow($pColumn = 0, $pRow = 0)
    {
    	return $this->getStyle(PHPExcel_Cell::stringFromColumnIndex($pColumn) . $pRow);
    }
    
    /**
     * Set shared cell style to a range of cells
     *
     * Please note that this will overwrite existing cell styles for cells in range!
     *
     * @param 	PHPExcel_Style	$pSharedCellStyle	Cell style to share
     * @param 	string			$pRange				Range of cells (i.e. "A1:B10"), or just one cell (i.e. "A1")
     * @throws	Exception
     */
     public function setSharedStyle(PHPExcel_Style $pSharedCellStyle = null, $pRange = '')
    {
    	// Uppercase coordinate
    	$pRange = strtoupper($pRange);
    	
   		// Is it a cell range or a single cell?
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
    		
   		// Make sure we can loop upwards on rows and columns
   		if ($rangeStart[0] > $rangeEnd[0] && $rangeStart[1] > $rangeEnd[1]) {
   			$tmp = $rangeStart;
   			$rangeStart = $rangeEnd;
   			$rangeEnd = $tmp;
   		}
    		   		
   		// Loop trough cells and apply styles
   		for ($col = $rangeStart[0]; $col <= $rangeEnd[0]; $col++) {
   			for ($row = $rangeStart[1]; $row <= $rangeEnd[1]; $row++) {
   				$this->getCell(PHPExcel_Cell::stringFromColumnIndex($col) . $row);
   				$this->_styles[ PHPExcel_Cell::stringFromColumnIndex($col) . $row ] = $pSharedCellStyle;
   			}
   		}
    }

    /**
     * Duplicate cell style to a range of cells
     *
     * Please note that this will overwrite existing cell styles for cells in range!
     *
     * @param 	PHPExcel_Style	$pCellStyle	Cell style to duplicate
     * @param 	string			$pRange		Range of cells (i.e. "A1:B10"), or just one cell (i.e. "A1")
     * @throws	Exception
     */
    public function duplicateStyle(PHPExcel_Style $pCellStyle = null, $pRange = '')
    {
    	// Uppercase coordinate
    	$pRange = strtoupper($pRange);
    	
   		// Is it a cell range or a single cell?
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
    		
   		// Make sure we can loop upwards on rows and columns
   		if ($rangeStart[0] > $rangeEnd[0] && $rangeStart[1] > $rangeEnd[1]) {
   			$tmp = $rangeStart;
   			$rangeStart = $rangeEnd;
   			$rangeEnd = $tmp;
   		}
    		   		
   		// Loop trough cells and apply styles
   		for ($col = $rangeStart[0]; $col <= $rangeEnd[0]; $col++) {
   			for ($row = $rangeStart[1]; $row <= $rangeEnd[1]; $row++) {
   				$this->getCell(PHPExcel_Cell::stringFromColumnIndex($col) . $row);
   				$this->_styles[ PHPExcel_Cell::stringFromColumnIndex($col) . $row ] = clone $pCellStyle;
   			}
   		}
    }
    
    /**
     * Duplicate cell style array to a range of cells
     *
     * Please note that this will overwrite existing cell styles for cells in range,
     * if they are in the styles array. For example, if you decide to set a range of
     * cells to font bold, only include font bold in the styles array.
     *
     * @param	array			$pStyles	Array containing style information
     * @param 	string			$pRange		Range of cells (i.e. "A1:B10"), or just one cell (i.e. "A1")
     * @throws	Exception
     */
    public function duplicateStyleArray($pStyles = null, $pRange = '')
    {
    	if (is_array($pStyles)) {
    	    // Uppercase coordinate
	    	$pRange = strtoupper($pRange);
	    	
	   		// Is it a cell range or a single cell?
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
	    		
	   		// Make sure we can loop upwards on rows and columns
	   		if ($rangeStart[0] > $rangeEnd[0] && $rangeStart[1] > $rangeEnd[1]) {
	   			$tmp = $rangeStart;
	   			$rangeStart = $rangeEnd;
	   			$rangeEnd = $tmp;
	   		}
	    		   		
	   		// Loop trough cells and apply styles array
	   		for ($col = $rangeStart[0]; $col <= $rangeEnd[0]; $col++) {
	   			for ($row = $rangeStart[1]; $row <= $rangeEnd[1]; $row++) {
	   				$this->getStyle(PHPExcel_Cell::stringFromColumnIndex($col) . $row)->applyFromArray($pStyles);
	   			}
	   		}
    	} else {
    		throw new Exception("Invalid style array passed.");
    	}
    }
    
    /**
     * Set break on a cell
     *
     * @param 	string			$pCell		Cell coordinate (e.g. A1)
     * @param 	int				$pBreak		Break type (type of PHPExcel_Worksheet::BREAK_*)
     * @throws	Exception
     */
    public function setBreak($pCell = 'A1', $pBreak = PHPExcel_Worksheet::BREAK_NONE)
    {
    	// Uppercase coordinate
    	$pCell = strtoupper($pCell);
    	
    	if ($pCell != '') {
    		$this->_breaks[strtoupper($pCell)] = $pBreak;
    	} else {
    		throw new Exception('No cell coordinate specified.');
    	}
    }
    
    /**
     * Set break on a cell by using numeric cell coordinates
     *
     * @param 	int 	$pColumn	Numeric column coordinate of the cell
     * @param 	int 	$pRow		Numeric row coordinate of the cell
     * @param 	int		$pBreak		Break type (type of PHPExcel_Worksheet::BREAK_*)
     * @throws	Exception
     */
    public function setBreakByColumnAndRow($pColumn = 0, $pRow = 0, $pBreak = PHPExcel_Worksheet::BREAK_NONE)
    {
    	$this->setBreak(PHPExcel_Cell::stringFromColumnIndex($pColumn) . $pRow, $pBreak);
    }
    
    /**
     * Get breaks
     *
     * @return array[]
     */
    public function getBreaks()
    {
    	return $this->_breaks;
    }
    
    /**
     * Set merge on a cell range
     *
     * @param 	string			$pRange		Cell range (e.g. A1:E1)
     * @throws	Exception
     */
    public function mergeCells($pRange = 'A1:A1')
    {
    	// Uppercase coordinate
    	$pRange = strtoupper($pRange);
    	
    	if (eregi(':', $pRange)) {
    		$this->_mergeCells[$pRange] = $pRange;
    	} else {
    		throw new Exception('Merge must be set on a range of cells.');
    	}
    }
    
    /**
     * Set merge on a cell range by using numeric cell coordinates
     *
     * @param 	int $pColumn1	Numeric column coordinate of the first cell
     * @param 	int $pRow1		Numeric row coordinate of the first cell
     * @param 	int $pColumn2	Numeric column coordinate of the last cell
     * @param 	int $pRow2		Numeric row coordinate of the last cell
     * @throws	Exception
     */
    public function mergeCellsByColumnAndRow($pColumn1 = 0, $pRow1 = 0, $pColumn2 = 0, $pRow2 = 0)
    {
    	$cellRange = PHPExcel_Cell::stringFromColumnIndex($pColumn1) . $pRow1 . ':' . PHPExcel_Cell::stringFromColumnIndex($pColumn2) . $pRow2;
    	$this->mergeCells($cellRange);
    }
       
    /**
     * Remove merge on a cell range
     *
     * @param 	string			$pRange		Cell range (e.g. A1:E1)
     * @throws	Exception
     */
    public function unmergeCells($pRange = 'A1:A1')
    {
    	// Uppercase coordinate
    	$pRange = strtoupper($pRange);
    	
    	if (eregi(':', $pRange)) {
    		if (isset($this->_mergeCells[$pRange])) {
    			unset($this->_mergeCells[$pRange]);
    		} else {
    			throw new Exception('Cell range ' . $pRange . ' not known as merged.');
    		}
    	} else {
    		throw new Exception('Merge can only be removed from a range of cells.');
    	}
    }
    
    /**
     * Remove merge on a cell range by using numeric cell coordinates
     *
     * @param 	int $pColumn1	Numeric column coordinate of the first cell
     * @param 	int $pRow1		Numeric row coordinate of the first cell
     * @param 	int $pColumn2	Numeric column coordinate of the last cell
     * @param 	int $pRow2		Numeric row coordinate of the last cell
     * @throws	Exception
     */
    public function unmergeCellsByColumnAndRow($pColumn1 = 0, $pRow1 = 0, $pColumn2 = 0, $pRow2 = 0)
    {
    	$cellRange = PHPExcel_Cell::stringFromColumnIndex($pColumn1) . $pRow1 . ':' . PHPExcel_Cell::stringFromColumnIndex($pColumn2) . $pRow2;
    	$this->unmergeCells($cellRange);
    }
    
    /**
     * Get merge cells
     *
     * @return array[]
     */
    public function getMergeCells()
    {
    	return $this->_mergeCells;
    }
    
    /**
     * Set protection on a cell range
     *
     * @param 	string			$pRange				Cell (e.g. A1) or cell range (e.g. A1:E1)
     * @param 	string			$pPassword			Password to unlock the protection
     * @param 	boolean 		$pAlreadyHashed 	If the password has already been hashed, set this to true
     * @throws	Exception
     */
    public function protectCells($pRange = 'A1', $pPassword = '', $pAlreadyHashed = false)
    {
    	// Uppercase coordinate
    	$pRange = strtoupper($pRange);
    	
    	if (!$pAlreadyHashed) {
    		$pPassword = PHPExcel_Shared_PasswordHasher::hashPassword($pPassword);
    	}
    	$this->_protectedCells[$pRange] = $pPassword;
    }
    
    /**
     * Set protection on a cell range by using numeric cell coordinates
     *
     * @param 	int 	$pColumn1			Numeric column coordinate of the first cell
     * @param 	int 	$pRow1				Numeric row coordinate of the first cell
     * @param 	int 	$pColumn2			Numeric column coordinate of the last cell
     * @param 	int 	$pRow2				Numeric row coordinate of the last cell
     * @param 	string	$pPassword			Password to unlock the protection
     * @param 	boolean $pAlreadyHashed 	If the password has already been hashed, set this to true
     * @throws	Exception
     */
    public function protectCellsByColumnAndRow($pColumn1 = 0, $pRow1 = 0, $pColumn2 = 0, $pRow2 = 0, $pPassword = '', $pAlreadyHashed = false)
    {
    	$cellRange = PHPExcel_Cell::stringFromColumnIndex($pColumn1) . $pRow1 . ':' . PHPExcel_Cell::stringFromColumnIndex($pColumn2) . $pRow2;
    	$this->protectCells($cellRange, $pPassword, $pAlreadyHashed);
    }
    
    /**
     * Remove protection on a cell range
     *
     * @param 	string			$pRange		Cell (e.g. A1) or cell range (e.g. A1:E1)
     * @throws	Exception
     */
    public function unprotectCells($pRange = 'A1')
    {
    	// Uppercase coordinate
    	$pRange = strtoupper($pRange);
    	
    	if (isset($this->_protectedCells[$pRange])) {
    		unset($this->_protectedCells[$pRange]);
    	} else {
    		throw new Exception('Cell range ' . $pRange . ' not known as protected.');
    	}
    }
    
    /**
     * Remove protection on a cell range by using numeric cell coordinates
     *
     * @param 	int 	$pColumn1			Numeric column coordinate of the first cell
     * @param 	int 	$pRow1				Numeric row coordinate of the first cell
     * @param 	int 	$pColumn2			Numeric column coordinate of the last cell
     * @param 	int 	$pRow2				Numeric row coordinate of the last cell
     * @param 	string	$pPassword			Password to unlock the protection
     * @param 	boolean $pAlreadyHashed 	If the password has already been hashed, set this to true
     * @throws	Exception
     */
    public function unprotectCellsByColumnAndRow($pColumn1 = 0, $pRow1 = 0, $pColumn2 = 0, $pRow2 = 0, $pPassword = '', $pAlreadyHashed = false)
    {
    	$cellRange = PHPExcel_Cell::stringFromColumnIndex($pColumn1) . $pRow1 . ':' . PHPExcel_Cell::stringFromColumnIndex($pColumn2) . $pRow2;
    	$this->unprotectCells($cellRange, $pPassword, $pAlreadyHashed);
    }
    
    /**
     * Get protected cells
     *
     * @return array[]
     */
    public function getProtectedCells()
    {
    	return $this->_protectedCells;
    }
    
    /**
     * Get Autofilter Range
     *
     * @return string
     */
    public function getAutoFilter()
    {
    	return $this->_autoFilter;
    }
    
    /**
     * Set Autofilter Range
     *
     * @param 	string		$pRange		Cell range (i.e. A1:E10)
     * @throws 	Exception
     */
    public function setAutoFilter($pRange = '')
    {
    	// Uppercase coordinate
    	$pRange = strtoupper($pRange);
    	
    	if (eregi(':', $pRange)) {
    		$this->_autoFilter = $pRange;
    	} else {
    		throw new Exception('Autofilter must be set on a range of cells.');
    	}
    }
    
    /**
     * Set Autofilter Range by using numeric cell coordinates
     *
     * @param 	int 	$pColumn1	Numeric column coordinate of the first cell
     * @param 	int 	$pRow1		Numeric row coordinate of the first cell
     * @param 	int 	$pColumn2	Numeric column coordinate of the second cell
     * @param 	int 	$pRow2		Numeric row coordinate of the second cell
     * @throws 	Exception
     */
    public function setAutoFilterByColumnAndRow($pColumn1 = 0, $pRow1 = 0, $pColumn2 = 0, $pRow2 = 0)
    {
    	$this->setAutoFilter(
    		PHPExcel_Cell::stringFromColumnIndex($pColumn1) . $pRow1
    		. ':' . 
    		PHPExcel_Cell::stringFromColumnIndex($pColumn2) . $pRow2
    	);
    }
    
    /**
     * Get Freeze Pane
     *
     * @return string
     */
    public function getFreezePane()
    {
    	return $this->_freezePane;
    }
    
    /**
     * Freeze Pane
     *
     * @param 	string		$pCell		Cell (i.e. A1)
     * @throws 	Exception
     */
    public function freezePane($pCell = '')
    {
    	// Uppercase coordinate
    	$pCell = strtoupper($pCell);
    	
    	if (!eregi(':', $pCell)) {
    		$this->_freezePane = $pCell;
    	} else {
    		throw new Exception('Freeze pane can not be set on a range of cells.');
    	}
    }
    
    /**
     * Freeze Pane by using numeric cell coordinates
     *
     * @param 	int 	$pColumn	Numeric column coordinate of the cell
     * @param 	int 	$pRow		Numeric row coordinate of the cell
     * @throws 	Exception
     */
    public function freezePaneByColumnAndRow($pColumn = 0, $pRow = 0)
    {
    	$this->freezePane(PHPExcel_Cell::stringFromColumnIndex($pColumn) . $pRow);
    }
    
    /**
     * Unfreeze Pane
     *
     * @return string
     */
    public function unfreezePane()
    {
    	$this->freezePane('');
    }
    
    /**
     * Insert a new row, updating all possible related data
     *
     * @param 	int	$pBefore	Insert before this one
     * @param 	int	$pNumRows	Number of rows to insert
     * @throws 	Exception
     */
    public function insertNewRowBefore($pBefore = 1, $pNumRows = 1) {
    	if ($pBefore >= 1) {
    		$objReferenceHelper = PHPExcel_ReferenceHelper::getInstance();
    		$objReferenceHelper->insertNewBefore('A' . $pBefore, 0, $pNumRows, $this);
    	} else {
    		throw new Exception("Rows can only be inserted before at least row 1.");
    	}
    	
        // Garbage collect...
        $this->garbageCollect();
    }
    
    /**
     * Insert a new column, updating all possible related data
     *
     * @param 	int	$pBefore	Insert before this one
     * @param 	int	$pNumCols	Number of columns to insert
     * @throws 	Exception
     */
    public function insertNewColumnBefore($pBefore = 'A', $pNumCols = 1) {
    	if (!is_numeric($pBefore)) {
    		$objReferenceHelper = PHPExcel_ReferenceHelper::getInstance();
    		$objReferenceHelper->insertNewBefore($pBefore . '1', $pNumCols, 0, $this);
    	} else {
    		throw new Exception("Column references should not be numeric.");
    	}
    	
        // Garbage collect...
        $this->garbageCollect();
    }
    
    /**
     * Insert a new column, updating all possible related data
     *
     * @param 	int	$pBefore	Insert before this one (numeric column coordinate of the cell)
     * @param 	int	$pNumCols	Number of columns to insert
     * @throws 	Exception
     */
    public function insertNewColumnBeforeByIndex($pBefore = 0, $pNumCols = 1) {
    	if ($pBefore >= 0) {
    		$this->insertNewColumnBefore(PHPExcel_Cell::stringFromColumnIndex($pBefore), $pNumCols);
    	} else {
    		throw new Exception("Columns can only be inserted before at least column A (0).");
    	}
    	
        // Garbage collect...
        $this->garbageCollect();
    }    

    /**
     * Delete a row, updating all possible related data
     *
     * @param 	int	$pRow		Remove starting with this one
     * @param 	int	$pNumRows	Number of rows to remove
     * @throws 	Exception
     */
    public function removeRow($pRow = 1, $pNumRows = 1) {
    	if ($pRow >= 1) {
    		$objReferenceHelper = PHPExcel_ReferenceHelper::getInstance();
    		$objReferenceHelper->insertNewBefore('A' . ($pRow + $pNumRows), 0, -$pNumRows, $this);
    	} else {
    		throw new Exception("Rows to be deleted should at least start from row 1.");
    	}
    	
        // Garbage collect...
        $this->garbageCollect();
    }
    
    /**
     * Remove a column, updating all possible related data
     *
     * @param 	int	$pColumn	Remove starting with this one
     * @param 	int	$pNumCols	Number of columns to remove
     * @throws 	Exception
     */
    public function removeColumn($pColumn = 'A', $pNumCols = 1) {
    	if (!is_numeric($pColumn)) {
    		$pColumn = PHPExcel_Cell::stringFromColumnIndex(PHPExcel_Cell::columnIndexFromString($pColumn) - 1 + $pNumCols);
    		$objReferenceHelper = PHPExcel_ReferenceHelper::getInstance();
    		$objReferenceHelper->insertNewBefore($pColumn . '1', -$pNumCols, 0, $this);
    	} else {
    		throw new Exception("Column references should not be numeric.");
    	}
    	
        // Garbage collect...
        $this->garbageCollect();
    }
    
    /**
     * Remove a column, updating all possible related data
     *
     * @param 	int	$pColumn	Remove starting with this one (numeric column coordinate of the cell)
     * @param 	int	$pNumCols	Number of columns to remove
     * @throws 	Exception
     */
    public function removeColumnByIndex($pColumn = 0, $pNumCols = 1) {
    	if ($pColumn >= 0) {
    		$this->removeColumn(PHPExcel_Cell::stringFromColumnIndex($pColumn), $pNumCols);
    	} else {
    		throw new Exception("Columns can only be inserted before at least column A (0).");
    	}
    	
        // Garbage collect...
        $this->garbageCollect();
    }
    
    /**
     * Show gridlines?
     *
     * @return boolean
     */
    public function getShowGridlines() {
    	return $this->_showGridlines;
    }
    
    /**
     * Set show gridlines
     *
     * @param boolean $pValue	Show gridlines (true/false)
     */
    public function setShowGridlines($pValue = false) {
    	$this->_showGridlines = $pValue;
    }
	
	/**
	* Print gridlines?
	*
	* @return boolean
	*/
	public function getPrintGridlines() {
		return $this->_printGridlines;
	}

	/**
	* Set print gridlines
	*
	* @param boolean $pValue Print gridlines (true/false)
	*/
	public function setPrintGridlines($pValue = false) {
		$this->_printGridlines = $pValue;
	}
    
    /**
     * Show summary below? (Row/Column outlining)
     *
     * @return boolean
     */
    public function getShowSummaryBelow() {
    	return $this->_showSummaryBelow;
    }
    
    /**
     * Set show summary below
     *
     * @param boolean $pValue	Show summary below (true/false)
     */
    public function setShowSummaryBelow($pValue = true) {
    	$this->_showSummaryBelow = $pValue;
    }
    
    /**
     * Show summary right? (Row/Column outlining)
     *
     * @return boolean
     */
    public function getShowSummaryRight() {
    	return $this->_showSummaryRight;
    }
    
    /**
     * Set show summary right
     *
     * @param boolean $pValue	Show summary right (true/false)
     */
    public function setShowSummaryRight($pValue = true) {
    	$this->_showSummaryRight = $pValue;
    }
    
    /**
     * Get comments
     *
     * @return PHPExcel_Comment[]
     */
    public function getComments()
    {
    	return $this->_comments;
    }
    
    /**
     * Get comment for cell
     *
     * @param 	string 	$pCellCoordinate	Cell coordinate to get comment for
     * @return 	PHPExcel_Comment
     * @throws 	Exception
     */
    public function getComment($pCellCoordinate = 'A1')
    {
    	// Uppercase coordinate
    	$pCellCoordinate = strtoupper($pCellCoordinate);
    	
    	if (eregi(':', $pCellCoordinate)) {
    		throw new Exception('Cell coordinate string can not be a range of cells.');
    	} else if (eregi('\$', $pCellCoordinate)) {
    		throw new Exception('Cell coordinate string must not be absolute.');
    	} else if ($pCellCoordinate == '') {
    		throw new Exception('Cell coordinate can not be zero-length string.');
    	} else {   		
    		// Check if we already have a comment for this cell.
    		// If not, create a new comment.
    		if (isset($this->_comments[$pCellCoordinate])) {
    			return $this->_comments[$pCellCoordinate];
    		} else {
    			$newComment = new PHPExcel_Comment();
    			$this->_comments[$pCellCoordinate] = $newComment;
    			return $newComment;
    		}
    	}
    }
    
    /**
     * Get comment for cell by using numeric cell coordinates
     *
     * @param 	int $pColumn	Numeric column coordinate of the cell
     * @param 	int $pRow		Numeric row coordinate of the cell
     * @return 	PHPExcel_Comment
     */
    public function getCommentByColumnAndRow($pColumn = 0, $pRow = 0)
    {
    	return $this->getComment(PHPExcel_Cell::stringFromColumnIndex($pColumn) . $pRow);
    }

    /**
     * Get selected cell
     *
     * @return string
     */
    public function getSelectedCell()
    {
    	return $this->_selectedCell;
    }
    
    /**
     * Selected cell
     *
     * @param 	string		$pCell		Cell (i.e. A1)
     * @throws 	Exception
     */
    public function setSelectedCell($pCell = '')
    {
    	// Uppercase coordinate
    	$pCell = strtoupper($pCell);
    	
    	if (!eregi(':', $pCell)) {
    		$this->_selectedCell = $pCell;
    	} else {
    		throw new Exception('Selected cell can not be set on a range of cells.');
    	}
    }
    
    /**
     * Selected cell by using numeric cell coordinates
     *
     * @param 	int 	$pColumn	Numeric column coordinate of the cell
     * @param 	int 	$pRow		Numeric row coordinate of the cell
     * @throws 	Exception
     */
    public function setSelectedCellByColumnAndRow($pColumn = 0, $pRow = 0)
    {
    	$this->setSelectedCell(PHPExcel_Cell::stringFromColumnIndex($pColumn) . $pRow);
    }
    
    /**
     * Fill worksheet from values in array
     *
     * @param array $source	Source array
     * @param mixed $nullValue Value treated as "null"
     * @throws Exception
     */
    public function fromArray($source = null, $nullValue = null) {
    	if (is_array($source)) {
			// Loop trough $source
			$currentRow = 0;
			$rowData = null;
			foreach ($source as $rowData) {
				$currentRow++;
				
				for ($i = 0; $i < count($rowData); $i++) {
					if ($rowData[$i] != $nullValue) {
						// Set cell value
						$this->setCellValue(
							PHPExcel_Cell::stringFromColumnIndex($i) . $currentRow, $rowData[$i]
						);
					}
				}
			}
    	} else {
    		throw new Exception("Parameter \$source should be an array.");
    	}
    }
    
    /**
     * Create array from worksheet
     *
     * @param mixed $nullValue Value treated as "null"
     * @param boolean $calculateFormulas Should formulas be calculated?
     * @return array
     */
    public function toArray($nullValue = null, $calculateFormulas = true) {
    	// Returnvalue
    	$returnValue = array();
    	
        // Garbage collect...
        $this->garbageCollect();
        
    	// Get worksheet dimension
    	$dimension = explode(':', $this->calculateWorksheetDimension());
    	$dimension[0] = PHPExcel_Cell::coordinateFromString($dimension[0]);
    	$dimension[0][0] = PHPExcel_Cell::columnIndexFromString($dimension[0][0]) - 1;
    	$dimension[1] = PHPExcel_Cell::coordinateFromString($dimension[1]);
    	$dimension[1][0] = PHPExcel_Cell::columnIndexFromString($dimension[1][0]) - 1;
    	
    	// Loop trough cells
    	for ($row = $dimension[0][1]; $row <= $dimension[1][1]; $row++) {
    		for ($column = $dimension[0][0]; $column <= $dimension[1][0]; $column++) {
    			// Cell exists?
    			if ($this->cellExistsByColumnAndRow($column, $row)) {
    				$cell = $this->getCellByColumnAndRow($column, $row);
    				
    				if ($cell->getValue() instanceof PHPExcel_RichText) {
    					$returnValue[$row][$column] = $cell->getValue()->getPlainText();
    				} else {
	    				if ($calculateFormulas) {
	    					$returnValue[$row][$column] = $cell->getCalculatedValue();
	    				} else {
	    					$returnValue[$row][$column] = $cell->getValue();
	    				}
    				}
    				
    				$style = $this->getDefaultStyle();
    				if (isset($this->_styles[$cell->getCoordinate()])) {
    					$style = $this->getStyleByColumnAndRow($column, $row);
    				}

    				$returnValue[$row][$column] = PHPExcel_Style_NumberFormat::toFormattedString($returnValue[$row][$column], $style->getNumberFormat()->getFormatCode());
    			} else {
    				$returnValue[$row][$column] = $nullValue;
    			}
    		}	
    	}
    	
    	// Return
    	return $returnValue;
    }
    
    /**
     * Run PHPExcel garabage collector.
     */
    public function garbageCollect() {
    	// Build a reference table from images
    	$imageCoordinates = array();	
  		$iterator = $this->getDrawingCollection()->getIterator();
   		while ($iterator->valid()) {
   			$imageCoordinates[$iterator->current()->getCoordinates()] = true;

   			$iterator->next();
   		}
    	
    	// Find cells that can be cleaned
    	foreach ($this->_cellCollection as $coordinate => $cell) {
    		// Can be cleaned?
    		$canBeCleaned = false;
    		
    		// Empty value?
    		if (is_null($cell->getValue()) || (!is_object($cell->getValue()) && $cell->getValue() === '' && !$cell->hasHyperlink())) {
    			// Style set? Default style?
    			if (!isset($this->_styles[$coordinate]) || $this->_styles[$coordinate]->getHashCode() == $this->_styles['default']->getHashCode()) {
    				// It can be cleaned!
    				$canBeCleaned = true;
    			}
    		}
    		
    		// Referenced in image?
    		if (isset($imageCoordinates[$coordinate]) && $imageCoordinates[$coordinate] === true) {
    			$canBeCleaned = false;	
    		}
    		
    		// Clean?
    		if ($canBeCleaned) {
    			unset($this->_cellCollection[$coordinate]);
    			
    			// Does it resemble the default style?
    			if (isset($this->_styles[$coordinate]) && $this->_styles[$coordinate]->getHashCode() == $this->_styles['default']->getHashCode()) {
    				unset($this->_styles[$coordinate]);
    			}
    		}
    	}
    }
    
	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
    	return md5(
    		  $this->_title
    		. $this->_autoFilter
    		. ($this->_protection->isProtectionEnabled() ? 't' : 'f')
    		. $this->calculateWorksheetDimension()
    		. __CLASS__
    	);
    }
        
    /**
     * Extract worksheet title from range.
     * 
     * Example: extractSheetTitle('test!A1') ==> 'test'
     * Example: extractSheetTitle('test!A1', true) ==> array('test', 'A1');
     *
     * @param string $pRange	Range to extract title from
     * @param bool $returnRange	Return range? (see example)
     * @return mixed
     */
    public static function extractSheetTitle($pRange, $returnRange = false) {
    	// Sheet title included?
    	if (strpos($pRange, '!') === false) {
    		return '';
    	}
    	
    	// Extract sheet title
    	$reference = explode('!', $pRange);
    	$reference[0] = str_replace("'", "", $reference[0]);
    	
    	if ($returnRange) {
    		return $reference;
    	} else {
    		return $reference[1];
    	}
    }
    
	/**
	 * Copy worksheet (!= clone!)
	 * 
	 * @return PHPExcel_Worksheet
	 */
	public function copy() {
		$copied = clone $this;
			
		foreach ($copied->_cellCollection as $coordinate => $cell) {
			$newCell = clone $cell;
			$newCell->rebindParent($copied);
			$copied->_cellCollection[$coordinate] = $newCell;
		}
		
		return $copied;
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
