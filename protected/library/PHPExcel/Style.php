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


/** PHPExcel_Style_Color */
require_once 'PHPExcel/Style/Color.php';

/** PHPExcel_Style_Font */
require_once 'PHPExcel/Style/Font.php';

/** PHPExcel_Style_Fill */
require_once 'PHPExcel/Style/Fill.php';

/** PHPExcel_Style_Borders */
require_once 'PHPExcel/Style/Borders.php';

/** PHPExcel_Style_Alignment */
require_once 'PHPExcel/Style/Alignment.php';

/** PHPExcel_Style_NumberFormat */
require_once 'PHPExcel/Style/NumberFormat.php';

/** PHPExcel_Style_Conditional */
require_once 'PHPExcel/Style/Conditional.php';

/** PHPExcel_Style_Protection */
require_once 'PHPExcel/Style/Protection.php';

/** PHPExcel_IComparable */
require_once 'PHPExcel/IComparable.php';

/**
 * PHPExcel_Style
 *
 * @category   PHPExcel
 * @package    PHPExcel_Cell
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Style implements PHPExcel_IComparable
{
	/**
	 * Font
	 *
	 * @var PHPExcel_Style_Font
	 */
	private $_font;
	
	/**
	 * Fill
	 *
	 * @var PHPExcel_Style_Fill
	 */
	private $_fill;

	/**
	 * Borders
	 *
	 * @var PHPExcel_Style_Borders
	 */
	private $_borders;
	
	/**
	 * Alignment
	 *
	 * @var PHPExcel_Style_Alignment
	 */
	private $_alignment;
	
	/**
	 * Number Format
	 *
	 * @var PHPExcel_Style_NumberFormat
	 */
	private $_numberFormat;
	
	/**
	 * Conditional styles
	 *
	 * @var PHPExcel_Style_Conditional[]
	 */
	private $_conditionalStyles;
	
	/**
	 * Protection
	 *
	 * @var PHPExcel_Style_Protection
	 */
	private $_protection;
	
    /**
     * Create a new PHPExcel_Style
     */
    public function __construct()
    {
    	// Initialise values

		/**
		 * The following properties are late bound. Binding is initiated by property classes when they are modified.
		 *
		 * _font
		 * _fill
		 * _borders
		 * _alignment
		 * _numberFormat
		 * _protection
		 *
		 */

    	$this->_conditionalStyles 	= array();
    }
 
    /**
     * Property Complete Bind
     *
     * Complete the binding process a child property object started
	 *
     * @param	$propertyObject
     * @param	$propertyName			Name of this property in the parent object
     * @throws Exception	 
     */ 
    public function propertyCompleteBind($propertyObject, $propertyName) {
    	switch($propertyName) {
    		case "_font":
				$this->_font = $propertyObject;
				break;
    			
    		case "_fill":
				$this->_fill = $propertyObject;
				break;
    			
    		case "_borders":
				$this->_borders = $propertyObject;
				break;
    			
    		case "_alignment":
				$this->_alignment = $propertyObject;
				break;
    			
			case "_numberFormat":
				$this->_numberFormat = $propertyObject;
				break;
				
			case "_protection":
				$this->_protection = $propertyObject;
				break;
			
			default:
				throw new Exception("Invalid property passed.");
    	}
    }

	/**
	 * Property Is Bound
	 *
	 * Determines if a child property is bound to this one
	 *
     * @param	$propertyName			Name of this property in the parent object
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 */
	public function propertyIsBound($propertyName) {
    	switch($propertyName) {
    		case "_font":
				return isset($this->_font);
    			
    		case "_fill":
				return isset($this->_fill);
    			
    		case "_borders":
				return isset($this->_borders);
    			
    		case "_alignment":
				return isset($this->_alignment);
    			
			case "_numberFormat":
				return isset($this->_numberFormat);
				
			case "_protection":
				return isset($this->_protection);
				
			default:
				throw new Exception("Invalid property passed.");
		}
	}
    
    /**
     * Apply styles from array
     * 
     * <code>
     * $objPHPExcel->getActiveSheet()->getStyle('B2')->applyFromArray(
     * 		array(
     * 			'font'    => array(
     * 				'name'      => 'Arial',
     * 				'bold'      => true,
     * 				'italic'    => false,
     * 				'underline' => PHPExcel_Style_Font::UNDERLINE_DOUBLE,
     * 				'strike'    => false,
     * 				'color'     => array(
     * 					'rgb' => '808080'
     * 				)
     * 			),
     * 			'borders' => array(
     * 				'bottom'     => array(
     * 					'style' => PHPExcel_Style_Border::BORDER_DASHDOT,
     * 					'color' => array(
     * 						'rgb' => '808080'
     * 					)
     * 				),
     * 				'top'     => array(
     * 					'style' => PHPExcel_Style_Border::BORDER_DASHDOT,
     * 					'color' => array(
     * 						'rgb' => '808080'
     * 					)
     * 				)
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
        	if (array_key_exists('fill', $pStyles)) {
        		$this->getFill()->applyFromArray($pStyles['fill']);
        	}
          	if (array_key_exists('font', $pStyles)) {
        		$this->getFont()->applyFromArray($pStyles['font']);
        	}
        	if (array_key_exists('borders', $pStyles)) {
        		$this->getBorders()->applyFromArray($pStyles['borders']);
        	}
        	if (array_key_exists('alignment', $pStyles)) {
        		$this->getAlignment()->applyFromArray($pStyles['alignment']);
        	}
           	if (array_key_exists('numberformat', $pStyles)) {
        		$this->getNumberFormat()->applyFromArray($pStyles['numberformat']);
        	}
        	if (array_key_exists('protection', $pStyles)) {
        		$this->getProtection()->applyFromArray($pStyles['protection']);
        	}
    	} else {
    		throw new Exception("Invalid style array passed.");
    	}
    }
    
    /**
     * Get Fill
     *
     * @return PHPExcel_Style_Fill
     */
    public function getFill() {
		if(isset($this->_fill))
			return $this->_fill;

		$property = new PHPExcel_Style_Fill();
		$property->propertyPrepareBind($this, "_fill");
		return $property;
    }
    
    /**
     * Get Font
     *
     * @return PHPExcel_Style_Font
     */
    public function getFont() {
		if(isset($this->_font))
			return $this->_font;

		$property = new PHPExcel_Style_Font();
		$property->propertyPrepareBind($this, "_font");
		return $property;
    }
    
    /**
     * Get Borders
     *
     * @return PHPExcel_Style_Borders
     */
    public function getBorders() {
		if(isset($this->_borders))
			return $this->_borders;

		$property = new PHPExcel_Style_Borders();
		$property->propertyPrepareBind($this, "_borders");
		return $property;
    }
    
    /**
     * Get Alignment
     *
     * @return PHPExcel_Style_Alignment
     */
    public function getAlignment() {
		if(isset($this->_alignment))
			return $this->_alignment;

		$property = new PHPExcel_Style_Alignment();
		$property->propertyPrepareBind($this, "_alignment");
		return $property;
    }
    
    /**
     * Get Number Format
     *
     * @return PHPExcel_Style_NumberFormat
     */
    public function getNumberFormat() {
		if(isset($this->_numberFormat))
			return $this->_numberFormat;

		$property = new PHPExcel_Style_NumberFormat();
		$property->propertyPrepareBind($this, "_numberFormat");
		return $property;
    }
    
    /**
     * Get Conditional Styles
     *
     * @return PHPExcel_Style_Conditional[]
     */
    public function getConditionalStyles() {
		return $this->_conditionalStyles;
    }
       
    /**
     * Set Conditional Styles
     *
     * @param PHPExcel_Style_Conditional[]	$pValue	Array of condtional styles
     */
    public function setConditionalStyles($pValue = null) {
    	if (is_array($pValue)) {
    		$this->_conditionalStyles = $pValue;
    	}
    }
    
    /**
     * Get Protection
     *
     * @return PHPExcel_Style_Protection
     */
    public function getProtection() {
		if(isset($this->_protection))
			return $this->_protection;

		$property = new PHPExcel_Style_Protection();
		$property->propertyPrepareBind($this, "_protection");
		return $property;
    }
   
	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
		$hashConditionals = '';
		foreach ($this->_conditionalStyles as $conditional) {
			$hashConditionals .= $conditional->getHashCode();
		}
		
    	return md5(
    		  $this->getFill()->getHashCode()
    		. $this->getFont()->getHashCode()
    		. $this->getBorders()->getHashCode()
    		. $this->getAlignment()->getHashCode()
    		. $this->getNumberFormat()->getHashCode()
    		. $hashConditionals
    		. $this->getProtection()->getHashCode()
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
