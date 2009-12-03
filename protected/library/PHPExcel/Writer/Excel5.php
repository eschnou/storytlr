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
 * @package	PHPExcel_Writer
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license	http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version	1.6.3, 2008-08-25
 */


/** PHPExcel_IWriter */
require_once 'PHPExcel/Writer/IWriter.php';

/** PHPExcel_Cell */
require_once 'PHPExcel/Cell.php';

/** PHPExcel_Writer_Excel5_Writer */
require_once 'PHPExcel/Writer/Excel5/Writer.php';

/** PHPExcel_RichText */
require_once 'PHPExcel/RichText.php';


/**
 * PHPExcel_Writer_Excel5
 *
 * @category   PHPExcel
 * @package	PHPExcel_Writer
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Writer_Excel5 implements PHPExcel_Writer_IWriter {
	/**
	 * PHPExcel object
	 *
	 * @var PHPExcel
	 */
	private $_phpExcel;

	/**
	 * Temporary storage directory
	 *
	 * @var string
	 */
	private $_tempDir = '';

	/**
	 * Color cache
	 */
	private $_colors = array();

	/**
	 * Create a new PHPExcel_Writer_Excel5
	 *
	 * @param	PHPExcel	$phpExcel	PHPExcel object
	 */
	public function __construct(PHPExcel $phpExcel) {
		$this->_phpExcel	= $phpExcel;
		$this->_tempDir		= '';
		$this->_colors		= array();
	}

	/**
	 * Save PHPExcel to file
	 *
	 * @param	string		$pFileName
	 * @throws	Exception
	 */
	public function save($pFilename = null) {
		$this->_colors		= array();

		$phpExcel = $this->_phpExcel;
		$workbook = new PHPExcel_Writer_Excel5_Writer($pFilename);
		$workbook->setVersion(8);

		// Set temp dir
		if ($this->_tempDir != '') {
			$workbook->setTempDir($this->_tempDir);
		}

		$saveDateReturnType = PHPExcel_Calculation_Functions::getReturnDateType();
		PHPExcel_Calculation_Functions::setReturnDateType(PHPExcel_Calculation_Functions::RETURNDATE_EXCEL);

		// Add empty sheets
		foreach ($phpExcel->getSheetNames() as $sheetIndex => $sheetName) {
			$phpSheet  = $phpExcel->getSheet($sheetIndex);
			$worksheet = $workbook->addWorksheet($sheetName);
		}
		$allWorksheets = $workbook->worksheets();

		// Add full sheet data
		foreach ($phpExcel->getSheetNames() as $sheetIndex => $sheetName) {
			$phpSheet  = $phpExcel->getSheet($sheetIndex);
			$worksheet = $allWorksheets[$sheetIndex];
			$worksheet->setInputEncoding("UTF-8");

			// Default style
			$emptyStyle = $phpSheet->getDefaultStyle();

			$aStyles = $phpSheet->getStyles();

			$freeze = $phpSheet->getFreezePane();
			if ($freeze) {
				list($column, $row) = PHPExcel_Cell::coordinateFromString($freeze);
				$worksheet->freezePanes(array($row - 1, PHPExcel_Cell::columnIndexFromString($column) - 1));
			}

			//if ($sheetIndex == $phpExcel->getActiveSheetIndex()) {
				// $worksheet->select();
			//}

			if ($phpSheet->getProtection()->getSheet()) {
				$worksheet->protect($phpSheet->getProtection()->getPassword(), true);
			}

			if (!$phpSheet->getShowGridlines()) {
				$worksheet->hideGridLines();
			}

			$formats = array();
			foreach ($phpSheet->getCellCollection() as $cell) {
				$row = $cell->getRow() - 1;
				$column = PHPExcel_Cell::columnIndexFromString($cell->getColumn()) - 1;

				// Don't break Excel!
				if ($row + 1 >= 65569) {
					break;
				}

				$style = $emptyStyle;
				if (isset($aStyles[$cell->getCoordinate()])) {
					$style = $aStyles[$cell->getCoordinate()];
				}
				$styleHash = $style->getHashCode();

				if (!isset($formats[$styleHash])) {
					$formats[$styleHash] = $workbook->addFormat(array(
						'HAlign' => $style->getAlignment()->getHorizontal(),
						'VAlign' => $this->_mapVAlign($style->getAlignment()->getVertical()),
						'TextRotation' => $style->getAlignment()->getTextRotation(),

						'Bold' => $style->getFont()->getBold(),
						'FontFamily' => $style->getFont()->getName(),
						'Color' => $this->_addColor($workbook, $style->getFont()->getColor()->getRGB()),
						'Underline' => $this->_mapUnderline($style->getFont()->getUnderline()),
						'Size' => $style->getFont()->getSize(),
						//~ 'Script' => $style->getSuperscript(),

						'NumFormat' => iconv("UTF-8", "Windows-1252", $style->getNumberFormat()->getFormatCode()),

						'Bottom' => $this->_mapBorderStyle($style->getBorders()->getBottom()->getBorderStyle()),
						'Top' => $this->_mapBorderStyle($style->getBorders()->getTop()->getBorderStyle()),
						'Left' => $this->_mapBorderStyle($style->getBorders()->getLeft()->getBorderStyle()),
						'Right' => $this->_mapBorderStyle($style->getBorders()->getRight()->getBorderStyle()),
						'BottomColor' => $this->_addColor($workbook, $style->getBorders()->getBottom()->getColor()->getRGB()),
						'TopColor' => $this->_addColor($workbook, $style->getBorders()->getTop()->getColor()->getRGB()),
						'RightColor' => $this->_addColor($workbook, $style->getBorders()->getRight()->getColor()->getRGB()),
						'LeftColor' => $this->_addColor($workbook, $style->getBorders()->getLeft()->getColor()->getRGB()),

						'FgColor' => $this->_addColor($workbook, $style->getFill()->getStartColor()->getRGB()),
						'BgColor' => $this->_addColor($workbook, $style->getFill()->getEndColor()->getRGB()),
						'Pattern' => $this->_mapFillType($style->getFill()->getFillType()),

					));
					if ($style->getAlignment()->getWrapText()) {
						$formats[$styleHash]->setTextWrap();
					}
					if ($style->getAlignment()->getShrinkToFit()) {
						$formats[$styleHash]->setShrinkToFit();
					}
					if ($style->getFont()->getItalic()) {
						$formats[$styleHash]->setItalic();
					}
					if ($style->getFont()->getStriketrough()) {
						$formats[$styleHash]->setStrikeOut();
					}
				}

				// Write cell value
				if ($cell->getValue() instanceof PHPExcel_RichText) {
					$worksheet->write($row, $column, $cell->getValue()->getPlainText(), $formats[$styleHash]);
				} else {
					// Hyperlink?
					if ($cell->hasHyperlink()) {
						$worksheet->writeUrl($row, $column, str_replace('sheet://', 'internal:', $cell->getHyperlink()->getUrl()), $cell->getValue(), $formats[$styleHash]);
					} else {
						$worksheet->write($row, $column, $cell->getValue(), $formats[$styleHash],$style->getNumberFormat()->getFormatCode());
					}
				}
			}

			// Column dimensions
			$phpSheet->calculateColumnWidths();
			$defaultWidth = null;
			if ($phpSheet->getDefaultColumnDimension()->getWidth() >= 0) {
				$defaultWidth = $phpSheet->getDefaultColumnDimension()->getWidth();
				for ($column = 0; $column < PHPExcel_Cell::columnIndexFromString($phpSheet->getHighestColumn()) - 1; $column++) {
					$worksheet->setColumn( $column, $column, $defaultWidth );
				}
			}
			foreach ($phpSheet->getColumnDimensions() as $columnDimension) {
				$column = PHPExcel_Cell::columnIndexFromString($columnDimension->getColumnIndex()) - 1;
				$worksheet->setColumn( $column, $column, $columnDimension->getWidth(), null, ($columnDimension->getVisible() ? '0' : '1'), $columnDimension->getOutlineLevel());
			}

			// Row dimensions
			$defaultHeight = null;
			if ($phpSheet->getDefaultRowDimension()->getRowHeight() >= 0) {
				$defaultHeight = $phpSheet->getDefaultRowDimension()->getRowHeight();
				for ($i = 0; $i < $phpSheet->getHighestRow() - 1; $i++) {
					$worksheet->setRow( $i, $defaultHeight );
				}
			}
			foreach ($phpSheet->getRowDimensions() as $rowDimension) {
				$worksheet->setRow( $rowDimension->getRowIndex() - 1, $rowDimension->getRowHeight(), null, ($rowDimension->getVisible() ? '0' : '1'), $rowDimension->getOutlineLevel() );
			}

			foreach ($phpSheet->getMergeCells() as $cells) {
				list($first, $last) = PHPExcel_Cell::splitRange($cells);
				list($firstColumn, $firstRow) = PHPExcel_Cell::coordinateFromString($first);
				list($lastColumn, $lastRow) = PHPExcel_Cell::coordinateFromString($last);
				$worksheet->mergeCells($firstRow - 1, PHPExcel_Cell::columnIndexFromString($firstColumn) - 1, $lastRow - 1, PHPExcel_Cell::columnIndexFromString($lastColumn) - 1);
			}

			foreach ($phpSheet->getDrawingCollection() as $drawing) {
				if ($drawing instanceof PHPExcel_Worksheet_BaseDrawing) {
					$filename = $drawing->getPath();
					$imagesize = getimagesize($filename);
					switch ($imagesize[2]) {
						case 1: $image = imagecreatefromgif($filename); break;
						case 2: $image = imagecreatefromjpeg($filename); break;
						case 3: $image = imagecreatefrompng($filename); break;
						default: continue 2;
					}
					list($column, $row) = PHPExcel_Cell::coordinateFromString($drawing->getCoordinates());
					$worksheet->insertBitmap($row - 1, PHPExcel_Cell::columnIndexFromString($column) - 1, $image, $drawing->getOffsetX(), $drawing->getOffsetY(), $drawing->getWidth() / $imagesize[0], $drawing->getHeight() / $imagesize[1]);
				}
			}

			// page setup
			if ($phpSheet->getPageSetup()->getOrientation() == PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE) {
				$worksheet->setLandscape();
			}
			$worksheet->setPaper($phpSheet->getPageSetup()->getPaperSize());
			$worksheet->setHeader($phpSheet->getHeaderFooter()->getOddHeader(), $phpSheet->getPageMargins()->getHeader());
			$worksheet->setFooter($phpSheet->getHeaderFooter()->getOddFooter(), $phpSheet->getPageMargins()->getFooter());
			$worksheet->setMarginLeft($phpSheet->getPageMargins()->getLeft());
			$worksheet->setMarginRight($phpSheet->getPageMargins()->getRight());
			$worksheet->setMarginTop($phpSheet->getPageMargins()->getTop());
			$worksheet->setMarginBottom($phpSheet->getPageMargins()->getBottom());

			// -------------------------------------------------------------------
			// Commented due to bug:
			// http://pear.php.net/bugs/bug.php?id=2146
			// -------------------------------------------------------------------
			// // repeatColumns / repeatRows
			// if ($phpSheet->getPageSetup()->isColumnsToRepeatAtLeftSet() || $phpSheet->getPageSetup()->isRowsToRepeatAtTopSet()) {
			// 	// Columns to repeat
			// 	if ($phpSheet->getPageSetup()->isColumnsToRepeatAtLeftSet()) {
			// 		$repeat = $phpSheet->getPageSetup()->getColumnsToRepeatAtLeft();
			//
			// 		$worksheet->repeatColumns(PHPExcel_Cell::columnIndexFromString($repeat[0] - 1), PHPExcel_Cell::columnIndexFromString($repeat[1] - 1));
			// 	}
			//
			// 	// Rows to repeat
			// 	if ($phpSheet->getPageSetup()->isRowsToRepeatAtTopSet()) {
			// 		$repeat = $phpSheet->getPageSetup()->getRowsToRepeatAtTop();
			//
			// 		$worksheet->repeatRows($repeat[0], $repeat[1]);
			// 	}
			// }

			// -------------------------------------------------------------------
			// Commented due to bug:
			// http://pear.php.net/bugs/bug.php?id=2146
			// -------------------------------------------------------------------
			// if ($phpSheet->getPageSetup()->isPrintAreaSet()) {
			//	// Print area
			//	$printArea = PHPExcel_Cell::splitRange($phpSheet->getPageSetup()->getPrintArea());
			//	$printArea[0] = PHPExcel_Cell::coordinateFromString($printArea[0]);
			//	$printArea[1] = PHPExcel_Cell::coordinateFromString($printArea[1]);
			//
			//	$worksheet->printArea(
			//		$printArea[0][1],
			//		PHPExcel_Cell::columnIndexFromString($printArea[0][0]) - 1,
			//		$printArea[1][1],
			//		PHPExcel_Cell::columnIndexFromString($printArea[1][0]) - 1
			//	);
			// }

			// Support for print scale
			if ($phpSheet->getPageSetup()->getScale()) {
				$worksheet->setPrintScale($phpSheet->getPageSetup()->getScale());
			}

			// Support for fitting to pages
			if ($phpSheet->getPageSetup()->getFitToWidth()) {
				if ($phpSheet->getPageSetup()->getFitToHeight()) {
					// Both properties are set, so use them
					// Note: This case is double, see below
					$worksheet->fitToPages($phpSheet->getPageSetup()->getFitToWidth(), $phpSheet->getPageSetup()->getFitToHeight());
				} else {
					// Only width given, make assumption about height
					$height = 0;
					$worksheet->fitToPages($phpSheet->getPageSetup()->getFitToWidth(), $height);
				}
			} else if ($phpSheet->getPageSetup()->getFitToHeight()) {
				if ($phpSheet->getPageSetup()->getFitToWidth()) {
					// Both properties are set, so use them
					// Note: This case is double, see below
					$worksheet->fitToPages($phpSheet->getPageSetup()->getFitToWidth(), $phpSheet->getPageSetup()->getFitToHeight());
				} else {
					// Only height given, make assumption about width
					$width = 0;
					$worksheet->fitToPages($width, $phpSheet->getPageSetup()->getFitToHeight());
				}
			}

			// Support for breaks
			$vBreaks = array();
			$hBreaks = array();
			foreach ($phpSheet->getBreaks() as $cell => $breakType) {
				// Fetch coordinates
				$coordinates = PHPExcel_Cell::coordinateFromString($cell);

				// Decide what to do by the type of break
				switch ($breakType) {
					case PHPExcel_Worksheet::BREAK_COLUMN:
						// Add to list of vertical breaks
						$vBreaks[] = $coordinates[0];
						break;

					case PHPExcel_Worksheet::BREAK_ROW:
						// Add to list of horizontal breaks
						$hBreaks[] = $coordinates[1];
						break;

					case PHPExcel_Worksheet::BREAK_NONE:
					default:
						// Nothing to do
						break;
				}
			}
			$worksheet->setVPagebreaks($vBreaks);
			$worksheet->setHPagebreaks($hBreaks);
		}

		PHPExcel_Calculation_Functions::setReturnDateType($saveDateReturnType);

		$workbook->close();
	}

	/**
	 * Add color
	 */
	private function _addColor($workbook, $rgb) {
		if (!isset($this->_colors[$rgb])) {
			$workbook->setCustomColor(8 + count($this->_colors), hexdec(substr($rgb, 0, 2)), hexdec(substr($rgb, 2, 2)), hexdec(substr($rgb, 4)));
			$this->_colors[$rgb] = 8 + count($this->_colors);
		}
		return $this->_colors[$rgb];
	}

	/**
	 * Map border style
	 */
	private function _mapBorderStyle($borderStyle) {
		switch ($borderStyle) {
			case PHPExcel_Style_Border::BORDER_NONE: return 0;
			case PHPExcel_Style_Border::BORDER_THICK: return 2;
			default: return 1; // map others to thin
		}
	}

	/**
	 * Map underline
	 */
	private function _mapUnderline($underline) {
		switch ($underline) {
			case PHPExcel_Style_Font::UNDERLINE_NONE:
				return 0;
			case PHPExcel_Style_Font::UNDERLINE_DOUBLE:
			case PHPExcel_Style_Font::UNDERLINE_DOUBLEACCOUNTING:
				return 2;
			default:
				return 1; // map others to single
		}
	}

	/**
	 * Map fill type
	 */
	private function _mapFillType($fillType) {
		switch ($fillType) { // just a guess
			case PHPExcel_Style_Fill::FILL_NONE: return 0;
			case PHPExcel_Style_Fill::FILL_SOLID: return 1;
			case PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR: return 2;
			case PHPExcel_Style_Fill::FILL_GRADIENT_PATH: return 3;
			case PHPExcel_Style_Fill::FILL_PATTERN_DARKDOWN: return 4;
			case PHPExcel_Style_Fill::FILL_PATTERN_DARKGRAY: return 5;
			case PHPExcel_Style_Fill::FILL_PATTERN_DARKGRID: return 6;
			case PHPExcel_Style_Fill::FILL_PATTERN_DARKHORIZONTAL: return 7;
			case PHPExcel_Style_Fill::FILL_PATTERN_DARKTRELLIS: return 8;
			case PHPExcel_Style_Fill::FILL_PATTERN_DARKUP: return 9;
			case PHPExcel_Style_Fill::FILL_PATTERN_DARKVERTICAL: return 10;
			case PHPExcel_Style_Fill::FILL_PATTERN_GRAY0625: return 11;
			case PHPExcel_Style_Fill::FILL_PATTERN_GRAY125: return 12;
			case PHPExcel_Style_Fill::FILL_PATTERN_LIGHTDOWN: return 13;
			case PHPExcel_Style_Fill::FILL_PATTERN_LIGHTGRAY: return 14;
			case PHPExcel_Style_Fill::FILL_PATTERN_LIGHTGRID: return 15;
			case PHPExcel_Style_Fill::FILL_PATTERN_LIGHTHORIZONTAL: return 16;
			case PHPExcel_Style_Fill::FILL_PATTERN_LIGHTTRELLIS: return 17;
			case PHPExcel_Style_Fill::FILL_PATTERN_LIGHTUP: return 18;
			case PHPExcel_Style_Fill::FILL_PATTERN_LIGHTVERTICAL: return 19;
			case PHPExcel_Style_Fill::FILL_PATTERN_MEDIUMGRAY: return 20;
		}

		return 0;
	}

	/**
	 * Map VAlign
	 */
	private function _mapVAlign($vAlign) {
		return ($vAlign == 'center' || $vAlign == 'justify' ? 'v' : '') . $vAlign;
	}

	/**
	 * Get an array of all styles
	 *
	 * @param	PHPExcel				$pPHPExcel
	 * @return	PHPExcel_Style[]		All styles in PHPExcel
	 * @throws	Exception
	 */
	private function _allStyles(PHPExcel $pPHPExcel = null)
	{
		// Get an array of all styles
		$aStyles		= array();

		for ($i = 0; $i < $pPHPExcel->getSheetCount(); $i++) {
			foreach ($pPHPExcel->getSheet($i)->getStyles() as $style) {
				$aStyles[] = $style;
			}
		}

		return $aStyles;
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
	 * @param	string	$pValue		Temporary storage directory
	 * @throws	Exception	Exception when directory does not exist
	 */
	public function setTempDir($pValue = '') {
		if (is_dir($pValue)) {
			$this->_tempDir = $pValue;
		} else {
			throw new Exception("Directory does not exist: $pValue");
		}
	}
}
