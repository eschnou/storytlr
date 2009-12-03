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
 * You should have received a copy of tshhe GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel_Reader
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.6.3, 2008-08-25
 */

// Original file header of ParseXL (used as the base for this class):
// --------------------------------------------------------------------------------
// Adapted from Excel_Spreadsheet_Reader developed by users bizon153,
// trex005, and mmp11 (SourceForge.net)
// http://sourceforge.net/projects/phpexcelreader/
// Primary changes made by canyoncasa (dvc) for ParseXL 1.00 ...
//	 Modelled moreso after Perl Excel Parse/Write modules
//	 Added Parse_Excel_Spreadsheet object
//		 Reads a whole worksheet or tab as row,column array or as
//		 associated hash of indexed rows and named column fields
//	 Added variables for worksheet (tab) indexes and names
//	 Added an object call for loading individual woorksheets
//	 Changed default indexing defaults to 0 based arrays
//	 Fixed date/time and percent formats
//	 Includes patches found at SourceForge...
//		 unicode patch by nobody
//		 unpack("d") machine depedency patch by matchy
//		 boundsheet utf16 patch by bjaenichen
//	 Renamed functions for shorter names
//	 General code cleanup and rigor, including <80 column width
//	 Included a testcase Excel file and PHP example calls
//	 Code works for PHP 5.x

// Primary changes made by canyoncasa (dvc) for ParseXL 1.10 ...
// http://sourceforge.net/tracker/index.php?func=detail&aid=1466964&group_id=99160&atid=623334
//	 Decoding of formula conditions, results, and tokens.
//	 Support for user-defined named cells added as an array "namedcells"
//		 Patch code for user-defined named cells supports single cells only.
//		 NOTE: this patch only works for BIFF8 as BIFF5-7 use a different
//		 external sheet reference structure


/** PHPExcel */
require_once 'PHPExcel.php';

/** PHPExcel_Reader_IReader */
require_once 'PHPExcel/Reader/IReader.php';

/** PHPExcel_Shared_OLERead */
require_once 'PHPExcel/Shared/OLERead.php';


