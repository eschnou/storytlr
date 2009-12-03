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
 * PHPExcel_Shared_String
 *
 * @category   PHPExcel
 * @package    PHPExcel_Shared
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Shared_String
{
	/**
	 * Convert from OpenXML escaped control character to PHP control character
	 * 
	 * Excel 2007 team:
	 * ----------------
	 * That's correct, control characters are stored directly in the shared-strings table. 
	 * We do encode characters that cannot be represented in XML using the following escape sequence:
	 * _xHHHH_ where H represents a hexadecimal character in the character's value...
	 * So you could end up with something like _x0008_ in a string (either in a cell value (<v>)
	 * element or in the shared string <t> element.
	 * 
	 * @param 	string	$value	Value to unescape
	 * @return 	string
	 */
	public static function ControlCharacterOOXML2PHP($value = '') {
		for ($i = 0; $i <= 19; $i++) {
			if ($i != 9 && $i != 10 && $i != 13) {
				$value = str_replace('_x' . sprintf('%04s' , strtoupper(dechex($i))) . '_', chr($i), $value);
			}
		}

		return $value;
	}
	
	/**
	 * Convert from PHP control character to OpenXML escaped control character
	 * 
	 * Excel 2007 team:
	 * ----------------
	 * That's correct, control characters are stored directly in the shared-strings table. 
	 * We do encode characters that cannot be represented in XML using the following escape sequence:
	 * _xHHHH_ where H represents a hexadecimal character in the character's value...
	 * So you could end up with something like _x0008_ in a string (either in a cell value (<v>)
	 * element or in the shared string <t> element.
	 * 
	 * @param 	string	$value	Value to escape
	 * @return 	string
	 */
	public static function ControlCharacterPHP2OOXML($value = '') {
		for ($i = 0; $i <= 19; $i++) {
			if ($i != 9 && $i != 10 && $i != 13) {
				$value = str_replace(chr($i), '_x' . sprintf('%04s' , strtoupper(dechex($i))) . '_', $value);
			}
		}

		return $value;
	}
	
	/**
	 * Check if a string contains UTF8 data
	 *
	 * @param string $value
	 * @return boolean
	 */
	public static function IsUTF8($value = '') {
		return utf8_encode(utf8_decode($value)) === $value;
	}
	
	/**
	 * Formats a numeric value as a string for output in various output writers
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function FormatNumber($value) {
		return number_format($value, 2, '.', '');
	}
}
