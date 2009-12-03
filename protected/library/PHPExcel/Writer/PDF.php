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

/** PHPExcel_Shared_Drawing */
require_once 'PHPExcel/Shared/Drawing.php';

/** PHPExcel_HashTable */
require_once 'PHPExcel/HashTable.php';

/** PHPExcel_Shared_PDF */
require_once 'PHPExcel/Shared/PDF.php';


/**
 * PHPExcel_Writer_PDF
 *
 * @category   PHPExcel
 * @package    PHPExcel_Writer
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Writer_PDF implements PHPExcel_Writer_IWriter {
	/**
	 * PHPExcel object
	 *
	 * @var PHPExcel
	 */
	private $_phpExcel;
	
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
	 * Temporary storage directory
	 *
	 * @var string
	 */
	private $_tempDir = '';
	
	/**
	 * Create a new PHPExcel_Writer_PDF
	 *
	 * @param 	PHPExcel	$phpExcel	PHPExcel object
	 */
	public function __construct(PHPExcel $phpExcel) {
		$this->_phpExcel = $phpExcel;
		$this->_sheetIndex 	= 0;
		$this->_tempDir = sys_get_temp_dir();
	}
	
	/**
	 * Save PHPExcel to file
	 *
	 * @param 	string 		$pFileName
	 * @throws 	Exception
	 */	
	public function save($pFilename = null) {
		// Open file
		$fileHandle = fopen($pFilename, 'w');
		if ($fileHandle === false) {
			throw new Exception("Could not open file $pFilename for writing.");
		}
		
		// Fetch sheets
		$sheets = array();
		if (is_null($this->_sheetIndex)) {
			$sheets = $this->_phpExcel->getAllSheets();
		} else {
			$sheets[] = $this->_phpExcel->getSheet($this->_sheetIndex);
		}
		
    	// PDF paper size
    	$paperSize = 'A4';
		
		// Create PDF
		$pdf = new FPDF('P', 'pt', $paperSize);
    	
		// Loop all sheets
		foreach ($sheets as $sheet) {
	    	// PDF orientation
	    	$orientation = 'P';
	    	if ($sheet->getPageSetup()->getOrientation() == PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE) {
		    	$orientation = 'L';
	    	}
	    	
			// Start sheet
			$pdf->SetAutoPageBreak(true);
			$pdf->SetFont('Arial', '', 10);
			$pdf->AddPage($orientation);
			
	    	// Get worksheet dimension
	    	$dimension = explode(':', $sheet->calculateWorksheetDimension());
	    	$dimension[0] = PHPExcel_Cell::coordinateFromString($dimension[0]);
	    	$dimension[0][0] = PHPExcel_Cell::columnIndexFromString($dimension[0][0]) - 1;
	    	$dimension[1] = PHPExcel_Cell::coordinateFromString($dimension[1]);
	    	$dimension[1][0] = PHPExcel_Cell::columnIndexFromString($dimension[1][0]) - 1;
	    	
	    	// Calculate column widths
	    	$sheet->calculateColumnWidths();

	    	// Loop trough cells
	    	for ($row = $dimension[0][1]; $row <= $dimension[1][1]; $row++) {
	    		// Line height
	    		$lineHeight = 0;
	    		
	    		// Calulate line height
	    		for ($column = $dimension[0][0]; $column <= $dimension[1][0]; $column++) {
	    		    $rowDimension = $sheet->getRowDimension($row);
	    			$cellHeight = PHPExcel_Shared_Drawing::pixelsToPoints(
	    				PHPExcel_Shared_Drawing::cellDimensionToPixels($rowDimension->getRowHeight())
	    			);
	    			if ($cellHeight <= 0) {
		    			$cellHeight = PHPExcel_Shared_Drawing::pixelsToPoints(
		    				PHPExcel_Shared_Drawing::cellDimensionToPixels($sheet->getDefaultRowDimension()->getRowHeight())
		    			);
	    			}
	    			if ($cellHeight <= 0) {
	    				$cellHeight = $sheet->getStyleByColumnAndRow($column, $row)->getFont()->getSize();
	    			}
	    			if ($cellHeight > $lineHeight) {
	    				$lineHeight = $cellHeight;
	    			}
	    		}
	    		
	    		// Output values
	    		for ($column = $dimension[0][0]; $column <= $dimension[1][0]; $column++) {
	    			// Start with defaults...
	    			$pdf->SetFont('Arial', '', 10);
	    			$pdf->SetTextColor(0, 0, 0);
	    			$pdf->SetDrawColor(100, 100, 100);
	    			$pdf->SetFillColor(255, 255, 255);
	    			
			    	// Coordinates
			    	$startX = $pdf->GetX();
			    	$startY = $pdf->GetY();
			    	
	    			// Cell exists?
	    			$cellData = '';
	    			if ($sheet->cellExistsByColumnAndRow($column, $row)) {
	    				if ($sheet->getCellByColumnAndRow($column, $row)->getValue() instanceof PHPExcel_RichText) {
	    					$cellData = $sheet->getCellByColumnAndRow($column, $row)->getValue()->getPlainText();
	    				} else {
		    				if ($this->_preCalculateFormulas) {
		    					$cellData = PHPExcel_Style_NumberFormat::ToFormattedString(
		    						$sheet->getCellByColumnAndRow($column, $row)->getCalculatedValue(),
		    						$sheet->getstyle( $sheet->getCellByColumnAndRow($column, $row)->getCoordinate() )->getNumberFormat()->getFormatCode()
		    					);
		    				} else {
		    					$cellData = PHPExcel_Style_NumberFormat::ToFormattedString(
		    						$sheet->getCellByColumnAndRow($column, $row)->getValue(),
		    						$sheet->getstyle( $sheet->getCellByColumnAndRow($column, $row)->getCoordinate() )->getNumberFormat()->getFormatCode()
		    					);
		    				}
	    				}
	    			}
	
	    			// Style information
	    			$style = $sheet->getStyleByColumnAndRow($column, $row);
	    			
	    			// Cell width
	    			$columnDimension = $sheet->getColumnDimensionByColumn($column);
	    			if ($columnDimension->getWidth() == -1) {
	    				$columnDimension->setAutoSize(true);
	    				$sheet->calculateColumnWidths(false);
	    			}
	    			$cellWidth = PHPExcel_Shared_Drawing::pixelsToPoints(
	    				PHPExcel_Shared_Drawing::cellDimensionToPixels($columnDimension->getWidth())
	    			);

	    			// Cell height
	    			$rowDimension = $sheet->getRowDimension($row);
	    			$cellHeight = PHPExcel_Shared_Drawing::pixelsToPoints(
	    				PHPExcel_Shared_Drawing::cellDimensionToPixels($rowDimension->getRowHeight())
	    			);
	    			if ($cellHeight <= 0) {
	    				$cellHeight = $style->getFont()->getSize();
	    			}
	    			
	    			// Column span? Rowspan?
	    			$singleCellWidth = $cellWidth;
	    			$singleCellHeight = $cellHeight;
					foreach ($sheet->getMergeCells() as $cells) {
						if ($sheet->getCellByColumnAndRow($column, $row)->isInRange($cells)) {
							list($first, ) = PHPExcel_Cell::splitRange($cells);
							
							if ($first == $sheet->getCellByColumnAndRow($column, $row)->getCoordinate()) {
								list($colSpan, $rowSpan) = PHPExcel_Cell::rangeDimension($cells);
								
								$cellWidth = $cellWidth * $colSpan;
								$cellHeight = $cellHeight * $rowSpan;
							}
								
							break;
						}
					}
					
					// Cell height OK?
					if ($cellHeight < $lineHeight) {
						$cellHeight = $lineHeight;
						$singleCellHeight = $cellHeight;
					}
	    			
	    			// Font formatting
	    			$fontStyle = '';
	    			if ($style->getFont()->getBold()) {
	    				$fontStyle .= 'B';
					}
					if ($style->getFont()->getItalic()) {
						$fontStyle .= 'I';
					}
					if ($style->getFont()->getUnderline() != PHPExcel_Style_Font::UNDERLINE_NONE) {
						$fontStyle .= 'U';
					}
					$pdf->SetFont('Arial', $fontStyle, $style->getFont()->getSize());
					
					// Text alignment
					$alignment = 'L';
					switch ($style->getAlignment()->getHorizontal()) {
						case PHPExcel_Style_Alignment::HORIZONTAL_CENTER:
							$alignment = 'C'; break;
						case PHPExcel_Style_Alignment::HORIZONTAL_RIGHT:
							$alignment = 'R'; break;
						case PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY:
							$alignment = 'J'; break;
						case PHPExcel_Style_Alignment::HORIZONTAL_LEFT:
						case PHPExcel_Style_Alignment::HORIZONTAL_GENERAL:
						default:
							$alignment = 'L'; break;						
					}
					
					// Text color
					$pdf->SetTextColor(
						hexdec(substr($style->getFont()->getColor()->getRGB(), 0, 2)),
						hexdec(substr($style->getFont()->getColor()->getRGB(), 2, 2)),
						hexdec(substr($style->getFont()->getColor()->getRGB(), 4, 2))
					);
					
					// Fill color
					if ($style->getFill()->getFillType() != PHPExcel_Style_Fill::FILL_NONE) {
						$pdf->SetFillColor(
							hexdec(substr($style->getFill()->getStartColor()->getRGB(), 0, 2)),
							hexdec(substr($style->getFill()->getStartColor()->getRGB(), 2, 2)),
							hexdec(substr($style->getFill()->getStartColor()->getRGB(), 4, 2))
						);
					}
					
					// Border color
					$borders = '';
	    			if ($style->getBorders()->getLeft()->getBorderStyle() != PHPExcel_Style_Border::BORDER_NONE) {
						$borders .= 'L';
						$pdf->SetDrawColor(
							hexdec(substr($style->getBorders()->getLeft()->getColor()->getRGB(), 0, 2)),
							hexdec(substr($style->getBorders()->getLeft()->getColor()->getRGB(), 2, 2)),
							hexdec(substr($style->getBorders()->getLeft()->getColor()->getRGB(), 4, 2))
						);
					}
	    			if ($style->getBorders()->getRight()->getBorderStyle() != PHPExcel_Style_Border::BORDER_NONE) {
						$borders .= 'R';
						$pdf->SetDrawColor(
							hexdec(substr($style->getBorders()->getRight()->getColor()->getRGB(), 0, 2)),
							hexdec(substr($style->getBorders()->getRight()->getColor()->getRGB(), 2, 2)),
							hexdec(substr($style->getBorders()->getRight()->getColor()->getRGB(), 4, 2))
						);
					}
	    			if ($style->getBorders()->getTop()->getBorderStyle() != PHPExcel_Style_Border::BORDER_NONE) {
						$borders .= 'T';
						$pdf->SetDrawColor(
							hexdec(substr($style->getBorders()->getTop()->getColor()->getRGB(), 0, 2)),
							hexdec(substr($style->getBorders()->getTop()->getColor()->getRGB(), 2, 2)),
							hexdec(substr($style->getBorders()->getTop()->getColor()->getRGB(), 4, 2))
						);
					}
	    			if ($style->getBorders()->getBottom()->getBorderStyle() != PHPExcel_Style_Border::BORDER_NONE) {
						$borders .= 'B';
						$pdf->SetDrawColor(
							hexdec(substr($style->getBorders()->getBottom()->getColor()->getRGB(), 0, 2)),
							hexdec(substr($style->getBorders()->getBottom()->getColor()->getRGB(), 2, 2)),
							hexdec(substr($style->getBorders()->getBottom()->getColor()->getRGB(), 4, 2))
						);
					}
					if ($borders == '') {
						$borders = 0;
					}
					if ($sheet->getShowGridlines()) {
						$borders = 'LTRB';
					}

	    			// Image?
			    	$iterator = $sheet->getDrawingCollection()->getIterator();
			    	while ($iterator->valid()) {
			    		if ($iterator->current()->getCoordinates() == PHPExcel_Cell::stringFromColumnIndex($column) . ($row + 1)) {
			    			try {
				    			$pdf->Image(
				    				$iterator->current()->getPath(),
				    				$pdf->GetX(),
				    				$pdf->GetY(),
				    				$iterator->current()->getWidth(),
				    				$iterator->current()->getHeight(),
				    				'',
				    				$this->_tempDir
				    			);
			    			} catch (Exception $ex) { }
			    		}
			    			
			    		$iterator->next();
			    	}
			    	
	    			// Print cell			
	    			$pdf->MultiCell(
	    				$cellWidth,
	    				$cellHeight,
	    				$cellData,
	    				$borders,
	    				$alignment,
	    				($style->getFill()->getFillType() == PHPExcel_Style_Fill::FILL_NONE ? 0 : 1)
	    			);
	    			
			    	// Coordinates
			    	$endX = $pdf->GetX();
			    	$endY = $pdf->GetY();

			    	// Revert to original Y location
			    	if ($endY > $startY) {
			    		$pdf->SetY($startY);
			    		if ($lineHeight < $lineHeight + ($endY - $startY)) {
			    			$lineHeight = $lineHeight + ($endY - $startY);
			    		}
			    	}
			    	$pdf->SetX($startX + $singleCellWidth);
			    	
			    	// Hyperlink?
			    	if ($sheet->getCellByColumnAndRow($column, $row)->hasHyperlink()) {
			    		if (!$sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->isInternal()) {
			    			$pdf->Link(
			    				$startX,
			    				$startY,
			    				$endX - $startX,
			    				$endY - $startY,
			    				$sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->getUrl()
			    			);
			    		}
			    	}
				}
				
				// Garbage collect!
				$sheet->garbageCollect();
				
				// Next line...
				$pdf->Ln($lineHeight);
	    	}
		}
		
		// Document info
		$pdf->SetTitle($this->_phpExcel->getProperties()->getTitle());
		$pdf->SetAuthor($this->_phpExcel->getProperties()->getCreator());
		$pdf->SetSubject($this->_phpExcel->getProperties()->getSubject());
		$pdf->SetKeywords($this->_phpExcel->getProperties()->getKeywords());
		$pdf->SetCreator($this->_phpExcel->getProperties()->getCreator());
    	
		// Write to file
		fwrite($fileHandle, $pdf->output($pFilename, 'S'));
		
		// Close file
		fclose($fileHandle);
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
	 * Write all sheets (resets sheetIndex to NULL)
	 */
	public function writeAllSheets() {
		$this->_sheetIndex = null;
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

	/**
	 * Get temporary storage directory
	 *
	 * @return string
	 */
	public function getTempDir() {
		return $this->_tempDir;
	}
	
	/**
	 * Set temporary storage directory
	 *
	 * @param 	string	$pValue		Temporary storage directory
	 * @throws 	Exception	Exception when directory does not exist
	 */
	public function setTempDir($pValue = '') {
		if (is_dir($pValue)) {
			$this->_tempDir = $pValue;
		} else {
			throw new Exception("Directory does not exist: $pValue");
		}
	}
}
