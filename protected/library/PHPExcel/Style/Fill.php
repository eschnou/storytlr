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


/** PHPExcel_Style_Color */
require_once 'PHPExcel/Style/Color.php';

/** PHPExcel_IComparable */
require_once 'PHPExcel/IComparable.php';


/**
 * PHPExcel_Style_Fill
 *
 * @category   PHPExcel
 * @package    PHPExcel_Style
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Style_Fill implements PHPExcel_IComparable
{
	/* Fill types */
	const FILL_NONE							= 'none';
	const FILL_SOLID						= 'solid';
	const FILL_GRADIENT_LINEAR				= 'linear';
	const FILL_GRADIENT_PATH				= 'path';
	const FILL_PATTERN_DARKDOWN				= 'darkDown';
	const FILL_PATTERN_DARKGRAY				= 'darkGray';
	const FILL_PATTERN_DARKGRID				= 'darkGrid';
	const FILL_PATTERN_DARKHORIZONTAL		= 'darkHorizontal';
	const FILL_PATTERN_DARKTRELLIS			= 'darkTrellis';
	const FILL_PATTERN_DARKUP				= 'darkUp';
	const FILL_PATTERN_DARKVERTICAL			= 'darkVertical';
	const FILL_PATTERN_GRAY0625				= 'gray0625';
	const FILL_PATTERN_GRAY125				= 'gray125';
	const FILL_PATTERN_LIGHTDOWN			= 'lightDown';
	const FILL_PATTERN_LIGHTGRAY			= 'lightGray';
	const FILL_PATTERN_LIGHTGRID			= 'lightGrid';
	const FILL_PATTERN_LIGHTHORIZONTAL		= 'lightHorizontal';
	const FILL_PATTERN_LIGHTTRELLIS			= 'lightTrellis';
	const FILL_PATTERN_LIGHTUP				= 'lightUp';
	const FILL_PATTERN_LIGHTVERTICAL		= 'lightVertical';
	const FILL_PATTERN_MEDIUMGRAY			= 'mediumGray';

	/**
	 * Fill type
	 *
	 * @var string
	 */
	private $_fillType;
	
	/**
	 * Rotation
	 *
	 * @var double
	 */
	private $_rotation;
	
	/**
	 * Start color
	 * 
	 * @var PHPExcel_Style_Color
	 */
	private $_startColor;
	
	/**
	 * End color
	 * 
	 * @var PHPExcel_Style_Color
	 */
	private $_endColor;
	
	/**
	 * Parent Style
	 *
	 * @var PHPExcel_Style
	 */
	 
	private $_parent;
	
	/**
	 * Parent Borders
	 *
	 * @var _parentPropertyName string
	 */
	private $_parentPropertyName;
		
    /**
     * Create a new PHPExcel_Style_Fill
     */
    public function __construct()
    {
    	// Initialise values
    	$this->_fillType			= PHPExcel_Style_Fill::FILL_NONE;
    	$this->_rotation			= 0;
		$this->_startColor			= new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_WHITE);
		$this->_endColor			= new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_BLACK);
    }

	/**
	 * Property Prepare bind
	 *
	 * Configures this object for late binding as a property of a parent object
	 *	 
	 * @param $parent
	 * @param $parentPropertyName
	 */
	public function propertyPrepareBind($parent, $parentPropertyName)
	{
		// Initialize parent PHPExcel_Style for late binding. This relationship purposely ends immediately when this object
		// is bound to the PHPExcel_Style object pointed to so as to prevent circular references.
		$this->_parent				= $parent;
		$this->_parentPropertyName	= $parentPropertyName;
	}

    /**
     * Property Get Bound
     *
     * Returns the PHPExcel_Style_Fill that is actual bound to PHPExcel_Style
	 *
	 * @return PHPExcel_Style_Fill
     */
	private function propertyGetBound() {
		if(!isset($this->_parent))
			return $this;																// I am bound

		if($this->_parent->propertyIsBound($this->_parentPropertyName))
			return $this->_parent->getFill();											// Another one is bound

		return $this;																	// No one is bound yet
	}
	
    /**
     * Property Begin Bind
     *
     * If no PHPExcel_Style_Fill has been bound to PHPExcel_Style then bind this one. Return the actual bound one.
	 *
	 * @return PHPExcel_Style_Fill
     */
	private function propertyBeginBind() {
		if(!isset($this->_parent))
			return $this;																// I am already bound

		if($this->_parent->propertyIsBound($this->_parentPropertyName))
			return $this->_parent->getFill();											// Another one is already bound
			
		$this->_parent->propertyCompleteBind($this, $this->_parentPropertyName);		// Bind myself
		$this->_parent = null;
		
		return $this;
	}
        
    /**
     * Apply styles from array
     * 
     * <code>
     * $objPHPExcel->getActiveSheet()->getStyle('B2')->getFill()->applyFromArray(
     * 		array(
     * 			'type'       => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
     * 			'rotation'   => 0,
     * 			'startcolor' => array(
     * 				'rgb' => '000000'
     * 			),
     * 			'endcolor'   => array(
     * 				'argb' => 'FFFFFFFF'
     * 			)
     * 		)
     * );
     * </code>
     * 
     * @param	array	$pStyles	Array containing style information
     * @throws	Exception
     */
    public function applyFromArray($pStyles = null) {
    	if (is_array($pStyles)) {
    	    if (array_key_exists('type', $pStyles)) {
    			$this->setFillType($pStyles['type']);
    		}
    	    if (array_key_exists('rotation', $pStyles)) {
    			$this->setRotation($pStyles['rotation']);
    		}
    	    if (array_key_exists('startcolor', $pStyles)) {
    			$this->getStartColor()->applyFromArray($pStyles['startcolor']);
    		}
    	    if (array_key_exists('endcolor', $pStyles)) {
    			$this->getEndColor()->applyFromArray($pStyles['endcolor']);
    		}
    	    if (array_key_exists('color', $pStyles)) {
    			$this->getStartColor()->applyFromArray($pStyles['color']);
    		}
    	} else {
    		throw new Exception("Invalid style array passed.");
    	}
    }
    
    /**
     * Get Fill Type
     *
     * @return string
     */
    public function getFillType() {
    	$property = $this->propertyGetBound();
    
    	if ($property->_fillType == '') {
    		$property->_fillType = self::FILL_NONE;
    	}
    	return $property->_fillType;
    }
    
    /**
     * Set Fill Type
     *
     * @param string $pValue	PHPExcel_Style_Fill fill type
     */
    public function setFillType($pValue = PHPExcel_Style_Fill::FILL_NONE) {
    	$this->propertyBeginBind()->_fillType = $pValue;
    }
    
    /**
     * Get Rotation
     *
     * @return double
     */
    public function getRotation() {
    	return $this->propertyGetBound()->_rotation;
    }
    
    /**
     * Set Rotation
     *
     * @param double $pValue
     */
    public function setRotation($pValue = 0) {
    	$this->propertyBeginBind()->_rotation = $pValue;
    }
    
    /**
     * Get Start Color
     *
     * @return PHPExcel_Style_Color
     */
    public function getStartColor() {
    	// It's a get but it may lead to a modified color which we won't detect but in which case we must bind.
    	// So bind as an assurance.
    	return $this->propertyBeginBind()->_startColor;
    }
    
    /**
     * Set Start Color
     *
     * @param 	PHPExcel_Style_Color $pValue
     * @throws 	Exception
     */
    public function setStartColor(PHPExcel_Style_Color $pValue = null) {
   		$this->propertyBeginBind()->_startColor = $pValue;
    }
    
    /**
     * Get End Color
     *
     * @return PHPExcel_Style_Color
     */
    public function getEndColor() {
    	// It's a get but it may lead to a modified color which we won't detect but in which case we must bind.
    	// So bind as an assurance.
    	return $this->propertyBeginBind()->_endColor;
    }
    
    /**
     * Set End Color
     *
     * @param 	PHPExcel_Style_Color $pValue
     * @throws 	Exception
     */
    public function setEndColor(PHPExcel_Style_Color $pValue = null) {
   		$this->propertyBeginBind()->_endColor = $pValue;
    }

	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
		$property = $this->propertyGetBound();
    	return md5(
    		  $property->_fillType
    		. $property->_rotation
    		. $property->_startColor->getHashCode()
    		. $property->_endColor->getHashCode()
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
