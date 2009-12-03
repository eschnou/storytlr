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


/** PHPExcel_IComparable */
require_once 'PHPExcel/IComparable.php';

/** PHPExcel_Cell */
require_once 'PHPExcel/Cell.php';

/** PHPExcel_RichText_ITextElement */
require_once 'PHPExcel/RichText/ITextElement.php';

/** PHPExcel_RichText_TextElement */
require_once 'PHPExcel/RichText/TextElement.php';

/** PHPExcel_RichText_Run */
require_once 'PHPExcel/RichText/Run.php';

/** PHPExcel_Style_Font */
require_once 'PHPExcel/Style/Font.php';

/**
 * PHPExcel_RichText
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_RichText implements PHPExcel_IComparable
{
	/**
	 * Rich text elements
	 *
	 * @var PHPExcel_RichText_ITextElement[]
	 */
	private $_richTextElements;
	
	/**
	 * Parent cell
	 *
	 * @var PHPExcel_Cell
	 */
	private $_parent;
	   
    /**
     * Create a new PHPExcel_RichText instance
     *
     * @param 	PHPExcel_Cell	$pParent
     * @throws	Exception
     */
    public function __construct(PHPExcel_Cell $pCell = null)
    {
    	// Initialise variables
    	$this->_richTextElements = array();
    	
    	// Set parent?
    	if (!is_null($pCell)) {
	    	// Set parent cell
	    	$this->_parent = $pCell;
	    		
	    	// Add cell text and style
	    	if ($this->_parent->getValue() != "") {
	    		$objRun = new PHPExcel_RichText_Run($this->_parent->getValue());
	    		$objRun->setFont(clone $this->_parent->getParent()->getStyle($this->_parent->getCoordinate())->getFont());
	    		$this->addText($objRun);
	    	}
	    		
	    	// Set parent value
	    	$this->_parent->setValue($this);
    	}
    }
    
    /**
     * Add text
     *
     * @param 	PHPExcel_RichText_ITextElement		$pText		Rich text element
     * @throws 	Exception
     */
    public function addText(PHPExcel_RichText_ITextElement $pText = null)
    {
    	$this->_richTextElements[] = $pText;
    }
    
    /**
     * Create text
     *
     * @param 	string	$pText	Text
     * @return	PHPExcel_RichText_TextElement
     * @throws 	Exception
     */
    public function createText($pText = '')
    {
    	$objText = new PHPExcel_RichText_TextElement($pText);
    	$this->addText($objText);
    	return $objText;
    }
    
    /**
     * Create text run
     *
     * @param 	string	$pText	Text
     * @return	PHPExcel_RichText_Run
     * @throws 	Exception
     */
    public function createTextRun($pText = '')
    {
    	$objText = new PHPExcel_RichText_Run($pText);
    	$this->addText($objText);
    	return $objText;
    }
    
    /**
     * Get plain text
     *
     * @return string
     */
    public function getPlainText()
    {
    	// Return value
    	$returnValue = '';
    	
    	// Loop trough all PHPExcel_RichText_ITextElement
    	foreach ($this->_richTextElements as $text) {
    		$returnValue .= $text->getText();
    	}
    	
    	// Return
    	return $returnValue;
    }
    
    /**
     * Get Rich Text elements
     *
     * @return PHPExcel_RichText_ITextElement[]
     */
    public function getRichTextElements()
    {
    	return $this->_richTextElements;
    }
    
    /**
     * Set Rich Text elements
     *
     * @param 	PHPExcel_RichText_ITextElement[]	$pElements		Array of elements
     * @throws 	Exception
     */
    public function setRichTextElements($pElements = null)
    {
    	if (is_array($pElements)) {
    		$this->_richTextElements = $pElements;
    	} else {
    		throw new Exception("Invalid PHPExcel_RichText_ITextElement[] array passed.");
    	}
    }
 
    /**
     * Get parent
     *
     * @return PHPExcel_Cell
     */
    public function getParent() {
    	return $this->_parent;
    }
    
    /**
     * Set parent
     *
     * @param PHPExcel_Cell	$value
     */
    public function setParent(PHPExcel_Cell $value) {
    	// Set parent
    	$this->_parent = $value;
    	
    	// Set parent value
    	$this->_parent->setValue($this);
    }
    
	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
		$hashElements = '';
		foreach ($this->_richTextElements as $element) {
			$hashElements .= $element->getHashCode();
		}
		
    	return md5(
    		  $hashElements
    		. __CLASS__
    	);
    }
    
	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone() {
		$vars = get_object_vars($this);
		foreach ($vars as $key => $value) {
			if ($key == '_parent') continue;
			
			if (is_object($value)) {
				$this->$key = clone $value;
			} else {
				$this->$key = $value;
			}
		}
	}
}
