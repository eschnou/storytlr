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


/**
 * PHPExcel_Shared_Font
 *
 * @category   PHPExcel
 * @package    PHPExcel_Shared
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Shared_Font
{
	/**
	 * Calculate an (approximate) OpenXML column width, based on font size and text contained
	 *
	 * @param 	int		$fontSize			Font size (in pixels or points)
	 * @param 	bool	$fontSizeInPixels	Is the font size specified in pixels (true) or in points (false) ?
	 * @param 	string	$columnText			Text to calculate width
	 * @return 	int		Column width
	 */
	public static function calculateColumnWidth($fontSize = 9, $fontSizeInPixels = false, $columnText = '') {
		if (!$fontSizeInPixels) {
			// Translate points size to pixel size
			$fontSize = PHPExcel_Shared_Font::fontSizeToPixels($fontSize);
		}
		
		// If it is rich text, use rich text...
		if ($columnText instanceof PHPExcel_RichText) {
			$columnText = $columnText->getPlainText();
		}
		
		// Only measure the part before the first newline character
		if (strpos($columnText, "\r") !== false) {
			$columnText = substr($columnText, 0, strpos($columnText, "\r"));
		}
		if (strpos($columnText, "\n") !== false) {
			$columnText = substr($columnText, 0, strpos($columnText, "\n"));
		}
		
		// Calculate column width
		return round( ( (strlen($columnText) * $fontSize + 5) / $fontSize * 256 ) / 256, 6);
	}
	
	/**
	 * Calculate an (approximate) pixel size, based on a font points size
	 *
	 * @param 	int		$fontSizeInPoints	Font size (in points)
	 * @return 	int		Font size (in pixels)
	 */
	public static function fontSizeToPixels($fontSizeInPoints = 12) {
		return ((16 / 12) * $fontSizeInPoints);
	}
}
