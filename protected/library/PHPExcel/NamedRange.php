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

/** PHPExcel_Worksheet */
require_once 'PHPExcel/Worksheet.php';


/**
 * PHPExcel_NamedRange
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_NamedRange
{
	/**
	 * Range name
	 *
	 * @var string
	 */
	private $_name;
	
	/**
	 * Worksheet on which the named range can be resolved
	 *
	 * @var PHPExcel_Worksheet
	 */
	private $_worksheet;
	
	/**
	 * Range of the referenced cells
	 *
	 * @var string
	 */
	private $_range;
	
	/**
	 * Is the named range local? (i.e. can only be used on $this->_worksheet)
	 *
	 * @var bool
	 */
	private $_localOnly;
    
    /**
     * Create a new NamedRange
     *
     * @param string $pName
     * @param PHPExcel_Worksheet $pWorksheet
     * @param string $pRange
     * @param bool $pLocalOnly
     */
    public function __construct($pName = null, PHPExcel_Worksheet $pWorksheet, $pRange = 'A1', $pLocalOnly = false)
    {
    	// Validate data
    	if (is_null($pName) || is_null($pWorksheet)|| is_null($pRange)) {
    		throw new Exception('Parameters can not be null.');
    	}
    	
    	// Set local members
    	$this->_name 		= $pName;
    	$this->_worksheet 	= $pWorksheet;
    	$this->_range 		= $pRange;
    	$this->_localOnly 	= $pLocalOnly;
    }
    
    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
    	return $this->_name;
    }
    
    /**
     * Set name
     *
     * @param string $value
     */
    public function setName($value = null) {
    	if (!is_null($value)) {
    		if (!is_null($this->_worksheet)) {
    			$this->_worksheet->getParent()->removeNamedRange($this->_name);
    		}
    		$this->_name = $value;
    		if (!is_null($this->_worksheet)) {
    			$this->_worksheet->getParent()->addNamedRange($this);
    		}
    	}
    }
    
    /**
     * Get worksheet
     *
     * @return PHPExcel_Worksheet
     */
    public function getWorksheet() {
    	return $this->_worksheet;
    }
    
    /**
     * Set worksheet
     *
     * @param PHPExcel_Worksheet $value
     */
    public function setWorksheet(PHPExcel_Worksheet $value = null) {
    	if (!is_null($value)) {
    		$this->_worksheet = $value;
    	}
    }
    
    /**
     * Get range
     *
     * @return string
     */
    public function getRange() {
    	return $this->_range;
    }
    
    /**
     * Set range
     *
     * @param string $value
     */
    public function setRange($value = null) {
    	if (!is_null($value)) {
    		$this->_range = $value;
    	}
    }
    
    /**
     * Get localOnly
     *
     * @return bool
     */
    public function getLocalOnly() {
    	return $this->_localOnly;
    }
    
    /**
     * Set localOnly
     *
     * @param bool $value
     */
    public function setLocalOnly($value = false) {
    	$this->_localOnly = $value;
    }
    
    /**
     * Resolve a named range to a regular cell range
     *
     * @param string $pNamedRange Named range
     * @param PHPExcel_Worksheet $pSheet Worksheet
     * @return PHPExcel_NamedRange
     */
    public static function resolveRange($pNamedRange = '', PHPExcel_Worksheet $pSheet) {
		return $pSheet->getParent()->getNamedRange($pNamedRange);
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
