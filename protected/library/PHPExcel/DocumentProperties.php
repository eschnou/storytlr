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


/**
 * PHPExcel_DocumentProperties
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_DocumentProperties
{
	/**
	 * Creator
	 *
	 * @var string
	 */
	private $_creator;
	
	/**
	 * LastModifiedBy
	 *
	 * @var string
	 */
	private $_lastModifiedBy;
	
	/**
	 * Created
	 *
	 * @var datetime
	 */
	private $_created;
	
	/**
	 * Modified
	 *
	 * @var datetime
	 */
	private $_modified;
	
	/**
	 * Title
	 *
	 * @var string
	 */
	private $_title;
	
	/**
	 * Description
	 *
	 * @var string
	 */
	private $_description;
	
	/**
	 * Subject
	 *
	 * @var string
	 */
	private $_subject;
	
	/**
	 * Keywords
	 *
	 * @var string
	 */
	private $_keywords;
	
	/**
	 * Category
	 *
	 * @var string
	 */
	private $_category;
	
    /**
     * Create a new PHPExcel_DocumentProperties
     */
    public function __construct()
    {
    	// Initialise values
    	$this->_creator 		= 'Unknown Creator';
    	$this->_lastModifiedBy 	= $this->_creator;
    	$this->_created 		= time();
    	$this->_modified 		= time();
    	$this->_title			= "Untitled Spreadsheet";
    	$this->_subject			= '';
    	$this->_description		= '';
    	$this->_keywords		= '';
    	$this->_category		= '';
    }
    
    /**
     * Get Creator
     *
     * @return string
     */
    public function getCreator() {
    	return $this->_creator;
    }
    
    /**
     * Set Creator
     *
     * @param string $pValue
     */
    public function setCreator($pValue = '') {
    	$this->_creator = $pValue;
    }
    
    /**
     * Get Last Modified By
     *
     * @return string
     */
    public function getLastModifiedBy() {
    	return $this->_lastModifiedBy;
    }
    
    /**
     * Set Last Modified By
     *
     * @param string $pValue
     */
    public function setLastModifiedBy($pValue = '') {
    	$this->_lastModifiedBy = $pValue;
    }
    
    /**
     * Get Created
     *
     * @return datetime
     */
    public function getCreated() {
    	return $this->_created;
    }
    
    /**
     * Set Created
     *
     * @param datetime $pValue
     */
    public function setCreated($pValue = null) {
    	if (is_null($pValue)) {
    		$pValue = time();
    	}
    	$this->_created = $pValue;
    }
    
    /**
     * Get Modified
     *
     * @return datetime
     */
    public function getModified() {
    	return $this->_modified;
    }
    
    /**
     * Set Modified
     *
     * @param datetime $pValue
     */
    public function setModified($pValue = null) {
    	if (is_null($pValue)) {
    		$pValue = time();
    	}
    	$this->_modified = $pValue;
    }
    
    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle() {
    	return $this->_title;
    }
    
    /**
     * Set Title
     *
     * @param string $pValue
     */
    public function setTitle($pValue = '') {
    	$this->_title = $pValue;
    }
    
    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription() {
    	return $this->_description;
    }
    
    /**
     * Set Description
     *
     * @param string $pValue
     */
    public function setDescription($pValue = '') {
    	$this->_description = $pValue;
    }
    
    /**
     * Get Subject
     *
     * @return string
     */
    public function getSubject() {
    	return $this->_subject;
    }
    
    /**
     * Set Subject
     *
     * @param string $pValue
     */
    public function setSubject($pValue = '') {
    	$this->_subject = $pValue;
    }
    
    /**
     * Get Keywords
     *
     * @return string
     */
    public function getKeywords() {
    	return $this->_keywords;
    }
    
    /**
     * Set Keywords
     *
     * @param string $pValue
     */
    public function setKeywords($pValue = '') {
    	$this->_keywords = $pValue;
    }
    
    /**
     * Get Category
     *
     * @return string
     */
    public function getCategory() {
    	return $this->_category;
    }
    
    /**
     * Set Category
     *
     * @param string $pValue
     */
    public function setCategory($pValue = '') {
    	$this->_category = $pValue;
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
