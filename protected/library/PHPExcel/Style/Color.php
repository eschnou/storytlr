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
 * @package    PHPExcel_Style
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.6.3, 2008-08-25
 */


/** PHPExcel_IComparable */
require_once 'PHPExcel/IComparable.php';


/**
 * PHPExcel_Style_Color
 *
 * @category   PHPExcel
 * @package    PHPExcel_Style
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Style_Color implements PHPExcel_IComparable
{
	/* Colors */
	const COLOR_BLACK						= 'FF000000';
	const COLOR_WHITE						= 'FFFFFFFF';
	const COLOR_RED							= 'FFFF0000';
	const COLOR_DARKRED						= 'FF800000';
	const COLOR_BLUE						= 'FF0000FF';
	const COLOR_DARKBLUE					= 'FF000080';
	const COLOR_GREEN						= 'FF00FF00';
	const COLOR_DARKGREEN					= 'FF008000';
	const COLOR_YELLOW						= 'FFFFFF00';
	const COLOR_DARKYELLOW					= 'FF808000';
	
	/**
	 * Indexed colors array
	 *
	 * @var array
	 */
	private static $_indexedColors;
	
	/**
	 * ARGB - Alpha RGB
	 *
	 * @var string
	 */
	private $_argb;
		
    /**
     * Create a new PHPExcel_Style_Color
     * 
     * @param string $pARGB
     */
    public function __construct($pARGB = PHPExcel_Style_Color::COLOR_BLACK)
    {
    	// Initialise values
    	$this->_argb			= $pARGB;
    }
    
    /**
     * Apply styles from array
     * 
     * <code>
     * $objPHPExcel->getActiveSheet()->getStyle('B2')->getFont()->getColor()->applyFromArray( array('rgb' => '808080') );
     * </code>
     * 
     * @param	array	$pStyles	Array containing style information
     * @throws	Exception
     */
    public function applyFromArray($pStyles = null) {
    	if (is_array($pStyles)) {
    		if (array_key_exists('rgb', $pStyles)) {
    			$this->setRGB($pStyles['rgb']);
    		}
    	    if (array_key_exists('argb', $pStyles)) {
    			$this->setARGB($pStyles['argb']);
    		}
    	} else {
    		throw new Exception("Invalid style array passed.");
    	}
    }
    
    /**
     * Get ARGB
     *
     * @return string
     */
    public function getARGB() {
    	return $this->_argb;
    }
    
    /**
     * Set ARGB
     *
     * @param string $pValue
     */
    public function setARGB($pValue = PHPExcel_Style_Color::COLOR_BLACK) {
    	if ($pValue == '') {
    		$pValue = PHPExcel_Style_Color::COLOR_BLACK;
    	}
    	$this->_argb = $pValue;
    }
    
    /**
     * Get RGB
     *
     * @return string
     */
    public function getRGB() {
    	return substr($this->_argb, 2);
    }
    
    /**
     * Set RGB
     *
     * @param string $pValue
     */
    public function setRGB($pValue = '000000') {
        if ($pValue == '') {
    		$pValue = '000000';
    	}
    	$this->_argb = 'FF' . $pValue;
    }
    
    /**
     * Get indexed color
     * 
     * @param	int		$pIndex
     * @return	PHPExcel_Style_Color
     */
    public static function indexedColor($pIndex) {
    	// Clean parameter
		$pIndex = intval($pIndex);
		
    	// Indexed colors
    	if (is_null(self::$_indexedColors)) {
			self::$_indexedColors = array();
			self::$_indexedColors[] = '00000000';
			self::$_indexedColors[] = '00FFFFFF';
			self::$_indexedColors[] = '00FF0000';
			self::$_indexedColors[] = '0000FF00';
			self::$_indexedColors[] = '000000FF';
			self::$_indexedColors[] = '00FFFF00';
			self::$_indexedColors[] = '00FF00FF';
			self::$_indexedColors[] = '0000FFFF';
			self::$_indexedColors[] = '00000000';
			self::$_indexedColors[] = '00FFFFFF';
			self::$_indexedColors[] = '00FF0000';
			self::$_indexedColors[] = '0000FF00';
			self::$_indexedColors[] = '000000FF';
			self::$_indexedColors[] = '00FFFF00';
			self::$_indexedColors[] = '00FF00FF';
			self::$_indexedColors[] = '0000FFFF';
			self::$_indexedColors[] = '00800000';
			self::$_indexedColors[] = '00008000';
			self::$_indexedColors[] = '00000080';
			self::$_indexedColors[] = '00808000';
			self::$_indexedColors[] = '00800080';
			self::$_indexedColors[] = '00008080';
			self::$_indexedColors[] = '00C0C0C0';
			self::$_indexedColors[] = '00808080';
			self::$_indexedColors[] = '009999FF';
			self::$_indexedColors[] = '00993366';
			self::$_indexedColors[] = '00FFFFCC';
			self::$_indexedColors[] = '00CCFFFF';
			self::$_indexedColors[] = '00660066';
			self::$_indexedColors[] = '00FF8080';
			self::$_indexedColors[] = '000066CC';
			self::$_indexedColors[] = '00CCCCFF';
			self::$_indexedColors[] = '00000080';
			self::$_indexedColors[] = '00FF00FF';
			self::$_indexedColors[] = '00FFFF00';
			self::$_indexedColors[] = '0000FFFF';
			self::$_indexedColors[] = '00800080';
			self::$_indexedColors[] = '00800000';
			self::$_indexedColors[] = '00008080';
			self::$_indexedColors[] = '000000FF';
			self::$_indexedColors[] = '0000CCFF';
			self::$_indexedColors[] = '00CCFFFF';
			self::$_indexedColors[] = '00CCFFCC';
			self::$_indexedColors[] = '00FFFF99';
			self::$_indexedColors[] = '0099CCFF';
			self::$_indexedColors[] = '00FF99CC';
			self::$_indexedColors[] = '00CC99FF';
			self::$_indexedColors[] = '00FFCC99';
			self::$_indexedColors[] = '003366FF';
			self::$_indexedColors[] = '0033CCCC';
			self::$_indexedColors[] = '0099CC00';
			self::$_indexedColors[] = '00FFCC00';
			self::$_indexedColors[] = '00FF9900';
			self::$_indexedColors[] = '00FF6600';
			self::$_indexedColors[] = '00666699';
			self::$_indexedColors[] = '00969696';
			self::$_indexedColors[] = '00003366';
			self::$_indexedColors[] = '00339966';
			self::$_indexedColors[] = '00003300';
			self::$_indexedColors[] = '00333300';
			self::$_indexedColors[] = '00993300';
			self::$_indexedColors[] = '00993366';
			self::$_indexedColors[] = '00333399';
			self::$_indexedColors[] = '00333333';
    	}
    	
		if (array_key_exists($pIndex, self::$_indexedColors)) {
			return new PHPExcel_Style_Color(self::$_indexedColors[$pIndex]);
		}
    	
    	return new PHPExcel_Style_Color();
    }

	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
    	return md5(
    		  $this->_argb
    		. __CLASS__
    	);
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
