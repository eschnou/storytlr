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


/** PHPExcel_RichText */
require_once 'PHPExcel/RichText.php';

/** PHPExcel_IComparable */
require_once 'PHPExcel/IComparable.php';


/**
 * PHPExcel_Comment
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Comment implements PHPExcel_IComparable
{
	/**
	 * Author
	 *
	 * @var string
	 */
	private $_author;
	
	/**
	 * Rich text comment
	 *
	 * @var PHPExcel_RichText
	 */
	private $_text;
		
    /**
     * Create a new PHPExcel_Comment
     * 
     * @throws	Exception
     */
    public function __construct()
    {
    	// Initialise variables
    	$this->_author		= 'Author';
    	$this->_text		= new PHPExcel_RichText();
    }
    
    /**
     * Get Author
     *
     * @return string
     */
    public function getAuthor() {
    	return $this->_author;
    }
    
    /**
     * Set Author
     *
     * @param string $pValue
     */
	public function setAuthor($pValue = '') {
		$this->_author = $pValue;
	}
    
    /**
     * Get Rich text comment
     *
     * @return PHPExcel_RichText
     */
    public function getText() {
    	return $this->_text;
    }
    
    /**
     * Set Rich text comment
     *
     * @param PHPExcel_RichText $pValue
     */
    public function setText(PHPExcel_RichText $pValue) {
    	$this->_text = $pValue;
    }
    
	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
    	return md5(
    		  $this->_author
    		. $this->_text->getHashCode()
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