/**
 * PHPExcel_Reader_Excel5
 *
 * This class uses {@link http://sourceforge.net/projects/phpexcelreader/parseXL}
 *
 * @category   PHPExcel
 * @package    PHPExcel_Reader
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Reader_Excel5 implements PHPExcel_Reader_IReader
{
	// ParseXL definitions
	const XLS_BIFF8 = 0x600;
	const XLS_BIFF7 = 0x500;
	const XLS_WorkbookGlobals = 0x5;
	const XLS_Worksheet = 0x10;
	
	const XLS_Type_BOF = 0x809;
	const XLS_Type_EOF = 0x0a;
	const XLS_Type_BOUNDSHEET = 0x85;
	const XLS_Type_DIMENSION = 0x200;
	const XLS_Type_ROW = 0x208;
	const XLS_Type_DBCELL = 0xd7;
	const XLS_Type_FILEPASS = 0x2f;
	const XLS_Type_NOTE = 0x1c;
	const XLS_Type_TXO = 0x1b6;
	const XLS_Type_RK = 0x7e;
	const XLS_Type_RK2 = 0x27e;
	const XLS_Type_MULRK = 0xbd;
	const XLS_Type_MULBLANK = 0xbe;
	const XLS_Type_INDEX = 0x20b;
	const XLS_Type_SST = 0xfc;
	const XLS_Type_EXTSST = 0xff;
	const XLS_Type_CONTINUE = 0x3c;
	const XLS_Type_LABEL = 0x204;
	const XLS_Type_LABELSST = 0xfd;
	const XLS_Type_NUMBER = 0x203;
	const XLS_Type_EXTSHEET = 0x17;
	const XLS_Type_NAME = 0x18;
	const XLS_Type_ARRAY = 0x221;
	const XLS_Type_STRING = 0x207;
	const XLS_Type_FORMULA = 0x406;
	const XLS_Type_FORMULA2 = 0x6;
	const XLS_Type_FORMAT = 0x41e;
	const XLS_Type_XF = 0xe0;
	const XLS_Type_BOOLERR = 0x205;
	const XLS_Type_UNKNOWN = 0xffff;
	const XLS_Type_NINETEENFOUR = 0x22;
	const XLS_Type_MERGEDCELLS = 0xe5;
	const XLS_Type_CODEPAGE = 0x42;
	const XLS_Type_PROTECT				= 0x0012;
	const XLS_Type_PASSWORD				= 0x0013;
	const XLS_Type_COLINFO				= 0x007d;
	const XLS_Type_FONT					= 0x0031;
	const XLS_Type_EXTERNALBOOK			= 0x01ae;
	const XLS_Type_BLANK				= 0x0201;
	const XLS_Type_SHEETPR				= 0x0081;
	const XLS_Type_DEFAULTROWHEIGHT 	= 0x0225;
	const XLS_Type_DEFCOLWIDTH 			= 0x0055;
	
	const XLS_utcOffsetDays = 25569;
	const XLS_utcOffsetDays1904 = 24107;
	const XLS_SecInADay = 86400;
	
	const XLS_DEF_NUM_FORMAT = "%s";

	/**
	 * Read data only?
	 *
	 * @var boolean
	 */
	private $_readDataOnly = false;
	
	private $_boundsheets = array();
	
	// shared numberFormats
	// $_formatRecords[] will eventually be replaced by $_numberFormat[]
	private $_formatRecords = array();
	private $_numberFormat = array();
	
	/**
	 * Shared strings
	 *
	 * @var array
	 */
	private $_sst = array();
	
	/**
	 * Shared styles
	 *
	 * @var array
	 */
	private $_xf = array();
	
	/**
	 * Shared fonts
	 *
	 * @var array
	 */
	private $_font = array();
	
	// REF structures
	private $_ref = array();
	
	private $_sheets = array();
	// dvc: added list of names and their sheet associated indexes
	private $_namedcells = array();
	private $_data;
	private $_pos;
	private $_ole;
	private $_defaultEncoding;
	private $_codepage;
	private $_defaultFormat = self::XLS_DEF_NUM_FORMAT;
	private $_columnsFormat = array();
	private $_rowoffset = 1;
	private $_coloffset = 1;
	// dvc: added for external sheets references
	private $_extshref = array();

	private $_dateFormats = array (
		// dvc: fixed known date formats
		0xe => 'd/m/Y',
		0xf => 'd-M-y',
		0x10 => 'd-M',
		0x11 => 'M-y',
		0x12 => 'h:i A',
		0x13 => 'h:i:s A',
		0x14 => 'H:i',
		0x15 => 'H:i:s',
		0x16 => 'd/m/Y H:i',
		0x2d => 'i:s',
		0x2e => 'H:i:s',
		0x2f => 'i:s'
	);

	// dvc: separated percent formats
	private $_percentFormats = array(
		0x9 => '%1.0f%%',
		0xa => '%1.2f%%'
	);

	// dvc: removed exponentials to format as default strings.
	private $_numberFormats = array(
		0x1 => '%1.0f',
		0x2 => '%1.2f',
		0x3 => '%1.0f',
		0x4 => '%1.2f',
		0x5 => '%1.0f',
		0x6 => '$%1.0f',
		0x7 => '$%1.2f',
		0x8 => '$%1.2f',
		0x25 => '%1.0f',
		0x26 => '%1.0f',
		0x27 => '%1.2f',
		0x28 => '%1.2f',
		0x29 => '%1.0f',
		0x2a => '$%1.0f',
		0x2b => '%1.2f',
		0x2c => '$%1.2f'
	);

	/**
	 * Read data only?
	 *
	 * @return boolean
	 */
	public function getReadDataOnly() {
		return $this->_readDataOnly;
	}
	
	/**
	 * Set read data only
	 *
	 * @param boolean $pValue
	 */
	public function setReadDataOnly($pValue = false) {
		$this->_readDataOnly = $pValue;
	}
	
	/**
	 * Loads PHPExcel from file
	 *
	 * @param 	string 		$pFilename
	 * @throws 	Exception
	 */
	public function load($pFilename)
	{
		// Check if file exists
		if (!file_exists($pFilename)) {
			throw new Exception("Could not open " . $pFilename . " for reading! File does not exist.");
		}

		// Initialisations
		$excel = new PHPExcel;
		$excel->removeSheetByIndex(0);

		// Use ParseXL for the hard work.
		$this->_ole = new PHPExcel_Shared_OLERead();

		$this->_rowoffset = $this->_coloffset = 0;
		$this->_defaultEncoding = 'ISO-8859-1';
		$this->_encoderFunction = function_exists('mb_convert_encoding') ?
			'mb_convert_encoding' : 'iconv';

		// get excel data
		$res = $this->_ole->read($pFilename);

		// oops, something goes wrong (Darko Miljanovic)
		if($res === false) { // check error code
			if($this->_ole->error == 1) { // bad file
				throw new Exception('The filename ' . $pFilename . ' is not readable');
			} elseif($this->_ole->error == 2) {
				throw new Exception('The filename ' . $pFilename . ' is not recognised as an Excel file');
			}
			// check other error codes here (eg bad fileformat, etc...)
		}

		$this->_data = $this->_ole->getWorkBook();
		$this->_pos = 0;
		
		/**
		 * PARSE WORKBOOK
		 *
		 **/
		$pos = 0;
		$code = ord($this->_data[$pos]) | ord($this->_data[$pos + 1]) << 8;
		$length = ord($this->_data[$pos + 2]) | ord($this->_data[$pos + 3]) << 8;
		$version = ord($this->_data[$pos + 4]) | ord($this->_data[$pos + 5]) << 8;
		$substreamType = ord($this->_data[$pos + 6]) | ord($this->_data[$pos + 7]) << 8;


		if (($version != self::XLS_BIFF8) && ($version != self::XLS_BIFF7)) {
			return false;
		}
		if ($substreamType != self::XLS_WorkbookGlobals){
			return false;
		}
		$pos += $length + 4;
		$code = ord($this->_data[$pos]) | ord($this->_data[$pos + 1]) << 8;
		$length = ord($this->_data[$pos + 2]) | ord($this->_data[$pos + 3]) << 8;
		$recordData = substr($this->_data, $pos + 4, $length);
		
		while ($code != self::XLS_Type_EOF){
			switch ($code) {
				case self::XLS_Type_SST:
					/**
					 * SST - Shared String Table
					 *
					 * This record contains a list of all strings used anywhere
					 * in the workbook. Each string occurs only once. The
					 * workbook uses indexes into the list to reference the
					 * strings.
					 *
					 * --	"OpenOffice.org's Documentation of the Microsoft
					 * 		Excel File Format"
					 **/
					// offset: 0; size: 4; total number of strings
					// offset: 4; size: 4; number of unique strings
					$spos = $pos + 4;
					$limitpos = $spos + $length;
					$uniqueStrings = $this->_GetInt4d($this->_data, $spos + 4);
					$spos += 8;
					// loop through the Unicode strings (16-bit length)
					for ($i = 0; $i < $uniqueStrings; $i++) {
						if ($spos == $limitpos) {
							// then we have reached end of SST record data
							$opcode = ord($this->_data[$spos]) | ord($this->_data[$spos + 1])<<8;
							$conlength = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3])<<8;
							if ($opcode != self::XLS_Type_CONTINUE) {
								// broken file, something is wrong
								return -1;
							}
							$spos += 4;
							$limitpos = $spos + $conlength;
						}
						// Read in the number of characters in the Unicode string
						$numChars = ord($this->_data[$spos]) | (ord($this->_data[$spos + 1]) << 8);
						$spos += 2;
						// option flags
						$optionFlags = ord($this->_data[$spos]);
						$spos++;
						// bit: 0; mask: 0x01; 0 = compressed; 1 = uncompressed
						$asciiEncoding = (($optionFlags & 0x01) == 0) ;
						// bit: 2; mask: 0x02; 0 = ordinary; 1 = Asian phonetic
						$extendedString = ( ($optionFlags & 0x04) != 0); // Asian phonetic
						// bit: 3; mask: 0x03; 0 = ordinary; 1 = Rich-Text
						$richString = ( ($optionFlags & 0x08) != 0);
						if ($richString) { // Read in the crun
							// number of Rich-Text formatting runs
							$formattingRuns = ord($this->_data[$spos]) | (ord($this->_data[$spos + 1]) << 8);
							$spos += 2;
						}
						if ($extendedString) {
							// size of Asian phonetic setting
							$extendedRunLength = $this->_GetInt4d($this->_data, $spos);
							$spos += 4;
						}
						// read in the characters
						$len = ($asciiEncoding) ? $numChars : $numChars * 2;
						if ($spos + $len < $limitpos) {
							$retstr = substr($this->_data, $spos, $len);
							$spos += $len;
						} else {
							// found countinue record
							$retstr = substr($this->_data, $spos, $limitpos - $spos);
							$bytesRead = $limitpos - $spos;
							// remaining characters in Unicode string
							$charsLeft = $numChars - (($asciiEncoding) ? $bytesRead : ($bytesRead / 2));
							$spos = $limitpos;
							// keep reading the characters
							while ($charsLeft > 0) {
								// record data 
								$opcode = ord($this->_data[$spos]) | ord($this->_data[$spos + 1]) << 8;
								
								// length of continue record data
								$conlength = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
								if ($opcode != self::XLS_Type_CONTINUE) {
									// broken file, something is wrong
									return -1;
								}
								$spos += 4;
								$limitpos = $spos + $conlength;
								
								// option flags are repeated when Unicode string is split by a continue record
								// OpenOffice.org documentation 5.21
								$option = ord($this->_data[$spos]);
								$spos += 1;
								
								if ($asciiEncoding && ($option == 0)) {
									// 1st fragment compressed
									// this fragment compressed
									$len = min($charsLeft, $limitpos - $spos);
									$retstr .= substr($this->_data, $spos, $len);
									$charsLeft -= $len;
									$asciiEncoding = true;

								} elseif (!$asciiEncoding && ($option != 0)) {
									// 1st fragment uncompressed
									// this fragment uncompressed
									$len = min($charsLeft * 2, $limitpos - $spos);
									$retstr .= substr($this->_data, $spos, $len);
									$charsLeft -= $len/2;
									$asciiEncoding = false;

								} elseif (!$asciiEncoding && ($option == 0)) {
									// 1st fragment uncompressed
									// this fragment compressed
									$len = min($charsLeft, $limitpos - $spos);
									for ($j = 0; $j < $len; $j++) {
										$retstr .= $this->_data[$spos + $j].chr(0);
									}
									$charsLeft -= $len;
									$asciiEncoding = false;
								} else {
									// 1st fragment compressed
									// this fragment uncompressed
									$newstr = '';
									for ($j = 0; $j < strlen($retstr); $j++) {
										$newstr = $retstr[$j].chr(0);
									}
									$retstr = $newstr;
									$len = min($charsLeft * 2, $limitpos - $spos);
									$retstr .= substr($this->_data, $spos, $len);
									$charsLeft -= $len/2;
									$asciiEncoding = false;
								}
								$spos += $len;
							}
						}
						//$retstr = ($asciiEncoding) ?
						//	$retstr : $this->_encodeUTF16($retstr);
						// convert string according codepage and BIFF version

						if($version == self::XLS_BIFF8) {
							$retstr = $this->_encodeUTF16($retstr, $asciiEncoding);

						} else {
							// SST only occurs in BIFF8, so why this part? 
							$retstr = $this->_decodeCodepage($retstr);
						}

						$fmtRuns = array();
						if ($richString) {
							// list of formatting runs
							for ($j = 0; $j < $formattingRuns; $j++) {
								// first formatted character; zero-based
								$charPos = $this->_getInt2d($this->_data, $spos + $j * 4);
								// index to font record
								$fontIndex = $this->_getInt2d($this->_data, $spos + 2 + $j * 4);
								$fmtRuns[] = array(
									'charPos' => $charPos,
									'fontIndex' => $fontIndex,
								);
							}
							$spos += 4 * $formattingRuns;
						}
						if ($extendedString) {
							// For Asian phonetic settings, we skip the extended string data
							$spos += $extendedRunLength;
						}
						$this->_sst[] = array(
							'value' => $retstr,
							'fmtRuns' => $fmtRuns,
						);
					}
					break;

				case self::XLS_Type_FILEPASS:
					/**
					 * SHEETPROTECTION
					 *
					 * This record is part of the File Protection Block. It
					 * contains information about the read/write password of the
					 * file. All record contents following this record will be
					 * encrypted.
					 *
					 * --	"OpenOffice.org's Documentation of the Microsoft
					 * 		Excel File Format"
					 */
					return false;
					break;

				case self::XLS_Type_EXTERNALBOOK:
					break;
				
				case self::XLS_Type_EXTSHEET:
					// external sheet references provided for named cells
					if ($version == self::XLS_BIFF8) {
						$xpos = $pos + 4;
						$xcnt = ord($this->_data[$xpos]) | ord($this->_data[$xpos + 1]) << 8;
						for ($x = 0; $x < $xcnt; $x++) {
							$this->_extshref[$x] = ord($this->_data[$xpos + 4 + 6*$x]) |
								ord($this->_data[$xpos + 5 + 6*$x]) << 8;
						}
					}
					
					// this if statement is going to replace the above one later
					if ($version == self::XLS_BIFF8) {
						// offset: 0; size: 2; number of following ref structures
						$nm = $this->_GetInt2d($recordData, 0);
						for ($i = 0; $i < $nm; $i++) {
							$this->_ref[] = array(
								// offset: 2 + 6 * $i; index to EXTERNALBOOK record
								'externalBookIndex' => $this->_getInt2d($recordData, 2 + 6 * $i),
								// offset: 4 + 6 * $i; index to first sheet in EXTERNALBOOK record
								'firstSheetIndex' => $this->_getInt2d($recordData, 4 + 6 * $i),
								// offset: 6 + 6 * $i; index to last sheet in EXTERNALBOOK record
								'lastSheetIndex' => $this->_getInt2d($recordData, 6 + 6 * $i),
							);
						}
					}
					break;

				case self::XLS_Type_NAME:
					/**
					 * DEFINEDNAME
					 *
					 * This record is part of a Link Table. It contains the name
					 * and the token array of an internal defined name. Token
					 * arrays of defined names contain tokens with aberrant
					 * token classes.
					 *
					 * --	"OpenOffice.org's Documentation of the Microsoft
					 * 		Excel File Format"
					 */
					// retrieves named cells
					$npos = $pos + 4;
					$opts = ord($this->_data[$npos]) | ord($this->_data[$npos + 1]) << 8;
					$nlen = ord($this->_data[$npos + 3]);
					$flen = ord($this->_data[$npos + 4]) | ord($this->_data[$npos + 5]) << 8;
					$fpos = $npos + 14 + 1 + $nlen;
					$nstr = substr($this->_data, $npos + 15, $nlen);
					$ftoken = ord($this->_data[$fpos]);
					if ($ftoken == 58 && $opts == 0 && $flen == 7) {
						$xref = ord($this->_data[$fpos + 1]) | ord($this->_data[$fpos + 2]) << 8;
						$frow = ord($this->_data[$fpos + 3]) | ord($this->_data[$fpos + 4]) << 8;
						$fcol = ord($this->_data[$fpos + 5]);
						if (array_key_exists($xref,$this->_extshref)) {
							$fsheet = $this->_extshref[$xref];
						} else {
							$fsheet = '';
						}
						$this->_namedcells[$nstr] = array(
							'sheet' => $fsheet,
							'row' => $frow,
							'column' => $fcol
						);
					}
					break;

				case self::XLS_Type_FORMAT:
					/**
					 * FORMAT
					 *
					 * This record contains information about a number format.
					 * All FORMAT records occur together in a sequential list.
					 *
					 * In BIFF2-BIFF4 other records referencing a FORMAT record
					 * contain a zero-based index into this list. From BIFF5 on
					 * the FORMAT record contains the index itself that will be
					 * used by other records.
					 *
					 * --	"OpenOffice.org's Documentation of the Microsoft
					 * 		Excel File Format"
					 */
					//$indexCode = ord($this->_data[$pos + 4]) | ord($this->_data[$pos + 5]) << 8;
					$indexCode = $this->_GetInt2d($recordData, 0);
					
					/*
					if ($version == self::XLS_BIFF8) {
					*/
						$formatString = $this->_readUnicodeStringLong(substr($recordData, 2));
					/*
					} else {
						$numchars = ord($this->_data[$pos + 6]);
						$formatString = substr($this->_data, $pos + 7, $numchars*2);
					}
					*/
					$this->_formatRecords[$indexCode] = $formatString;
					
					// now also stored in array _format[]
					// _formatRecords[] will be removed from code later
					$this->_numberFormat[$indexCode] = $formatString;

					break;

				case self::XLS_Type_FONT:
					/**
					 * FONT
					 */
					$this->_font[] = $this->_readFont($recordData);
					
					break;
					
				case self::XLS_Type_XF:
					/**
					 * XF - Extended Format
					 *
					 * This record contains formatting information for cells,
					 * rows, columns or styles.
					 *
					 * --	"OpenOffice.org's Documentation of the Microsoft
					 * 		Excel File Format"
					 */
					$indexCode = ord($this->_data[$pos + 6]) | ord($this->_data[$pos + 7]) << 8;
					if (array_key_exists($indexCode, $this->_dateFormats)) {
						$this->_formatRecords['xfrecords'][] = array(
							'type' => 'date',
							'format' => $this->_dateFormats[$indexCode],
							'code' => $indexCode
						);
					} elseif (array_key_exists($indexCode, $this->_percentFormats)) {
						$this->_formatRecords['xfrecords'][] = array(
							'type' => 'percent',
							'format' => $this->_percentFormats[$indexCode],
							'code' => $indexCode
						);
					} elseif (array_key_exists($indexCode, $this->_numberFormats)) {
						$this->_formatRecords['xfrecords'][] = array(
							'type' => 'number',
							'format' => $this->_numberFormats[$indexCode],
							'code' => $indexCode
						);
					} else {
						if ($indexCode > 0 && isset($this->_formatRecords[$indexCode])) {
							// custom formats...
							$formatstr = $this->_formatRecords[$indexCode];
							if ($formatstr) {
								// dvc: reg exp changed to custom date/time format chars
								if (preg_match("/^[hmsdy]/i", $formatstr)) {
									// custom datetime format
									// dvc: convert Excel formats to PHP date formats
									// first remove escapes related to non-format characters
									$formatstr = str_replace('\\', '', $formatstr);
									// 4-digit year
									$formatstr = str_replace('yyyy', 'Y', $formatstr);
									// 2-digit year
									$formatstr = str_replace('yy', 'y', $formatstr);
									// first letter of month - no php equivalent
									$formatstr = str_replace('mmmmm', 'M', $formatstr);
									// full month name
									$formatstr = str_replace('mmmm', 'F', $formatstr);
									// short month name
									$formatstr = str_replace('mmm', 'M', $formatstr);
									// mm is minutes if time or month w/leading zero
									$formatstr = str_replace(':mm', ':i', $formatstr);
									// tmp place holder
									$formatstr = str_replace('mm', 'x', $formatstr);
									// month no leading zero
									$formatstr = str_replace('m', 'n', $formatstr);
									// month leading zero
									$formatstr = str_replace('x', 'm', $formatstr);
									// 12-hour suffix
									$formatstr = str_replace('AM/PM', 'A', $formatstr);
									// tmp place holder
									$formatstr = str_replace('dd', 'x', $formatstr);
									// days no leading zero
									$formatstr = str_replace('d', 'j', $formatstr);
									// days leading zero
									$formatstr = str_replace('x', 'd', $formatstr);
									// seconds
									$formatstr = str_replace('ss', 's', $formatstr);
									// fractional seconds - no php equivalent
									$formatstr = str_replace('.S', '', $formatstr);
									if (! strpos($formatstr,'A')) { // 24-hour format
										$formatstr = str_replace('h', 'H', $formatstr);
										}
									// user defined flag symbol????
									$formatstr = str_replace(';@', '', $formatstr);
									$this->_formatRecords['xfrecords'][] = array(
										'type' => 'date',
										'format' => $formatstr,
										'code' => $indexCode
									);
								}
								// dvc: new code for custom percent formats
								else if (preg_match('/%$/', $formatstr)) { // % number format
									if (preg_match('/\.[#0]+/i',$formatstr,$m)) {
										$s = substr($m[0],0,1).(strlen($m[0])-1);
										$formatstr = str_replace($m[0],$s,$formatstr);
									}
									if (preg_match('/^[#0]+/',$formatstr,$m)) {
										$formatstr = str_replace($m[0],strlen($m[0]),$formatstr);
									}
									$formatstr = '%' . str_replace('%',"f%%",$formatstr);
									$this->_formatRecords['xfrecords'][] = array(
										'type' => 'percent',
										'format' => $formatstr,
										'code' => $indexCode
									);
								}
								// dvc: code for other unknown formats
								else {
									// dvc: changed to add format to unknown for debug
									$this->_formatRecords['xfrecords'][] = array(
										'type' => 'other',
										'format' => $this->_defaultFormat,
										'code' => $indexCode
									);
								}
							}

						} else {
							// dvc: changed to add format to unknown for debug
							if (isset($this->_formatRecords[$indexCode])) {
								$formatstr = $this->_formatRecords[$indexCode];
								$type = 'undefined';
							} else {
								$formatstr = $this->_defaultFormat;
								$type = 'default';
							}
							$this->_formatRecords['xfrecords'][] = array(
								'type' => $type,
								'format' => $formatstr,
								'code' => $indexCode
							);
						}
					}
					
					// store styles in xf array
					$this->_xf[] = $this->_readBIFF8Style($recordData);
					
					break;

				case self::XLS_Type_NINETEENFOUR:
					/**
					 * DATEMODE
					 *
					 * This record specifies the base date for displaying date
					 * values. All dates are stored as count of days past this
					 * base date. In BIFF2-BIFF4 this record is part of the
					 * Calculation Settings Block. In BIFF5-BIFF8 it is
					 * stored in the Workbook Globals Substream.
					 *
					 * --	"OpenOffice.org's Documentation of the Microsoft
					 * 		Excel File Format"
					 */
					$this->_nineteenFour = (ord($this->_data[$pos + 4]) == 1);
					
					/*
					if (ord($this->_data[$pos + 4]) == 1) {
						PHPExcel_Shared_Date::setExcelCalendar(PHPExcel_Shared_Date::CALENDAR_MAC_1904);
					} else {
						PHPExcel_Shared_Date::setExcelCalendar(PHPExcel_Shared_Date::CALENDAR_WINDOWS_1900);
					}
					*/
					break;

				case self::XLS_Type_BOUNDSHEET:
					/**
					 * SHEET
					 *
					 * This record is  located in the  Workbook Globals
					 * Substream  and represents a sheet inside the workbook.
					 * One SHEET record is written for each sheet. It stores the
					 * sheet name and a stream offset to the BOF record of the
					 * respective Sheet Substream within the Workbook Stream.
					 *
					 * --	"OpenOffice.org's Documentation of the Microsoft
					 * 		Excel File Format"
					 */
					$rec_offset = $this->_GetInt4d($this->_data, $pos + 4);
					$rec_typeFlag = ord($this->_data[$pos + 8]);
					$rec_visibilityFlag = ord($this->_data[$pos + 9]);
					$rec_length = ord($this->_data[$pos + 10]);



					if ($version == self::XLS_BIFF8) {
						$compressedUTF16 = ((ord($this->_data[$pos + 11]) & 0x01) == 0);
						$rec_length = ($compressedUTF16) ? $rec_length : $rec_length*2;
						$rec_name = $this->_encodeUTF16(substr($this->_data, $pos + 12, $rec_length), $compressedUTF16);
					} elseif ($version == self::XLS_BIFF7) {
						$rec_name		= substr($this->_data, $pos + 11, $rec_length);
					}
					$this->_boundsheets[] = array(
						'name' => $rec_name,
						'offset' => $rec_offset
					);
					break;

				case self::XLS_Type_CODEPAGE:
					/**
					 * CODEPAGE
					 *
					 * This record stores the text encoding used to write byte
					 * strings, stored as MS Windows code page identifier.
					 *
					 * --	"OpenOffice.org's Documentation of the Microsoft
					 * 		Excel File Format"
					 */
					$codepage = $this->_GetInt2d($this->_data, $pos + 4);
					switch($codepage) {
						case 367: // ASCII
							$this->_codepage ="ASCII";
							break;
						case 437: //OEM US
							$this->_codepage ="CP437";
							break;
						case 720: //OEM Arabic
							// currently not supported by libiconv
							$this->_codepage = "";
							break;
						case 737: //OEM Greek
							$this->_codepage ="CP737";
							break;
						case 775: //OEM Baltic
							$this->_codepage ="CP775";
							break;
						case 850: //OEM Latin I
							$this->_codepage ="CP850";
							break;
						case 852: //OEM Latin II (Central European)
							$this->_codepage ="CP852";
							break;
						case 855: //OEM Cyrillic
							$this->_codepage ="CP855";
							break;
						case 857: //OEM Turkish
							$this->_codepage ="CP857";
							break;
						case 858: //OEM Multilingual Latin I with Euro
							$this->_codepage ="CP858";
							break;
						case 860: //OEM Portugese
							$this->_codepage ="CP860";
							break;
						case 861: //OEM Icelandic
							$this->_codepage ="CP861";
							break;
						case 862: //OEM Hebrew
							$this->_codepage ="CP862";
							break;
						case 863: //OEM Canadian (French)
							$this->_codepage ="CP863";
							break;
						case 864: //OEM Arabic
							$this->_codepage ="CP864";
							break;
						case 865: //OEM Nordic
							$this->_codepage ="CP865";
							break;
						case 866: //OEM Cyrillic (Russian)
							$this->_codepage ="CP866";
							break;
						case 869: //OEM Greek (Modern)
							$this->_codepage ="CP869";
							break;
						case 874: //ANSI Thai
							$this->_codepage ="CP874";
							break;
						case 932: //ANSI Japanese Shift-JIS
							$this->_codepage ="CP932";
							break;
						case 936: //ANSI Chinese Simplified GBK
							$this->_codepage ="CP936";
							break;
						case 949: //ANSI Korean (Wansung)
							$this->_codepage ="CP949";
							break;
						case 950: //ANSI Chinese Traditional BIG5
							$this->_codepage ="CP950";
							break;
						case 1200: //UTF-16 (BIFF8)
							$this->_codepage ="UTF-16LE";
							break;
						case 1250:// ANSI Latin II (Central European)
							$this->_codepage ="CP1250";
							break;
						case 1251: //ANSI Cyrillic
							$this->_codepage ="CP1251";
							break;
						case 1252: //ANSI Latin I (BIFF4-BIFF7)
							$this->_codepage ="CP1252";
							break;
						case 1253: //ANSI Greek
							$this->_codepage ="CP1253";
							break;
						case 1254: //ANSI Turkish
							$this->_codepage ="CP1254";
							break;
						case 1255: //ANSI Hebrew
							$this->_codepage ="CP1255";
							break;
						case 1256: //ANSI Arabic
							$this->_codepage ="CP1256";
							break;
						case 1257: //ANSI Baltic
							$this->_codepage ="CP1257";
							break;
						case 1258: //ANSI Vietnamese
							$this->_codepage ="CP1258";
							break;
						case 1361: //ANSI Korean (Johab)
							$this->_codepage ="CP1361";
							break;
						case 10000: //Apple Roman
							// currently not supported by libiconv
							$this->_codepage = "";
							break;
						case 32768: //Apple Roman
							// currently not supported by libiconv
							$this->_codepage = "";
							break;
						case 32769: //ANSI Latin I (BIFF2-BIFF3)
							// currently not supported by libiconv
							$this->_codepage = "";
							break;
					}
					break;
					
			}
			$pos += $length + 4;
			$code = ord($this->_data[$pos]) | ord($this->_data[$pos + 1]) << 8;
			$length = ord($this->_data[$pos + 2]) | ord($this->_data[$pos + 3]) << 8;
			$recordData = substr($this->_data, $pos + 4, $length);
		}
		/**
		 *
		 * PARSE THE INDIVIDUAL SHEETS
		 *
		 **/
		foreach ($this->_boundsheets as $key => $val){
			// add sheet to PHPExcel object
			$sheet = $excel->createSheet();
			$sheet->setTitle((string) $val['name']);
			
			$this->_sn = $key;
			$spos = $val['offset'];
			$cont = true;
			// read BOF
			$code = ord($this->_data[$spos]) | ord($this->_data[$spos + 1]) << 8;
			$length = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
			$version = ord($this->_data[$spos + 4]) | ord($this->_data[$spos + 5]) << 8;
			$substreamType = ord($this->_data[$spos + 6]) | ord($this->_data[$spos + 7]) << 8;

			if (($version != self::XLS_BIFF8) && ($version != self::XLS_BIFF7)) {
				return -1;
			}
			if ($substreamType != self::XLS_Worksheet) {
				return -2;
			}

			$spos += $length + 4;
			while($cont) {
				$lowcode = ord($this->_data[$spos]);
				if ($lowcode == self::XLS_Type_EOF) {
					break;
				}

				$code = $lowcode | ord($this->_data[$spos + 1]) << 8;
				$length = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
				$recordData = substr($this->_data, $spos + 4, $length);
				
				$spos += 4;
				$this->_sheets[$this->_sn]['maxrow'] = $this->_rowoffset - 1;
				$this->_sheets[$this->_sn]['maxcol'] = $this->_coloffset - 1;
				unset($this->_rectype);
				unset($this->_formula);
				unset($this->_formula_result);
				$this->_multiplier = 1; // need for format with %

				switch ($code) {
					case self::XLS_Type_DIMENSION:
						/**
						 * DIMENSION
						 *
						 * This record contains the range address of the used area
						 * in the current sheet.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						if (!isset($this->_numRows)) {
							if (($length == 10) ||	($version == self::XLS_BIFF7)){
								$this->_sheets[$this->_sn]['numRows'] = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
								$this->_sheets[$this->_sn]['numCols'] = ord($this->_data[$spos + 6]) | ord($this->_data[$spos + 7]) << 8;
							} else {
								$this->_sheets[$this->_sn]['numRows'] = ord($this->_data[$spos + 4]) | ord($this->_data[$spos + 5]) << 8;
								$this->_sheets[$this->_sn]['numCols'] = ord($this->_data[$spos + 10]) | ord($this->_data[$spos + 11]) << 8;
							}
						}
						break;

					case self::XLS_Type_MERGEDCELLS:
						/**
						 * MERGEDCELLS
						 *
						 * This record contains the addresses of merged cell ranges
						 * in the current sheet.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						if ($version == self::XLS_BIFF8 && !$this->_readDataOnly) {
							$cellRanges = $this->_GetInt2d($this->_data, $spos);
							
							for ($i = 0; $i < $cellRanges; $i++) {
								$fr = $this->_GetInt2d($this->_data, $spos + 8 * $i + 2); // first row
								$lr = $this->_GetInt2d($this->_data, $spos + 8 * $i + 4); // last row
								$fc = $this->_GetInt2d($this->_data, $spos + 8 * $i + 6); // first column
								$lc = $this->_GetInt2d($this->_data, $spos + 8 * $i + 8); // last column
								
								// this part no longer needed, instead apply cell merge on PHPExcel worksheet object
								/*
								if ($lr - $fr > 0) {
									$this->_sheets[$this->_sn]['cellsInfo'][$fr + 1][$fc + 1]['rowspan'] = $lr - $fr + 1;
								}
								if ($lc - $fc > 0) {
									$this->_sheets[$this->_sn]['cellsInfo'][$fr + 1][$fc + 1]['colspan'] = $lc - $fc + 1;
								}
								*/
								$sheet->mergeCellsByColumnAndRow($fc, $fr + 1, $lc, $lr + 1);
							}
						}
						break;

					case self::XLS_Type_RK:
					case self::XLS_Type_RK2:
						/**
						 * RK
						 *
						 * This record represents a cell that contains an RK value
						 * (encoded integer or floating-point value). If a
						 * floating-point value cannot be encoded to an RK value,
						 * a NUMBER record will be written. This record replaces the
						 * record INTEGER written in BIFF2.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						$row = ord($this->_data[$spos]) | ord($this->_data[$spos + 1]) << 8;
						$column = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
						$rknum = $this->_GetInt4d($this->_data, $spos + 6);
						$numValue = $this->_GetIEEE754($rknum);

						/*
						if ($this->_isDate($spos)) {
							list($string, $raw) = $this->_createDate($numValue);
						} else {
							$raw = $numValue;
							if (isset($this->_columnsFormat[$column + 1])){
								$this->_curformat = $this->_columnsFormat[$column + 1];
							}
							$string = sprintf($this->_curformat,$numValue*$this->_multiplier);
						}
						*/
						
						// offset 4; size: 2; index to XF record
						$xfindex = $this->_getInt2d($recordData, 4);
						// add BIFF8 style information
						if ($version == self::XLS_BIFF8 && !$this->_readDataOnly) {
							$sheet->getStyleByColumnAndRow($column, $row + 1)->applyFromArray($this->_xf[$xfindex]);
							
							if (PHPExcel_Shared_Date::isDateTimeFormatCode($this->_xf[$xfindex]['numberformat']['code'])) {
								$numValue = (int) PHPExcel_Shared_Date::ExcelToPHP($numValue);
							}
							
						}
						
						//$this->_addcell($row, $column, $string, $raw);
						//$sheet->setCellValueByColumnAndRow($column, $row + 1, $string);
						
						$sheet->setCellValueByColumnAndRow($column, $row + 1, $numValue);
						
						break;

					case self::XLS_Type_LABELSST:
						/**
						 * LABELSST
						 *
						 * This record represents a cell that contains a string. It
						 * replaces the LABEL record and RSTRING record used in
						 * BIFF2-BIFF5.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						$row = ord($this->_data[$spos]) | ord($this->_data[$spos + 1]) << 8;
						$column = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
						$xfindex = ord($this->_data[$spos + 4]) | ord($this->_data[$spos + 5]) << 8;
						$index = $this->_GetInt4d($this->_data, $spos + 6);
						//$this->_addcell($row, $column, $this->_sst[$index]);
						
						if ($fmtRuns = $this->_sst[$index]['fmtRuns']) {
							// then we have rich text
							$richText = new PHPExcel_RichText($sheet->getCellByColumnAndRow($column, $row + 1));
							$charPos = 0;
							for ($i = 0; $i <= count($this->_sst[$index]['fmtRuns']); $i++) {
								if (isset($fmtRuns[$i])) {
									$text = mb_substr($this->_sst[$index]['value'], $charPos, $fmtRuns[$i]['charPos'] - $charPos, 'UTF-8');
									$charPos = $fmtRuns[$i]['charPos'];
								} else {
									$text = mb_substr($this->_sst[$index]['value'], $charPos, mb_strlen($this->_sst[$index]['value']), 'UTF-8');
								}
								
								if (mb_strlen($text) > 0) {
									$textRun = $richText->createTextRun($text);
									if (isset($fmtRuns[$i - 1])) {
										if ($fmtRuns[$i - 1]['fontIndex'] < 4) {
											$fontIndex = $fmtRuns[$i - 1]['fontIndex'];
										} else {
											// this has to do with that index 4 is omitted in all BIFF versions for some strange reason
											// check the OpenOffice documentation of the FONT record
											$fontIndex = $fmtRuns[$i - 1]['fontIndex'] - 1;
										}
										$textRun->getFont()->applyFromArray($this->_font[$fontIndex]);
									}
								}
							}
						} else {
							$sheet->setCellValueByColumnAndRow($column, $row + 1, $this->_sst[$index]['value']);
						}
						
						// add BIFF8 style information
						if ($version == self::XLS_BIFF8 && !$this->_readDataOnly) {
							$sheet->getStyleByColumnAndRow($column, $row + 1)->applyFromArray($this->_xf[$xfindex]);
						}
						break;

					case self::XLS_Type_MULRK:
						/**
						 * MULRK - Multiple RK
						 *
						 * This record represents a cell range containing RK value
						 * cells. All cells are located in the same row.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						$row = ord($this->_data[$spos]) | ord($this->_data[$spos + 1]) << 8;
						$colFirst = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
						$colLast = ord($this->_data[$spos + $length - 2]) | ord($this->_data[$spos + $length - 1]) << 8;
						$columns = $colLast - $colFirst + 1;
						$tmppos = $spos + 4;

						for ($i = 0; $i < $columns; $i++) {
							// offset: 0; size: 2; index to XF record
							$xfindex = $this->_getInt2d($recordData, 4 + 6 * $i);
							
							// offset: 2; size: 4; RK value
							$numValue = $this->_GetIEEE754($this->_GetInt4d($this->_data, $tmppos + 2));
							/*
							if ($this->_isDate($tmppos-4)) {
								list($string, $raw) = $this->_createDate($numValue);
							} else {
								$raw = $numValue;
								if (isset($this->_columnsFormat[$colFirst + $i + 1])){
									$this->_curformat = $this->_columnsFormat[$colFirst+ $i + 1];
								}
								$string = sprintf($this->_curformat, $numValue *
									$this->_multiplier);
							}
							*/
							//$this->_addcell($row, $colFirst + $i, $string, $raw);
							if ($version == self::XLS_BIFF8 && !$this->_readDataOnly) {
								$sheet->getStyleByColumnAndRow($colFirst + $i, $row + 1)->applyFromArray($this->_xf[$xfindex]);
								if (PHPExcel_Shared_Date::isDateTimeFormatCode($this->_xf[$xfindex]['numberformat']['code'])) {
									$numValue = (int) PHPExcel_Shared_Date::ExcelToPHP($numValue);
								}
							}
							//$sheet->setCellValueByColumnAndRow($colFirst + $i, $row + 1, $string);
							$sheet->setCellValueByColumnAndRow($colFirst + $i, $row + 1, $numValue);
							$tmppos += 6;
						}
						break;

					case self::XLS_Type_NUMBER:
						/**
						 * NUMBER
						 *
						 * This record represents a cell that contains a
						 * floating-point value.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						$row = ord($this->_data[$spos]) | ord($this->_data[$spos + 1]) << 8;
						$column = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
						// offset 4; size: 2; index to XF record
						$xfindex = $this->_GetInt2d($recordData, 4);

						$numValue = $this->_createNumber($spos);
						/*
						if ($this->_isDate($spos)) {
							$numValue = $this->_createNumber($spos);
							list($string, $raw) = $this->_createDate($numValue);
						} else {
							if (isset($this->_columnsFormat[$column + 1])) {
								$this->_curformat = $this->_columnsFormat[$column + 1];
							}
							$raw = $this->_createNumber($spos);
							$string = sprintf($this->_curformat, $raw * $this->_multiplier);
						}
						*/
						// add BIFF8 style information
						if ($version == self::XLS_BIFF8 && !$this->_readDataOnly) {
							$sheet->getStyleByColumnAndRow($column, $row + 1)->applyFromArray($this->_xf[$xfindex]);
							if (PHPExcel_Shared_Date::isDateTimeFormatCode($this->_xf[$xfindex]['numberformat']['code'])) {
								$numValue = (int) PHPExcel_Shared_Date::ExcelToPHP($numValue);
							}
						}
						//$this->_addcell($row, $column, $string, $raw);
						//$sheet->setCellValueByColumnAndRow($column, $row + 1, $string);
						$sheet->setCellValueByColumnAndRow($column, $row + 1, $numValue);
						
						break;

					case self::XLS_Type_FORMULA:
					case self::XLS_Type_FORMULA2:
						/**
						 * FORMULA
						 *
						 * This record contains the token array and the result of a
						 * formula cell.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						// offset: 0; size: 2; row index
						$row = ord($this->_data[$spos]) | ord($this->_data[$spos + 1]) << 8;
						// offset: 2; size: 2; col index
						$column = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
						// offset: 4; size: 2; XF index
						$xfindex = ord($this->_data[$spos + 4]) | ord($this->_data[$spos + 5]) << 8;

						// offset: 6; size: 8; result of the formula
						if ((ord($this->_data[$spos + 6]) == 0) &&
						(ord($this->_data[$spos + 12]) == 255) &&
						(ord($this->_data[$spos + 13]) == 255)) {
							//String formula. Result follows in appended STRING record
							$this->_formula_result = 'string';
							$soff = $spos + $length;
							$scode = ord($this->_data[$soff]) | ord($this->_data[$soff + 1])<<8;
							$sopt = ord($this->_data[$soff + 6]);
							// only reads byte strings...
							if ($scode == self::XLS_Type_STRING && $sopt == '0') {
								$slen = ord($this->_data[$soff + 4]) | ord($this->_data[$soff + 5]) << 8;
								$string = substr($this->_data, $soff + 7, ord($this->_data[$soff + 4]) | ord($this->_data[$soff + 5]) << 8);
							} else {
								$string = 'NOT FOUND';
							}
							$raw = $string;

						} elseif ((ord($this->_data[$spos + 6]) == 1) &&
						(ord($this->_data[$spos + 12]) == 255) &&
						(ord($this->_data[$spos + 13]) == 255)) {
							//Boolean formula. Result is in +2; 0=false,1=true
							$this->_formula_result = 'boolean';
							$raw = ord($this->_data[$spos + 8]);
							if ($raw) {
								$string = "TRUE";
							} else {
								$string = "FALSE";
							}

						} elseif ((ord($this->_data[$spos + 6]) == 2) &&
						(ord($this->_data[$spos + 12]) == 255) &&
						(ord($this->_data[$spos + 13]) == 255)) {
							//Error formula. Error code is in +2
							$this->_formula_result = 'error';
							$raw = ord($this->_data[$spos + 8]);
							$string = 'ERROR:'.$raw;

						} elseif ((ord($this->_data[$spos + 6]) == 3) &&
						(ord($this->_data[$spos + 12]) == 255) &&
						(ord($this->_data[$spos + 13]) == 255)) {
							//Formula result is a null string
							$this->_formula_result = 'null';
							$raw = '';
							$string = '';

						} else {
							// forumla result is a number, first 14 bytes like _NUMBER record
							$string = $this->_createNumber($spos);
							/*
							$this->_formula_result = 'number';
							if ($this->_isDate($spos)) {
								$numValue = $this->_createNumber($spos);
								list($string, $raw) = $this->_createDate($numValue);
							} else {
								if (isset($this->_columnsFormat[$column + 1])){
									$this->_curformat = $this->_columnsFormat[$column + 1];
								}
								$raw = $this->_createNumber($spos);
								$string = sprintf($this->_curformat, $raw * $this->_multiplier);
							}
							*/
						}
						// save the raw formula tokens for end user interpretation
						// Excel stores as a token record
						$this->_rectype = 'formula';
						// read formula record tokens ...
						$tokenlength = ord($this->_data[$spos + 20]) | ord($this->_data[$spos + 21]) << 8;
						for ($i = 0; $i < $tokenlength; $i++) {
							$this->_formula[$i] = ord($this->_data[$spos + 22 + $i]);
						}
						// add BIFF8 style information
						if ($version == self::XLS_BIFF8 && !$this->_readDataOnly) {
							$sheet->getStyleByColumnAndRow($column, $row + 1)->applyFromArray($this->_xf[$xfindex]);
							if (PHPExcel_Shared_Date::isDateTimeFormatCode($this->_xf[$xfindex]['numberformat']['code'])) {
								$string = (int) PHPExcel_Shared_Date::ExcelToPHP($string);
							}
						}
						//$this->_addcell($row, $column, $string, $raw);
						$sheet->setCellValueByColumnAndRow($column, $row + 1, $string);
						
						// offset: 14: size: 2; option flags, recalculate always, recalculate on open etc.
						// offset: 16: size: 4; not used
						// offset: 20: size: variable; formula structure
						// WORK IN PROGRESS: TRUE FORMULA SUPPORT
						//   resolve BIFF8 formula tokens into human readable formula
						//   so it can be added as formula
						// $formulaStructure = substr($recordData, 20);
						// $formulaString = $this->_getFormulaStringFromStructure($formulaStructure); // get human language
						break;

					case self::XLS_Type_BOOLERR:
						/**
						 * BOOLERR
						 *
						 * This record represents a Boolean value or error value
						 * cell.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						// offset: 0; size: 2; row index
						$row = ord($this->_data[$spos]) | ord($this->_data[$spos + 1]) << 8;
						// offset: 2; size: 2; column index
						$column = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
						// offset: 4; size: 2; index to XF record
						$xfindex = $this->_GetInt2d($recordData, 4);
						// offset: 6; size: 1; the boolean value or error value
						$value = ord($recordData[6]);
						// offset: 7; size: 1; 0=boolean; 1=error
						$isError = ord($recordData[7]);
						if (!$isError) {
							$sheet->getCellByColumnAndRow($column, $row + 1)->setValueExplicit((bool) $value, PHPExcel_Cell_DataType::TYPE_BOOL);
						}
						// add BIFF8 style information
						if ($version == self::XLS_BIFF8 && !$this->_readDataOnly) {
							$sheet->getStyleByColumnAndRow($column, $row + 1)->applyFromArray($this->_xf[$xfindex]);
						}
						break;

					case self::XLS_Type_ROW:
						/**
						 * ROW
						 *
						 * This record contains the properties of a single row in a
						 * sheet. Rows and cells in a sheet are divided into blocks
						 * of 32 rows.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						if (!$this->_readDataOnly) {
							// offset: 0; size: 2; index of this row
							$r = $this->_GetInt2d($recordData, 0);
							// offset: 2; size: 2; index to column of the first cell which is described by a cell record
							// offset: 4; size: 2; index to column of the last cell which is described by a cell record, increased by 1
							// offset: 6; size: 2;
								// bit: 14-0; mask: 0x7FF; height of the row, in twips = 1/20 of a point
								$height = (0x7FF & $this->_GetInt2d($recordData, 6)) >> 0;
								// bit: 15: mask: 0x8000; 0 = row has custom height; 1= row has default height
								$useDefaultHeight = (0x8000 & $this->_GetInt2d($recordData, 6)) >> 15;
								
								if (!$useDefaultHeight) {
									$sheet->getRowDimension($r + 1)->setRowHeight($height / 20);
								}
							// offset: 8; size: 2; not used
							// offset: 10; size: 2; not used in BIFF5-BIFF8
							// offset: 12; size: 4; option flags and default row formatting
								// bit: 2-0: mask: 0x00000007; outline level of the row
								$level = (0x00000007 & $this->_GetInt4d($recordData, 12)) >> 0;
								$sheet->getRowDimension($r + 1)->setOutlineLevel($level);
								// bit: 4; mask: 0x00000010; 1 = outline group start or ends here... and is collapsed
								$isCollapsed = (0x00000010 & $this->_GetInt4d($recordData, 12)) >> 4;
								$sheet->getRowDimension($r + 1)->setCollapsed($isCollapsed);
								// bit: 5; mask: 0x00000020; 1 = row is hidden
								$isHidden = (0x00000020 & $this->_GetInt4d($recordData, 12)) >> 5;
								$sheet->getRowDimension($r + 1)->setVisible(!$isHidden);
						}
						break;
						
					case self::XLS_Type_DBCELL:
						/**
						 * DBCELL
						 *
						 * This record is written once in a Row Block. It contains
						 * relative offsets to calculate the stream position of the
						 * first cell record for each row. The offset list in this
						 * record contains as many offsets as ROW records are
						 * present in the Row Block.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						break;
						
					case self::XLS_Type_MULBLANK:
						/**
						 * MULBLANK - Multiple BLANK
						 *
						 * This record represents a cell range of empty cells. All
						 * cells are located in the same row
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						// offset: 0; size: 2; index to row
						$row = $this->_GetInt2d($recordData, 0);
						// offset: 2; size: 2; index to first column
						$fc = $this->_GetInt2d($recordData, 2);
						// offset: 4; size: 2 x nc; list of indexes to XF records
						// add BIFF8 style information
						if ($version == self::XLS_BIFF8 && !$this->_readDataOnly) {
							for ($i = 0; $i < $length / 2 - 4; $i++) {
								$xfindex = $this->_GetInt2d($recordData, 4 + 2 * $i);
								$sheet->getStyleByColumnAndRow($fc + $i, $row + 1)->applyFromArray($this->_xf[$xfindex]);
							}
						}
						// offset: 6; size 2; index to last column (not needed)
						
						break;

					case self::XLS_Type_LABEL:
						/**
						 * LABEL
						 *
						 * This record represents a cell that contains a string. In
						 * BIFF8 it is usually replaced by the LABELSST record.
						 * Excel still uses this record, if it copies unformatted
						 * text cells to the clipboard.
						 *
						 * --	"OpenOffice.org's Documentation of the Microsoft
						 * 		Excel File Format"
						 */
						$row = ord($this->_data[$spos]) | ord($this->_data[$spos + 1]) << 8;
						$column = ord($this->_data[$spos + 2]) | ord($this->_data[$spos + 3]) << 8;
						/*
						$this->_addcell($row, $column, substr($this->_data, $spos + 8,
							ord($this->_data[$spos + 6]) | ord($this->_data[$spos + 7]) << 8));
						*/
						$sheet->setCellValueByColumnAndRow($column, $row + 1, substr($this->_data, $spos + 8,
							ord($this->_data[$spos + 6]) | ord($this->_data[$spos + 7]) << 8));
						break;
						
					case self::XLS_Type_PROTECT:
						/**
						 * PROTECT - Sheet protection (BIFF2 through BIFF8)
						 *   if this record is omitted, then it also means no sheet protection
						 */
						if (!$this->_readDataOnly) {
							// offset: 0; size: 2;
							// bit 0, mask 0x01; sheet protection
							$isSheetProtected = (0x01 & $this->_GetInt2d($recordData, 0)) >> 0;
							switch ($isSheetProtected) {
								case 0: break;
								case 1: $sheet->getProtection()->setSheet(true); break;
							}
						}
						break;
						
					case self::XLS_Type_PASSWORD:
						/**
						 * PASSWORD - Sheet protection (hashed) password (BIFF2 through BIFF8)
						 */
						if (!$this->_readDataOnly) {
							// offset: 0; size: 2; 16-bit hash value of password
							$password = strtoupper(dechex($this->_GetInt2d($recordData, 0))); // the hashed password
							$sheet->getProtection()->setPassword($password, true);
						}
						break;
						
					case self::XLS_Type_COLINFO:
						/**
						 * COLINFO - Column information
						 */
						if (!$this->_readDataOnly) {
							// offset: 0; size: 2; index to first column in range
							$fc = $this->_GetInt2d($recordData, 0); // first column index
							// offset: 2; size: 2; index to last column in range
							$lc = $this->_GetInt2d($recordData, 2); // first column index
							// offset: 4; size: 2; width of the column in 1/256 of the width of the zero character
							$width = $this->_GetInt2d($recordData, 4);
							
							// offset: 6; size: 2; index to XF record for default column formatting
							
							// offset: 8; size: 2; option flags
								// bit: 0; mask: 0x0001; 1= columns are hidden
								$isHidden = (0x0001 & $this->_GetInt2d($recordData, 8)) >> 0;
								// bit: 10-8; mask: 0x0700; outline level of the columns (0 = no outline)
								$level = (0x0700 & $this->_GetInt2d($recordData, 8)) >> 8;
								// bit: 12; mask: 0x1000; 1 = collapsed
								$isCollapsed = (0x1000 & $this->_GetInt2d($recordData, 8)) >> 12;
							
							// offset: 10; size: 2; not used
							
							for ($i = $fc; $i <= $lc; $i++) {
								$sheet->getColumnDimensionByColumn($i)->setWidth($width / 256);
								$sheet->getColumnDimensionByColumn($i)->setVisible(!$isHidden);
								$sheet->getColumnDimensionByColumn($i)->setOutlineLevel($level);
								$sheet->getColumnDimensionByColumn($i)->setCollapsed($isCollapsed);
							}
						}
						break;
						
					case self::XLS_Type_DEFCOLWIDTH:
						// offset: 0; size: 2; row index
						$width = $this->_GetInt2d($recordData, 0);
						$sheet->getDefaultColumnDimension()->setWidth($width);
						break;
						
					case self::XLS_Type_DEFAULTROWHEIGHT:
						// offset: 0; size: 2; option flags
						// offset: 2; size: 2; default height for unused rows, (twips 1/20 point)
						$height = $this->_GetInt2d($recordData, 2);
						$sheet->getDefaultRowDimension()->setRowHeight($height / 20);
						break;
						
					case self::XLS_Type_BLANK:
						// offset: 0; size: 2; row index
						$row = $this->_GetInt2d($recordData, 0);
						// offset: 2; size: 2; col index
						$col = $this->_GetInt2d($recordData, 2);
						// offset: 4; size: 2; XF index
						$xfindex = $this->_GetInt2d($recordData, 4);
						
						// add BIFF8 style information
						if ($version == self::XLS_BIFF8 && !$this->_readDataOnly) {
							$sheet->getStyleByColumnAndRow($col, $row + 1)->applyFromArray($this->_xf[$xfindex]);
						}
						break;
						
					case self::XLS_Type_SHEETPR:
						// offset: 0; size: 2
						// bit: 6; mask: 0x0040; 0 = outline buttons above outline group
						$isSummaryBelow = (0x0040 & $this->_GetInt2d($recordData, 0)) >> 6;
						$sheet->setShowSummaryBelow($isSummaryBelow);
						// bit: 7; mask: 0x0080; 0 = outline buttons left of outline group
						$isSummaryRight = (0x0080 & $this->_GetInt2d($recordData, 0)) >> 7;
						$sheet->setShowSummaryRight($isSummaryRight);
						break;
					
					case self::XLS_Type_EOF:
						$cont = false;
						break;

					default:
						break;
				}
				$spos += $length;
			}
			if (!isset($this->_sheets[$this->_sn]['numRows'])){
				$this->_sheets[$this->_sn]['numRows'] = $this->_sheets[$this->_sn]['maxrow'];
			}
			if (!isset($this->_sheets[$this->_sn]['numCols'])){
				$this->_sheets[$this->_sn]['numCols'] = $this->_sheets[$this->_sn]['maxcol'];
			}
		}
		

		/*
		foreach($this->_boundsheets as $index => $details) {
			$sheet = $excel->getSheet($index);

			// read all the columns of all the rows !
			$numrows = $this->_sheets[$index]['numRows'];
			$numcols = $this->_sheets[$index]['numCols'];
			for ($row = 0; $row < $numrows; $row++) {
				for ($col = 0; $col < $numcols; $col++) {
					$cellcontent = $cellinfo = null;
					if (isset($this->_sheets[$index]['cells'][$row][$col])===true) {
						$cellcontent = $this->_sheets[$index]['cells'][$row][$col];
					} else {
						continue;
					}
					
					if (isset($this->_sheets[$index]['cellsInfo'][$row][$col])===true) {
						$cellinfo = $this->_sheets[$index]['cellsInfo'][$row][$col];
					}

					$sheet->setCellValueByColumnAndRow($col, $row + 1,
						$cellcontent);
				}
			}
		};
		*/
		return $excel;
	}

	// set $encoder for method of UTF-16LE encoding
	private function _setUTFEncoder($encoder = 'iconv')
	{
		$this->_encoderFunction = '';
		if ($encoder == 'iconv') {
			$this->_encoderFunction = function_exists('iconv') ? 'iconv' : 'mb_convert_encoding';

		} elseif ($encoder == 'mb') {

		}
	}
	
	private function _isDate($spos)
	{
		$xfindex = ord($this->_data[$spos + 4]) | ord($this->_data[$spos + 5]) << 8;
		$this->_curformat = $this->_formatRecords['xfrecords'][$xfindex]['format'];
		$this->_fmtcode = $this->_formatRecords['xfrecords'][$xfindex]['code'];

		if ($this->_formatRecords['xfrecords'][$xfindex]['type'] == 'date') {
			$this->_rectype = 'date';
			return true;

		} else if (($xfindex == 0x9) || ($xfindex == 0xa) || ($this->_formatRecords['xfrecords'][$xfindex]['type'] == 'percent')) {
			$this->_rectype = 'number';
			$this->_multiplier = 100;
			}

		else if ($this->_formatRecords['xfrecords'][$xfindex]['type'] == 'number') {
			$this->_rectype = 'number';

		} else {
			$this->_rectype = 'unknown';
		}
		return false;
	}

	private function _createDate($numValue)
	{
		if ($numValue > 1){
			$utcDays = $numValue - ($this->_nineteenFour ? self::XLS_utcOffsetDays1904 : self::XLS_utcOffsetDays);
			$utcValue = round(($utcDays * self::XLS_SecInADay));
			// dvc: excel returns local date/time as absolutes,
			// i.e. 1 hr = 0.04166, 1 day = 1,
			// so need to treat as GMT to translate
			$string = gmdate ($this->_curformat, $utcValue);
			$raw = $utcValue;
		} else {
			// assume a time format...
			$raw = $numValue;
			$hours = round($numValue * 24);
			$mins = round($numValue * 24*60) - $hours * 60;
			$secs = round($numValue * self::XLS_SecInADay) - $hours *60*60 - $mins * 60;
			$string = date ($this->_curformat, mktime($hours, $mins, $secs));
		}
		return array($string, $raw);
	}

	/**
	 * Reads 8 bytes and returns IEEE 754 float
	 */
	private function _createNumber($spos)
	{
		$rknumhigh = $this->_GetInt4d($this->_data, $spos + 10);
		$rknumlow = $this->_GetInt4d($this->_data, $spos + 6);
		$sign = ($rknumhigh & 0x80000000) >> 31;
		$exp = ($rknumhigh & 0x7ff00000) >> 20;
		$mantissa = (0x100000 | ($rknumhigh & 0x000fffff));
		$mantissalow1 = ($rknumlow & 0x80000000) >> 31;
		$mantissalow2 = ($rknumlow & 0x7fffffff);
		$value = $mantissa / pow( 2 , (20- ($exp - 1023)));

		if ($mantissalow1 != 0) {
			$value += 1 / pow (2 , (21 - ($exp - 1023)));
		}

		$value += $mantissalow2 / pow (2 , (52 - ($exp - 1023)));
		if ($sign) {
			$value = -1 * $value;
		}

		return	$value;
	}

	/**
	 * Same as _createNumber, but not hardcoded to read from $this->_data
	 */
	private function _readNumber($subData)
	{
		$rknumhigh = $this->_GetInt4d($subData, 4);
		$rknumlow = $this->_GetInt4d($subData, 0);
		$sign = ($rknumhigh & 0x80000000) >> 31;
		$exp = ($rknumhigh & 0x7ff00000) >> 20;
		$mantissa = (0x100000 | ($rknumhigh & 0x000fffff));
		$mantissalow1 = ($rknumlow & 0x80000000) >> 31;
		$mantissalow2 = ($rknumlow & 0x7fffffff);
		$value = $mantissa / pow( 2 , (20- ($exp - 1023)));

		if ($mantissalow1 != 0) {
			$value += 1 / pow (2 , (21 - ($exp - 1023)));
		}

		$value += $mantissalow2 / pow (2 , (52 - ($exp - 1023)));
		if ($sign) {
			$value = -1 * $value;
		}

		return	$value;
	}

	/*
	private function _addcell($row, $col, $string, $raw = '')
	{
		$this->_sheets[$this->_sn]['maxrow'] =
			max($this->_sheets[$this->_sn]['maxrow'], $row + $this->_rowoffset);
		$this->_sheets[$this->_sn]['maxcol'] =
			max($this->_sheets[$this->_sn]['maxcol'], $col + $this->_coloffset);
		$this->_sheets[$this->_sn]['cells'][$row +
			$this->_rowoffset][$col + $this->_coloffset] = $string;

		if ($raw) {
			$this->_sheets[$this->_sn]['cellsInfo'][$row + $this->_rowoffset][$col + $this->_coloffset]['raw'] = $raw;
		}

		if (isset($this->_rectype)) {
			$this->_sheets[$this->_sn]['cellsInfo'][$row +
				$this->_rowoffset][$col + $this->_coloffset]['type'] =
					$this->_rectype;

			if (isset($this->_curformat)) {
				$this->_sheets[$this->_sn]['cellsInfo'][$row +
					$this->_rowoffset][$col + $this->_coloffset]['format'] =
						$this->_curformat;
			}

			if (isset($this->_fmtcode)) {
				$this->_sheets[$this->_sn]['cellsInfo'][$row +
					$this->_rowoffset][$col + $this->_coloffset]['code'] =
						$this->_fmtcode;
			}

			if (isset($this->_formula)) {
				$this->_sheets[$this->_sn]['cellsInfo'][$row +
					$this->_rowoffset][$col + $this->_coloffset]['formula_tokens'] =
						$this->_formula;
			}

			if (isset($this->_formula_result)) {
				$this->_sheets[$this->_sn]['cellsInfo'][$row +
					$this->_rowoffset][$col + $this->_coloffset]['formula_result'] =
						$this->_formula_result;
			}
		}
	}
	*/

	private function _GetIEEE754($rknum)
	{
		if (($rknum & 0x02) != 0) {
			$value = $rknum >> 2;
		}
		else {
			// changes by mmp, info on IEEE754 encoding from
			// research.microsoft.com/~hollasch/cgindex/coding/ieeefloat.html
			// The RK format calls for using only the most significant 30 bits
			// of the 64 bit floating point value. The other 34 bits are assumed
			// to be 0 so we use the upper 30 bits of $rknum as follows...
			$sign = ($rknum & 0x80000000) >> 31;
			$exp = ($rknum & 0x7ff00000) >> 20;
			$mantissa = (0x100000 | ($rknum & 0x000ffffc));
			$value = $mantissa / pow( 2 , (20- ($exp - 1023)));
			if ($sign) {
				$value = -1 * $value;
			}
			//end of changes by mmp
		}
		if (($rknum & 0x01) != 0) {
			$value /= 100;
		}
		return $value;
	}

	// returns UTF-8 string from UTF-16 string either compressed or uncompressed
	private function _encodeUTF16($string, $compressed = '')
	{
		$result = $string;
		//if ($this->_defaultEncoding) {
		//	if($compressed) {
		//		$string = $this->_uncompressByteString($string);
		//	}
		//	switch ($this->_encoderFunction){
		//		case 'iconv' :
		//			$result = iconv('UTF-16LE', $this->_defaultEncoding, $string);
		//			break;
		//		case 'mb_convert_encoding' :
		//			$result = mb_convert_encoding($string, $this->_defaultEncoding,
		//				'UTF-16LE' );
		//			break;
		//	}
		//}
		if($compressed) {
			$string = $this->_uncompressByteString($string);
 		}
		switch ($this->_encoderFunction){
			case 'iconv' :
				$result = iconv('UTF-16LE', 'UTF-8', $string);
				break;
			case 'mb_convert_encoding' :
				$result = mb_convert_encoding($string, 'UTF-8', 'UTF-16LE');
				break;
		}
		return $result;
	}

	private function _GetInt2d($data, $pos)
	{
		return ord($data[$pos]) | (ord($data[$pos + 1]) << 8);
	}

	private function _GetInt4d($data, $pos)
	{
		return ord($data[$pos]) | (ord($data[$pos + 1]) << 8) |
			(ord($data[$pos + 2]) << 16) | (ord($data[$pos + 3]) << 24);
	}

	private function _uncompressByteString($string)
	{
		$uncompressedString = "";
		for($i = 0; $i < strlen($string); $i++) {
			$uncompressedString .= $string[$i]."\0";
		}

		return $uncompressedString;
	}

	private function _decodeCodepage($string)
	{
		$result = $string;
		if ($this->_defaultEncoding && $this->_codepage) {
			switch ($this->_encoderFunction) {
				case 'iconv' :
					$result = iconv($this->_codepage,$this->_defaultEncoding,$string);
					break;
				case 'mb_convert_encoding' :
					$result = mb_convert_encoding($string, $this->_defaultEncoding, $this->_codepage );
					break;
			}
		}
		return $result;
	}
	
	/**
	 * Get numberFormat from index
	 * returns false if we couldn't resolve numberFormat
	 */
	private function _readBIFF8NumberFormat($numberFormatIndex)
	{
		// there are two possibilites
		// A. numberFormatIndex refers to a built-in numberformat and will not be found in the numberFormat array
		//		instead, it is omitted, and we have to determine numberFormat based on table look-up
		// B. numberFormatIndex refers to a user-defined numberformat and will be found in the numberFormat array
		switch ($numberFormatIndex) {
		case  0: $code = PHPExcel_Style_NumberFormat::FORMAT_GENERAL; break;
		case  1: $code = PHPExcel_Style_NumberFormat::FORMAT_NUMBER; break;
		case  2: $code = PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00; break;
		case  3: $code = '#,##0'; break;
		case  4: $code = PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1; break;
		// case 5 is omitted since it is always written by Excel, depend on locale;
		// case 6 is omitted since it is always written by Excel, depend on locale;
		// case 7 is omitted since it is always written by Excel, depend on locale;
		// case 8 is omitted since it is always written by Excel, depend on locale;
		case  9: $code = PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE; break;
		case 10: $code = PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00; break;
		case 11: $code = '0.00E+00'; break;
		case 12: $code = '# ?/?'; break;
		case 13: $code = '# ??/??'; break;
		case 14: $code = 'M/D/YY'; break; // not always correct, but the best we can do: taken from Microsoft Windws regional settings
		case 15: $code = 'D-MMM-YY'; break;
		case 16: $code = 'D-MMM'; break;
		case 17: $code = 'MMM-YY'; break;
		case 18: $code = 'h:mm AM/PM'; break;
		case 19: $code = 'h:mm:ss AM/PM'; break;
		case 20: $code = 'h:mm'; break;
		case 21: $code = 'h:mm:ss'; break;
		case 22: $code = 'M/D/YY h:mm'; break; // not always correct, but the best we can do: taken from Microsoft Windws regional settings
		case 37: $code = '_(#,##0_);(#,##0)'; break;
		case 38: $code = '_(#,##0_);[Red(#,##0)]'; break;
		case 39: $code = '_(#,##0.00_);(#,##0.00)'; break;
		// case 41 is omitted since it is always written by Excel, depend on locale;
		// case 42 is omitted since it is always written by Excel, depend on locale;
		// case 43 is omitted since it is always written by Excel, depend on locale;
		// case 44 is omitted since it is always written by Excel, depend on locale;
		case 45: $code = 'mm:ss'; break;
		case 46: $code = '[h]:mm:ss'; break;
		case 47: $code = 'mm:ss.0'; break;
		case 48: $code = '##0.0E+0'; break;
		case 49: $code = '@'; break;
		default:
			// check for user-defined number format
			$code = isset($this->_numberFormat[$numberFormatIndex]) ?
				$this->_numberFormat[$numberFormatIndex] : PHPExcel_Style_NumberFormat::FORMAT_GENERAL;
			break;
		}
		return array('code' => $code);
	}
	
	/**
	 * Extracts style from XF record data (not everything is extracted at the moment)
	 */
	private function _readBIFF8Style($recordData)
	{
		$style = array();
		
		// offset:  0; size: 2; Index to FONT record
		if ($this->_GetInt2d($recordData, 0) < 4) {
			$fontIndex = $this->_GetInt2d($recordData, 0);
		} else {
			// this has to do with that index 4 is omitted in all BIFF versions for some strange reason
			// check the OpenOffice documentation of the FONT record
			$fontIndex = $this->_GetInt2d($recordData, 0) - 1;
		}
		$style['font'] = $this->_font[$fontIndex];
		
		// offset:  2; size: 2; Index to FORMAT record
			$numberFormatIndex = $this->_GetInt2d($recordData, 2);
			$style['numberformat'] = $this->_readBIFF8NumberFormat($numberFormatIndex);
		
		$style['protection'] = array();
		// offset:  4; size: 2; XF type, cell protection, and parent style XF
			// bit 2-0; mask 0x0007; XF_TYPE_PROT
			$xfTypeProt = $this->_GetInt2d($recordData, 4);
				// bit 0; mask 0x01; 1 = cell is locked
				$isLocked = (0x01 & $xfTypeProt) >> 0;
				$style['protection']['locked'] = $isLocked ? true : false;
				// bit 1;
				// bit 2;
		
		// offset:  6; size: 1; Alignment and text break
		$style['alignment'] = array();
			// bit 2-0, mask 0x07; horizontal alignment
			$horAlign = (0x07 & ord($recordData[6])) >> 0;
			switch ($horAlign) {
				case 0: $style['alignment']['horizontal'] = PHPExcel_Style_Alignment::HORIZONTAL_GENERAL; break;
				case 1: $style['alignment']['horizontal'] = PHPExcel_Style_Alignment::HORIZONTAL_LEFT; break;
				case 2: $style['alignment']['horizontal'] = PHPExcel_Style_Alignment::HORIZONTAL_CENTER; break;
				case 3: $style['alignment']['horizontal'] = PHPExcel_Style_Alignment::HORIZONTAL_RIGHT; break;
				case 5: $style['alignment']['horizontal'] = PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY; break;
			}
			// bit 3, mask 0x08; wrap text
			$wrapText = (0x08 & ord($recordData[6])) >> 3;
			switch ($wrapText) {
				case 0: $style['alignment']['wrap'] = false; break;
				case 1: $style['alignment']['wrap'] = true; break;
			}
			// bit 6-4, mask 0x70; vertical alignment
			$vertAlign = (0x70 & ord($recordData[6])) >> 4;
			switch ($vertAlign) {
				case 0: $style['alignment']['vertical'] = PHPExcel_Style_Alignment::VERTICAL_TOP; break;
				case 1: $style['alignment']['vertical'] = PHPExcel_Style_Alignment::VERTICAL_CENTER; break;
				case 2: $style['alignment']['vertical'] = PHPExcel_Style_Alignment::VERTICAL_BOTTOM; break;
				case 3: $style['alignment']['vertical'] = PHPExcel_Style_Alignment::VERTICAL_JUSTIFY; break;
			}
		// offset:  7; size: 1; XF_ROTATION: Text rotation angle
		// offset:  8; size: 1; Indentation, shrink to cell size, and text direction
		// offset:  9; size: 1; Flags used for attribute groups
		
		$style['borders'] = array(
			'left' => array(),
			'right' => array(),
			'top' => array(),
			'bottom' => array(),
		);
		// offset: 10; size: 4; Cell border lines and background area
			// bit: 3-0; mask: 0x0000000F; left style
			if ($bordersLeftStyle = $this->_readBorderStyle((0x0000000F & $this->_GetInt4d($recordData, 10)) >> 0)) {
				$style['borders']['left']['style'] = $bordersLeftStyle;
			}
			// bit: 7-4; mask: 0x000000F0; right style
			if ($bordersRightStyle = $this->_readBorderStyle((0x000000F0 & $this->_GetInt4d($recordData, 10)) >> 4)) {
				$style['borders']['right']['style'] = $bordersRightStyle;
			}
			// bit: 11-8; mask: 0x00000F00; top style
			if ($bordersTopStyle = $this->_readBorderStyle((0x00000F00 & $this->_GetInt4d($recordData, 10)) >> 8)) {
				$style['borders']['top']['style'] = $bordersTopStyle;
			}
			// bit: 15-12; mask: 0x0000F000; bottom style
			if ($bordersBottomStyle = $this->_readBorderStyle((0x0000F000 & $this->_GetInt4d($recordData, 10)) >> 12)) {
				$style['borders']['bottom']['style'] = $bordersBottomStyle;
			}
			// bit: 22-16; mask: 0x007F0000; left color
			if ($bordersLeftColor = $this->_readColor((0x007F0000 & $this->_GetInt4d($recordData, 10)) >> 16)) {
				$style['borders']['left']['color'] = $bordersLeftColor;
			}
			// bit: 29-23; mask: 0x3F800000; right color
			if ($bordersRightColor = $this->_readColor((0x3F800000 & $this->_GetInt4d($recordData, 10)) >> 23)) {
				$style['borders']['right']['color'] = $bordersRightColor;
			}
			
		$style['fill'] = array();
		// offset: 14; size: 4;
			// bit: 6-0; mask: 0x0000007F; top color
			if ($bordersTopColor = $this->_readColor((0x0000007F & $this->_GetInt4d($recordData, 14)) >> 0)) {
				$style['borders']['top']['color'] = $bordersTopColor;
			}
			// bit: 13-7; mask: 0x00003F80; top color
			if ($bordersBottomColor = $this->_readColor((0x00003F80 & $this->_GetInt4d($recordData, 14)) >> 7)) {
				$style['borders']['bottom']['color'] = $bordersBottomColor;
			}
			// bit: 31-26; mask: 0xFC000000 fill pattern
			if ($fillType = $this->_getFillPatternByIndex((0xFC000000 & $this->_GetInt4d($recordData, 14)) >> 26)) {
				$style['fill']['type'] = $fillType;
			}
		// offset: 18; size: 2; pattern and background colour
			// bit: 6-0; mask: 0x007F; color index for pattern color
			if ($rgb = $this->_readColor((0x007F & $this->_GetInt2d($recordData, 18)) >> 0)) {
				$style['fill']['startcolor'] = $rgb;
			}
			// bit: 13-7; mask: 0x3F80; color index for pattern background
			if ($rgb = $this->_readColor((0x3F80 & $this->_GetInt2d($recordData, 18)) >> 7)) {
				$style['fill']['endcolor'] = $rgb;
			}
		return $style;
	}
	
	/**
	 * OpenOffice documentation: 2.5.11
	 */
	private function _readBorderStyle($index)
	{
		switch ($index) {
		case 0x00: return PHPExcel_Style_Border::BORDER_NONE;
		case 0x01: return PHPExcel_Style_Border::BORDER_THIN;
		case 0x02: return PHPExcel_Style_Border::BORDER_MEDIUM;
		case 0x03: return PHPExcel_Style_Border::BORDER_DASHED;
		case 0x04: return PHPExcel_Style_Border::BORDER_DOTTED;
		case 0x05: return PHPExcel_Style_Border::BORDER_THICK;
		case 0x06: return PHPExcel_Style_Border::BORDER_DOUBLE;
		case 0x07: return PHPExcel_Style_Border::BORDER_HAIR;
		case 0x08: return PHPExcel_Style_Border::BORDER_MEDIUMDASHED;
		case 0x09: return PHPExcel_Style_Border::BORDER_DASHDOT;
		case 0x0A: return PHPExcel_Style_Border::BORDER_MEDIUMDASHDOT;
		case 0x0B: return PHPExcel_Style_Border::BORDER_DASHDOTDOT;
		case 0x0C: return PHPExcel_Style_Border::BORDER_MEDIUMDASHDOTDOT;
		case 0x0D: return PHPExcel_Style_Border::BORDER_SLANTDASHDOT;
		default: return false;
		}
	}
	
	/**
	 * Get fill pattern from index
	 * OpenOffice documentation: 2.5.12
	 */
	private function _getFillPatternByIndex($index)
	{
		switch ($index) {
		case 0x00: return PHPExcel_Style_Fill::FILL_NONE;
		case 0x01: return PHPExcel_Style_Fill::FILL_SOLID;
		case 0x02: return PHPExcel_Style_Fill::FILL_PATTERN_MEDIUMGRAY;
		case 0x03: return PHPExcel_Style_Fill::FILL_PATTERN_DARKGRAY;
		case 0x04: return PHPExcel_Style_Fill::FILL_PATTERN_LIGHTGRAY;
		case 0x05: return PHPExcel_Style_Fill::FILL_PATTERN_DARKHORIZONTAL;
		case 0x06: return PHPExcel_Style_Fill::FILL_PATTERN_DARKVERTICAL;
		case 0x07: return PHPExcel_Style_Fill::FILL_PATTERN_DARKDOWN;
		case 0x08: return PHPExcel_Style_Fill::FILL_PATTERN_DARKUP;
		case 0x09: return PHPExcel_Style_Fill::FILL_PATTERN_DARKGRID;
		case 0x0A: return PHPExcel_Style_Fill::FILL_PATTERN_DARKTRELLIS;
		case 0x0B: return PHPExcel_Style_Fill::FILL_PATTERN_LIGHTHORIZONTAL;
		case 0x0C: return PHPExcel_Style_Fill::FILL_PATTERN_LIGHTVERTICAL;
		case 0x0D: return PHPExcel_Style_Fill::FILL_PATTERN_LIGHTDOWN;
		case 0x0E: return PHPExcel_Style_Fill::FILL_PATTERN_LIGHTUP;
		case 0x0F: return PHPExcel_Style_Fill::FILL_PATTERN_LIGHTGRID;
		case 0x10: return PHPExcel_Style_Fill::FILL_PATTERN_LIGHTTRELLIS;
		case 0x11: return PHPExcel_Style_Fill::FILL_PATTERN_GRAY125;
		case 0x12: return PHPExcel_Style_Fill::FILL_PATTERN_GRAY0625;
		default: return false;
		}
	}
	
	/**
	 * Extracts font details from FONT record data (not everything is extracted yet)
	 *
	 **/
	private function _readFont($recordData)
	{
		$font = array();
		// offset: 0; size: 2; height of the font (in twips = 1/20 of a point)
		$size = $this->_GetInt2d($recordData, 0);
		$font['size'] = $size / 20;
		
		// offset: 2; size: 2; option flags
			// bit: 0; mask 0x0001; bold (redundant in BIFF5-BIFF8)
			// bit: 1; mask 0x0002; italic
			$isItalic = (0x0002 & $this->_GetInt2d($recordData, 2)) >> 1;
			if ($isItalic) $font['italic'] = true;
			
			// bit: 2; mask 0x0004; underlined (redundant in BIFF5-BIFF8)
			// bit: 3; mask 0x0008; strike
			$isStrike = (0x0008 & $this->_GetInt2d($recordData, 2)) >> 3;
			if ($isStrike) $font['strike'] = true;
		
		// offset: 4; size: 2; colour index
		if ($color = $this->_readColor($this->_GetInt2d($recordData, 4))) {
			$font['color'] = $color;
		}
		
		// offset: 6; size: 2; font weight
		$weight = $this->_GetInt2d($recordData, 6);
		switch ($weight) {
			case 0x02BC: $font['bold'] = true;
		}
		
		// offset: 8; size: 2; escapement type
		
		// offset: 10; size: 1; underline type
		$underlineType = ord($recordData[10]);
		switch ($underlineType) {
			case 0x00: break; // no underline
			case 0x01: $font['underline'] = PHPExcel_Style_Font::UNDERLINE_SINGLE; break;
			case 0x02: $font['underline'] = PHPExcel_Style_Font::UNDERLINE_DOUBLE; break;
			case 0x21: $font['underline'] = PHPExcel_Style_Font::UNDERLINE_SINGLEACCOUNTING; break;
			case 0x22: $font['underline'] = PHPExcel_Style_Font::UNDERLINE_DOUBLEACCOUNTING; break;
		}
		
		// offset: 11; size: 1; font family
		// offset: 12; size: 1; character set
		// offset: 13; size: 1; not used
		// offset: 14; size: var; font name
		$unicode = $this->_readUnicodeStringShort(substr($recordData, 14));
		$font['name'] = $unicode['string'];
		
		return $font;
	}
	
	/**
	 * Extracts an Excel Unicode short string (8-bit string length)
	 * OpenOffice documentation: 2.5.3
	 * this function is under construction, needs to support rich text, and Asian phonetic settings
	 * function will automatically find out where the Unicode string ends.
	 */
	private function _readUnicodeStringShort($subData)
	{
		$string = '';
		
		// offset: 0: size: 1; length of the string (character count)
		$characterCount = ord($subData[0]);
		// offset: 1: size: 1; option flags
			// bit: 0; mask: 0x01; character compression (0 = compressed 8-bit, 1 = uncompressed 16-bit)
			$isCompressed = !((0x01 & ord($subData[1])) >> 0);
			
			// bit: 2; mask: 0x04; Asian phonetic settings
			$hasAsian = (0x04) & ord($subData[1]) >> 2;
			
			// bit: 3; mask: 0x08; Rich-Text settings
			$hasRichText = (0x08) & ord($subData[1]) >> 3;
			
		// offset: 2: size: var; character array
		// this offset assumes richtext and Asian phonetic settings are off which is generally wrong
		// needs to be fixed
		$string = $this->_encodeUTF16(substr($subData, 2, $isCompressed ? $characterCount : 2 * $characterCount), $isCompressed);
		
		return array(
			'size' => $isCompressed ? 2 + $characterCount : 2 + 2 * $characterCount, // the size in bytes including the length byte + option flags
			'string' => $string,
		);
	}
	
	/**
	 * Extracts an Excel Unicode long string (16-bit string length)
	 * OpenOffice documentation: 2.5.3
	 * this function is under construction, needs to support rich text, and Asian phonetic settings
	 *
	 * this function should look like _readUnicodeStringShort and also return the size in bytes
	 */
	private function _readUnicodeStringLong($subData)
	{
		$string = '';
		
		// offset: 0: size: 2; length of the string (character count)
		$characterCount = $this->_GetInt2d($subData, 0);
		// offset: 2: size: 1; option flags
			// bit: 0; mask: 0x01; character compression (0 = compressed 8-bit, 1 = uncompressed 16-bit)
			$isCompressed = !((0x01 & ord($subData[2])) >> 0);
			
			// bit: 2; mask: 0x04; Asian phonetic settings
			$hasAsian = (0x04) & ord($subData[2]) >> 2;
			
			// bit: 3; mask: 0x08; Rich-Text settings
			$hasRichText = (0x08) & ord($subData[2]) >> 3;
			
		// offset: 3: size: var; character array
		// this offset assumes richtext and Asian phonetic settings are off which is generally wrong
		// needs to be fixed
		$string = $this->_encodeUTF16(substr($subData, 3), $isCompressed);
		
		return $string;
	}
	
	/**
	 * Reads a cell address in BIFF8 e.g. 'A2' or '$A$2'
	 * section 3.3.4
	 */
	private function _readBIFF8CellAddress($cellAddressStructure)
	{
		// offset: 0; size: 2; index to row (0... 65535) (or offset (-32768... 32767))
			$row = $this->_getInt2d($cellAddressStructure, 0) + 1;
			
		// offset: 2; size: 2; index to column or column offset + relative flags
			// bit: 7-0; mask 0x00FF; column index
			$column = PHPExcel_Cell::stringFromColumnIndex(0x00FF & $this->_getInt2d($cellAddressStructure, 2));
			// bit: 14; mask 0x4000; (1 = relative column index, 0 = absolute column index)
			if (!(0x4000 & $this->_getInt2d($cellAddressStructure, 2))) {
				$column = '$' . $column;
			}
			// bit: 15; mask 0x8000; (1 = relative row index, 0 = absolute row index)
			if (!(0x8000 & $this->_getInt2d($cellAddressStructure, 2))) {
				$row = '$' . $row;
			}
			
		
		return $column . $row;
	}
	
	/**
	 * Reads a cell range address in BIFF8 e.g. 'A2:B6' or '$A$2:$B$6'
	 * section 3.3.4
	 */
	private function _readBIFF8CellRangeAddress($subData)
	{
		// offset: 0; size: 2; index to first row (0... 65535) (or offset (-32768... 32767))
			$fr = $this->_getInt2d($subData, 0) + 1;
		// offset: 2; size: 2; index to last row (0... 65535) (or offset (-32768... 32767))
			$lr = $this->_getInt2d($subData, 2) + 1;
		// offset: 4; size: 2; index to first column or column offset + relative flags
			// bit: 7-0; mask 0x00FF; column index
			$fc = PHPExcel_Cell::stringFromColumnIndex(0x00FF & $this->_getInt2d($subData, 4));
			// bit: 14; mask 0x4000; (1 = relative column index, 0 = absolute column index)
			if (!(0x4000 & $this->_getInt2d($subData, 4))) {
				$fc = '$' . $fc;
			}
			// bit: 15; mask 0x8000; (1 = relative row index, 0 = absolute row index)
			if (!(0x8000 & $this->_getInt2d($subData, 4))) {
				$fr = '$' . $fr;
			}
		// offset: 6; size: 2; index to last column or column offset + relative flags
			// bit: 7-0; mask 0x00FF; column index
			$lc = PHPExcel_Cell::stringFromColumnIndex(0x00FF & $this->_getInt2d($subData, 6));
			// bit: 14; mask 0x4000; (1 = relative column index, 0 = absolute column index)
			if (!(0x4000 & $this->_getInt2d($subData, 6))) {
				$lc = '$' . $lc;
			}
			// bit: 15; mask 0x8000; (1 = relative row index, 0 = absolute row index)
			if (!(0x8000 & $this->_getInt2d($subData, 6))) {
				$lr = '$' . $lr;
			}
			
		return "$fc$fr:$lc$lr";
	}
	
	/**
	 * Extracts color array from BIFF8 built-in color index
	 */
	private function _readColor($subData)
	{
		switch ($subData) {
			case 0x08: return array('rgb' => '000000');
			case 0x09: return array('rgb' => 'FFFFFF');
			case 0x0A: return array('rgb' => 'FF0000');
			case 0x0B: return array('rgb' => '00FF00');
			case 0x0C: return array('rgb' => '0000FF');
			case 0x0D: return array('rgb' => 'FFFF00');
			case 0x0E: return array('rgb' => 'FF00FF');
			case 0x0F: return array('rgb' => '00FFFF');
			case 0x10: return array('rgb' => '800000');
			case 0x11: return array('rgb' => '008000');
			case 0x12: return array('rgb' => '000080');
			case 0x13: return array('rgb' => '808000');
			case 0x14: return array('rgb' => '800080');
			case 0x15: return array('rgb' => '008080');
			case 0x16: return array('rgb' => 'C0C0C0');
			case 0x17: return array('rgb' => '808080');
			case 0x18: return array('rgb' => '9999FF');
			case 0x19: return array('rgb' => '993366');
			case 0x1A: return array('rgb' => 'FFFFCC');
			case 0x1B: return array('rgb' => 'CCFFFF');
			case 0x1C: return array('rgb' => '660066');
			case 0x1D: return array('rgb' => 'FF8080');
			case 0x1E: return array('rgb' => '0066CC');
			case 0x1F: return array('rgb' => 'CCCCFF');
			case 0x20: return array('rgb' => '000080');
			case 0x21: return array('rgb' => 'FF00FF');
			case 0x22: return array('rgb' => 'FFFF00');
			case 0x23: return array('rgb' => '00FFFF');
			case 0x24: return array('rgb' => '800080');
			case 0x25: return array('rgb' => '800000');
			case 0x26: return array('rgb' => '008080');
			case 0x27: return array('rgb' => '0000FF');
			case 0x28: return array('rgb' => '00CCFF');
			case 0x29: return array('rgb' => 'CCFFFF');
			case 0x2A: return array('rgb' => 'CCFFCC');
			case 0x2B: return array('rgb' => 'FFFF99');
			case 0x2C: return array('rgb' => '99CCFF');
			case 0x2D: return array('rgb' => 'FF99CC');
			case 0x2E: return array('rgb' => 'CC99FF');
			case 0x2F: return array('rgb' => 'FFCC99');
			case 0x30: return array('rgb' => '3366FF');
			case 0x31: return array('rgb' => '33CCCC');
			case 0x32: return array('rgb' => '99CC00');
			case 0x33: return array('rgb' => 'FFCC00');
			case 0x34: return array('rgb' => 'FF9900');
			case 0x35: return array('rgb' => 'FF6600');
			case 0x36: return array('rgb' => '666699');
			case 0x37: return array('rgb' => '969696');
			case 0x38: return array('rgb' => '003366');
			case 0x39: return array('rgb' => '339966');
			case 0x3A: return array('rgb' => '003300');
			case 0x3B: return array('rgb' => '333300');
			case 0x3C: return array('rgb' => '993300');
			case 0x3D: return array('rgb' => '993366');
			case 0x3E: return array('rgb' => '333399');
			case 0x3F: return array('rgb' => '333333');
			default: return false;
		}
	}
	
	/**
	 * Get a sheet range like Sheet1:Sheet3 from REF index
	 * Note: If there is only one sheet in the range, one gets e.g Sheet1
	 */
	private function _readSheetRangeByRefIndex($index)
	{
		// we are assuming that ref index refers to internal workbook
		// in general, this is wrong, fix later
		echo "Index:$index:";
		if (isset($this->_ref[$index])) {
			$firstSheetName = $this->_boundsheets[$this->_ref[$index]['firstSheetIndex']]['name'];
			$firstSheetName = (strpos($firstSheetName, ' ') !== false) ?
				"'$firstSheetName'" : $firstSheetName;
			$lastSheetName = $this->_boundsheets[$this->_ref[$index]['lastSheetIndex']]['name'];
			$lastSheetName = (strpos($lastSheetName, ' ') !== false) ?
				"'$lastSheetName'" : $lastSheetName;
			if ($firstSheetName == $lastSheetName) {
				return $firstSheetName;
			} else {
				return "$firstSheetName:$lastSheetName";
			}
		}
		return false;
	}
	
	/**
	 * Convert formula structure (HEX data) into human readable Excel formula
	 * Experimental function under construction not currently in use
	 * returns e.g. 'A3+A5*5'
	 */
	private function _getFormulaStringFromStructure($formulaStructure)
	{
		$formulaHL = '=';
		
		echo '<xmp>';
		// offset: 0; size: 2; size of the following formula data
		$sz = $this->_getInt2d($formulaStructure, 0);
		echo 'size: ' . $sz . "\n";
		
		// offset: 2; size: sz
		$formulaData = substr($formulaStructure, 2, $sz);
		
		// offset: 2 + sz; size: variable (optional)
		if (strlen($formulaStructure) > 2 + $sz) {
			$additionalData = substr($formulaStructure, 2 + $sz);
		}
		
		// dump the entire formula structure
		echo 'the entire formulastructure: ';
		echo '<span style="background:yellow">';
		for ($i = 0; $i < 2; $i++) {
			echo sprintf('%02X', ord($formulaStructure[$i])) . ' ';
		}
		echo '</span>';
		echo '<span style="background:cyan">';
		for ($i = 2; $i < 2 + $sz; $i++) {
			echo sprintf('%02X', ord($formulaStructure[$i])) . ' ';
		}
		echo '</span>';
		echo "\n----\n";
		
		// start parsing the $formulaData
		$tokens = array();
		$formulaDataRemain = $formulaData;
		while (strlen($formulaDataRemain) > 0 and $token = $this->_getNextToken($formulaDataRemain)) {
			$tokens[] = $token;
			var_dump($token);
			$formulaDataRemain = substr($formulaDataRemain, $token['size']);
		}
		
		$formulaString = $this->_createFormulaStringFromTokens($tokens);
		
		return false;
	}
	
	/**
	 * Convert array of tokens into string
	 * Experimental function under construction not currently in use
	 * returns e.g. 'A3+A5*5'
	 */
	private function _createFormulaStringFromTokens($tokens)
	{
		$formulaStrings = array();
		foreach ($tokens as $token) {
			// initialize space
			$space0 = isset($space0) ? $space0 : '';
			
			switch ($token['name']) {
			case 'tAdd': // addition
			case 'tConcat': // addition
			case 'tDiv': // division
			case 'tEQ': // equaltiy
			case 'tGT': // greater than
			case 'tLE': // less than or equals
			case 'tLT': // less than
			case 'tMul': // multiplication
			case 'tNE': // multiplication
			case 'tPower': // power
			case 'tSub': // subtraction
				$op2 = array_pop($formulaStrings);
				$op1 = array_pop($formulaStrings);
				$formulaStrings[] = "$op1$space0{$token['data']}$op2";
				unset($space0);
				break;
			case 'tArea': // cell range address
				$formulaStrings[] = $space0 . $token['data'];
				unset($space0);
				break;
			case 'tAttrIf':
				// token is only important for Excel formula evaluator
				// not needed here
				break;
			case 'tAttrSkip':
				// token is only important for Excel formula evaluator
				// not needed here
				break;
			case 'tAttrSpace': // space / carriage return
				// space will be used when next token arrives, do not alter formulaString stack
				switch ($token['data']['spacetype']) {
				case 'type0': $space0 = str_repeat(' ', $token['data']['spacecount']); break;
				default: break; // unrecognized
				}
				break;
			case 'tAttrSum': // SUM function with one parameter
				$op = array_pop($formulaStrings);
				$formulaStrings[] = "{$space0}SUM($op)";
				unset($space0);
				break;
			case 'tAttrVolatile': // indicates volatile function
				// token is only important for Excel formula evaluator
				// not needed here
				break;
			case 'tBool': // boolean
				$formulaStrings[] = "$space0{$token['data']}";
				unset($space0);
				break;
			case 'tFunc': // function with fixed number of arguments
			case 'tFuncV': // function with variable number of arguments
				$ops = array(); // array of operators
				for ($i = 0; $i < $token['data']['args']; $i++) {
					$ops[] = array_pop($formulaStrings);
				}
				$ops = array_reverse($ops);
				$formulaStrings[] = "$space0{$token['data']['function']}(" . implode(',', $ops) . ")";
				unset($space0);
				break;
			case 'tParen': // parenthesis
				$expression = array_pop($formulaStrings);
				$formulaStrings[] = "($expression)";
				// space0 won't occur
				break;
			case 'tInt': // integer
				$formulaStrings[] = "$space0{$token['data']}";
				unset($space0);
				break;
			case 'tNum': // number
				$formulaStrings[] = "$space0{$token['data']}";
				unset($space0);
				break;
			case 'tRef': // single cell reference
				$formulaStrings[] = "$space0{$token['data']}";
				unset($space0);
				break;
			case 'tRef3d': // 3d cell reference V
				$formulaStrings[] = "$space0{$token['data']}";
				unset($space0);
				break;
			case 'tStr': // string
				$formulaStrings[] = "$space0{$token['data']}";
				unset($space0);
				break;
			}
		}
		$formulaString = $formulaStrings[0];
		
		echo '----' . "\n";
		echo 'Formula string: ' . $formulaString;
		return $formulaString;
	}
	
	/**
	 * Fetch next token from portion of formula data (HEX)
	 * Experimental function under construction not currently in use
	 */
	private function _getNextToken($formulaDataRemain)
	{
		// offset: 0; size: 1; token id
		$id = ord($formulaDataRemain[0]); // token id
		$name = false; // initialize token name
		
		switch ($id) {
		case 0x03: $name = 'tAdd';		$size = 1;	$data = '+';	break;
		case 0x04: $name = 'tSub';		$size = 1;	$data = '-';	break;
		case 0x05: $name = 'tMul';		$size = 1;	$data = '*';	break;
		case 0x06: $name = 'tDiv';		$size = 1;	$data = '/';	break;
		case 0x07: $name = 'tPower';	$size = 1;	$data = '^';	break;
		case 0x08: $name = 'tConcat';	$size = 1;	$data = '&';	break;
		case 0x09: $name = 'tLT';		$size = 1;	$data = '<';	break;
		case 0x0A: $name = 'tLE';		$size = 1;	$data = '<=';	break;
		case 0x0B: $name = 'tEQ';		$size = 1;	$data = '=';	break;
		case 0x0D: $name = 'tGT';		$size = 1;	$data = '>';	break;
		case 0x0E: $name = 'tNE';		$size = 1;	$data = '<>';	break;
		case 0x15: // parenthesis
			$name  = 'tParen';
			$size  = 1;
			$data = null;
			break;
		case 0x17: // string
			$name = 'tStr';
			// offset: 1; size: var; Unicode string, 8-bit string length
			$unicode = $this->_readUnicodeStringShort(substr($formulaDataRemain, 1));
			$size = 1 + $unicode['size'];
			$data = "\"$unicode[string]\"";
			break;
		case 0x19: // Special attribute
			// offset: 1; size: 1; attribute type flags:
			switch (ord($formulaDataRemain[1])) {
			case 0x01:
				$name = 'tAttrVolatile';
				$size = 4;
				$data = null;
				break;
			case 0x02:
				$name = 'tAttrIf';
				$size = 4;
				$data = null;
				break;
			case 0x08:
				$name = 'tAttrSkip';
				$size = 4;
				$data = null;
				break;
			case 0x10:
				$name = 'tAttrSum';
				$size = 4;
				$data = null;
				break;
			case 0x40:
				$name = 'tAttrSpace';
				$size = 4;
				// offset: 2; size: 2; space type and position
				switch (ord($formulaDataRemain[2])) {
				case 0x00: $spacetype = 'type0'; break;
				default: $name = null; break; // unrecognized
				}
				// offset: 3; size: 1; number of inserted spaces/carriage returns
				$spacecount = ord($formulaDataRemain[3]);
				
				$data = array('spacetype' => $spacetype, 'spacecount' => $spacecount);
				break;
			default:
				$name = null; // unrecognized
				break;
			}
			break;
		case 0x1D: // boolean
			// offset: 1; size: 1; 0 = false, 1 = true;
			$name = 'tBool';
			$size = 2;
			$data = ord($formulaDataRemain[1]) ? 'TRUE' : 'FALSE';
			break;
		case 0x1E: // integer
			// offset: 1; size: 2; unsigned 16-bit integer
			$name = 'tInt';
			$size = 3;
			$data = $this->_getInt2d($formulaDataRemain, 1);
			break;
		case 0x1F: // number
			// offset: 1; size: 8;
			$name = 'tNum';
			$size = 9;
			$data = $this->_readNumber(substr($formulaDataRemain, 1));
			var_dump($data);
			break;
		case 0x24: // single cell reference e.g. A5
		case 0x44:
			$name = 'tRef';
			$size = 5;
			$data = $this->_readBIFF8CellAddress(substr($formulaDataRemain, 1, 4));
			break;
		case 0x25: // cell range reference to cells in the same sheet
		case 0x45:
			$name = 'tArea';
			$size = 9;
			$data = $this->_readBIFF8CellRangeAddress(substr($formulaDataRemain, 1, 8));
			break;
		case 0x41: // function with fixed number of arguments
			$name = 'tFunc';
			$size = 3;
			// offset: 1; size: 2; index to built-in sheet function
			switch ($this->_GetInt2d($formulaDataRemain, 1)) {
			case   3: $function = 'ISERROR'; 	$args = 1; 	break;
			case  10: $function = 'NA'; 		$args = 0; 	break;
			case  15: $function = 'SIN'; 		$args = 1; 	break;
			case  19: $function = 'PI'; 		$args = 0; 	break;
			case  20: $function = 'SQRT'; 		$args = 1; 	break;
			case  25: $function = 'INT'; 		$args = 1; 	break;
			case  38: $function = 'NOT'; 		$args = 1; 	break;
			case  39: $function = 'MOD'; 		$args = 2;	break;
			case  48: $function = 'TEXT'; 		$args = 2;	break;
			case  65: $function = 'DATE'; 		$args = 3;	break;
			case  66: $function = 'TIME'; 		$args = 3;	break;
			case  67: $function = 'DAY'; 		$args = 1;	break;
			case  68: $function = 'MONTH'; 		$args = 1;	break;
			case  69: $function = 'YEAR'; 		$args = 1;	break;
			case  71: $function = 'HOUR'; 		$args = 1;	break;
			case  72: $function = 'MINUTE'; 	$args = 1;	break;
			case  73: $function = 'SECOND'; 	$args = 1;	break;
			case  74: $function = 'NOW'; 		$args = 0;	break;
			case 140: $function = 'DATEVALUE'; 	$args = 1;	break;
			case 221: $function = 'TODAY'; 		$args = 0;	break;
			default: $name = null; break; // unrecognized
			}
			if ($name) {
				$data = array('function' => $function, 'args' => $args);
			}
			break;
		case 0x42: // function with variable number of arguments
		case 0x62: // function with variable number of arguments
			$name = 'tFuncV';
			$size = 4;
			// offset: 1; size: 1; number of arguments
			$args = ord($formulaDataRemain[1]);
			// offset: 2: size: 2; index to built-in sheet function
			switch ($this->_GetInt2d($formulaDataRemain, 2)) {
			case   1: $function = 'IF';			break;
			case   4: $function = 'SUM';		break;
			case   6: $function = 'MIN';		break;
			case  29: $function = 'INDEX';		break;
			case  36: $function = 'AND';		break;
			case  37: $function = 'OR';			break;
			case  64: $function = 'MATCH';		break;
			case  78: $function = 'OFFSET';		break;
			case 102: $function = 'VLOOKUP';	break;
			case 345: $function = 'SUMIF';		break;
			default: $name = null; // unrecognized
			}
			if ($name) {
				$data = array('function' => $function, 'args' => $args);
			}
			break;
		case 0x5A: // 3d cell reference V
			$name = 'tRef3d';
			$size = 7;
			// offset: 1; size: 2; index to REF entry
			$sheetRange = $this->_readSheetRangeByRefIndex($this->_getInt2d($formulaDataRemain, 1));
			// offset: 3; size: 4; cell address
			$cellAddress = $this->_readBIFF8CellAddress(substr($formulaDataRemain, 3, 4));
			$data = "$sheetRange!$cellAddress";
			break;
		// case 0x39: // don't know how to deal with
		default:
			$name = null; // unregognized
			break;
		}
		
		if ($name) {
			return array(
				'id' => $id,
				'name' => $name,
				'size' => $size,
				'data' => $data,
			);
		}
		
		echo 'Could not recognize this structure: ' . sprintf('%02X', $id) . "\n";
		
		return false;
	}
	
	/**
	 * Dump a byte sequence, only used for debugging
	 * Experimental function to be removed
	 */
	private function _dump($data)
	{
		for ($i = 0; $i < strlen($data); $i++) {
			echo sprintf('%02X', ord($data[$i])) . ' ';
		}
	}
	
}
