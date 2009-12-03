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
 * @package	PHPExcel_Calculation
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license	http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version	1.6.3, 2008-08-25
 */


define('EPS', 2.22e-16);
define('MAX_VALUE', 1.2e308);
define('LOG_GAMMA_X_MAX_VALUE', 2.55e305);
define('SQRT2PI', 2.5066282746310005024157652848110452530069867406099);
define('XMININ', 2.23e-308);
define('MAX_ITERATIONS', 150);
define('PRECISION', 8.88E-016);
define('EULER', 2.71828182845904523536);

$savedPrecision = ini_get('precision');
if ($savedPrecision < 15) {
	ini_set('precision',15);
}


/** PHPExcel_Cell */
require_once 'PHPExcel/Cell.php';

/** PHPExcel_Cell_DataType */
require_once 'PHPExcel/Cell/DataType.php';

/** PHPExcel_Shared_Date */
require_once 'PHPExcel/Shared/Date.php';


/**
 * PHPExcel_Calculation_Functions
 *
 * @category   PHPExcel
 * @package	PHPExcel_Calculation
 * @copyright  Copyright (c) 2006 - 2008 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Calculation_Functions {

	/** constants */
	const COMPATIBILITY_EXCEL		= 'Excel';
	const COMPATIBILITY_GNUMERIC	= 'Gnumeric';
	const COMPATIBILITY_OPENOFFICE	= 'OpenOfficeCalc';

	const RETURNDATE_PHP_NUMERIC = 'P';
	const RETURNDATE_PHP_OBJECT = 'O';
	const RETURNDATE_EXCEL = 'E';


	/**
	 * Compatibility mode to use for error checking and responses
	 *
	 * @var string
	 */
	private static $compatibilityMode	= self::COMPATIBILITY_EXCEL;

	/**
	 * Data Type to use when returning date values
	 *
	 * @var integer
	 */
	private static $ReturnDateType	= self::RETURNDATE_PHP_NUMERIC;

	/**
	 * List of error codes
	 *
	 * @var array
	 */
	private static $_errorCodes	= array( 'null'				=> '#NULL!',
										 'divisionbyzero'	=> '#DIV/0!',
										 'value'			=> '#VALUE!',
										 'reference'		=> '#REF!',
										 'name'				=> '#NAME?',
										 'num'				=> '#NUM!',
										 'na'				=> '#N/A'
									   );


	/**
	 * Set the Compatibility Mode
	 *
	 * @param	 string		$compatibilityMode		Compatibility Mode
	 * @return	 boolean	(Success or Failure)
	 */
	public static function setCompatibilityMode($compatibilityMode) {
		if (($compatibilityMode == self::COMPATIBILITY_EXCEL) ||
			($compatibilityMode == self::COMPATIBILITY_GNUMERIC) ||
			($compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
			self::$compatibilityMode = $compatibilityMode;
			return True;
		}
		return False;
	}

	/**
	 * Return the current Compatibility Mode
	 *
	 * @return	 string		$compatibilityMode		Compatibility Mode
	 */
	public static function getCompatibilityMode() {
		return self::$compatibilityMode;
	}

	/**
	 * Set the Return Date Format (Excel, PHP Serialized or PHP Object)
	 *
	 * @param	 integer	$returnDateType			Return Date Format
	 * @return	 boolean							Success or failure
	 */
	public static function setReturnDateType($returnDateType) {
		if (($returnDateType == self::RETURNDATE_PHP_NUMERIC) ||
			($returnDateType == self::RETURNDATE_PHP_OBJECT) ||
			($returnDateType == self::RETURNDATE_EXCEL)) {
			self::$ReturnDateType = $returnDateType;
			return True;
		}
		return False;
	}	//	function setReturnDateType()


	/**
	 * Return the Return Date Format (Excel, PHP Serialized or PHP Object)
	 *
	 * @return	 integer	$returnDateType			Return Date Format
	 */
	public static function getReturnDateType() {
		return self::$ReturnDateType;
	}	//	function getReturnDateType()


	/**
	 * DUMMY
	 *
	 * @return  string	#NAME?
	 */
	public static function DUMMY() {
		return self::$_errorCodes['name'];
	}

	/**
	 * NA
	 *
	 * @return  string	#N/A!
	 */
	public static function NA() {
		return self::$_errorCodes['na'];
	}

	/**
	 * LOGICAL_AND
	 *
	 * Returns boolean TRUE if all its arguments are TRUE; returns FALSE if one or more argument is FALSE.
	 *
	 *	Booleans arguments are treated as True or False as appropriate
	 *	Integer or floating point arguments are treated as True, except for 0 or 0.0 which are False
	 *	If any argument value is a string, or a Null, it is ignored
	 *
	 *	Quirk of Excel:
	 *		String values passed directly to the function rather than through a cell reference
	 *			e.g.=AND(1,"A",1)
	 *		will return a #VALUE! error, _not_ ignoring the string.
	 *		This behaviour is not replicated
	 *
	 * @param	array of mixed		Data Series
	 * @return  boolean
	 */
	public static function LOGICAL_AND() {
		// Return value
		$returnValue = True;

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		$argCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a boolean value?
			if (is_bool($arg)) {
				$returnValue = $returnValue && $arg;
				$argCount++;
			} elseif ((is_numeric($arg)) && (!is_string($arg))) {
				$returnValue = $returnValue && ($arg != 0);
				$argCount++;
			}
		}

		// Return
		if ($argCount == 0) {
			return self::$_errorCodes['value'];
		}
		return $returnValue;
	}

	/**
	 * LOGICAL_OR
	 *
	 * Returns boolean TRUE if any argument is TRUE; returns FALSE if all arguments are FALSE.
	 *
	 *	Booleans arguments are treated as True or False as appropriate
	 *	Integer or floating point arguments are treated as True, except for 0 or 0.0 which are False
	 *	If any argument value is a string, or a Null, it is ignored
	 *
	 * @param	array of mixed		Data Series
	 * @return  boolean
	 */
	public static function LOGICAL_OR() {
		// Return value
		$returnValue = False;

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		$argCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a boolean value?
			if (is_bool($arg)) {
				$returnValue = $returnValue || $arg;
				$argCount++;
			} elseif ((is_numeric($arg)) && (!is_string($arg))) {
				$returnValue = $returnValue || ($arg != 0);
				$argCount++;
			}
		}

		// Return
		if ($argCount == 0) {
			return self::$_errorCodes['value'];
		}
		return $returnValue;
	}

	/**
	 * LOGICAL_FALSE
	 *
	 * Returns FALSE.
	 *
	 * @return  boolean
	 */
	public static function LOGICAL_FALSE() {
		return False;
	}

	/**
	 * LOGICAL_TRUE
	 *
	 * Returns TRUE.
	 *
	 * @return  boolean
	 */
	public static function LOGICAL_TRUE() {
		return True;
	}

	/**
	 * ATAN2
	 *
	 * This function calculates the arc tangent of the two variables x and y. It is similar to
	 *		calculating the arc tangent of y / x, except that the signs of both arguments are used
	 *		to determine the quadrant of the result.
	 * Note that Excel reverses the arguments, so we need to reverse them here before calling the
	 *		standard PHP atan() function
	 *
	 * @param	float	$x		Number
	 * @param	float	$y		Number
	 * @return  float	Square Root of Number * Pi
	 */
	public static function REVERSE_ATAN2($x, $y) {
		$x	= self::flattenSingleValue($x);
		$y	= self::flattenSingleValue($y);

		return atan2($y, $x);
	}

	/**
	 * SUM
	 *
	 * SUM computes the sum of all the values and cells referenced in the argument list.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function SUM() {
		// Return value
		$returnValue = 0;

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$returnValue += $arg;
			}
		}

		// Return
		return $returnValue;
	}

	/**
	 * SUMSQ
	 *
	 * Returns the sum of the squares of the arguments
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function SUMSQ() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$returnValue += pow($arg,2);
			}
		}

		// Return
		return $returnValue;
	}

	/**
	 * PRODUCT
	 *
	 * PRODUCT returns the product of all the values and cells referenced in the argument list.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function PRODUCT() {
		// Return value
		$returnValue = null;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if (is_null($returnValue)) {
					$returnValue = $arg;
				} else {
					$returnValue *= $arg;
				}
			}
		}

		// Return
		if (is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}

	/**
	 * QUOTIENT
	 *
	 * QUOTIENT function returns the integer portion of a division.numerator is the divided number
	 * and denominator is the divisor.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function QUOTIENT() {
		// Return value
		$returnValue = null;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if (is_null($returnValue)) {
					if (($returnValue == 0) || ($arg == 0)) {
						$returnValue = 0;
					} else {
						$returnValue = $arg;
					}
				} else {
					if (($returnValue == 0) || ($arg == 0)) {
						$returnValue = 0;
					} else {
						$returnValue /= $arg;
					}
				}
			}
		}

		// Return
		return intval($returnValue);
	}

	/**
	 * MIN
	 *
	 * MIN returns the value of the element of the values passed that has the smallest value,
	 * with negative numbers considered smaller than positive numbers.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function MIN() {
		// Return value
		$returnValue = null;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if ((is_null($returnValue)) || ($arg < $returnValue)) {
					$returnValue = $arg;
				}
			}
		}

		// Return
		if(is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}

	/**
	 * MINA
	 *
	 * Returns the smallest value in a list of arguments, including numbers, text, and logical values
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function MINA() {
		// Return value
		$returnValue = null;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				if ((is_null($returnValue)) || ($arg < $returnValue)) {
					$returnValue = $arg;
				}
			}
		}

		// Return
		if(is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}

	/**
	 * SMALL
	 *
	 * Returns the nth smallest value in a data set. You can use this function to
	 * select a value based on its relative standing.
	 *
	 * @param	array of mixed		Data Series
	 * @param	float	Entry in the series to return
	 * @return	float
	 */
	public static function SMALL() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$n = array_pop($aArgs);

		if ((is_numeric($n)) && (!is_string($n))) {
			$mArgs = array();
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$mArgs[] = $arg;
				}
			}
			$count = self::COUNT($mArgs);
			$n = floor(--$n);
			if (($n < 0) || ($n >= $count) || ($count == 0)) {
				return self::$_errorCodes['num'];
			}
			sort($mArgs);
			return $mArgs[$n];
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * MAX
	 *
	 * MAX returns the value of the element of the values passed that has the highest value,
	 * with negative numbers considered smaller than positive numbers.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function MAX() {
		// Return value
		$returnValue = null;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if ((is_null($returnValue)) || ($arg > $returnValue)) {
					$returnValue = $arg;
				}
			}
		}

		// Return
		if(is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}

	/**
	 * MAXA
	 *
	 * Returns the greatest value in a list of arguments, including numbers, text, and logical values
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function MAXA() {
		// Return value
		$returnValue = null;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				if ((is_null($returnValue)) || ($arg > $returnValue)) {
					$returnValue = $arg;
				}
			}
		}

		// Return
		if(is_null($returnValue)) {
			return 0;
		}
		return $returnValue;
	}

	/**
	 * LARGE
	 *
	 * Returns the nth largest value in a data set. You can use this function to
	 * select a value based on its relative standing.
	 *
	 * @param	array of mixed		Data Series
	 * @param	float	Entry in the series to return
	 * @return	float
	 *
	 */
	public static function LARGE() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$n = floor(array_pop($aArgs));

		if ((is_numeric($n)) && (!is_string($n))) {
			$mArgs = array();
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$mArgs[] = $arg;
				}
			}
			$count = self::COUNT($mArgs);
			$n = floor(--$n);
			if (($n < 0) || ($n >= $count) || ($count == 0)) {
				return self::$_errorCodes['num'];
			}
			rsort($mArgs);
			return $mArgs[$n];
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * PERCENTILE
	 *
	 * Returns the nth percentile of values in a range..
	 *
	 * @param	array of mixed		Data Series
	 * @param	float	$entry		Entry in the series to return
	 * @return	float
	 */
	public static function PERCENTILE() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$entry = array_pop($aArgs);

		if ((is_numeric($entry)) && (!is_string($entry))) {
			if (($entry < 0) || ($entry > 1)) {
				return self::$_errorCodes['num'];
			}
			$mArgs = array();
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$mArgs[] = $arg;
				}
			}
			$mValueCount = count($mArgs);
			if ($mValueCount > 0) {
				sort($mArgs);
				$count = self::COUNT($mArgs);
				$index = $entry * ($count-1);
				$iBase = floor($index);
				if ($index == $iBase) {
					return $mArgs[$index];
				} else {
					$iNext = $iBase + 1;
					$iProportion = $index - $iBase;
					return $mArgs[$iBase] + (($mArgs[$iNext] - $mArgs[$iBase]) * $iProportion) ;
				}
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * QUARTILE
	 *
	 * Returns the quartile of a data set.
	 *
	 * @param	array of mixed		Data Series
	 * @param	float	$entry		Entry in the series to return
	 * @return	float
	 */
	public static function QUARTILE() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$entry = floor(array_pop($aArgs));

		if ((is_numeric($entry)) && (!is_string($entry))) {
			$entry /= 4;
			if (($entry < 0) || ($entry > 1)) {
				return self::$_errorCodes['num'];
			}
			return self::PERCENTILE($aArgs,$entry);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * COUNT
	 *
	 * Counts the number of cells that contain numbers within the list of arguments
	 *
	 * @param	array of mixed		Data Series
	 * @return  int
	 */
	public static function COUNT() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			if ((is_bool($arg)) && (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
				$arg = (int) $arg;
			}
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				++$returnValue;
			}
		}

		// Return
		return $returnValue;
	}

	/**
	 * COUNTBLANK
	 *
	 * Counts the number of empty cells within the list of arguments
	 *
	 * @param	array of mixed		Data Series
	 * @return  int
	 */
	public static function COUNTBLANK() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a blank cell?
			if ((is_null($arg)) || ((is_string($arg)) && ($arg == ''))) {
				++$returnValue;
			}
		}

		// Return
		return $returnValue;
	}

	/**
	 * COUNTA
	 *
	 * Counts the number of cells that are not empty within the list of arguments
	 *
	 * @param	array of mixed		Data Series
	 * @return  int
	 */
	public static function COUNTA() {
		// Return value
		$returnValue = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric, boolean or string value?
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
				++$returnValue;
			}
		}

		// Return
		return $returnValue;
	}

	/**
	 * AVERAGE
	 *
	 * Returns the average (arithmetic mean) of the arguments
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function AVERAGE() {
		// Return value
		$returnValue = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			if ((is_bool($arg))  && (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
				$arg = (integer) $arg;
			}
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if (is_null($returnValue)) {
					$returnValue = $arg;
				} else {
					$returnValue += $arg;
				}
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			return $returnValue / $aCount;
		} else {
			return self::$_errorCodes['divisionbyzero'];
		}
	}

	/**
	 * AVERAGEA
	 *
	 * Returns the average of its arguments, including numbers, text, and logical values
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function AVERAGEA() {
		// Return value
		$returnValue = null;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				if (is_null($returnValue)) {
					$returnValue = $arg;
				} else {
					$returnValue += $arg;
				}
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			return $returnValue / $aCount;
		} else {
			return self::$_errorCodes['divisionbyzero'];
		}
	}

	/**
	 * MEDIAN
	 *
	 * Returns the median of the given numbers. The median is the number in the middle of a set of numbers.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function MEDIAN() {
		// Return value
		$returnValue = self::$_errorCodes['num'];

		$mArgs = array();
		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$mArgs[] = $arg;
			}
		}

		$mValueCount = count($mArgs);
		if ($mValueCount > 0) {
			sort($mArgs,SORT_NUMERIC);
			$mValueCount = $mValueCount / 2;
			if ($mValueCount == floor($mValueCount)) {
				$returnValue = ($mArgs[$mValueCount--] + $mArgs[$mValueCount]) / 2;
			} else {
				$mValueCount == floor($mValueCount);
				$returnValue = $mArgs[$mValueCount];
			}
		}

		// Return
		return $returnValue;
	}

	//
	//	Special variant of array_count_values that isn't limited to strings and integers,
	//		but can work with floating point numbers as values
	//
	private static function modeCalc($data) {
		$frequencyArray = array();
		foreach($data as $datum) {
			$found = False;
			foreach($frequencyArray as $key => $value) {
				if ((string)$value['value'] == (string)$datum) {
					++$frequencyArray[$key]['frequency'];
					$found = True;
					break;
				}
			}
			if (!$found) {
				$frequencyArray[] = array('value'		=> $datum,
										  'frequency'	=>	1 );
			}
		}

		foreach($frequencyArray as $key => $value) {
			$frequencyList[$key] = $value['frequency'];
			$valueList[$key] = $value['value'];
		}
		array_multisort($frequencyList, SORT_DESC, $valueList, SORT_ASC, SORT_NUMERIC, $frequencyArray);

		if ($frequencyArray[0]['frequency'] == 1) {
			return self::NA();
		}
		return $frequencyArray[0]['value'];
	}

	/**
	 * MODE
	 *
	 * Returns the most frequently occurring, or repetitive, value in an array or range of data
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function MODE() {
		// Return value
		$returnValue = self::NA();

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());

		$mArgs = array();
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$mArgs[] = $arg;
			}
		}

		if (count($mArgs) > 0) {
			return self::modeCalc($mArgs);
		}

		// Return
		return $returnValue;
	}

	/**
	 * DEVSQ
	 *
	 * Returns the sum of squares of deviations of data points from their sample mean.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function DEVSQ() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGE(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = -1;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_bool($arg))  && (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
					$arg = (int) $arg;
				}
				if ((is_numeric($arg)) && (!is_string($arg))) {
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (is_null($returnValue)) {
				return self::$_errorCodes['num'];
			} else {
				return $returnValue;
			}
		}
		return self::NA();
	}

	/**
	 * AVEDEV
	 *
	 * Returns the average of the absolute deviations of data points from their mean.
	 * AVEDEV is a measure of the variability in a data set.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function AVEDEV() {
		$aArgs = self::flattenArray(func_get_args());

		// Return value
		$returnValue = null;

		$aMean = self::AVERAGE($aArgs);
		if ($aMean != self::$_errorCodes['divisionbyzero']) {
			$aCount = 0;
			foreach ($aArgs as $arg) {
				if ((is_bool($arg))  && (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE)) {
					$arg = (integer) $arg;
				}
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					if (is_null($returnValue)) {
						$returnValue = abs($arg - $aMean);
					} else {
						$returnValue += abs($arg - $aMean);
					}
					++$aCount;
				}
			}

			// Return
			return $returnValue / $aCount ;
		}
		return self::$_errorCodes['num'];
	}

	/**
	 * GEOMEAN
	 *
	 * Returns the geometric mean of an array or range of positive data. For example, you
	 * can use GEOMEAN to calculate average growth rate given compound interest with
	 * variable rates.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function GEOMEAN() {
		$aMean = self::PRODUCT(func_get_args());
		if (is_numeric($aMean) && ($aMean > 0)) {
			$aArgs = self::flattenArray(func_get_args());
			$aCount = self::COUNT($aArgs) ;
			if (self::MIN($aArgs) > 0) {
				return pow($aMean, (1 / $aCount));
			}
		}
		return self::$_errorCodes['num'];
	}

	/**
	 * HARMEAN
	 *
	 * Returns the harmonic mean of a data set. The harmonic mean is the reciprocal of the
	 * arithmetic mean of reciprocals.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function HARMEAN() {
		// Return value
		$returnValue = self::NA();

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		if (self::MIN($aArgs) < 0) {
			return self::$_errorCodes['num'];
		}
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				if ($arg <= 0) {
					return self::$_errorCodes['num'];
				}
				if (is_null($returnValue)) {
					$returnValue = (1 / $arg);
				} else {
					$returnValue += (1 / $arg);
				}
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			return 1 / ($returnValue / $aCount);
		} else {
			return $returnValue;
		}
	}

	/**
	 * TRIMMEAN
	 *
	 * Returns the mean of the interior of a data set. TRIMMEAN calculates the mean
	 * taken by excluding a percentage of data points from the top and bottom tails
	 * of a data set.
	 *
	 * @param	array of mixed		Data Series
	 * @param	float	Percentage to discard
	 * @return	float
	 */
	public static function TRIMMEAN() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$percent = array_pop($aArgs);

		if ((is_numeric($percent)) && (!is_string($percent))) {
			if (($percent < 0) || ($percent > 1)) {
				return self::$_errorCodes['num'];
			}
			$mArgs = array();
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$mArgs[] = $arg;
				}
			}
			$discard = floor(self::COUNT($mArgs) * $percent / 2);
			sort($mArgs);
			for ($i=0; $i < $discard; ++$i) {
				array_pop($mArgs);
				array_shift($mArgs);
			}
			return self::AVERAGE($mArgs);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * STDEV
	 *
	 * Estimates standard deviation based on a sample. The standard deviation is a measure of how
	 * widely values are dispersed from the average value (the mean).
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function STDEV() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGE(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = -1;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (($aCount > 0) && ($returnValue > 0)) {
				return sqrt($returnValue / $aCount);
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}

	/**
	 * STDEVA
	 *
	 * Estimates standard deviation based on a sample, including numbers, text, and logical values
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function STDEVA() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGEA(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = -1;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) & ($arg != '')))) {
					if (is_bool($arg)) {
						$arg = (integer) $arg;
					} elseif (is_string($arg)) {
						$arg = 0;
					}
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (($aCount > 0) && ($returnValue > 0)) {
				return sqrt($returnValue / $aCount);
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}

	/**
	 * STDEVP
	 *
	 * Calculates standard deviation based on the entire population
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function STDEVP() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGE(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = 0;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (($aCount > 0) && ($returnValue > 0)) {
				return sqrt($returnValue / $aCount);
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}

	/**
	 * STDEVPA
	 *
	 * Calculates standard deviation based on the entire population, including numbers, text, and logical values
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function STDEVPA() {
		// Return value
		$returnValue = null;

		$aMean = self::AVERAGEA(func_get_args());
		if (!is_null($aMean)) {
			$aArgs = self::flattenArray(func_get_args());

			$aCount = 0;
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) & ($arg != '')))) {
					if (is_bool($arg)) {
						$arg = (integer) $arg;
					} elseif (is_string($arg)) {
						$arg = 0;
					}
					if (is_null($returnValue)) {
						$returnValue = pow(($arg - $aMean),2);
					} else {
						$returnValue += pow(($arg - $aMean),2);
					}
					++$aCount;
				}
			}

			// Return
			if (($aCount > 0) && ($returnValue > 0)) {
				return sqrt($returnValue / $aCount);
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}

	/**
	 * VARFunc
	 *
	 * Estimates variance based on a sample.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function VARFunc() {
		// Return value
		$returnValue = self::$_errorCodes['divisionbyzero'];

		$summerA = $summerB = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$summerA += ($arg * $arg);
				$summerB += $arg;
				++$aCount;
			}
		}

		// Return
		if ($aCount > 1) {
			$summerA = $summerA * $aCount;
			$summerB = ($summerB * $summerB);
			$returnValue = ($summerA - $summerB) / ($aCount * ($aCount - 1));
		}
		return $returnValue;
	}

	/**
	 * VARA
	 *
	 * Estimates variance based on a sample, including numbers, text, and logical values
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function VARA() {
		// Return value
		$returnValue = self::$_errorCodes['divisionbyzero'];

		$summerA = $summerB = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
				if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) & ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				$summerA += ($arg * $arg);
				$summerB += $arg;
				++$aCount;
			}
		}

		// Return
		if ($aCount > 1) {
			$summerA = $summerA * $aCount;
			$summerB = ($summerB * $summerB);
			$returnValue = ($summerA - $summerB) / ($aCount * ($aCount - 1));
		}
		return $returnValue;
	}

	/**
	 * VARP
	 *
	 * Calculates variance based on the entire population
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function VARP() {
		// Return value
		$returnValue = self::$_errorCodes['divisionbyzero'];

		$summerA = $summerB = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$summerA += ($arg * $arg);
				$summerB += $arg;
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			$summerA = $summerA * $aCount;
			$summerB = ($summerB * $summerB);
			$returnValue = ($summerA - $summerB) / ($aCount * $aCount);
		}
		return $returnValue;
	}

	/**
	 * VARPA
	 *
	 * Calculates variance based on the entire population, including numbers, text, and logical values
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function VARPA() {
		// Return value
		$returnValue = self::$_errorCodes['divisionbyzero'];

		$summerA = $summerB = 0;

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$aCount = 0;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) & ($arg != '')))) {
				if (is_bool($arg)) {
					$arg = (integer) $arg;
				} elseif (is_string($arg)) {
					$arg = 0;
				}
				$summerA += ($arg * $arg);
				$summerB += $arg;
				++$aCount;
			}
		}

		// Return
		if ($aCount > 0) {
			$summerA = $summerA * $aCount;
			$summerB = ($summerB * $summerB);
			$returnValue = ($summerA - $summerB) / ($aCount * $aCount);
		}
		return $returnValue;
	}

	/**
	 * SUBTOTAL
	 *
	 * Returns a subtotal in a list or database.
	 *
	 * @param	int		the number 1 to 11 that specifies which function to
	 *					use in calculating subtotals within a list.
	 * @param	array of mixed		Data Series
	 * @return	float
	 */
	public static function SUBTOTAL() {
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$subtotal = array_shift($aArgs);

		if ((is_numeric($subtotal)) && (!is_string($subtotal))) {
			switch($subtotal) {
				case 1	:
					return self::AVERAGE($aArgs);
					break;
				case 2	:
					return self::COUNT($aArgs);
					break;
				case 3	:
					return self::COUNTA($aArgs);
					break;
				case 4	:
					return self::MAX($aArgs);
					break;
				case 5	:
					return self::MIN($aArgs);
					break;
				case 6	:
					return self::PRODUCT($aArgs);
					break;
				case 7	:
					return self::STDEV($aArgs);
					break;
				case 8	:
					return self::STDEVP($aArgs);
					break;
				case 9	:
					return self::SUM($aArgs);
					break;
				case 10	:
					return self::VARFunc($aArgs);
					break;
				case 11	:
					return self::VARP($aArgs);
					break;
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * SQRTPI
	 *
	 * Returns the square root of (number * pi).
	 *
	 * @param	float	$number		Number
	 * @return  float	Square Root of Number * Pi
	 */
	public static function SQRTPI($number) {
		$number	= self::flattenSingleValue($number);

		if (is_numeric($number)) {
			if ($number < 0) {
				return self::$_errorCodes['num'];
			}
			return sqrt($number * pi()) ;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * FACT
	 *
	 * Returns the factorial of a number.
	 *
	 * @param	float	$factVal	Factorial Value
	 * @return  int		Factorial
	 */
	public static function FACT($factVal) {
		$factVal	= self::flattenSingleValue($factVal);

		if (is_numeric($factVal)) {
			if ($factVal < 0) {
				return self::$_errorCodes['num'];
			}
			$factLoop = floor($factVal);
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				if ($factVal > $factLoop) {
					return self::$_errorCodes['num'];
				}
			}
			$factorial = 1;
			while ($factLoop > 1) {
				$factorial *= $factLoop--;
			}
			return $factorial ;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * FACTDOUBLE
	 *
	 * Returns the double factorial of a number.
	 *
	 * @param	float	$factVal	Factorial Value
	 * @return  int		Double Factorial
	 */
	public static function FACTDOUBLE($factVal) {
		$factLoop	= floor(self::flattenSingleValue($factVal));

		if (is_numeric($factLoop)) {
			if ($factVal < 0) {
				return self::$_errorCodes['num'];
			}
			$factorial = 1;
			while ($factLoop > 1) {
				$factorial *= $factLoop--;
				--$factLoop;
			}
			return $factorial ;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * MULTINOMIAL
	 *
	 * Returns the ratio of the factorial of a sum of values to the product of factorials.
	 *
	 * @param	array of mixed		Data Series
	 * @return  float
	 */
	public static function MULTINOMIAL() {

		// Loop through arguments
		$aArgs = self::flattenArray(func_get_args());
		$summer = 0;
		$divisor = 1;
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if (is_numeric($arg)) {
				if ($arg < 1) {
					return self::$_errorCodes['num'];
				}
				$summer += floor($arg);
				$divisor *= self::FACT($arg);
			} else {
				return self::$_errorCodes['value'];
			}
		}

		// Return
		if ($summer > 0) {
			$summer = self::FACT($summer);
			return $summer / $divisor;
		}
		return 0;
	}

	/**
	 * CEILING
	 *
	 * Returns number rounded up, away from zero, to the nearest multiple of significance.
	 *
	 * @param	float	$number			Number to round
	 * @param	float	$significance	Significance
	 * @return  float	Rounded Number
	 */
	public static function CEILING($number,$significance=null) {
		$number			= self::flattenSingleValue($number);
		$significance	= self::flattenSingleValue($significance);

		if ((is_null($significance)) && (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC)) {
			$significance = $number/abs($number);
		}

		if ((is_numeric($number)) && (is_numeric($significance))) {
			if (self::SIGN($number) == self::SIGN($significance)) {
				if ($significance == 0.0) {
					return 0;
				}
				return ceil($number / $significance) * $significance;
			} else {
				return self::$_errorCodes['num'];
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * EVEN
	 *
	 * Returns number rounded up to the nearest even integer.
	 *
	 * @param	float	$number			Number to round
	 * @return  int		Rounded Number
	 */
	public static function EVEN($number) {
		$number	= self::flattenSingleValue($number);

		if (is_numeric($number)) {
			$significance = 2 * self::SIGN($number);
			return self::CEILING($number,$significance);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * ODD
	 *
	 * Returns number rounded up to the nearest odd integer.
	 *
	 * @param	float	$number			Number to round
	 * @return  int		Rounded Number
	 */
	public static function ODD($number) {
		$number	= self::flattenSingleValue($number);

		if (is_numeric($number)) {
			$significance = self::SIGN($number);
			if ($significance == 0) {
				return 1;
			}
			$result = self::CEILING($number,$significance);
			if (self::IS_EVEN($result)) {
				$result += $significance;
			}
			return $result;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * ROUNDUP
	 *
	 * Rounds a number up to a specified number of decimal places
	 *
	 * @param	float	$number			Number to round
	 * @param	int		$digits			Number of digits to which you want to round $number
	 * @return  float	Rounded Number
	 */
	public static function ROUNDUP($number,$digits) {
		$number	= self::flattenSingleValue($number);
		$digits	= self::flattenSingleValue($digits);

		if (is_numeric($number)) {
			if ((is_numeric($digits)) && ($digits >= 0)) {
				$significance = pow(10,$digits);
				return ceil($number * $significance) / $significance;
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * ROUNDDOWN
	 *
	 * Rounds a number down to a specified number of decimal places
	 *
	 * @param	float	$number			Number to round
	 * @param	int		$digits			Number of digits to which you want to round $number
	 * @return  float	Rounded Number
	 */
	public static function ROUNDDOWN($number,$digits) {
		$number	= self::flattenSingleValue($number);
		$digits	= self::flattenSingleValue($digits);

		if (is_numeric($number)) {
			if ((is_numeric($digits)) && ($digits >= 0)) {
				$significance = pow(10,$digits);
				return floor($number * $significance) / $significance;
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * MROUND
	 *
	 * Rounds a number to the nearest multiple of a specified value
	 *
	 * @param	float	$number			Number to round
	 * @param	int		$multiple		Multiple to which you want to round $number
	 * @return  float	Rounded Number
	 */
	public static function MROUND($number,$multiple) {
		$number		= self::flattenSingleValue($number);
		$multiple	= self::flattenSingleValue($multiple);

		if ((is_numeric($number)) && (is_numeric($multiple))) {
			if ((self::SIGN($number)) == (self::SIGN($multiple))) {
				$lowerVal = floor($number / $multiple) * $multiple;
				$upperVal = ceil($number / $multiple) * $multiple;
				$adjustUp = abs($number - $upperVal);
				$adjustDown = abs($number - $lowerVal) + PRECISION;
				if ($adjustDown < $adjustUp) {
					return $lowerVal;
				}
				return $upperVal;
			}
			return self::$_errorCodes['num'];
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * SIGN
	 *
	 * Determines the sign of a number. Returns 1 if the number is positive, zero (0)
	 * if the number is 0, and -1 if the number is negative.
	 *
	 * @param	float	$number			Number to round
	 * @return  int		sign value
	 */
	public static function SIGN($number) {
		$number	= self::flattenSingleValue($number);

		if (is_numeric($number)) {
			if ($number == 0.0) {
				return 0;
			}
			return $number / abs($number);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * FLOOR
	 *
	 * Rounds number down, toward zero, to the nearest multiple of significance.
	 *
	 * @param	float	$number			Number to round
	 * @param	float	$significance	Significance
	 * @return  float	Rounded Number
	 */
	public static function FLOOR($number,$significance=null) {
		$number			= self::flattenSingleValue($number);
		$significance	= self::flattenSingleValue($significance);

		if ((is_null($significance)) && (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC)) {
			$significance = $number/abs($number);
		}

		if ((is_numeric($number)) && (is_numeric($significance))) {
			if ((float) $significance == 0.0) {
				return self::$_errorCodes['divisionbyzero'];
			}
			if (self::SIGN($number) == self::SIGN($significance)) {
				return floor($number / $significance) * $significance;
			} else {
				return self::$_errorCodes['num'];
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * PERMUT
	 *
	 * Returns the number of permutations for a given number of objects that can be
	 * selected from number objects. A permutation is any set or subset of objects or
	 * events where internal order is significant. Permutations are different from
	 * combinations, for which the internal order is not significant. Use this function
	 * for lottery-style probability calculations.
	 *
	 * @param	int		$numObjs	Number of different objects
	 * @param	int		$numInSet	Number of objects in each permutation
	 * @return  int		Number of permutations
	 */
	public static function PERMUT($numObjs,$numInSet) {
		$numObjs	= self::flattenSingleValue($numObjs);
		$numInSet	= self::flattenSingleValue($numInSet);

		if ((is_numeric($numObjs)) && (is_numeric($numInSet))) {
			if ($numObjs < $numInSet) {
				return self::$_errorCodes['num'];
			}
			return self::FACT($numObjs) / self::FACT($numObjs - $numInSet);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * COMBIN
	 *
	 * Returns the number of combinations for a given number of items. Use COMBIN to
	 * determine the total possible number of groups for a given number of items.
	 *
	 * @param	int		$numObjs	Number of different objects
	 * @param	int		$numInSet	Number of objects in each combination
	 * @return  int		Number of combinations
	 */
	public static function COMBIN($numObjs,$numInSet) {
		$numObjs	= self::flattenSingleValue($numObjs);
		$numInSet	= self::flattenSingleValue($numInSet);

		if ((is_numeric($numObjs)) && (is_numeric($numInSet))) {
			if ($numObjs < $numInSet) {
				return self::$_errorCodes['num'];
			} elseif ($numInSet < 0) {
				return self::$_errorCodes['num'];
			}
			return (self::FACT($numObjs) / self::FACT($numObjs - $numInSet)) / self::FACT($numInSet);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * SERIESSUM
	 *
	 * Returns the sum of a power series
	 *
	 * @param	float			$x	Input value to the power series
	 * @param	float			$n	Initial power to which you want to raise $x
	 * @param	float			$m	Step by which to increase $n for each term in the series
	 * @param	array of mixed		Data Series
	 * @return	float
	 */
	public static function SERIESSUM() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());

		$x = array_shift($aArgs);
		$n = array_shift($aArgs);
		$m = array_shift($aArgs);

		if ((is_numeric($x)) && (is_numeric($n)) && (is_numeric($m))) {
			// Calculate
			$i = 0;
			foreach($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$returnValue += $arg * pow($x,$n + ($m * $i++));
				} else {
					return self::$_errorCodes['value'];
				}
			}
			// Return
			return $returnValue;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * STANDARDIZE
	 *
	 * Returns a normalized value from a distribution characterized by mean and standard_dev.
	 *
	 * @param	float	$value		Value to normalize
	 * @param	float	$mean		Mean Value
	 * @param	float	$stdDev		Standard Deviation
	 * @return  float	Standardized value
	 */
	public static function STANDARDIZE($value,$mean,$stdDev) {
		$value	= self::flattenSingleValue($value);
		$mean	= self::flattenSingleValue($mean);
		$stdDev	= self::flattenSingleValue($stdDev);

		if ((is_numeric($value)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if ($stdDev <= 0) {
				return self::$_errorCodes['num'];
			}
			return ($value - $mean) / $stdDev ;
		}
		return self::$_errorCodes['value'];
	}

	//
	//	Private method to return an array of the factors of the input value
	//
	private static function factors($value) {
		$startVal = floor($value/2);

		$factorArray = array();
		for($i=$startVal; $i>1; --$i) {
			if (($value/$i) == floor($value/$i)) {
				$subFactors = self::factors($i);
				if ($i == sqrt($value)) {
					$factorArray = array_merge($factorArray,$subFactors,$subFactors);
				} else {
					$value /= $i;
					$factorArray = array_merge($factorArray,$subFactors);
				}
			}
		}
		if (count($factorArray) > 0) {
			return $factorArray;
		} else {
			return array((integer)$value);
		}
	}

	/**
	 * LCM
	 *
	 * Returns the lowest common multiplier of a series of numbers
	 *
	 * @param	$array	Values to calculate the Lowest Common Multiplier
	 * @return  int		Lowest Common Multiplier
	 */
	public static function LCM() {
		$aArgs = self::flattenArray(func_get_args());

		$returnValue = 1;
		$allPoweredFactors = array();
		foreach($aArgs as $value) {
			if (!is_numeric($value)) {
				return self::$_errorCodes['value'];
			}
			if ($value < 1) {
				return self::$_errorCodes['num'];
			}
			$myFactors = self::factors(floor($value));
			$myCountedFactors = array_count_values($myFactors);
			$myPoweredFactors = array();
			foreach($myCountedFactors as $myCountedFactor => $myCountedPower) {
				$myPoweredFactors[$myCountedFactor] = pow($myCountedFactor,$myCountedPower);
			}
			foreach($myPoweredFactors as $myPoweredValue => $myPoweredFactor) {
				if (array_key_exists($myPoweredValue,$allPoweredFactors)) {
					if ($allPoweredFactors[$myPoweredValue] < $myPoweredFactor) {
						$allPoweredFactors[$myPoweredValue] = $myPoweredFactor;
					}
				} else {
					$allPoweredFactors[$myPoweredValue] = $myPoweredFactor;
				}
			}
		}
		foreach($allPoweredFactors as $allPoweredFactor) {
			$returnValue *= (integer) $allPoweredFactor;
		}
		return $returnValue;
	}

	/**
	 * GCD
	 *
	 * Returns the greatest common divisor of a series of numbers
	 *
	 * @param	$array	Values to calculate the Greatest Common Divisor
	 * @return  int		Greatest Common Divisor
	 */
	public static function GCD() {
		$aArgs = self::flattenArray(func_get_args());

		$returnValue = 1;
		$allPoweredFactors = array();
		foreach($aArgs as $value) {
			if ($value == 0) {
				return 0;
			}
			$myFactors = self::factors($value);
			$myCountedFactors = array_count_values($myFactors);
			$allValuesFactors[] = $myCountedFactors;
		}
		$allValuesCount = count($allValuesFactors);
		$mergedArray = $allValuesFactors[0];
		for ($i=1;$i < $allValuesCount; ++$i) {
			$mergedArray = array_intersect_key($mergedArray,$allValuesFactors[$i]);
		}
		$mergedArrayValues = count($mergedArray);
		if ($mergedArrayValues == 0) {
			return $returnValue;
		} elseif ($mergedArrayValues > 1) {
			foreach($mergedArray as $mergedKey => $mergedValue) {
				foreach($allValuesFactors as $highestPowerTest) {
					foreach($highestPowerTest as $testKey => $testValue) {
						if (($testKey == $mergedKey) && ($testValue < $mergedValue)) {
							$mergedArray[$mergedKey] = $testValue;
							$mergedValue = $testValue;
						}
					}
				}
			}

			$returnValue = 1;
			foreach($mergedArray as $key => $value) {
				$returnValue *= pow($key,$value);
			}
			return $returnValue;
		} else {
			$keys = array_keys($mergedArray);
			$key = $keys[0];
			$value = $mergedArray[$key];
			foreach($allValuesFactors as $testValue) {
				foreach($testValue as $mergedKey => $mergedValue) {
					if (($mergedKey == $key) && ($mergedValue < $value)) {
						$value = $mergedValue;
					}
				}
			}
			return pow($key,$value);
		}
	}

	/**
	 * BINOMDIST
	 *
	 * Returns the individual term binomial distribution probability. Use BINOMDIST in problems with
	 * a fixed number of tests or trials, when the outcomes of any trial are only success or failure,
	 * when trials are independent, and when the probability of success is constant throughout the
	 * experiment. For example, BINOMDIST can calculate the probability that two of the next three
	 * babies born are male.
	 *
	 * @param	float		$value			Number of successes in trials
	 * @param	float		$trials			Number of trials
	 * @param	float		$probability	Probability of success on each trial
	 * @param	boolean		$cumulative
	 * @return  float
	 *
	 * @todo	Cumulative distribution function
	 *
	 */
	public static function BINOMDIST($value, $trials, $probability, $cumulative) {
		$value			= floor(self::flattenSingleValue($value));
		$trials			= floor(self::flattenSingleValue($trials));
		$probability	= self::flattenSingleValue($probability);

		if ((is_numeric($value)) && (is_numeric($trials)) && (is_numeric($probability))) {
			if (($value < 0) || ($value > $trials)) {
				return self::$_errorCodes['num'];
			}
			if (($probability < 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					$summer = 0;
					for ($i = 0; $i <= $value; ++$i) {
						$summer += self::COMBIN($trials,$i) * pow($probability,$i) * pow(1 - $probability,$trials - $i);
					}
					return $summer;
				} else {
					return self::COMBIN($trials,$value) * pow($probability,$value) * pow(1 - $probability,$trials - $value) ;
				}
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * NEGBINOMDIST
	 *
	 * Returns the negative binomial distribution. NEGBINOMDIST returns the probability that
	 * there will be number_f failures before the number_s-th success, when the constant
	 * probability of a success is probability_s. This function is similar to the binomial
	 * distribution, except that the number of successes is fixed, and the number of trials is
	 * variable. Like the binomial, trials are assumed to be independent.
	 *
	 * @param	float		$failures		Number of Failures
	 * @param	float		$successes		Threshold number of Successes
	 * @param	float		$probability	Probability of success on each trial
	 * @return  float
	 *
	 */
	public static function NEGBINOMDIST($failures, $successes, $probability) {
		$failures		= floor(self::flattenSingleValue($failures));
		$successes		= floor(self::flattenSingleValue($successes));
		$probability	= self::flattenSingleValue($probability);

		if ((is_numeric($failures)) && (is_numeric($successes)) && (is_numeric($probability))) {
			if (($failures < 0) || ($successes < 1)) {
				return self::$_errorCodes['num'];
			}
			if (($probability < 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				if (($failures + $successes - 1) <= 0) {
					return self::$_errorCodes['num'];
				}
			}
			return (self::COMBIN($failures + $successes - 1,$successes - 1)) * (pow($probability,$successes)) * (pow(1 - $probability,$failures)) ;
		}
		return self::$_errorCodes['value'];
	}


	/**
	 * CRITBINOM
	 *
	 * Returns the smallest value for which the cumulative binomial distribution is greater
	 * than or equal to a criterion value
	 *
	 * See http://support.microsoft.com/kb/828117/ for details of the algorithm used
	 *
	 * @param	float		$trials			number of Bernoulli trials
	 * @param	float		$probability	probability of a success on each trial
	 * @param	float		$alpha			criterion value
	 * @return  int
	 *
	 *	@todo	Warning. This implementation differs from the algorithm detailed on the MS
	 *			web site in that $CumPGuessMinus1 = $CumPGuess - 1 rather than $CumPGuess - $PGuess
	 *			This eliminates a potential endless loop error, but may have an adverse affect on the
	 *			accuracy of the function (although all my tests have so far returned correct results).
	 *
	 */
	public static function CRITBINOM($trials, $probability, $alpha) {
		$trials			= floor(self::flattenSingleValue($trials));
		$probability	= self::flattenSingleValue($probability);
		$alpha			= self::flattenSingleValue($alpha);

		if ((is_numeric($trials)) && (is_numeric($probability)) && (is_numeric($alpha))) {
			if ($trials < 0) {
				return self::$_errorCodes['num'];
			}
			if (($probability < 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if (($alpha < 0) || ($alpha > 1)) {
				return self::$_errorCodes['num'];
			}
			if ($alpha <= 0.5) {
				$t = sqrt(log(1 / pow($alpha,2)));
				$trialsApprox = 0 - ($t + (2.515517 + 0.802853 * $t + 0.010328 * $t * $t) / (1 + 1.432788 * $t + 0.189269 * $t * $t + 0.001308 * $t * $t * $t));
			} else {
				$t = sqrt(log(1 / pow(1 - $alpha,2)));
				$trialsApprox = $t - (2.515517 + 0.802853 * $t + 0.010328 * $t * $t) / (1 + 1.432788 * $t + 0.189269 * $t * $t + 0.001308 * $t * $t * $t);
			}
			$Guess = floor($trials * $probability + $trialsApprox * sqrt($trials * $probability * (1 - $probability)));
			if ($Guess < 0) {
				$Guess = 0;
			} elseif ($Guess > $trials) {
				$Guess = $trials;
			}

			$TotalUnscaledProbability = $UnscaledPGuess = $UnscaledCumPGuess = 0.0;
			$EssentiallyZero = 10e-12;

			$m = floor($trials * $probability);
			++$TotalUnscaledProbability;
			if ($m == $Guess) { ++$UnscaledPGuess; }
			if ($m <= $Guess) { ++$UnscaledCumPGuess; }

			$PreviousValue = 1;
			$Done = False;
			$k = $m + 1;
			while ((!$Done) && ($k <= $trials)) {
				$CurrentValue = $PreviousValue * ($trials - $k + 1) * $probability / ($k * (1 - $probability));
				$TotalUnscaledProbability += $CurrentValue;
				if ($k == $Guess) { $UnscaledPGuess += $CurrentValue; }
				if ($k <= $Guess) { $UnscaledCumPGuess += $CurrentValue; }
				if ($CurrentValue <= $EssentiallyZero) { $Done = True; }
				$PreviousValue = $CurrentValue;
				++$k;
			}

			$PreviousValue = 1;
			$Done = False;
			$k = $m - 1;
			while ((!$Done) && ($k >= 0)) {
				$CurrentValue = $PreviousValue * $k + 1 * (1 - $probability) / (($trials - $k) * $probability);
				$TotalUnscaledProbability += $CurrentValue;
				if ($k == $Guess) { $UnscaledPGuess += $CurrentValue; }
				if ($k <= $Guess) { $UnscaledCumPGuess += $CurrentValue; }
				if (CurrentValue <= EssentiallyZero) { $Done = True; }
				$PreviousValue = $CurrentValue;
				--$k;
			}

			$PGuess = $UnscaledPGuess / $TotalUnscaledProbability;
			$CumPGuess = $UnscaledCumPGuess / $TotalUnscaledProbability;

//			$CumPGuessMinus1 = $CumPGuess - $PGuess;
			$CumPGuessMinus1 = $CumPGuess - 1;

			while (True) {
				if (($CumPGuessMinus1 < $alpha) && ($CumPGuess >= $alpha)) {
					return $Guess;
				} elseif (($CumPGuessMinus1 < $alpha) && ($CumPGuess < $alpha)) {
					$PGuessPlus1 = $PGuess * ($trials - $Guess) * $probability / $Guess / (1 - $probability);
					$CumPGuessMinus1 = $CumPGuess;
					$CumPGuess = $CumPGuess + $PGuessPlus1;
					$PGuess = $PGuessPlus1;
					++$Guess;
				} elseif (($CumPGuessMinus1 >= $alpha) && ($CumPGuess >= $alpha)) {
					$PGuessMinus1 = $PGuess * $Guess * (1 - $probability) / ($trials - $Guess + 1) / $probability;
					$CumPGuess = $CumPGuessMinus1;
					$CumPGuessMinus1 = $CumPGuessMinus1 - $PGuess;
					$PGuess = $PGuessMinus1;
					--$Guess;
				}
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * CHIDIST
	 *
	 * Returns the one-tailed probability of the chi-squared distribution.
	 *
	 * @param	float		$value			Value for the function
	 * @param	float		$degrees		degrees of freedom
	 * @return  float
	 */
	public static function CHIDIST($value, $degrees) {
		$value		= self::flattenSingleValue($value);
		$degrees	= floor(self::flattenSingleValue($degrees));

		if ((is_numeric($value)) && (is_numeric($degrees))) {
			if ($degrees < 1) {
				return self::$_errorCodes['num'];
			}
			if ($value < 0) {
				if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
					return 1;
				}
				return self::$_errorCodes['num'];
			}
			return 1 - (self::incompleteGamma($degrees/2,$value/2) / self::gamma($degrees/2));
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * CHIINV
	 *
	 * Returns the one-tailed probability of the chi-squared distribution.
	 *
	 * @param	float		$probability	Probability for the function
	 * @param	float		$degrees		degrees of freedom
	 * @return  float
	 */
	public static function CHIINV($probability, $degrees) {
		$probability	= self::flattenSingleValue($probability);
		$degrees		= floor(self::flattenSingleValue($degrees));

		if ((is_numeric($probability)) && (is_numeric($degrees))) {
			$xLo = 100;
			$xHi = 0;
			$maxIteration = 100;

			$x = $xNew = 1;
			$dx	= 1;
			$i = 0;

			while ((abs($dx) > PRECISION) && ($i++ < MAX_ITERATIONS)) {
				// Apply Newton-Raphson step
				$result = self::CHIDIST($x, $degrees);
				$error = $result - $probability;
				if ($error == 0.0) {
					$dx = 0;
				} elseif ($error < 0.0) {
					$xLo = $x;
				} else {
					$xHi = $x;
				}
				// Avoid division by zero
				if ($result != 0.0) {
					$dx = $error / $result;
					$xNew = $x - $dx;
				}
				// If the NR fails to converge (which for example may be the
				// case if the initial guess is too rough) we apply a bisection
				// step to determine a more narrow interval around the root.
				if (($xNew < $xLo) || ($xNew > $xHi) || ($result == 0.0)) {
					$xNew = ($xLo + $xHi) / 2;
					$dx = $xNew - $x;
				}
				$x = $xNew;
			}
			if ($i == MAX_ITERATIONS) {
				return self::$_errorCodes['na'];
			}
			return round($x,12);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * EXPONDIST
	 *
	 * Returns the exponential distribution. Use EXPONDIST to model the time between events,
	 * such as how long an automated bank teller takes to deliver cash. For example, you can
	 * use EXPONDIST to determine the probability that the process takes at most 1 minute.
	 *
	 * @param	float		$value			Value of the function
	 * @param	float		$lambda			The parameter value
	 * @param	boolean		$cumulative
	 * @return  float
	 */
	public static function EXPONDIST($value, $lambda, $cumulative) {
		$value	= self::flattenSingleValue($value);
		$lambda	= self::flattenSingleValue($lambda);
		$cumulative	= self::flattenSingleValue($cumulative);

		if ((is_numeric($value)) && (is_numeric($lambda))) {
			if (($value < 0) || ($lambda < 0)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					return 1 - exp(0-$value*$lambda);
				} else {
					return $lambda * exp(0-$value*$lambda);
				}
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * FISHER
	 *
	 * Returns the Fisher transformation at x. This transformation produces a function that
	 * is normally distributed rather than skewed. Use this function to perform hypothesis
	 * testing on the correlation coefficient.
	 *
	 * @param	float		$value
	 * @return  float
	 */
	public static function FISHER($value) {
		$value	= self::flattenSingleValue($value);

		if (is_numeric($value)) {
			if (($value <= -1) || ($lambda >= 1)) {
				return self::$_errorCodes['num'];
			}
			return 0.5 * log((1+$value)/(1-$value));
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * FISHERINV
	 *
	 * Returns the inverse of the Fisher transformation. Use this transformation when
	 * analyzing correlations between ranges or arrays of data. If y = FISHER(x), then
	 * FISHERINV(y) = x.
	 *
	 * @param	float		$value
	 * @return  float
	 */
	public static function FISHERINV($value) {
		$value	= self::flattenSingleValue($value);

		if (is_numeric($value)) {
			return (exp(2 * $value) - 1) / (exp(2 * $value) + 1);
		}
		return self::$_errorCodes['value'];
	}

	// Function cache for logBeta
	private static $logBetaCache_p			= 0.0;
	private static $logBetaCache_q			= 0.0;
	private static $logBetaCache_result	= 0.0;

	/**
	 * The natural logarithm of the beta function.
	 * @param p require p>0
	 * @param q require q>0
	 * @return 0 if p<=0, q<=0 or p+q>2.55E305 to avoid errors and over/underflow
	 * @author Jaco van Kooten
	 */
	private static function logBeta($p, $q) {
		if ($p != self::$logBetaCache_p || $q != self::$logBetaCache_q) {
			self::$logBetaCache_p = $p;
			self::$logBetaCache_q = $q;
			if (($p <= 0.0) || ($q <= 0.0) || (($p + $q) > LOG_GAMMA_X_MAX_VALUE)) {
				self::$logBetaCache_result = 0.0;
			} else {
				self::$logBetaCache_result = self::logGamma($p) + self::logGamma($q) - self::logGamma($p + $q);
			}
		}
		return self::$logBetaCache_result;
	}

	/**
	 * Evaluates of continued fraction part of incomplete beta function.
	 * Based on an idea from Numerical Recipes (W.H. Press et al, 1992).
	 * @author Jaco van Kooten
	 */
	private static function betaFraction($x, $p, $q) {
		$c = 1.0;
		$sum_pq  = $p + $q;
		$p_plus  = $p + 1.0;
		$p_minus = $p - 1.0;
		$h = 1.0 - $sum_pq * $x / $p_plus;
		if (abs($h) < XMININ) {
			$h = XMININ;
		}
		$h = 1.0 / $h;
		$frac  = $h;
		$m	 = 1;
		$delta = 0.0;
		while ($m <= MAX_ITERATIONS && abs($delta-1.0) > PRECISION ) {
			$m2 = 2 * $m;
			// even index for d
			$d = $m * ($q - $m) * $x / ( ($p_minus + $m2) * ($p + $m2));
			$h = 1.0 + $d * $h;
			if (abs($h) < XMININ) {
				$h = XMININ;
			}
			$h = 1.0 / $h;
			$c = 1.0 + $d / $c;
			if (abs($c) < XMININ) {
				$c = XMININ;
			}
			$frac *= $h * $c;
			// odd index for d
			$d = -($p + $m) * ($sum_pq + $m) * $x / (($p + $m2) * ($p_plus + $m2));
			$h = 1.0 + $d * $h;
			if (abs($h) < XMININ) {
				$h = XMININ;
			}
			$h = 1.0 / $h;
			$c = 1.0 + $d / $c;
			if (abs($c) < XMININ) {
				$c = XMININ;
			}
			$delta = $h * $c;
			$frac *= $delta;
			++$m;
		}
		return $frac;
	}

	/**
	 * logGamma function
	 *
	 * @version 1.1
	 * @author Jaco van Kooten
	 *
	 * Original author was Jaco van Kooten. Ported to PHP by Paul Meagher.
	 *
	 * The natural logarithm of the gamma function. <br />
	 * Based on public domain NETLIB (Fortran) code by W. J. Cody and L. Stoltz <br />
	 * Applied Mathematics Division <br />
	 * Argonne National Laboratory <br />
	 * Argonne, IL 60439 <br />
	 * <p>
	 * References:
	 * <ol>
	 * <li>W. J. Cody and K. E. Hillstrom, 'Chebyshev Approximations for the Natural
	 *	 Logarithm of the Gamma Function,' Math. Comp. 21, 1967, pp. 198-203.</li>
	 * <li>K. E. Hillstrom, ANL/AMD Program ANLC366S, DGAMMA/DLGAMA, May, 1969.</li>
	 * <li>Hart, Et. Al., Computer Approximations, Wiley and sons, New York, 1968.</li>
	 * </ol>
	 * </p>
	 * <p>
	 * From the original documentation:
	 * </p>
	 * <p>
	 * This routine calculates the LOG(GAMMA) function for a positive real argument X.
	 * Computation is based on an algorithm outlined in references 1 and 2.
	 * The program uses rational functions that theoretically approximate LOG(GAMMA)
	 * to at least 18 significant decimal digits.  The approximation for X > 12 is from
	 * reference 3, while approximations for X < 12.0 are similar to those in reference
	 * 1, but are unpublished. The accuracy achieved depends on the arithmetic system,
	 * the compiler, the intrinsic functions, and proper selection of the
	 * machine-dependent constants.
	 * </p>
	 * <p>
	 * Error returns: <br />
	 * The program returns the value XINF for X .LE. 0.0 or when overflow would occur.
	 * The computation is believed to be free of underflow and overflow.
	 * </p>
	 * @return MAX_VALUE for x < 0.0 or when overflow would occur, i.e. x > 2.55E305
	 */

	// Function cache for logGamma
	private static $logGammaCache_result	= 0.0;
	private static $logGammaCache_x		= 0.0;

	private static function logGamma($x) {
		// Log Gamma related constants
		static $lg_d1 = -0.5772156649015328605195174;
		static $lg_d2 = 0.4227843350984671393993777;
		static $lg_d4 = 1.791759469228055000094023;

		static $lg_p1 = array(	4.945235359296727046734888,
								201.8112620856775083915565,
								2290.838373831346393026739,
								11319.67205903380828685045,
								28557.24635671635335736389,
								38484.96228443793359990269,
								26377.48787624195437963534,
								7225.813979700288197698961 );
		static $lg_p2 = array(	4.974607845568932035012064,
								542.4138599891070494101986,
								15506.93864978364947665077,
								184793.2904445632425417223,
								1088204.76946882876749847,
								3338152.967987029735917223,
								5106661.678927352456275255,
								3074109.054850539556250927 );
		static $lg_p4 = array(	14745.02166059939948905062,
								2426813.369486704502836312,
								121475557.4045093227939592,
								2663432449.630976949898078,
								29403789566.34553899906876,
								170266573776.5398868392998,
								492612579337.743088758812,
								560625185622.3951465078242 );

		static $lg_q1 = array(	67.48212550303777196073036,
								1113.332393857199323513008,
								7738.757056935398733233834,
								27639.87074403340708898585,
								54993.10206226157329794414,
								61611.22180066002127833352,
								36351.27591501940507276287,
								8785.536302431013170870835 );
		static $lg_q2 = array(	183.0328399370592604055942,
								7765.049321445005871323047,
								133190.3827966074194402448,
								1136705.821321969608938755,
								5267964.117437946917577538,
								13467014.54311101692290052,
								17827365.30353274213975932,
								9533095.591844353613395747 );
		static $lg_q4 = array(	2690.530175870899333379843,
								639388.5654300092398984238,
								41355999.30241388052042842,
								1120872109.61614794137657,
								14886137286.78813811542398,
								101680358627.2438228077304,
								341747634550.7377132798597,
								446315818741.9713286462081 );

		static $lg_c  = array(	-0.001910444077728,
								8.4171387781295e-4,
								-5.952379913043012e-4,
								7.93650793500350248e-4,
								-0.002777777777777681622553,
								0.08333333333333333331554247,
								0.0057083835261 );

	// Rough estimate of the fourth root of logGamma_xBig
	static $lg_frtbig = 2.25e76;
	static $pnt68	 = 0.6796875;


	if ($x == self::$logGammaCache_x) {
		return self::$logGammaCache_result;
	}
	$y = $x;
	if ($y > 0.0 && $y <= LOG_GAMMA_X_MAX_VALUE) {
		if ($y <= EPS) {
			$res = -log(y);
		} elseif ($y <= 1.5) {
			// ---------------------
			//  EPS .LT. X .LE. 1.5
			// ---------------------
			if ($y < $pnt68) {
				$corr = -log($y);
				$xm1  = $y;
			} else {
				$corr = 0.0;
				$xm1  = $y - 1.0;
			}
			if ($y <= 0.5 || $y >= $pnt68) {
				$xden = 1.0;
				$xnum = 0.0;
				for ($i = 0; $i < 8; ++$i) {
					$xnum = $xnum * $xm1 + $lg_p1[$i];
					$xden = $xden * $xm1 + $lg_q1[$i];
				}
				$res = $corr + $xm1 * ($lg_d1 + $xm1 * ($xnum / $xden));
			} else {
				$xm2  = $y - 1.0;
				$xden = 1.0;
				$xnum = 0.0;
				for ($i = 0; $i < 8; ++$i) {
					$xnum = $xnum * $xm2 + $lg_p2[$i];
					$xden = $xden * $xm2 + $lg_q2[$i];
				}
				$res = $corr + $xm2 * ($lg_d2 + $xm2 * ($xnum / $xden));
			}
		} elseif ($y <= 4.0) {
			// ---------------------
			//  1.5 .LT. X .LE. 4.0
			// ---------------------
			$xm2  = $y - 2.0;
			$xden = 1.0;
			$xnum = 0.0;
			for ($i = 0; $i < 8; ++$i) {
				$xnum = $xnum * $xm2 + $lg_p2[$i];
				$xden = $xden * $xm2 + $lg_q2[$i];
			}
			$res = $xm2 * ($lg_d2 + $xm2 * ($xnum / $xden));
		} elseif ($y <= 12.0) {
			// ----------------------
			//  4.0 .LT. X .LE. 12.0
			// ----------------------
			$xm4  = $y - 4.0;
			$xden = -1.0;
			$xnum = 0.0;
			for ($i = 0; $i < 8; ++$i) {
				$xnum = $xnum * $xm4 + $lg_p4[$i];
				$xden = $xden * $xm4 + $lg_q4[$i];
			}
			$res = $lg_d4 + $xm4 * ($xnum / $xden);
		} else {
			// ---------------------------------
			//  Evaluate for argument .GE. 12.0
			// ---------------------------------
			$res = 0.0;
			if ($y <= $lg_frtbig) {
				$res = $lg_c[6];
				$ysq = $y * $y;
				for ($i = 0; $i < 6; ++$i)
					$res = $res / $ysq + $lg_c[$i];
				}
				$res  /= $y;
				$corr = log($y);
				$res  = $res + log(SQRT2PI) - 0.5 * $corr;
				$res  += $y * ($corr - 1.0);
			}
		} else {
			// --------------------------
			//  Return for bad arguments
			// --------------------------
			$res = MAX_VALUE;
		}
		// ------------------------------
		//  Final adjustments and return
		// ------------------------------
		self::$logGammaCache_x = $x;
		self::$logGammaCache_result = $res;
		return $res;
	}

	/**
	 * Beta function.
	 *
	 * @author Jaco van Kooten
	 *
	 * @param p require p>0
	 * @param q require q>0
	 * @return 0 if p<=0, q<=0 or p+q>2.55E305 to avoid errors and over/underflow
	 */
	private static function beta($p, $q) {
		if ($p <= 0.0 || $q <= 0.0 || ($p + $q) > LOG_GAMMA_X_MAX_VALUE) {
			return 0.0;
		} else {
			return exp(self::logBeta($p, $q));
		}
	}

	/**
	 * Incomplete beta function
	 *
	 * @author Jaco van Kooten
	 * @author Paul Meagher
	 *
	 * The computation is based on formulas from Numerical Recipes, Chapter 6.4 (W.H. Press et al, 1992).
	 * @param x require 0<=x<=1
	 * @param p require p>0
	 * @param q require q>0
	 * @return 0 if x<0, p<=0, q<=0 or p+q>2.55E305 and 1 if x>1 to avoid errors and over/underflow
	 */
	private static function incompleteBeta($x, $p, $q) {
		if ($x <= 0.0) {
			return 0.0;
		} elseif ($x >= 1.0) {
			return 1.0;
		} elseif (($p <= 0.0) || ($q <= 0.0) || (($p + $q) > LOG_GAMMA_X_MAX_VALUE)) {
			return 0.0;
		}
		$beta_gam = exp((0 - self::logBeta($p, $q)) + $p * log($x) + $q * log(1.0 - $x));
		if ($x < ($p + 1.0) / ($p + $q + 2.0)) {
			return $beta_gam * self::betaFraction($x, $p, $q) / $p;
		} else {
			return 1.0 - ($beta_gam * self::betaFraction(1 - $x, $q, $p) / $q);
		}
	}

	/**
	 * BETADIST
	 *
	 * Returns the beta distribution.
	 *
	 * @param	float		$value			Value at which you want to evaluate the distribution
	 * @param	float		$alpha			Parameter to the distribution
	 * @param	float		$beta			Parameter to the distribution
	 * @param	boolean		$cumulative
	 * @return  float
	 *
	 */
	public static function BETADIST($value,$alpha,$beta,$rMin=0,$rMax=1) {
		$value	= self::flattenSingleValue($value);
		$alpha	= self::flattenSingleValue($alpha);
		$beta	= self::flattenSingleValue($beta);
		$rMin	= self::flattenSingleValue($rMin);
		$rMax	= self::flattenSingleValue($rMax);

		if ((is_numeric($value)) && (is_numeric($alpha)) && (is_numeric($beta)) && (is_numeric($rMin)) && (is_numeric($rMax))) {
			if (($value < $rMin) || ($value > $rMax) || ($alpha <= 0) || ($beta <= 0) || ($rMin == $rMax)) {
				return self::$_errorCodes['num'];
			}
			if ($rMin > $rMax) {
				$tmp = $rMin;
				$rMin = $rMax;
				$rMax = $tmp;
			}
			$value -= $rMin;
			$value /= ($rMax - $rMin);
			return self::incompleteBeta($value,$alpha,$beta);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * BETAINV
	 *
	 * Returns the inverse of the beta distribution.
	 *
	 * @param	float		$probability	Probability at which you want to evaluate the distribution
	 * @param	float		$alpha			Parameter to the distribution
	 * @param	float		$beta			Parameter to the distribution
	 * @param	boolean		$cumulative
	 * @return  float
	 *
	 */
	public static function BETAINV($probability,$alpha,$beta,$rMin=0,$rMax=1) {
		$probability	= self::flattenSingleValue($probability);
		$alpha			= self::flattenSingleValue($alpha);
		$beta			= self::flattenSingleValue($beta);
		$rMin			= self::flattenSingleValue($rMin);
		$rMax			= self::flattenSingleValue($rMax);

		if ((is_numeric($probability)) && (is_numeric($alpha)) && (is_numeric($beta)) && (is_numeric($rMin)) && (is_numeric($rMax))) {
			if (($alpha <= 0) || ($beta <= 0) || ($rMin == $rMax) || ($probability <= 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if ($rMin > $rMax) {
				$tmp = $rMin;
				$rMin = $rMax;
				$rMax = $tmp;
			}
			$a = 0;
			$b = 2;
			$maxIteration = 100;

			$i = 0;
			while ((($b - $a) > PRECISION) && ($i++ < MAX_ITERATIONS)) {
				$guess = ($a + $b) / 2;
				$result = self::BETADIST($guess, $alpha, $beta);
				if (($result == $probability) || ($result == 0)) {
					$b = $a;
				} elseif ($result > $probability) {
					$b = $guess;
				} else {
					$a = $guess;
				}
			}
			if ($i == MAX_ITERATIONS) {
				return self::$_errorCodes['na'];
			}
			return round($rMin + $guess * ($rMax - $rMin),12);
		}
		return self::$_errorCodes['value'];
	}

	//
	//	Private implementation of the incomplete Gamma function
	//
	private static function incompleteGamma($a,$x) {
		static $max = 32;
		$summer = 0;
		for ($n=0; $n<=$max; ++$n) {
			$divisor = $a;
			for ($i=1; $i<=$n; ++$i) {
				$divisor *= ($a + $i);
			}
			$summer += (pow($x,$n) / $divisor);
		}
		return pow($x,$a) * exp(0-$x) * $summer;
	}


	//
	//	Private implementation of the Gamma function
	//
	private static function gamma($data) {
		if ($data == 0.0) return 0;

		static $p0 = 1.000000000190015;
		static $p = array ( 1 => 76.18009172947146,
							2 => -86.50532032941677,
							3 => 24.01409824083091,
							4 => -1.231739572450155,
							5 => 1.208650973866179e-3,
							6 => -5.395239384953e-6
						  );

		$y = $x = $data;
		$tmp = $x + 5.5;
		$tmp -= ($x + 0.5) * log($tmp);

		$summer = $p0;
		for ($j=1;$j<=6;++$j) {
			$summer += ($p[$j] / ++$y);
		}
		return exp(0 - $tmp + log(2.5066282746310005 * $summer / $x));
	}

	/**
	 * GAMMADIST
	 *
	 * Returns the gamma distribution.
	 *
	 * @param	float		$value			Value at which you want to evaluate the distribution
	 * @param	float		$a				Parameter to the distribution
	 * @param	float		$b				Parameter to the distribution
	 * @param	boolean		$cumulative
	 * @return  float
	 *
	 */
	public static function GAMMADIST($value,$a,$b,$cumulative) {
		$value	= self::flattenSingleValue($value);
		$a		= self::flattenSingleValue($a);
		$b		= self::flattenSingleValue($b);

		if ((is_numeric($value)) && (is_numeric($a)) && (is_numeric($b))) {
			if (($value < 0) || ($a <= 0) || ($b <= 0)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					return self::incompleteGamma($a,$value / $b) / self::gamma($a);
				} else {
					return (1 / (pow($b,$a) * self::gamma($a))) * pow($value,$a-1) * exp(0-($value / $b));
				}
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * GAMMAINV
	 *
	 * Returns the inverse of the beta distribution.
	 *
	 * @param	float		$probability	Probability at which you want to evaluate the distribution
	 * @param	float		$alpha			Parameter to the distribution
	 * @param	float		$beta			Parameter to the distribution
	 * @param	boolean		$cumulative
	 * @return  float
	 *
	 */
	public static function GAMMAINV($probability,$alpha,$beta) {
		$probability	= self::flattenSingleValue($probability);
		$alpha			= self::flattenSingleValue($alpha);
		$beta			= self::flattenSingleValue($beta);
		$rMin			= self::flattenSingleValue($rMin);
		$rMax			= self::flattenSingleValue($rMax);

		if ((is_numeric($probability)) && (is_numeric($alpha)) && (is_numeric($beta))) {
			if (($alpha <= 0) || ($beta <= 0) || ($probability <= 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			$xLo = 0;
			$xHi = 100;
			$maxIteration = 100;

			$x = $xNew = 1;
			$dx	= 1;
			$i = 0;

			while ((abs($dx) > PRECISION) && ($i++ < MAX_ITERATIONS)) {
				// Apply Newton-Raphson step
				$result = self::GAMMADIST($x, $alpha, $beta, True);
				$error = $result - $probability;
				if ($error == 0.0) {
					$dx = 0;
				} elseif ($error < 0.0) {
					$xLo = $x;
				} else {
					$xHi = $x;
				}
				// Avoid division by zero
				if ($result != 0.0) {
					$dx = $error / $result;
					$xNew = $x - $dx;
				}
				// If the NR fails to converge (which for example may be the
				// case if the initial guess is too rough) we apply a bisection
				// step to determine a more narrow interval around the root.
				if (($xNew < $xLo) || ($xNew > $xHi) || ($result == 0.0)) {
					$xNew = ($xLo + $xHi) / 2;
					$dx = $xNew - $x;
				}
				$x = $xNew;
			}
			if ($i == MAX_ITERATIONS) {
				return self::$_errorCodes['na'];
			}
			return round($x,12);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * GAMMALN
	 *
	 * Returns the natural logarithm of the gamma function.
	 *
	 * @param	float		$value
	 * @return  float
	 */
	public static function GAMMALN($value) {
		$value	= self::flattenSingleValue($value);

		if (is_numeric($value)) {
			if ($value <= 0) {
				return self::$_errorCodes['num'];
			}
			return log(self::gamma($value));
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * NORMDIST
	 *
	 * Returns the normal distribution for the specified mean and standard deviation. This
	 * function has a very wide range of applications in statistics, including hypothesis
	 * testing.
	 *
	 * @param	float		$value
	 * @param	float		$mean		Mean Value
	 * @param	float		$stdDev		Standard Deviation
	 * @param	boolean		$cumulative
	 * @return  float
	 *
	 */
	public static function NORMDIST($value, $mean, $stdDev, $cumulative) {
		$value	= self::flattenSingleValue($value);
		$mean	= self::flattenSingleValue($mean);
		$stdDev	= self::flattenSingleValue($stdDev);

		if ((is_numeric($value)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if ($stdDev < 0) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					return 0.5 * (1 + self::erfVal(($value - $mean) / ($stdDev * sqrt(2))));
				} else {
					return (1 / (SQRT2PI * $stdDev)) * exp(0  - (pow($value - $mean,2) / (2 * pow($stdDev,2))));
				}
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * NORMSDIST
	 *
	 * Returns the standard normal cumulative distribution function. The distribution has
	 * a mean of 0 (zero) and a standard deviation of one. Use this function in place of a
	 * table of standard normal curve areas.
	 *
	 * @param	float		$value
	 * @return  float
	 */
	public static function NORMSDIST($value) {
		$value	= self::flattenSingleValue($value);

		return self::NORMDIST($value, 0, 1, True);
	}

	/**
	 * LOGNORMDIST
	 *
	 * Returns the cumulative lognormal distribution of x, where ln(x) is normally distributed
	 * with parameters mean and standard_dev.
	 *
	 * @param	float		$value
	 * @return  float
	 */
	public static function LOGNORMDIST($value, $mean, $stdDev) {
		$value	= self::flattenSingleValue($value);
		$mean	= self::flattenSingleValue($mean);
		$stdDev	= self::flattenSingleValue($stdDev);

		if ((is_numeric($value)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if (($value <= 0) || ($stdDev <= 0)) {
				return self::$_errorCodes['num'];
			}
			return self::NORMSDIST((log($value) - $mean) / $stdDev);
		}
		return self::$_errorCodes['value'];
	}

	/***************************************************************************
	 *								inverse_ncdf.php
	 *							-------------------
	 *   begin				: Friday, January 16, 2004
	 *   copyright			: (C) 2004 Michael Nickerson
	 *   email				: nickersonm@yahoo.com
	 *
	 ***************************************************************************/
	private static function inverse_ncdf($p) {
		//	Inverse ncdf approximation by Peter J. Acklam, implementation adapted to
		//	PHP by Michael Nickerson, using Dr. Thomas Ziegler's C implementation as
		//	a guide.  http://home.online.no/~pjacklam/notes/invnorm/index.html
		//	I have not checked the accuracy of this implementation.  Be aware that PHP
		//	will truncate the coeficcients to 14 digits.

		//	You have permission to use and distribute this function freely for
		//	whatever purpose you want, but please show common courtesy and give credit
		//	where credit is due.

		//	Input paramater is $p - probability - where 0 < p < 1.

		//	Coefficients in rational approximations
		static $a = array(	1 => -3.969683028665376e+01,
							2 => 2.209460984245205e+02,
							3 => -2.759285104469687e+02,
							4 => 1.383577518672690e+02,
							5 => -3.066479806614716e+01,
							6 => 2.506628277459239e+00
						  );

		static $b = array(	1 => -5.447609879822406e+01,
							2 => 1.615858368580409e+02,
							3 => -1.556989798598866e+02,
							4 => 6.680131188771972e+01,
							5 => -1.328068155288572e+01
						  );

		static $c = array(	1 => -7.784894002430293e-03,
							2 => -3.223964580411365e-01,
							3 => -2.400758277161838e+00,
							4 => -2.549732539343734e+00,
							5 => 4.374664141464968e+00,
							6 => 2.938163982698783e+00
						  );

		static $d = array(	1 => 7.784695709041462e-03,
							2 => 3.224671290700398e-01,
							3 => 2.445134137142996e+00,
							4 => 3.754408661907416e+00
						  );

		//	Define lower and upper region break-points.
		$p_low =  0.02425;			//Use lower region approx. below this
		$p_high = 1 - $p_low;		//Use upper region approx. above this

		if (0 < $p && $p < $p_low) {
			//	Rational approximation for lower region.
			$q = sqrt(-2 * log($p));
			return ((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) * $q + $c[6]) /
					(((($d[1] * $q + $d[2]) * $q + $d[3]) * $q + $d[4]) * $q + 1);
		} elseif ($p_low <= $p && $p <= $p_high) {
			//	Rational approximation for central region.
			$q = $p - 0.5;
			$r = $q * $q;
			return ((((($a[1] * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $r + $a[6]) * $q /
				   ((((($b[1] * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + $b[5]) * $r + 1);
		} elseif ($p_high < $p && $p < 1) {
			//	Rational approximation for upper region.
			$q = sqrt(-2 * log(1 - $p));
			return -((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) * $q + $c[6]) /
					 (((($d[1] * $q + $d[2]) * $q + $d[3]) * $q + $d[4]) * $q + 1);
		}
		//	If 0 < p < 1, return a null value
		return self::$_errorCodes['null'];
	}

	private static function inverse_ncdf2($prob) {
		//	Approximation of inverse standard normal CDF developed by
		//	B. Moro, "The Full Monte," Risk 8(2), Feb 1995, 57-58.

		$a1 = 2.50662823884;
		$a2 = -18.61500062529;
		$a3 = 41.39119773534;
		$a4 = -25.44106049637;

		$b1 = -8.4735109309;
		$b2 = 23.08336743743;
		$b3 = -21.06224101826;
		$b4 = 3.13082909833;

		$c1 = 0.337475482272615;
		$c2 = 0.976169019091719;
		$c3 = 0.160797971491821;
		$c4 = 2.76438810333863E-02;
		$c5 = 3.8405729373609E-03;
		$c6 = 3.951896511919E-04;
		$c7 = 3.21767881768E-05;
		$c8 = 2.888167364E-07;
		$c9 = 3.960315187E-07;

		$y = $prob - 0.5;
		if (abs($y) < 0.42) {
			$z = pow($y,2);
			$z = $y * ((($a4 * $z + $a3) * $z + $a2) * $z + $a1) / (((($b4 * $z + $b3) * $z + $b2) * $z + $b1) * $z + 1);
		} else {
			if ($y > 0) {
				$z = log(-log(1 - $prob));
			} else {
				$z = log(-log($prob));
			}
			$z = $c1 + $z * ($c2 + $z * ($c3 + $z * ($c4 + $z * ($c5 + $z * ($c6 + $z * ($c7 + $z * ($c8 + $z * $c9)))))));
			if ($y < 0) {
				$z = -$z;
			}
		}
		return $z;
	}

	private static function inverse_ncdf3($p) {
		//	ALGORITHM AS241 APPL. STATIST. (1988) VOL. 37, NO. 3.
		//	Produces the normal deviate Z corresponding to a given lower
		//	tail area of P; Z is accurate to about 1 part in 10**16.
		//
		//	This is a PHP version of the original FORTRAN code that can
		//	be found at http://lib.stat.cmu.edu/apstat/
		$split1 = 0.425;
		$split2 = 5;
		$const1 = 0.180625;
		$const2 = 1.6;

		//	coefficients for p close to 0.5
		$a0 = 3.3871328727963666080;
		$a1 = 1.3314166789178437745E+2;
		$a2 = 1.9715909503065514427E+3;
		$a3 = 1.3731693765509461125E+4;
		$a4 = 4.5921953931549871457E+4;
		$a5 = 6.7265770927008700853E+4;
		$a6 = 3.3430575583588128105E+4;
		$a7 = 2.5090809287301226727E+3;

		$b1 = 4.2313330701600911252E+1;
		$b2 = 6.8718700749205790830E+2;
		$b3 = 5.3941960214247511077E+3;
		$b4 = 2.1213794301586595867E+4;
		$b5 = 3.9307895800092710610E+4;
		$b6 = 2.8729085735721942674E+4;
		$b7 = 5.2264952788528545610E+3;

		//	coefficients for p not close to 0, 0.5 or 1.
		$c0 = 1.42343711074968357734;
		$c1 = 4.63033784615654529590;
		$c2 = 5.76949722146069140550;
		$c3 = 3.64784832476320460504;
		$c4 = 1.27045825245236838258;
		$c5 = 2.41780725177450611770E-1;
		$c6 = 2.27238449892691845833E-2;
		$c7 = 7.74545014278341407640E-4;

		$d1 = 2.05319162663775882187;
		$d2 = 1.67638483018380384940;
		$d3 = 6.89767334985100004550E-1;
		$d4 = 1.48103976427480074590E-1;
		$d5 = 1.51986665636164571966E-2;
		$d6 = 5.47593808499534494600E-4;
		$d7 = 1.05075007164441684324E-9;

		//	coefficients for p near 0 or 1.
		$e0 = 6.65790464350110377720;
		$e1 = 5.46378491116411436990;
		$e2 = 1.78482653991729133580;
		$e3 = 2.96560571828504891230E-1;
		$e4 = 2.65321895265761230930E-2;
		$e5 = 1.24266094738807843860E-3;
		$e6 = 2.71155556874348757815E-5;
		$e7 = 2.01033439929228813265E-7;

		$f1 = 5.99832206555887937690E-1;
		$f2 = 1.36929880922735805310E-1;
		$f3 = 1.48753612908506148525E-2;
		$f4 = 7.86869131145613259100E-4;
		$f5 = 1.84631831751005468180E-5;
		$f6 = 1.42151175831644588870E-7;
		$f7 = 2.04426310338993978564E-15;

		$q = $p - 0.5;

		//	computation for p close to 0.5
		if (abs($q) <= split1) {
			$R = $const1 - $q * $q;
			$z = $q * ((((((($a7 * $R + $a6) * $R + $a5) * $R + $a4) * $R + $a3) * $R + $a2) * $R + $a1) * $R + $a0) /
					  ((((((($b7 * $R + $b6) * $R + $b5) * $R + $b4) * $R + $b3) * $R + $b2) * $R + $b1) * $R + 1);
		} else {
			if ($q < 0) {
				$R = $p;
			} else {
				$R = 1 - $p;
			}
			$R = pow(-log($R),2);

			//	computation for p not close to 0, 0.5 or 1.
			If ($R <= $split2) {
				$R = $R - $const2;
				$z = ((((((($c7 * $R + $c6) * $R + $c5) * $R + $c4) * $R + $c3) * $R + $c2) * $R + $c1) * $R + $c0) /
					 ((((((($d7 * $R + $d6) * $R + $d5) * $R + $d4) * $R + $d3) * $R + $d2) * $R + $d1) * $R + 1);
			} else {
			//	computation for p near 0 or 1.
				$R = $R - $split2;
				$z = ((((((($e7 * $R + $e6) * $R + $e5) * $R + $e4) * $R + $e3) * $R + $e2) * $R + $e1) * $R + $e0) /
					 ((((((($f7 * $R + $f6) * $R + $f5) * $R + $f4) * $R + $f3) * $R + $f2) * $R + $f1) * $R + 1);
			}
			if ($q < 0) {
				$z = -$z;
			}
		}
		return $z;
	}

	/**
	 * NORMINV
	 *
	 * Returns the inverse of the normal cumulative distribution for the specified mean and standard deviation.
	 *
	 * @param	float		$value
	 * @param	float		$mean		Mean Value
	 * @param	float		$stdDev		Standard Deviation
	 * @return  float
	 *
	 */
	public static function NORMINV($probability,$mean,$stdDev) {
		$probability	= self::flattenSingleValue($probability);
		$mean			= self::flattenSingleValue($mean);
		$stdDev			= self::flattenSingleValue($stdDev);

		if ((is_numeric($probability)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if (($probability < 0) || ($probability > 1)) {
				return self::$_errorCodes['num'];
			}
			if ($stdDev < 0) {
				return self::$_errorCodes['num'];
			}
			return (self::inverse_ncdf($probability) * $stdDev) + $mean;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * NORMSINV
	 *
	 * Returns the inverse of the standard normal cumulative distribution
	 *
	 * @param	float		$value
	 * @return  float
	 */
	public static function NORMSINV($value) {
		return self::NORMINV($value, 0, 1);
	}

	/**
	 * LOGINV
	 *
	 * Returns the inverse of the normal cumulative distribution
	 *
	 * @param	float		$value
	 * @return  float
	 *
	 * @todo	Try implementing P J Acklam's refinement algorithm for greater
	 *			accuracy if I can get my head round the mathematics
	 *			(as described at) http://home.online.no/~pjacklam/notes/invnorm/
	 */
	public static function LOGINV($probability, $mean, $stdDev) {
		$probability	= self::flattenSingleValue($probability);
		$mean			= self::flattenSingleValue($mean);
		$stdDev			= self::flattenSingleValue($stdDev);

		if ((is_numeric($probability)) && (is_numeric($mean)) && (is_numeric($stdDev))) {
			if (($probability < 0) || ($probability > 1) || ($stdDev <= 0)) {
				return self::$_errorCodes['num'];
			}
			return exp($mean + $stdDev * self::NORMSINV($probability));
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * HYPGEOMDIST
	 *
	 * Returns the hypergeometric distribution. HYPGEOMDIST returns the probability of a given number of
	 * sample successes, given the sample size, population successes, and population size.
	 *
	 * @param	float		$sampleSuccesses		Number of successes in the sample
	 * @param	float		$sampleNumber			Size of the sample
	 * @param	float		$populationSuccesses	Number of successes in the population
	 * @param	float		$populationNumber		Population size
	 * @return  float
	 *
	 */
	public static function HYPGEOMDIST($sampleSuccesses, $sampleNumber, $populationSuccesses, $populationNumber) {
		$sampleSuccesses		= floor(self::flattenSingleValue($sampleSuccesses));
		$sampleNumber			= floor(self::flattenSingleValue($sampleNumber));
		$populationSuccesses	= floor(self::flattenSingleValue($populationSuccesses));
		$populationNumber		= floor(self::flattenSingleValue($populationNumber));

		if ((is_numeric($sampleSuccesses)) && (is_numeric($sampleNumber)) && (is_numeric($populationSuccesses)) && (is_numeric($populationNumber))) {
			if (($sampleSuccesses < 0) || ($sampleSuccesses > $sampleNumber) || ($sampleSuccesses > $populationSuccesses)) {
				return self::$_errorCodes['num'];
			}
			if (($sampleNumber <= 0) || ($sampleNumber > $populationNumber)) {
				return self::$_errorCodes['num'];
			}
			if (($populationSuccesses <= 0) || ($populationSuccesses > $populationNumber)) {
				return self::$_errorCodes['num'];
			}
			return self::COMBIN($populationSuccesses,$sampleSuccesses) *
				   self::COMBIN($populationNumber - $populationSuccesses,$sampleNumber - $sampleSuccesses) /
				   self::COMBIN($populationNumber,$sampleNumber);
		}
		return self::$_errorCodes['value'];
	}

	public static function hypGeom($sampleSuccesses, $sampleNumber, $populationSuccesses, $populationNumber) {
		return self::COMBIN($populationSuccesses,$sampleSuccesses) *
			   self::COMBIN($populationNumber - $populationSuccesses,$sampleNumber - $sampleSuccesses) /
			   self::COMBIN($populationNumber,$sampleNumber);
	}

	/**
	 * TDIST
	 *
	 * Returns the probability of Student's T distribution.
	 *
	 * @param	float		$value			Value for the function
	 * @param	float		$degrees		degrees of freedom
	 * @param	float		$tails			number of tails (1 or 2)
	 * @return  float
	 */
	public static function TDIST($value, $degrees, $tails) {
		$value		= self::flattenSingleValue($value);
		$degrees	= floor(self::flattenSingleValue($degrees));
		$tails		= floor(self::flattenSingleValue($tails));

		if ((is_numeric($value)) && (is_numeric($degrees)) && (is_numeric($tails))) {
			if (($value < 0) || ($degrees < 1) || ($tails < 1) || ($tails > 2)) {
				return self::$_errorCodes['num'];
			}
			//	tdist, which finds the probability that corresponds to a given value
			//	of t with k degrees of freedom.  This algorithm is translated from a
			//	pascal function on p81 of "Statistical Computing in Pascal" by D
			//	Cooke, A H Craven & G M Clark (1985: Edward Arnold (Pubs.) Ltd:
			//	London).  The above Pascal algorithm is itself a translation of the
			//	fortran algoritm "AS 3" by B E Cooper of the Atlas Computer
			//	Laboratory as reported in (among other places) "Applied Statistics
			//	Algorithms", editied by P Griffiths and I D Hill (1985; Ellis
			//	Horwood Ltd.; W. Sussex, England).
//			$ta = 2 / pi();
			$ta = 0.636619772367581;
			$tterm = $degrees;
			$ttheta = atan2($value,sqrt($tterm));
			$tc = cos($ttheta);
			$ts = sin($ttheta);
			$tsum = 0;

			if (($degrees % 2) == 1) {
				$ti = 3;
				$tterm = $tc;
			} else {
				$ti = 2;
				$tterm = 1;
			}

			$tsum = $tterm;
			while ($ti < $degrees) {
				$tterm *= $tc * $tc * ($ti - 1) / $ti;
				$tsum += $tterm;
				$ti += 2;
			}
			$tsum *= $ts;
			if (($degrees % 2) == 1) { $tsum = $ta * ($tsum + $ttheta); }
			$tValue = 0.5 * (1 + $tsum);
			if ($tails == 1) {
				return 1 - abs($tValue);
			} else {
				return 1 - abs((1 - $tValue) - $tValue);
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * TINV
	 *
	 * Returns the one-tailed probability of the chi-squared distribution.
	 *
	 * @param	float		$probability	Probability for the function
	 * @param	float		$degrees		degrees of freedom
	 * @return  float
	 */
	public static function TINV($probability, $degrees) {
		$probability	= self::flattenSingleValue($probability);
		$degrees		= floor(self::flattenSingleValue($degrees));

		if ((is_numeric($probability)) && (is_numeric($degrees))) {
			$xLo = 100;
			$xHi = 0;
			$maxIteration = 100;

			$x = $xNew = 1;
			$dx	= 1;
			$i = 0;

			while ((abs($dx) > PRECISION) && ($i++ < MAX_ITERATIONS)) {
				// Apply Newton-Raphson step
				$result = self::TDIST($x, $degrees, 2);
				$error = $result - $probability;
				if ($error == 0.0) {
					$dx = 0;
				} elseif ($error < 0.0) {
					$xLo = $x;
				} else {
					$xHi = $x;
				}
				// Avoid division by zero
				if ($result != 0.0) {
					$dx = $error / $result;
					$xNew = $x - $dx;
				}
				// If the NR fails to converge (which for example may be the
				// case if the initial guess is too rough) we apply a bisection
				// step to determine a more narrow interval around the root.
				if (($xNew < $xLo) || ($xNew > $xHi) || ($result == 0.0)) {
					$xNew = ($xLo + $xHi) / 2;
					$dx = $xNew - $x;
				}
				$x = $xNew;
			}
			if ($i == MAX_ITERATIONS) {
				return self::$_errorCodes['na'];
			}
			return round($x,12);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * CONFIDENCE
	 *
	 * Returns the confidence interval for a population mean
	 *
	 * @param	float		$alpha
	 * @param	float		$stdDev		Standard Deviation
	 * @param	float		$size
	 * @return  float
	 *
	 */
	public static function CONFIDENCE($alpha,$stdDev,$size) {
		$alpha	= self::flattenSingleValue($alpha);
		$stdDev	= self::flattenSingleValue($stdDev);
		$size	= floor(self::flattenSingleValue($size));

		if ((is_numeric($alpha)) && (is_numeric($stdDev)) && (is_numeric($size))) {
			if (($alpha <= 0) || ($alpha >= 1)) {
				return self::$_errorCodes['num'];
			}
			if (($stdDev <= 0) || ($size < 1)) {
				return self::$_errorCodes['num'];
			}
			return self::NORMSINV(1 - $alpha / 2) * $stdDev / sqrt($size);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * POISSON
	 *
	 * Returns the Poisson distribution. A common application of the Poisson distribution
	 * is predicting the number of events over a specific time, such as the number of
	 * cars arriving at a toll plaza in 1 minute.
	 *
	 * @param	float		$value
	 * @param	float		$mean		Mean Value
	 * @param	boolean		$cumulative
	 * @return  float
	 *
	 */
	public static function POISSON($value, $mean, $cumulative) {
		$value	= self::flattenSingleValue($value);
		$mean	= self::flattenSingleValue($mean);

		if ((is_numeric($value)) && (is_numeric($mean))) {
			if (($value <= 0) || ($mean <= 0)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					$summer = 0;
					for ($i = 0; $i <= floor($value); ++$i) {
						$summer += pow($mean,$i) / self::FACT($i);
					}
					return exp(0-$mean) * $summer;
				} else {
					return (exp(0-$mean) * pow($mean,$value)) / self::FACT($value);
				}
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * WEIBULL
	 *
	 * Returns the Weibull distribution. Use this distribution in reliability
	 * analysis, such as calculating a device's mean time to failure.
	 *
	 * @param	float		$value
	 * @param	float		$alpha		Alpha Parameter
	 * @param	float		$beta		Beta Parameter
	 * @param	boolean		$cumulative
	 * @return  float
	 *
	 */
	public static function WEIBULL($value, $alpha, $beta, $cumulative) {
		$value	= self::flattenSingleValue($value);
		$alpha	= self::flattenSingleValue($alpha);
		$beta	= self::flattenSingleValue($beta);

		if ((is_numeric($value)) && (is_numeric($alpha)) && (is_numeric($beta))) {
			if (($value < 0) || ($alpha <= 0) || ($beta <= 0)) {
				return self::$_errorCodes['num'];
			}
			if ((is_numeric($cumulative)) || (is_bool($cumulative))) {
				if ($cumulative) {
					return 1 - exp(0 - pow($value / $beta,$alpha));
				} else {
					return ($alpha / pow($beta,$alpha)) * pow($value,$alpha - 1) * exp(0 - pow($value / $beta,$alpha));
				}
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * SKEW
	 *
	 * Returns the skewness of a distribution. Skewness characterizes the degree of asymmetry
	 * of a distribution around its mean. Positive skewness indicates a distribution with an
	 * asymmetric tail extending toward more positive values. Negative skewness indicates a
	 * distribution with an asymmetric tail extending toward more negative values.
	 *
	 * @param	array	Data Series
	 * @return  float
	 */
	public static function SKEW() {
		$aArgs = self::flattenArray(func_get_args());
		$mean = self::AVERAGE($aArgs);
		$stdDev = self::STDEV($aArgs);

		$count = $summer = 0;
		// Loop through arguments
		foreach ($aArgs as $arg) {
			// Is it a numeric value?
			if ((is_numeric($arg)) && (!is_string($arg))) {
				$summer += pow((($arg - $mean) / $stdDev),3) ;
				++$count;
			}
		}

		// Return
		if ($count > 2) {
			return $summer * ($count / (($count-1) * ($count-2)));
		}
		return self::$_errorCodes['divisionbyzero'];
	}

	/**
	 * KURT
	 *
	 * Returns the kurtosis of a data set. Kurtosis characterizes the relative peakedness
	 * or flatness of a distribution compared with the normal distribution. Positive
	 * kurtosis indicates a relatively peaked distribution. Negative kurtosis indicates a
	 * relatively flat distribution.
	 *
	 * @param	array	Data Series
	 * @return  float
	 */
	public static function KURT() {
		$aArgs = self::flattenArray(func_get_args());
		$mean = self::AVERAGE($aArgs);
		$stdDev = self::STDEV($aArgs);

		if ($stdDev > 0) {
			$count = $summer = 0;
			// Loop through arguments
			foreach ($aArgs as $arg) {
				// Is it a numeric value?
				if ((is_numeric($arg)) && (!is_string($arg))) {
					$summer += pow((($arg - $mean) / $stdDev),4) ;
					++$count;
				}
			}

			// Return
			if ($count > 3) {
				return $summer * ($count * ($count+1) / (($count-1) * ($count-2) * ($count-3))) - (3 * pow($count-1,2) / (($count-2) * ($count-3)));
			}
		}
		return self::$_errorCodes['divisionbyzero'];
	}

	/**
	 * RAND
	 *
	 * @param	int		$min	Minimal value
	 * @param	int		$max	Maximal value
	 * @return  int		Random number
	 */
	public static function RAND($min = 0, $max = 0) {
		$min		= self::flattenSingleValue($min);
		$max		= self::flattenSingleValue($max);

		if ($min == 0 && $max == 0) {
			return (rand(0,10000000)) / 10000000;
		} else {
			return rand($min, $max);
		}
	}

	/**
	 * MOD
	 *
	 * @param	int		$a		Dividend
	 * @param	int		$b		Divisor
	 * @return  int		Remainder
	 */
	public static function MOD($a = 1, $b = 1) {
		$a		= self::flattenSingleValue($a);
		$b		= self::flattenSingleValue($b);

		return $a % $b;
	}

	/**
	 * ASCIICODE
	 *
	 * @param	string	$character	Value
	 * @return  int
	 */
	public static function ASCIICODE($characters) {
		$characters	= self::flattenSingleValue($characters);
		if (is_bool($characters)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$characters = (int) $characters;
			} else {
				if ($characters) {
					$characters = 'True';
				} else {
					$characters = 'False';
				}
			}
		}

		if (strlen($characters) > 0) {
			return ord(substr($characters, 0, 1));
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * CONCATENATE
	 *
	 * @return  string
	 */
	public static function CONCATENATE() {
		// Return value
		$returnValue = '';

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			if (is_bool($arg)) {
				if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
					$arg = (int) $arg;
				} else {
					if ($arg) {
						$arg = 'TRUE';
					} else {
						$arg = 'FALSE';
					}
				}
			}
			$returnValue .= $arg;
		}

		// Return
		return $returnValue;
	}

	/**
	 * SEARCHSENSITIVE
	 *
	 * @param	string	$needle		The string to look for
	 * @param	string	$haystack	The string in which to look
	 * @param	int		$offset		Offset within $haystack
	 * @return  string
	 */
	public static function SEARCHSENSITIVE($needle,$haystack,$offset=1) {
		$needle		= (string) self::flattenSingleValue($needle);
		$haystack	= (string) self::flattenSingleValue($haystack);
		$offset		= self::flattenSingleValue($offset);

		if (($offset > 0) && (strlen($haystack) > $offset)) {
			$pos = strpos($haystack, $needle, --$offset);
			if ($pos !== false) {
				return ++$pos;
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * SEARCHINSENSITIVE
	 *
	 * @param	string	$needle		The string to look for
	 * @param	string	$haystack	The string in which to look
	 * @param	int		$offset		Offset within $haystack
	 * @return  string
	 */
	public static function SEARCHINSENSITIVE($needle,$haystack,$offset=1) {
		$needle		= (string) self::flattenSingleValue($needle);
		$haystack	= (string) self::flattenSingleValue($haystack);
		$offset		= self::flattenSingleValue($offset);

		if (($offset > 0) && (strlen($haystack) > $offset)) {
			$pos = stripos($haystack, $needle, --$offset);
			if ($pos !== false) {
				return ++$pos;
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * LEFT
	 *
	 * @param	string	$value	Value
	 * @param	int		$chars	Number of characters
	 * @return  string
	 */
	public static function LEFT($value = '', $chars = null) {
		$value		= self::flattenSingleValue($value);
		$chars		= self::flattenSingleValue($chars);

		return substr($value, 0, $chars);
	}

	/**
	 * RIGHT
	 *
	 * @param	string	$value	Value
	 * @param	int		$chars	Number of characters
	 * @return  string
	 */
	public static function RIGHT($value = '', $chars = null) {
		$value		= self::flattenSingleValue($value);
		$chars		= self::flattenSingleValue($chars);

		return substr($value, strlen($value) - $chars);
	}

	/**
	 * MID
	 *
	 * @param	string	$value	Value
	 * @param	int		$start	Start character
	 * @param	int		$chars	Number of characters
	 * @return  string
	 */
	public static function MID($value = '', $start = 1, $chars = null) {
		$value		= self::flattenSingleValue($value);
		$start		= self::flattenSingleValue($start);
		$chars		= self::flattenSingleValue($chars);

		return substr($value, --$start, $chars);
	}

	/**
	 * RETURNSTRING
	 *
	 * @param	mixed	$value	Value to check
	 * @return  boolean
	 */
	public static function RETURNSTRING($testValue = '') {
		$testValue	= self::flattenSingleValue($testValue);

		if (is_string($testValue)) {
			return $testValue;
		}
		return Null;
	}

	/**
	 * TRIMSPACES
	 *
	 * @param	mixed	$value	Value to check
	 * @return  string
	 */
	public static function TRIMSPACES($stringValue = '') {
		$stringValue	= self::flattenSingleValue($stringValue);

		if (is_string($stringValue)) {
			return str_replace('  ',' ',trim($stringValue));
		}
		return Null;
	}

	private static $_invalidChars = Null;

	/**
	 * TRIMNONPRINTABLE
	 *
	 * @param	mixed	$value	Value to check
	 * @return  string
	 */
	public static function TRIMNONPRINTABLE($stringValue = '') {
		$stringValue	= self::flattenSingleValue($stringValue);

		if (self::$_invalidChars == Null) {
			self::$_invalidChars = range(chr(0),chr(31));
		}

		if (is_string($stringValue)) {
			return str_replace(self::$_invalidChars,'',trim($stringValue,"\x00..\x1F"));
		}
		return Null;
	}

	/**
	 * IS_BLANK
	 *
	 * @param	mixed	$value	Value to check
	 * @return  boolean
	 */
	public static function IS_BLANK($value = '') {
		$value	= self::flattenSingleValue($value);

		return (is_null($value) || (is_string($value) && ($value == '')));
	}

	/**
	 * IS_ERR
	 *
	 * @param	mixed	$value	Value to check
	 * @return  boolean
	 */
	public static function IS_ERR($value = '') {
		$value		= self::flattenSingleValue($value);

		return self::IS_ERROR($value) && (!self::IS_NA($value));
	}

	/**
	 * IS_ERROR
	 *
	 * @param	mixed	$value	Value to check
	 * @return  boolean
	 */
	public static function IS_ERROR($value = '') {
		$value		= self::flattenSingleValue($value);

		return in_array($value, array_values(self::$_errorCodes));
	}

	/**
	 * IS_NA
	 *
	 * @param	mixed	$value	Value to check
	 * @return  boolean
	 */
	public static function IS_NA($value = '') {
		$value		= self::flattenSingleValue($value);

		return ($value == self::$_errorCodes['na']);
	}

	/**
	 * IS_EVEN
	 *
	 * @param	mixed	$value	Value to check
	 * @return  boolean
	 */
	public static function IS_EVEN($value = 0) {
		$value		= self::flattenSingleValue($value);

		while (intval($value) != $value) {
			$value *= 10;
		}
		return ($value % 2 == 0);
	}

	/**
	 * IS_NUMBER
	 *
	 * @param	mixed	$value		Value to check
	 * @return  boolean
	 */
	public static function IS_NUMBER($value = 0) {
		$value		= self::flattenSingleValue($value);

		return is_numeric($value);
	}

	/**
	 * IS_LOGICAL
	 *
	 * @param	mixed	$value		Value to check
	 * @return  boolean
	 */
	public static function IS_LOGICAL($value = true) {
		$value		= self::flattenSingleValue($value);

		return is_bool($value);
	}

	/**
	 * IS_TEXT
	 *
	 * @param	mixed	$value		Value to check
	 * @return  boolean
	 */
	public static function IS_TEXT($value = '') {
		$value		= self::flattenSingleValue($value);

		return is_string($value);
	}

	/**
	 * STATEMENT_IF
	 *
	 * @param	mixed	$value		Value to check
	 * @param	mixed	$truepart	Value when true
	 * @param	mixed	$falsepart	Value when false
	 * @return  mixed
	 */
	public static function STATEMENT_IF($value = true, $truepart = '', $falsepart = '') {
		$value		= self::flattenSingleValue($value);
		$truepart	= self::flattenSingleValue($truepart);
		$falsepart	= self::flattenSingleValue($falsepart);

		return ($value ? $truepart : $falsepart);
	}

	/**
	 * STATEMENT_IFERROR
	 *
	 * @param	mixed	$value		Value to check , is also value when no error
	 * @param	mixed	$errorpart	Value when error
	 * @return  mixed
	 */
	public static function STATEMENT_IFERROR($value = '', $errorpart = '') {
		return self::STATEMENT_IF(self::IS_ERROR($value), $errorpart, $value);
	}

	/**
	 * VERSION
	 *
	 * @return  string	Version information
	 */
	public static function VERSION() {
		return 'PHPExcel 1.6.3, 2008-08-25';
	}

	/**
	 * DATE
	 *
	 * @param	long	$year
	 * @param	long	$month
	 * @param	long	$day
	 * @return  mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function DATE($year = 0, $month = 1, $day = 1) {
		$year	= (integer) self::flattenSingleValue($year);
		$month	= (integer) self::flattenSingleValue($month);
		$day	= (integer) self::flattenSingleValue($day);

		$baseYear = PHPExcel_Shared_Date::getExcelCalendar();
		// Validate parameters
		if ($year < ($baseYear-1900)) {
			return self::$_errorCodes['num'];
		}
		if ((($baseYear-1900) != 0) && ($year < $baseYear) && ($year >= 1900)) {
			return self::$_errorCodes['num'];
		}

		if (($year < $baseYear) && ($year > ($baseYear-1900))) {
			$year += 1900;
		}

		if ($month < 1) {
			//	Handle year/month adjustment if month < 1
			--$month;
			$year += ceil($month / 12) - 1;
			$month = 13 - abs($month % 12);
		} elseif ($month > 12) {
			//	Handle year/month adjustment if month > 12
			$year += floor($month / 12);
			$month = ($month % 12);
		}

		// Re-validate the year parameter after adjustments
		if (($year < $baseYear) || ($year >= 10000)) {
			return self::$_errorCodes['num'];
		}

		// Execute function
		$excelDateValue = PHPExcel_Shared_Date::FormattedPHPToExcel($year, $month, $day);
		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) $excelDateValue;
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP($excelDateValue);
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return PHPExcel_Shared_Date::ExcelToPHPObject($excelDateValue);
												  break;
		}
	}

	/**
	 * TIME
	 *
	 * @param	long	$hour
	 * @param	long	$minute
	 * @param	long	$second
	 * @return  mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function TIME($hour = 0, $minute = 0, $second = 0) {
		$hour	= self::flattenSingleValue($hour);
		$minute	= self::flattenSingleValue($minute);
		$second	= self::flattenSingleValue($second);

		if ($hour == '') { $hour = 0; }
		if ($minute == '') { $minute = 0; }
		if ($second == '') { $second = 0; }

		if ((!is_numeric($hour)) || (!is_numeric($minute)) || (!is_numeric($second))) {
			return self::$_errorCodes['value'];
		}
		$hour	= (integer) $hour;
		$minute	= (integer) $minute;
		$second	= (integer) $second;

		if ($second < 0) {
			$minute += floor($second / 60);
			$second = 60 - abs($second % 60);
			if ($second == 60) { $second = 0; }
		} elseif ($second >= 60) {
			$minute += floor($second / 60);
			$second = $second % 60;
		}
		if ($minute < 0) {
			$hour += floor($minute / 60);
			$minute = 60 - abs($minute % 60);
			if ($minute == 60) { $minute = 0; }
		} elseif ($minute >= 60) {
			$hour += floor($minute / 60);
			$minute = $minute % 60;
		}

		if ($hour > 23) {
			$hour = $hour % 24;
		} elseif ($hour < 0) {
			return self::$_errorCodes['num'];
		}

		// Execute function
		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: $date = 0;
												  $calendar = PHPExcel_Shared_Date::getExcelCalendar();
												  if ($calendar != PHPExcel_Shared_Date::CALENDAR_WINDOWS_1900) {
													 $date = 1;
												  }
												  return (float) PHPExcel_Shared_Date::FormattedPHPToExcel($calendar, 1, $date, $hour, $minute, $second);
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP(PHPExcel_Shared_Date::FormattedPHPToExcel(1970, 1, 1, $hour-1, $minute, $second));	// -2147468400; //	-2147472000 + 3600
												  break;
			case self::RETURNDATE_PHP_OBJECT	: $dayAdjust = 0;
												  if ($hour < 0) {
													 $dayAdjust = floor($hour / 24);
													 $hour = 24 - abs($hour % 24);
													 if ($hour == 24) { $hour = 0; }
												  } elseif ($hour >= 24) {
													 $dayAdjust = floor($hour / 24);
													 $hour = $hour % 24;
												  }
												  $phpDateObject = new DateTime('1900-01-01 '.$hour.':'.$minute.':'.$second);
												  if ($dayAdjust != 0) {
													 $phpDateObject->modify($dayAdjust.' days');
												  }
												  return $phpDateObject;
												  break;
		}
	}

	/**
	 * DATEVALUE
	 *
	 * @param	string	$dateValue
	 * @return  mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function DATEVALUE($dateValue = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);

		$PHPDateArray = date_parse($dateValue);
		if (($PHPDateArray === False) || ($PHPDateArray['error_count'] > 0)) {
			$testVal1 = strtok($dateValue,'/- ');
			if ($testVal1 !== False) {
				$testVal2 = strtok('/- ');
				if ($testVal2 !== False) {
					$testVal3 = strtok('/- ');
					if ($testVal3 === False) {
						$testVal3 = strftime('%Y');
					}
				} else {
					return self::$_errorCodes['value'];
				}
			} else {
				return self::$_errorCodes['value'];
			}
			$PHPDateArray = date_parse($testVal1.'-'.$testVal2.'-'.$testVal3);
			if (($PHPDateArray === False) || ($PHPDateArray['error_count'] > 0)) {
				$PHPDateArray = date_parse($testVal2.'-'.$testVal1.'-'.$testVal3);
				if (($PHPDateArray === False) || ($PHPDateArray['error_count'] > 0)) {
					return self::$_errorCodes['value'];
				}
			}
		}

		if (($PHPDateArray !== False) && ($PHPDateArray['error_count'] == 0)) {
			// Execute function
			if ($PHPDateArray['year'] == '')	{ $PHPDateArray['year'] = strftime('%Y'); }
			if ($PHPDateArray['month'] == '')	{ $PHPDateArray['month'] = strftime('%m'); }
			if ($PHPDateArray['day'] == '')		{ $PHPDateArray['day'] = strftime('%d'); }
			$excelDateValue = floor(PHPExcel_Shared_Date::FormattedPHPToExcel($PHPDateArray['year'],$PHPDateArray['month'],$PHPDateArray['day'],$PHPDateArray['hour'],$PHPDateArray['minute'],$PHPDateArray['second']));

			switch (self::getReturnDateType()) {
				case self::RETURNDATE_EXCEL			: return (float) $excelDateValue;
													  break;
				case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP($excelDateValue);
													  break;
				case self::RETURNDATE_PHP_OBJECT	: return new DateTime($PHPDateArray['year'].'-'.$PHPDateArray['month'].'-'.$PHPDateArray['day'].' 00:00:00');
													  break;
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * _getDateValue
	 *
	 * @param	string	$dateValue
	 * @return  mixed	Excel date/time serial value, or string if error
	 */
	private static function _getDateValue($dateValue) {
		if (!is_numeric($dateValue)) {
			if ((is_string($dateValue)) && (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC)) {
				return self::$_errorCodes['value'];
			}
			if ((is_object($dateValue)) && ($dateValue instanceof PHPExcel_Shared_Date::$dateTimeObjectType)) {
				$dateValue = PHPExcel_Shared_Date::PHPToExcel($dateValue);
			} else {
				$saveReturnDateType = self::getReturnDateType();
				self::setReturnDateType(self::RETURNDATE_EXCEL);
				$dateValue = self::DATEVALUE($dateValue);
				self::setReturnDateType($saveReturnDateType);
			}
		} elseif (!is_float($dateValue)) {
			$dateValue = PHPExcel_Shared_Date::PHPToExcel($dateValue);
		}
		return $dateValue;
	}

	/**
	 * TIMEVALUE
	 *
	 * @param	string	$timeValue
	 * @return  mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function TIMEVALUE($timeValue) {
		$timeValue	= self::flattenSingleValue($timeValue);

		if ((($PHPDateArray = date_parse($timeValue)) !== False) && ($PHPDateArray['error_count'] == 0)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$excelDateValue = PHPExcel_Shared_Date::FormattedPHPToExcel($PHPDateArray['year'],$PHPDateArray['month'],$PHPDateArray['day'],$PHPDateArray['hour'],$PHPDateArray['minute'],$PHPDateArray['second']);
			} else {
				$excelDateValue = PHPExcel_Shared_Date::FormattedPHPToExcel(1900,1,1,$PHPDateArray['hour'],$PHPDateArray['minute'],$PHPDateArray['second']) - 1;
			}

			switch (self::getReturnDateType()) {
				case self::RETURNDATE_EXCEL			: return (float) $excelDateValue;
													  break;
				case self::RETURNDATE_PHP_NUMERIC	: return (integer) $phpDateValue = PHPExcel_Shared_Date::ExcelToPHP($excelDateValue+25569) - 3600;;
													  break;
				case self::RETURNDATE_PHP_OBJECT	: return new DateTime('1900-01-01 '.$PHPDateArray['hour'].':'.$PHPDateArray['minute'].':'.$PHPDateArray['second']);
													  break;
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * _getTimeValue
	 *
	 * @param	string	$timeValue
	 * @return  mixed	Excel date/time serial value, or string if error
	 */
	private static function _getTimeValue($timeValue) {
		$saveReturnDateType = self::getReturnDateType();
		self::setReturnDateType(self::RETURNDATE_EXCEL);
		$timeValue = self::TIMEVALUE($timeValue);
		self::setReturnDateType($saveReturnDateType);
		return $timeValue;
	}

	/**
	 * DATETIMENOW
	 *
	 * @return  mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function DATETIMENOW() {
		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) PHPExcel_Shared_Date::PHPToExcel(time());
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) time();
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return new DateTime();
												  break;
		}
	}

	/**
	 * DATENOW
	 *
	 * @return  mixed	Excel date/time serial value, PHP date/time serial value or PHP date/time object,
	 *						depending on the value of the ReturnDateType flag
	 */
	public static function DATENOW() {
		$excelDateTime = floor(PHPExcel_Shared_Date::PHPToExcel(time()));
		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) $excelDateTime;
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP($excelDateTime) - 3600;
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return PHPExcel_Shared_Date::ExcelToPHPObject($excelDateTime);
												  break;
		}
	}

	private static function isLeapYear($year) {
		return ((($year % 4) == 0) && (($year % 100) != 0) || (($year % 400) == 0));
	}

	private static function dateDiff360($startDay, $startMonth, $startYear, $endDay, $endMonth, $endYear, $methodUS) {
		if ($startDay == 31) {
			$startDay--;
		} elseif ($methodUS && ($startMonth == 2 && ($startDay == 29 || ($startDay == 28 && !self::isLeapYear($startYear))))) {
			$startDay = 30;
		}
		if ($endDay == 31) {
			if ($methodUS && $startDay != 30) {
				$endDay = 1;
				if ($endMonth == 12) {
					$endYear++;
					$endMonth = 1;
				} else {
					$endMonth++;
				}
			} else {
				$endDay = 30;
			}
		}

		return $endDay + $endMonth * 30 + $endYear * 360 - $startDay - $startMonth * 30 - $startYear * 360;
	}

	/**
	 * DAYS360
	 *
	 * @param	long	$startDate		Excel date serial value or a standard date string
	 * @param	long	$endDate		Excel date serial value or a standard date string
	 * @param	boolean	$method			US or European Method
	 * @return  long	PHP date/time serial
	 */
	public static function DAYS360($startDate = 0, $endDate = 0, $method = false) {
		$startDate	= self::flattenSingleValue($startDate);
		$endDate	= self::flattenSingleValue($endDate);

		if (is_string($startDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (is_string($endDate = self::_getDateValue($endDate))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPStartDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($startDate);
		$startDay = $PHPStartDateObject->format('j');
		$startMonth = $PHPStartDateObject->format('n');
		$startYear = $PHPStartDateObject->format('Y');

		$PHPEndDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($endDate);
		$endDay = $PHPEndDateObject->format('j');
		$endMonth = $PHPEndDateObject->format('n');
		$endYear = $PHPEndDateObject->format('Y');

		return self::dateDiff360($startDay, $startMonth, $startYear, $endDay, $endMonth, $endYear, !$method);
	}

	/**
	 * DATEDIF
	 *
	 * @param	long	$startDate		Excel date serial value or a standard date string
	 * @param	long	$endDate		Excel date serial value or a standard date string
	 * @param	string	$unit
	 * @return  long	Interval between the dates
	 */
	public static function DATEDIF($startDate = 0, $endDate = 0, $unit = 'D') {
		$startDate	= self::flattenSingleValue($startDate);
		$endDate	= self::flattenSingleValue($endDate);
		$unit		= strtoupper(self::flattenSingleValue($unit));

		if (is_string($startDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (is_string($endDate = self::_getDateValue($endDate))) {
			return self::$_errorCodes['value'];
		}

		// Validate parameters
		if ($startDate >= $endDate) {
			return self::$_errorCodes['num'];
		}

		// Execute function
		$difference = $endDate - $startDate;

		$PHPStartDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($startDate);
		$startDays = $PHPStartDateObject->format('j');
		$startMonths = $PHPStartDateObject->format('n');
		$startYears = $PHPStartDateObject->format('Y');

		$PHPEndDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($endDate);
		$endDays = $PHPEndDateObject->format('j');
		$endMonths = $PHPEndDateObject->format('n');
		$endYears = $PHPEndDateObject->format('Y');

		$retVal = self::$_errorCodes['num'];
		switch ($unit) {
			case 'D':
				$retVal = intval($difference);
				break;
			case 'M':
				$retVal = intval($endMonths - $startMonths) + (intval($endYears - $startYears) * 12);
				//	We're only interested in full months
				if ($endDays < $startDays) {
					$retVal--;
				}
				break;
			case 'Y':
				$retVal = intval($endYears - $startYears);
				//	We're only interested in full months
				if ($endMonths < $startMonths) {
					$retVal--;
				} elseif (($endMonths == $startMonths) && ($endDays < $startDays)) {
					$retVal--;
				}
				break;
			case 'MD':
				if ($endDays < $startDays) {
					$retVal = $endDays;
					$PHPEndDateObject->modify('-'.$endDays.' days');
					$adjustDays = $PHPEndDateObject->format('j');
					if ($adjustDays > $startDays) {
						$retVal += ($adjustDays - $startDays);
					}
				} else {
					$retVal = $endDays - $startDays;
				}
				break;
			case 'YM':
				$retVal = abs(intval($endMonths - $startMonths));
				//	We're only interested in full months
				if ($endDays < $startDays) {
					$retVal--;
				}
				break;
			case 'YD':
				$retVal = intval($difference);
				if ($endYears > $startYears) {
					while ($endYears > $startYears) {
						$PHPEndDateObject->modify('-1 year');
						$endYears = $PHPEndDateObject->format('Y');
					}
					$retVal = abs($PHPEndDateObject->format('z') - $PHPStartDateObject->format('z'));
				}
				break;
		}
		return $retVal;
	}

	/**
	 * YEARFRAC
	 *
	 * @param	long	$startDate		Excel date serial value or a standard date string
	 * @param	long	$endDate		Excel date serial value or a standard date string
	 * @param	integer	$method			Method used for the calculation
	 * @return  long	PHP date/time serial
	 */
	public static function YEARFRAC($startDate = 0, $endDate = 0, $method = 0) {
		$startDate	= self::flattenSingleValue($startDate);
		$endDate	= self::flattenSingleValue($endDate);
		$method		= self::flattenSingleValue($method);

		if (is_string($startDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (is_string($endDate = self::_getDateValue($endDate))) {
			return self::$_errorCodes['value'];
		}

		if ((is_numeric($method)) && (!is_string($method))) {
			switch($method) {
				case 0	:
					return self::DAYS360($startDate,$endDate) / 360;
					break;
				case 1	:
					$startYear = self::YEAR($startDate);
					$endYear = self::YEAR($endDate);
					$leapDay = 0;
					if (self::isLeapYear($startYear) || self::isLeapYear($endYear)) {
						$leapDay = 1;
					}
					return self::DATEDIF($startDate,$endDate) / (365 + $leapDay);
					break;
				case 2	:
					return self::DATEDIF($startDate,$endDate) / 360;
					break;
				case 3	:
					return self::DATEDIF($startDate,$endDate) / 365;
					break;
				case 4	:
					return self::DAYS360($startDate,$endDate,True) / 360;
					break;
			}
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * NETWORKDAYS
	 *
	 * @param	mixed				Start date
	 * @param	mixed				End date
	 * @param	array of mixed		Optional Date Series
	 * @return  long	Interval between the dates
	 */
	public static function NETWORKDAYS($startDate,$endDate) {
		//	Flush the mandatory start and end date that are referenced in the function definition
		$dateArgs = self::flattenArray(func_get_args());
		array_shift($dateArgs);
		array_shift($dateArgs);

		//	Validate the start and end dates
		if (is_string($startDate = $sDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (is_string($endDate = $eDate = self::_getDateValue($endDate))) {
			return self::$_errorCodes['value'];
		}

		if ($sDate > $eDate) {
			$startDate = $eDate;
			$endDate = $sDate;
		}

		// Execute function
		$startDoW = 6 - self::DAYOFWEEK($startDate,2);
		if ($startDoW < 0) { $startDoW = 0; }
		$endDoW = self::DAYOFWEEK($endDate,2);
		if ($endDoW >= 6) { $endDoW = 0; }

		$wholeWeekDays = floor(($endDate - $startDate) / 7) * 5;
		$partWeekDays = $endDoW + $startDoW;
		if ($partWeekDays > 5) {
			$partWeekDays -= 5;
		}

		//	Test any extra holiday parameters
		$holidayCountedArray = array();
		foreach ($dateArgs as $holidayDate) {
			if (is_string($holidayDate = self::_getDateValue($holidayDate))) {
				return self::$_errorCodes['value'];
			}
			if (($holidayDate >= $startDate) && ($holidayDate <= $endDate)) {
				if ((self::DAYOFWEEK($holidayDate,2) < 6) && (!in_array($holidayDate,$holidayCountedArray))) {
					--$partWeekDays;
					$holidayCountedArray[] = $holidayDate;
				}
			}
		}

		if ($sDate > $eDate) {
			return 0 - ($wholeWeekDays + $partWeekDays);
		}
		return $wholeWeekDays + $partWeekDays;
	}

	/**
	 * WORKDAY
	 *
	 * @param	mixed				Start date
	 * @param	mixed				number of days for adjustment
	 * @param	array of mixed		Optional Date Series
	 * @return  long	Interval between the dates
	 */
	public static function WORKDAY($startDate,$endDays) {
		$dateArgs = self::flattenArray(func_get_args());

		array_shift($dateArgs);
		array_shift($dateArgs);

		if (is_string($startDate = self::_getDateValue($startDate))) {
			return self::$_errorCodes['value'];
		}
		if (!is_numeric($endDays)) {
			return self::$_errorCodes['value'];
		}
		$endDate = (float) $startDate + (floor($endDays / 5) * 7) + ($endDays % 5);
		if ($endDays < 0) {
			$endDate += 7;
		}

		$endDoW = self::DAYOFWEEK($endDate,3);
		if ($endDoW >= 5) {
			if ($endDays >= 0) {
				$endDate += (7 - $endDoW);
			} else {
				$endDate -= ($endDoW - 5);
			}
		}

		//	Test any extra holiday parameters
		if (count($dateArgs) > 0) {
			$holidayCountedArray = $holidayDates = array();
			foreach ($dateArgs as $holidayDate) {
				if (is_string($holidayDate = self::_getDateValue($holidayDate))) {
					return self::$_errorCodes['value'];
				}
				$holidayDates[] = $holidayDate;
			}
			if ($endDays >= 0) {
				sort($holidayDates, SORT_NUMERIC);
			} else {
				rsort($holidayDates, SORT_NUMERIC);
			}
			foreach ($holidayDates as $holidayDate) {
				if ($endDays >= 0) {
					if (($holidayDate >= $startDate) && ($holidayDate <= $endDate)) {
						if ((self::DAYOFWEEK($holidayDate,2) < 6) && (!in_array($holidayDate,$holidayCountedArray))) {
							++$endDate;
							$holidayCountedArray[] = $holidayDate;
						}
					}
				} else {
					if (($holidayDate <= $startDate) && ($holidayDate >= $endDate)) {
						if ((self::DAYOFWEEK($holidayDate,2) < 6) && (!in_array($holidayDate,$holidayCountedArray))) {
							--$endDate;
							$holidayCountedArray[] = $holidayDate;
						}
					}
				}
				$endDoW = self::DAYOFWEEK($endDate,3);
				if ($endDoW >= 5) {
					if ($endDays >= 0) {
						$endDate += (7 - $endDoW);
					} else {
						$endDate -= ($endDoW - 5);
					}
				}
			}
		}

		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) $endDate;
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP($endDate);
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return PHPExcel_Shared_Date::ExcelToPHPObject($endDate);
												  break;
		}
	}

	/**
	 * DAYOFMONTH
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @return  int		Day
	 */
	public static function DAYOFMONTH($dateValue = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);

		return $PHPDateObject->format('j');
	}

	/**
	 * DAYOFWEEK
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @return  int		Day
	 */
	public static function DAYOFWEEK($dateValue = 1, $style = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);
		$style		= floor(self::flattenSingleValue($style));

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);
		$DoW = $PHPDateObject->format('w');
		$firstDay = 1;
		switch ($style) {
			case 1: ++$DoW;
					break;
			case 2: if ($DoW == 0) { $DoW = 7; }
					break;
			case 3: if ($DoW == 0) { $DoW = 7; }
					$firstDay = 0;
					--$DoW;
					break;
			default:
		}
		if (self::$compatibilityMode == self::COMPATIBILITY_EXCEL) {
			//	Test for Excel's 1900 leap year, and introduce the error as required
			if (($PHPDateObject->format('Y') == 1900) && ($PHPDateObject->format('n') <= 2)) {
				--$DoW;
				if ($DoW < $firstDay) {
					$DoW += 7;
				}
			}
		}

		return $DoW;
	}

	/**
	 * WEEKOFYEAR
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @param	boolean	$method			Week begins on Sunday or Monday
	 * @return  int		Week Number
	 */
	public static function WEEKOFYEAR($dateValue = 1, $method = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);
		$method		= floor(self::flattenSingleValue($method));

		if (!is_numeric($method)) {
			return self::$_errorCodes['value'];
		} elseif (($method < 1) || ($method > 2)) {
			return self::$_errorCodes['num'];
		}

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);
		$dayOfYear = $PHPDateObject->format('z');
		$dow = $PHPDateObject->format('w');
		$PHPDateObject->modify('-'.$dayOfYear.' days');
		$dow = $PHPDateObject->format('w');
		$daysInFirstWeek = 7 - (($dow + (2 - $method)) % 7);
		$dayOfYear -= $daysInFirstWeek;
		$weekOfYear = ceil($dayOfYear / 7) + 1;

		return $weekOfYear;
	}

	/**
	 * MONTHOFYEAR
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @return  int		Month
	 */
	public static function MONTHOFYEAR($dateValue = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);

		return $PHPDateObject->format('n');
	}

	/**
	 * YEAR
	 *
	 * @param	long	$dateValue		Excel date serial value or a standard date string
	 * @return  int		Year
	 */
	public static function YEAR($dateValue = 1) {
		$dateValue	= self::flattenSingleValue($dateValue);

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);

		return $PHPDateObject->format('Y');
	}

	/**
	 * HOUROFDAY
	 *
	 * @param	mixed	$timeValue		Excel time serial value or a standard time string
	 * @return  int		Hour
	 */
	public static function HOUROFDAY($timeValue = 0) {
		$timeValue	= self::flattenSingleValue($timeValue);

		if (!is_numeric($timeValue)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				$testVal = strtok($timeValue,'/-: ');
				if (strlen($testVal) < strlen($timeValue)) {
					return self::$_errorCodes['value'];
				}
			}
			$timeValue = self::_getTimeValue($timeValue);
			if (is_string($timeValue)) {
				return self::$_errorCodes['value'];
			}
		}
		// Execute function
		if (is_real($timeValue)) {
			$timeValue = PHPExcel_Shared_Date::ExcelToPHP($timeValue);
		}
		return date('G',$timeValue);
	}

	/**
	 * MINUTEOFHOUR
	 *
	 * @param	long	$timeValue		Excel time serial value or a standard time string
	 * @return  int		Minute
	 */
	public static function MINUTEOFHOUR($timeValue = 0) {
		$timeValue = $timeTester	= self::flattenSingleValue($timeValue);

		if (!is_numeric($timeValue)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				$testVal = strtok($timeValue,'/-: ');
				if (strlen($testVal) < strlen($timeValue)) {
					return self::$_errorCodes['value'];
				}
			}
			$timeValue = self::_getTimeValue($timeValue);
			if (is_string($timeValue)) {
				return self::$_errorCodes['value'];
			}
		}
		// Execute function
		if (is_real($timeValue)) {
			$timeValue = PHPExcel_Shared_Date::ExcelToPHP($timeValue);
		}
		return (int) date('i',$timeValue);
	}

	/**
	 * SECONDOFMINUTE
	 *
	 * @param	long	$timeValue		Excel time serial value or a standard time string
	 * @return  int		Second
	 */
	public static function SECONDOFMINUTE($timeValue = 0) {
		$timeValue	= self::flattenSingleValue($timeValue);

		if (!is_numeric($timeValue)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
				$testVal = strtok($timeValue,'/-: ');
				if (strlen($testVal) < strlen($timeValue)) {
					return self::$_errorCodes['value'];
				}
			}
			$timeValue = self::_getTimeValue($timeValue);
			if (is_string($timeValue)) {
				return self::$_errorCodes['value'];
			}
		}
		// Execute function
		if (is_real($timeValue)) {
			$timeValue = PHPExcel_Shared_Date::ExcelToPHP($timeValue);
		}
		return (int) date('s',$timeValue);
	}

	private static function adjustDateByMonths ($dateValue = 0, $adjustmentMonths = 0) {
		// Execute function
		$PHPDateObject = PHPExcel_Shared_Date::ExcelToPHPObject($dateValue);
		$oMonth = (int) $PHPDateObject->format('m');
		$oYear = (int) $PHPDateObject->format('Y');

		$adjustmentMonthsString = (string) $adjustmentMonths;
		if ($adjustmentMonths > 0) {
			$adjustmentMonthsString = '+'.$adjustmentMonths;
		}
		if ($adjustmentMonths != 0) {
			$PHPDateObject->modify($adjustmentMonthsString.' months');
		}
		$nMonth = (int) $PHPDateObject->format('m');
		$nYear = (int) $PHPDateObject->format('Y');

		$monthDiff = ($nMonth - $oMonth) + (($nYear - $oYear) * 12);
		if ($monthDiff != $adjustmentMonths) {
			$adjustDays = (int) $PHPDateObject->format('d');
			$adjustDaysString = '-'.$adjustDays.' days';
			$PHPDateObject->modify($adjustDaysString);
		}
		return $PHPDateObject;
	}

	/**
	 * EDATE
	 *
	 * Returns the serial number that represents the date that is the indicated number of months before or after a specified date
	 * (the start_date). Use EDATE to calculate maturity dates or due dates that fall on the same day of the month as the date of issue.
	 *
	 * @param	long	$dateValue				Excel date serial value or a standard date string
	 * @param	int		$adjustmentMonths		Number of months to adjust by
	 * @return  long	Excel date serial value
	 */
	public static function EDATE($dateValue = 1, $adjustmentMonths = 0) {
		$dateValue			= self::flattenSingleValue($dateValue);
		$adjustmentMonths	= floor(self::flattenSingleValue($adjustmentMonths));

		if (!is_numeric($adjustmentMonths)) {
			return self::$_errorCodes['value'];
		}

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = self::adjustDateByMonths($dateValue,$adjustmentMonths);

		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) PHPExcel_Shared_Date::PHPToExcel($PHPDateObject);
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP(PHPExcel_Shared_Date::PHPToExcel($PHPDateObject));
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return $PHPDateObject;
												  break;
		}
	}

	/**
	 * EOMONTH
	 *
	 * Returns the serial number for the last day of the month that is the indicated number of months before or after start_date.
	 * Use EOMONTH to calculate maturity dates or due dates that fall on the last day of the month.
	 *
	 * @param	long	$dateValue			Excel date serial value or a standard date string
	 * @param	int		$adjustmentMonths	Number of months to adjust by
	 * @return  long	Excel date serial value
	 */
	public static function EOMONTH($dateValue = 1, $adjustmentMonths = 0) {
		$dateValue			= self::flattenSingleValue($dateValue);
		$adjustmentMonths	= floor(self::flattenSingleValue($adjustmentMonths));

		if (!is_numeric($adjustmentMonths)) {
			return self::$_errorCodes['value'];
		}

		if (is_string($dateValue = self::_getDateValue($dateValue))) {
			return self::$_errorCodes['value'];
		}

		// Execute function
		$PHPDateObject = self::adjustDateByMonths($dateValue,$adjustmentMonths+1);
		$adjustDays = (int) $PHPDateObject->format('d');
		$adjustDaysString = '-'.$adjustDays.' days';
		$PHPDateObject->modify($adjustDaysString);

		switch (self::getReturnDateType()) {
			case self::RETURNDATE_EXCEL			: return (float) PHPExcel_Shared_Date::PHPToExcel($PHPDateObject);
												  break;
			case self::RETURNDATE_PHP_NUMERIC	: return (integer) PHPExcel_Shared_Date::ExcelToPHP(PHPExcel_Shared_Date::PHPToExcel($PHPDateObject));
												  break;
			case self::RETURNDATE_PHP_OBJECT	: return $PHPDateObject;
												  break;
		}
	}

	/**
	 * TRUNC
	 *
	 * Truncates value to the number of fractional digits by number_digits.
	 *
	 * @param	float		$value
	 * @param	int			$number_digits
	 * @return  float		Truncated value
	 */
	public static function TRUNC($value = 0, $number_digits = 0) {
		$value			= self::flattenSingleValue($value);
		$number_digits	= self::flattenSingleValue($number_digits);

		// Validate parameters
		if ($number_digits < 0) {
			return self::$_errorCodes['value'];
		}

		// Truncate
		if ($number_digits > 0) {
			$value = $value * pow(10, $number_digits);
		}
		$value = intval($value);
		if ($number_digits > 0) {
			$value = $value / pow(10, $number_digits);
		}

		// Return
		return $value;
	}

	/**
	 * POWER
	 *
	 * Computes x raised to the power y.
	 *
	 * @param	float		$x
	 * @param	float		$y
	 * @return  float
	 */
	public static function POWER($x = 0, $y = 2) {
		$x	= self::flattenSingleValue($x);
		$y	= self::flattenSingleValue($y);

		// Validate parameters
		if ($x < 0) {
			return self::$_errorCodes['num'];
		}
		if ($x == 0 && $y <= 0) {
			return self::$_errorCodes['divisionbyzero'];
		}

		// Return
		return pow($x, $y);
	}

	/**
	 * BINTODEC
	 *
	 * Return a binary value as Decimal.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function BINTODEC($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
			$x = floor($x);
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[01]/',$x,$out)) {
			return self::$_errorCodes['num'];
		}
		if (strlen($x) > 10) {
			return self::$_errorCodes['num'];
		} elseif (strlen($x) == 10) {
			//	Two's Complement
			$x = substr($x,-9);
			return '-'.(512-bindec($x));
		}
		return bindec($x);
	}

	/**
	 * BINTOHEX
	 *
	 * Return a binary value as Hex.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function BINTOHEX($x) {
		$x	= floor(self::flattenSingleValue($x));

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
			$x = floor($x);
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[01]/',$x,$out)) {
			return self::$_errorCodes['num'];
		}
		if (strlen($x) > 10) {
			return self::$_errorCodes['num'];
		} elseif (strlen($x) == 10) {
			//	Two's Complement
			return str_repeat('F',8).substr(strtoupper(dechex(bindec(substr($x,-9)))),-2);
		}
		return strtoupper(dechex(bindec($x)));
	}

	/**
	 * BINTOOCT
	 *
	 * Return a binary value as Octal.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function BINTOOCT($x) {
		$x	= floor(self::flattenSingleValue($x));

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		if (self::$compatibilityMode == self::COMPATIBILITY_GNUMERIC) {
			$x = floor($x);
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[01]/',$x,$out)) {
			return self::$_errorCodes['num'];
		}
		if (strlen($x) > 10) {
			return self::$_errorCodes['num'];
		} elseif (strlen($x) == 10) {
			//	Two's Complement
			return str_repeat('7',7).substr(strtoupper(dechex(bindec(substr($x,-9)))),-3);
		}
		return decoct(bindec($x));
	}

	/**
	 * DECTOBIN
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function DECTOBIN($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[-0123456789.]/',$x,$out)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) floor($x);
		$r = decbin($x);
		if (strlen($r) == 32) {
			//	Two's Complement
			$r = substr($r,-10);
		} elseif (strlen($r) > 11) {
			return self::$_errorCodes['num'];
		}
		return $r;
	}

	/**
	 * DECTOOCT
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function DECTOOCT($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[-0123456789.]/',$x,$out)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) floor($x);
		$r = decoct($x);
		if (strlen($r) == 11) {
			//	Two's Complement
			$r = substr($r,-10);
		}
		return ($r);
	}

	/**
	 * DECTOHEX
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function DECTOHEX($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			if (self::$compatibilityMode == self::COMPATIBILITY_OPENOFFICE) {
				$x = (int) $x;
			} else {
				return self::$_errorCodes['value'];
			}
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[-0123456789.]/',$x,$out)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) floor($x);
		$r = strtoupper(dechex($x));
		if (strlen($r) == 8) {
			//	Two's Complement
			$r = 'FF'.$r;
		}
		return ($r);
	}

	/**
	 * HEXTOBIN
	 *
	 * Return a hex value as binary.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function HEXTOBIN($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[0123456789ABCDEF]/',strtoupper($x),$out)) {
			return self::$_errorCodes['num'];
		}
		return decbin(hexdec($x));
	}

	/**
	 * HEXTOOCT
	 *
	 * Return a hex value as octal.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function HEXTOOCT($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[0123456789ABCDEF]/',strtoupper($x),$out)) {
			return self::$_errorCodes['num'];
		}
		return decoct(hexdec($x));
	}

	/**
	 * HEXTODEC
	 *
	 * Return a hex value as octal.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function HEXTODEC($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (strlen($x) > preg_match_all('/[0123456789ABCDEF]/',strtoupper($x),$out)) {
			return self::$_errorCodes['num'];
		}
		return hexdec($x);
	}

	/**
	 * OCTTOBIN
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function OCTTOBIN($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (preg_match_all('/[01234567]/',$x,$out) != strlen($x)) {
			return self::$_errorCodes['num'];
		}
		return decbin(octdec($x));
	}

	/**
	 * OCTTODEC
	 *
	 * Return an octal value as binary.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function OCTTODEC($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (preg_match_all('/[01234567]/',$x,$out) != strlen($x)) {
			return self::$_errorCodes['num'];
		}
		return octdec($x);
	}

	/**
	 * OCTTOHEX
	 *
	 * Return an octal value as hex.
	 *
	 * @param	string		$x
	 * @return  string
	 */
	public static function OCTTOHEX($x) {
		$x	= self::flattenSingleValue($x);

		if (is_bool($x)) {
			return self::$_errorCodes['value'];
		}
		$x = (string) $x;
		if (preg_match_all('/[01234567]/',$x,$out) != strlen($x)) {
			return self::$_errorCodes['num'];
		}
		return strtoupper(dechex(octdec($x)));
	}

	public function parseComplex($complexNumber) {
		$workString = $complexNumber;

		$realNumber = $imaginary = 0;
		//	Extract the suffix, if there is one
		$suffix = substr($workString,-1);
		if (!is_numeric($suffix)) {
			$workString = substr($workString,0,-1);
		} else {
			$suffix = '';
		}

		//	Split the input into its Real and Imaginary components
		$leadingSign = (($workString{0} == '+') || ($workString{0} == '-')) ? 1 : 0;
		$power = '';
		$realNumber = strtok($workString, '+-');
		if (strtoupper(substr($realNumber,-1)) == 'E') {
			$power = strtok('+-');
			$leadingSign++;
		}
		$realNumber = substr($workString,0,strlen($realNumber)+strlen($power)+$leadingSign);

		if ($suffix != '') {
			$imaginary = substr($workString,strlen($realNumber));

			if (($imaginary == '') && (($realNumber == '') || ($realNumber == '+') || ($realNumber == '-'))) {
				$imaginary = $realNumber.'1';
				$realNumber = '0';
			} else if ($imaginary == '') {
				$imaginary = $realNumber;
				$realNumber = '0';
			} elseif (($imaginary == '+') || ($imaginary == '-')) {
				$imaginary .= '1';
			}
		}

		$complexArray = array( 'real'		=> $realNumber,
							   'imaginary'	=> $imaginary,
							   'suffix'		=> $suffix
							 );

		return $complexArray;
	}

	/**
	 * COMPLEX
	 *
	 * returns a complex number of the form x + yi or x + yj.
	 *
	 * @param	float		$realNumber
	 * @param	float		$imaginary
	 * @param	string		$suffix
	 * @return  string
	 */
	public static function COMPLEX($realNumber=0.0, $imaginary=0.0, $suffix='i') {
		$realNumber	= self::flattenSingleValue($realNumber);
		$imaginary	= self::flattenSingleValue($imaginary);
		$suffix		= self::flattenSingleValue($suffix);

		if (((is_numeric($realNumber)) && (is_numeric($imaginary))) &&
			(($suffix == 'i') || ($suffix == 'j'))) {
			if ($realNumber == 0.0) {
				if ($imaginary == 0.0) {
					return (string) '0';
				} elseif ($imaginary == 1.0) {
					return (string) $suffix;
				} elseif ($imaginary == -1.0) {
					return (string) '-'.$suffix;
				}
				return (string) $imaginary.$suffix;
			} elseif ($imaginary == 0.0) {
				return (string) $realNumber;
			} elseif ($imaginary == 1.0) {
				return (string) $realNumber.'+'.$suffix;
			} elseif ($imaginary == -1.0) {
				return (string) $realNumber.'-'.$suffix;
			}
			if ($imaginary > 0) { $imaginary = (string) '+'.$imaginary; }
			return (string) $realNumber.$imaginary.$suffix;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * IMAGINARY
	 *
	 * Returns the imaginary coefficient of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  real
	 */
	public static function IMAGINARY($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}
		return $parsedComplex['imaginary'];
	}

	/**
	 * IMREAL
	 *
	 * Returns the real coefficient of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  real
	 */
	public static function IMREAL($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}
		return $parsedComplex['real'];
	}

	/**
	 * IMABS
	 *
	 * Returns the absolute value (modulus) of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  real
	 */
	public static function IMABS($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}
		return sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary']));
	}

	/**
	 * IMARGUMENT
	 *
	 * Returns the argument theta of a complex number, i.e. the angle in radians from the real axis to the representation of the number in polar coordinates.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMARGUMENT($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if ($parsedComplex['real'] == 0.0) {
			if ($parsedComplex['imaginary'] == 0.0) {
				return 0.0;
			} elseif($parsedComplex['imaginary'] < 0.0) {
				return pi() / -2;
			} else {
				return pi() / 2;
			}
		} elseif ($parsedComplex['real'] > 0.0) {
			return atan($parsedComplex['imaginary'] / $parsedComplex['real']);
		} elseif ($parsedComplex['imaginary'] < 0.0) {
			return 0 - (pi() - atan(abs($parsedComplex['imaginary']) / abs($parsedComplex['real'])));
		} else {
			return pi() - atan($parsedComplex['imaginary'] / abs($parsedComplex['real']));
		}
	}

	/**
	 * IMCONJUGATE
	 *
	 * Returns the complex conjugate of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMCONJUGATE($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);

		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if ($parsedComplex['imaginary'] == 0.0) {
			return $parsedComplex['real'];
		} else {
			return self::COMPLEX($parsedComplex['real'], 0 - $parsedComplex['imaginary'], $parsedComplex['suffix']);
		}
	}

	/**
	 * IMCOS
	 *
	 * Returns the cosine of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMCOS($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if ($parsedComplex['imaginary'] == 0.0) {
			return cos($parsedComplex['real']);
		} else {
			return self::IMCONJUGATE(self::COMPLEX(cos($parsedComplex['real']) * cosh($parsedComplex['imaginary']),sin($parsedComplex['real']) * sinh($parsedComplex['imaginary']),$parsedComplex['suffix']));
		}
	}

	/**
	 * IMSIN
	 *
	 * Returns the sine of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMSIN($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if ($parsedComplex['imaginary'] == 0.0) {
			return sin($parsedComplex['real']);
		} else {
			return self::COMPLEX(sin($parsedComplex['real']) * cosh($parsedComplex['imaginary']),cos($parsedComplex['real']) * sinh($parsedComplex['imaginary']),$parsedComplex['suffix']);
		}
	}

	/**
	 * IMSQRT
	 *
	 * Returns the square root of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMSQRT($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		$theta = self::IMARGUMENT($complexNumber);
		$d1 = cos($theta / 2);
		$d2 = sin($theta / 2);
		$r = sqrt(sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary'])));

		if ($parsedComplex['suffix'] == '') {
			return self::COMPLEX($d1 * $r,$d2 * $r);
		} else {
			return self::COMPLEX($d1 * $r,$d2 * $r,$parsedComplex['suffix']);
		}
	}

	/**
	 * IMLN
	 *
	 * Returns the natural logarithm of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMLN($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return self::$_errorCodes['num'];
		}

		$logR = log(sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary'])));
		$t = self::IMARGUMENT($complexNumber);

		if ($parsedComplex['suffix'] == '') {
			return self::COMPLEX($logR,$t);
		} else {
			return self::COMPLEX($logR,$t,$parsedComplex['suffix']);
		}
	}

	/**
	 * IMLOG10
	 *
	 * Returns the common logarithm (base 10) of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMLOG10($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return self::$_errorCodes['num'];
		} elseif (($parsedComplex['real'] > 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return log10($parsedComplex['real']);
		}

		return self::IMPRODUCT(log10(EULER),self::IMLN($complexNumber));
	}

	/**
	 * IMLOG2
	 *
	 * Returns the common logarithm (base 10) of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMLOG2($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return self::$_errorCodes['num'];
		} elseif (($parsedComplex['real'] > 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return log($parsedComplex['real'],2);
		}

		return self::IMPRODUCT(log(EULER,2),self::IMLN($complexNumber));
	}

	/**
	 * IMEXP
	 *
	 * Returns the exponential of a complex number in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMEXP($complexNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		if (($parsedComplex['real'] == 0.0) && ($parsedComplex['imaginary'] == 0.0)) {
			return '1';
		}

		$e = exp($parsedComplex['real']);
		$eX = $e * cos($parsedComplex['imaginary']);
		$eY = $e * sin($parsedComplex['imaginary']);

		if ($parsedComplex['suffix'] == '') {
			return self::COMPLEX($eX,$eY);
		} else {
			return self::COMPLEX($eX,$eY,$parsedComplex['suffix']);
		}
	}

	/**
	 * IMPOWER
	 *
	 * Returns a complex number in x + yi or x + yj text format raised to a power.
	 *
	 * @param	string		$complexNumber
	 * @return  string
	 */
	public static function IMPOWER($complexNumber,$realNumber) {
		$complexNumber	= self::flattenSingleValue($complexNumber);
		$realNumber		= self::flattenSingleValue($realNumber);

		if (!is_numeric($realNumber)) {
			return self::$_errorCodes['value'];
		}

		$parsedComplex = self::parseComplex($complexNumber);
		if (!is_array($parsedComplex)) {
			return $parsedComplex;
		}

		$r = sqrt(($parsedComplex['real'] * $parsedComplex['real']) + ($parsedComplex['imaginary'] * $parsedComplex['imaginary']));
		$rPower = pow($r,$realNumber);
		$theta = self::IMARGUMENT($complexNumber) * $realNumber;
		if ($parsedComplex['imaginary'] == 0.0) {
			return self::COMPLEX($rPower * cos($theta),$rPower * sin($theta),$parsedComplex['suffix']);
		} else {
			return self::COMPLEX($rPower * cos($theta),$rPower * sin($theta),$parsedComplex['suffix']);
		}
	}

	/**
	 * IMDIV
	 *
	 * Returns the quotient of two complex numbers in x + yi or x + yj text format.
	 *
	 * @param	string		$complexDividend
	 * @param	string		$complexDivisor
	 * @return  real
	 */
	public static function IMDIV($complexDividend,$complexDivisor) {
		$complexDividend	= self::flattenSingleValue($complexDividend);
		$complexDivisor	= self::flattenSingleValue($complexDivisor);

		$parsedComplexDividend = self::parseComplex($complexDividend);
		if (!is_array($parsedComplexDividend)) {
			return $parsedComplexDividend;
		}

		$parsedComplexDivisor = self::parseComplex($complexDivisor);
		if (!is_array($parsedComplexDivisor)) {
			return $parsedComplexDividend;
		}

		if ($parsedComplexDividend['suffix'] != $parsedComplexDivisor['suffix']) {
			return self::$_errorCodes['num'];
		}

		$d1 = ($parsedComplexDividend['real'] * $parsedComplexDivisor['real']) + ($parsedComplexDividend['imaginary'] * $parsedComplexDivisor['imaginary']);
		$d2 = ($parsedComplexDividend['imaginary'] * $parsedComplexDivisor['real']) - ($parsedComplexDividend['real'] * $parsedComplexDivisor['imaginary']);
		$d3 = ($parsedComplexDivisor['real'] * $parsedComplexDivisor['real']) + ($parsedComplexDivisor['imaginary'] * $parsedComplexDivisor['imaginary']);

		return $d1/$d3.$d2/$d3.$parsedComplexDivisor['suffix'];
	}

	/**
	 * IMSUB
	 *
	 * Returns the difference of two complex numbers in x + yi or x + yj text format.
	 *
	 * @param	string		$complexNumber1
	 * @param	string		$complexNumber2
	 * @return  real
	 */
	public static function IMSUB($complexNumber1,$complexNumber2) {
		$complexNumber1	= self::flattenSingleValue($complexNumber1);
		$complexNumber2	= self::flattenSingleValue($complexNumber2);

		$parsedComplex1 = self::parseComplex($complexNumber1);
		if (!is_array($parsedComplex1)) {
			return $parsedComplex1;
		}

		$parsedComplex2 = self::parseComplex($complexNumber2);
		if (!is_array($parsedComplex2)) {
			return $parsedComplex2;
		}

		if ($parsedComplex1['suffix'] != $parsedComplex2['suffix']) {
			return self::$_errorCodes['num'];
		}

		$d1 = $parsedComplex1['real'] - $parsedComplex2['real'];
		$d2 = $parsedComplex1['imaginary'] - $parsedComplex2['imaginary'];

		return self::COMPLEX($d1,$d2,$parsedComplex1['suffix']);
	}

	/**
	 * IMSUM
	 *
	 * Returns the sum of two or more complex numbers in x + yi or x + yj text format.
	 *
	 * @param	array of mixed		Data Series
	 * @return  real
	 */
	public static function IMSUM() {
		// Return value
		$returnValue = self::parseComplex('0');
		$activeSuffix = '';

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			$parsedComplex = self::parseComplex($arg);
			if (!is_array($parsedComplex)) {
				return $parsedComplex;
			}

			if ($activeSuffix == '') {
				$activeSuffix = $parsedComplex['suffix'];
			} elseif ($activeSuffix != $parsedComplex['suffix']) {
				return self::$_errorCodes['num'];
			}

			$returnValue['real'] += $parsedComplex['real'];
			$returnValue['imaginary'] += $parsedComplex['imaginary'];
		}

		if ($returnValue['imaginary'] == 0.0) { $activeSuffix = ''; }
		return self::COMPLEX($returnValue['real'],$returnValue['imaginary'],$activeSuffix);
	}

	/**
	 * IMPRODUCT
	 *
	 * Returns the product of two or more complex numbers in x + yi or x + yj text format.
	 *
	 * @param	array of mixed		Data Series
	 * @return  real
	 */
	public static function IMPRODUCT() {
		// Return value
		$returnValue = self::parseComplex('1');
		$activeSuffix = '';

		// Loop through the arguments
		$aArgs = self::flattenArray(func_get_args());
		foreach ($aArgs as $arg) {
			$parsedComplex = self::parseComplex($arg);
			if (!is_array($parsedComplex)) {
				return $parsedComplex;
			}
			$workValue = $returnValue;
			if (($parsedComplex['suffix'] != '') && ($activeSuffix == '')) {
				$activeSuffix = $parsedComplex['suffix'];
			} elseif (($parsedComplex['suffix'] != '') && ($activeSuffix != $parsedComplex['suffix'])) {
				return self::$_errorCodes['num'];
			}
			$returnValue['real'] = ($workValue['real'] * $parsedComplex['real']) - ($workValue['imaginary'] * $parsedComplex['imaginary']);
			$returnValue['imaginary'] = ($workValue['real'] * $parsedComplex['imaginary']) + ($workValue['imaginary'] * $parsedComplex['real']);
		}

		if ($returnValue['imaginary'] == 0.0) { $activeSuffix = ''; }
		return self::COMPLEX($returnValue['real'],$returnValue['imaginary'],$activeSuffix);
	}

	/**
	 * BESSELI
	 *
	 * Returns the modified Bessel function, which is equivalent to the Bessel function evaluated for purely imaginary arguments
	 *
	 * @param	float		$x
	 * @param	float		$n
	 * @return  int
	 */
	public static function BESSELI($x, $n) {
		$x	= self::flattenSingleValue($x);
		$n	= floor(self::flattenSingleValue($n));

		if ((is_numeric($x)) && (is_numeric($n))) {
			if ($n < 0) {
				return self::$_errorCodes['num'];
			}
			$f_2_PI = 2 * pi();

			if (abs($x) <= 30) {
				$fTerm = pow($x / 2, $n) / self::FACT($n);
				$nK = 1;
				$fResult = $fTerm;
				$fSqrX = pow($x,2) / 4;
				do {
					$fTerm *= $fSqrX;
					$fTerm /= ($nK * ($nK + $n));
					$fResult += $fTerm;
				} while ((abs($fTerm) > 1e-10) && (++$nK < 100));
			} else {
				$fXAbs = abs($x);
				$fResult = exp($fXAbs) / sqrt($f_2_PI * $fXAbs);
				if (($n && 1) && ($x < 0)) {
					$fResult = -$fResult;
				}
			}
			return $fResult;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * BESSELJ
	 *
	 * Returns the Bessel function
	 *
	 * @param	float		$x
	 * @param	float		$n
	 * @return  int
	 */
	public static function BESSELJ($x, $n) {
		$x	= self::flattenSingleValue($x);
		$n	= floor(self::flattenSingleValue($n));

		if ((is_numeric($x)) && (is_numeric($n))) {
			if ($n < 0) {
				return self::$_errorCodes['num'];
			}
			$f_2_DIV_PI = 2 / pi();
			$f_PI_DIV_2 = pi() / 2;
			$f_PI_DIV_4 = pi() / 4;

			$fResult = 0;
			if (abs($x) <= 30) {
				$fTerm = pow($x / 2, $n) / self::FACT($n);
				$nK = 1;
				$fResult = $fTerm;
				$fSqrX = pow($x,2) / -4;
				do {
					$fTerm *= $fSqrX;
					$fTerm /= ($nK * ($nK + $n));
					$fResult += $fTerm;
				} while ((abs($fTerm) > 1e-10) && (++$nK < 100));
			} else {
				$fXAbs = abs($x);
				$fResult = sqrt($f_2_DIV_PI / $fXAbs) * cos($fXAbs - $n * $f_PI_DIV_2 - $f_PI_DIV_4);
				if (($n && 1) && ($x < 0)) {
					$fResult = -$fResult;
				}
			}
			return $fResult;
		}
		return self::$_errorCodes['value'];
	}

	private static function Besselk0($fNum) {
		if ($fNum <= 2) {
			$fNum2 = $fNum * 0.5;
			$y = pow($fNum2,2);
			$fRet = -log($fNum2) * self::BESSELI($fNum, 0) +
					(-0.57721566 + $y * (0.42278420 + $y * (0.23069756 + $y * (0.3488590e-1 + $y * (0.262698e-2 + $y *
					(0.10750e-3 + $y * 0.74e-5))))));
		} else {
			$y = 2 / $fNum;
			$fRet = exp(-$fNum) / sqrt($fNum) *
					(1.25331414 + $y * (-0.7832358e-1 + $y * (0.2189568e-1 + $y * (-0.1062446e-1 + $y *
					(0.587872e-2 + $y * (-0.251540e-2 + $y * 0.53208e-3))))));
		}
		return $fRet;
	}

	private static function Besselk1($fNum) {
		if ($fNum <= 2) {
			$fNum2 = $fNum * 0.5;
			$y = pow($fNum2,2);
			$fRet = log($fNum2) * self::BESSELI($fNum, 1) +
					(1 + $y * (0.15443144 + $y * (-0.67278579 + $y * (-0.18156897 + $y * (-0.1919402e-1 + $y *
					(-0.110404e-2 + $y * (-0.4686e-4))))))) / $fNum;
		} else {
			$y = 2 / $fNum;
			$fRet = exp(-$fNum) / sqrt($fNum) *
					(1.25331414 + $y * (0.23498619 + $y * (-0.3655620e-1 + $y * (0.1504268e-1 + $y * (-0.780353e-2 + $y *
					(0.325614e-2 + $y * (-0.68245e-3)))))));
		}
		return $fRet;
	}

	/**
	 * BESSELK
	 *
	 * Returns the modified Bessel function, which is equivalent to the Bessel functions evaluated for purely imaginary arguments.
	 *
	 * @param	float		$x
	 * @param	float		$n
	 * @return  int
	 */
	public static function BESSELK($x, $ord) {
		$x	= self::flattenSingleValue($x);
		$n	= floor(self::flattenSingleValue($ord));

		if ((is_numeric($x)) && (is_numeric($ord))) {
			if ($ord < 0) {
				return self::$_errorCodes['num'];
			}

			switch($ord) {
				case 0 :	return self::Besselk0($x);
							break;
				case 1 :	return self::Besselk1($x);
							break;
				default :	$fTox	= 2 / $x;
							$fBkm	= self::Besselk0($x);
							$fBk	= self::Besselk1($x);
							for ($n = 1; $n < $ord; $n++) {
								$fBkp	= $fBkm + $n * $fTox * $fBk;
								$fBkm	= $fBk;
								$fBk	= $fBkp;
							}
			}
			return $fBk;
		}
		return self::$_errorCodes['value'];
	}

	private static function Bessely0($fNum) {
		if ($fNum < 8) {
			$y = pow($fNum,2);
			$f1 = -2957821389.0 + $y * (7062834065.0 + $y * (-512359803.6 + $y * (10879881.29 + $y * (-86327.92757 + $y * 228.4622733))));
			$f2 = 40076544269.0 + $y * (745249964.8 + $y * (7189466.438 + $y * (47447.26470 + $y * (226.1030244 + $y))));
			$fRet = $f1 / $f2 + 0.636619772 * self::BESSELJ($fNum, 0) * log($fNum);
		} else {
			$z = 8 / $fNum;
			$y = pow($z,2);
			$xx = $fNum - 0.785398164;
			$f1 = 1 + $y * (-0.1098628627e-2 + $y * (0.2734510407e-4 + $y * (-0.2073370639e-5 + $y * 0.2093887211e-6)));
			$f2 = -0.1562499995e-1 + $y * (0.1430488765e-3 + $y * (-0.6911147651e-5 + $y * (0.7621095161e-6 + $y * (-0.934945152e-7))));
			$fRet = sqrt(0.636619772 / $fNum) * (sin($xx) * $f1 + $z * cos($xx) * $f2);
		}
		return $fRet;
	}

	private static function Bessely1($fNum) {
		if ($fNum < 8) {
			$y = pow($fNum,2);
			$f1 = $fNum * (-0.4900604943e13 + $y * (0.1275274390e13 + $y * (-0.5153438139e11 + $y * (0.7349264551e9 + $y *
				(-0.4237922726e7 + $y * 0.8511937935e4)))));
			$f2 = 0.2499580570e14 + $y * (0.4244419664e12 + $y * (0.3733650367e10 + $y * (0.2245904002e8 + $y *
				(0.1020426050e6 + $y * (0.3549632885e3 + $y)))));
			$fRet = $f1 / $f2 + 0.636619772 * ( self::BESSELJ($fNum, 1) * log($fNum) - 1 / $fNum);
		} else {
			$z = 8 / $fNum;
			$y = $z * $z;
			$xx = $fNum - 2.356194491;
			$f1 = 1 + $y * (0.183105e-2 + $y * (-0.3516396496e-4 + $y * (0.2457520174e-5 + $y * (-0.240337019e6))));
			$f2 = 0.04687499995 + $y * (-0.2002690873e-3 + $y * (0.8449199096e-5 + $y * (-0.88228987e-6 + $y * 0.105787412e-6)));
			$fRet = sqrt(0.636619772 / $fNum) * (sin($xx) * $f1 + $z * cos($xx) * $f2);
			#i12430# ...but this seems to work much better.
//			$fRet = sqrt(0.636619772 / $fNum) * sin($fNum - 2.356194491);
		}
		return $fRet;
	}

	/**
	 * BESSELY
	 *
	 * Returns the Bessel function, which is also called the Weber function or the Neumann function.
	 *
	 * @param	float		$x
	 * @param	float		$n
	 * @return  int
	 */
	public static function BESSELY($x, $ord) {
		$x	= self::flattenSingleValue($x);
		$n	= floor(self::flattenSingleValue($ord));

		if ((is_numeric($x)) && (is_numeric($ord))) {
			if ($ord < 0) {
				return self::$_errorCodes['num'];
			}

			switch($ord) {
				case 0 :	return self::Bessely0($x);
							break;
				case 1 :	return self::Bessely1($x);
							break;
				default:	$fTox	= 2 / $x;
							$fBym	= self::Bessely0($x);
							$fBy	= self::Bessely1($x);
							for ($n = 1; $n < $ord; $n++) {
								$fByp	= $n * $fTox * $fBy - $fBym;
								$fBym	= $fBy;
								$fBy	= $fByp;
							}
			}
			return $fBy;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * DELTA
	 *
	 * Tests whether two values are equal. Returns 1 if number1 = number2; returns 0 otherwise.
	 *
	 * @param	float		$a
	 * @param	float		$b
	 * @return  int
	 */
	public static function DELTA($a, $b=0) {
		$a	= self::flattenSingleValue($a);
		$b	= self::flattenSingleValue($b);

		return (int) ($a == $b);
	}

	/**
	 * GESTEP
	 *
	 * Returns 1 if number = step; returns 0 (zero) otherwise
	 *
	 * @param	float		$number
	 * @param	float		$step
	 * @return  int
	 */
	public static function GESTEP($number, $step=0) {
		$number	= self::flattenSingleValue($number);
		$step	= self::flattenSingleValue($step);

		return (int) ($number >= $step);
	}

	//
	//	Private method to calculate the erf value
	//
	private static $two_sqrtpi = 1.128379167095512574;
	private static $rel_error = 1E-15;

	private static function erfVal($x) {
		if (abs($x) > 2.2) {
			return 1 - self::erfcVal($x);
		}
		$sum = $term = $x;
		$xsqr = pow($x,2);
		$j = 1;
		do {
			$term *= $xsqr / $j;
			$sum -= $term / (2 * $j + 1);
			++$j;
			$term *= $xsqr / $j;
			$sum += $term / (2 * $j + 1);
			++$j;
			if ($sum == 0) {
				break;
			}
		} while (abs($term / $sum) > self::$rel_error);
		return  self::$two_sqrtpi * $sum;
	}

	/**
	 * ERF
	 *
	 * Returns the error function integrated between lower_limit and upper_limit
	 *
	 * @param	float		$lower	lower bound for integrating ERF
	 * @param	float		$upper	upper bound for integrating ERF.
	 *								If omitted, ERF integrates between zero and lower_limit
	 * @return  int
	 */
	public static function ERF($lower, $upper = 0) {
		$lower	= self::flattenSingleValue($lower);
		$upper	= self::flattenSingleValue($upper);

		if ((is_numeric($lower)) && (is_numeric($upper))) {
			if (($lower < 0) || ($upper < 0)) {
				return self::$_errorCodes['num'];
			}
			if ($upper > $lower) {
				return self::erfVal($upper) - self::erfVal($lower);
			} else {
				return self::erfVal($lower) - self::erfVal($upper);
			}
		}
		return self::$_errorCodes['value'];
	}

	//
	//	Private method to calculate the erfc value
	//
	private static $one_sqrtpi = 0.564189583547756287;

	private static function erfcVal($x) {
		if (abs($x) < 2.2) {
			return 1 - self::erfVal($x);
		}
		if ($x < 0) {
			return 2 - self::erfc(-$x);
		}
		$a = $n = 1;
		$b = $c = $x;
		$d = pow($x,2) + 0.5;
		$q1 = $q2 = $b / $d;
		$t = 0;
		do {
			$t = $a * $n + $b * $x;
			$a = $b;
			$b = $t;
			$t = $c * $n + $d * $x;
			$c = $d;
			$d = $t;
			$n += 0.5;
			$q1 = $q2;
			$q2 = $b / $d;
		} while ((abs($q1 - $q2) / $q2) > self::$rel_error);
		return self::$one_sqrtpi * exp(-$x * $x) * $q2;
	}

	/**
	 * ERFC
	 *
	 * Returns the complementary ERF function integrated between x and infinity
	 *
	 * @param	float		$x		The lower bound for integrating ERF
	 * @return  int
	 */
	public static function ERFC($x) {
		$x	= self::flattenSingleValue($x);

		if (is_numeric($x)) {
			if ($x < 0) {
				return self::$_errorCodes['num'];
			}
			return self::erfcVal($x);
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * EFFECT
	 *
	 * Returns the effective interest rate given the nominal rate and the number of compounding payments per year.
	 *
	 * @param	float	$nominal_rate	  Nominal interest rate
	 * @param	int		$npery			   Number of compounding payments per year
	 * @return	float
	 */
	public static function EFFECT($nominal_rate = 0, $npery = 0) {
		$nominal_rate	= self::flattenSingleValue($$nominal_rate);
		$npery			= (int)self::flattenSingleValue($npery);

		// Validate parameters
		if ($$nominal_rate <= 0 || $npery < 1) {
			return self::$_errorCodes['num'];
		}

		return pow((1 + $nominal_rate / $npery), $npery) - 1;
	}

	/**
	 * NOMINAL
	 *
	 * Returns the nominal interest rate given the effective rate and the number of compounding payments per year.
	 *
	 * @param	float	$effect_rate	Effective interest rate
	 * @param	int		$npery			Number of compounding payments per year
	 * @return	float
	 */
	public static function NOMINAL($effect_rate = 0, $npery = 0) {
		$effect_rate	= self::flattenSingleValue($effect_rate);
		$npery			= (int)self::flattenSingleValue($npery);

		// Validate parameters
		if ($effect_rate <= 0 || $npery < 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		return $npery * (pow($effect_rate + 1, 1 / $npery) - 1);
	}

	/**
	 * PV
	 *
	 * Returns the Present Value of a cash flow with constant payments and interest rate (annuities).
	 *
	 * @param	float	$rate	Interest rate per period
	 * @param	int		$nper	Number of periods
	 * @param	float	$pmt	Periodic payment (annuity)
	 * @param	float	$fv		Future Value
	 * @param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return	float
	 */
	public static function PV($rate = 0, $nper = 0, $pmt = 0, $fv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$nper	= self::flattenSingleValue($nper);
		$pmt	= self::flattenSingleValue($pmt);
		$fv		= self::flattenSingleValue($fv);
		$type	= self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		if (!is_null($rate) && $rate != 0) {
			return (-$pmt * (1 + $rate * $type) * ((pow(1 + $rate, $nper) - 1) / $rate) - $fv) / pow(1 + $rate, $nper);
		} else {
			return -$fv - $pmt * $nper;
		}
	}

	/**
	 * FV
	 *
	 * Returns the Future Value of a cash flow with constant payments and interest rate (annuities).
	 *
	 * @param	float	$rate	Interest rate per period
	 * @param	int		$nper	Number of periods
	 * @param	float	$pmt	Periodic payment (annuity)
	 * @param	float	$pv		Present Value
	 * @param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return	float
	 */
	public static function FV($rate = 0, $nper = 0, $pmt = 0, $pv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$nper	= self::flattenSingleValue($nper);
		$pmt	= self::flattenSingleValue($pmt);
		$pv		= self::flattenSingleValue($pv);
		$type	= self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		if (!is_null($rate) && $rate != 0) {
			return -$pv * pow(1 + $rate, $nper) - $pmt * (1 + $rate * $type) * (pow(1 + $rate, $nper) - 1) / $rate;
		} else {
			return -$pv - $pmt * $nper;
		}
	}

	/**
	 * PMT
	 *
	 * Returns the constant payment (annuity) for a cash flow with a constant interest rate.
	 *
	 * @param	float	$rate	Interest rate per period
	 * @param	int		$nper	Number of periods
	 * @param	float	$pv		Present Value
	 * @param	float	$fv		Future Value
	 * @param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return	float
	 */
	public static function PMT($rate = 0, $nper = 0, $pv = 0, $fv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$nper	= self::flattenSingleValue($nper);
		$pv		= self::flattenSingleValue($pv);
		$fv		= self::flattenSingleValue($fv);
		$type	= self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		if (!is_null($rate) && $rate != 0) {
			return (-$fv - $pv * pow(1 + $rate, $nper)) / (1 + $rate * $type) / ((pow(1 + $rate, $nper) - 1) / $rate);
		} else {
			return (-$pv - $fv) / $nper;
		}
	}

	/**
	 * NPER
	 *
	 * Returns the number of periods for a cash flow with constant periodic payments (annuities), and interest rate.
	 *
	 * @param	float	$rate	Interest rate per period
	 * @param	int		$pmt	Periodic payment (annuity)
	 * @param	float	$pv		Present Value
	 * @param	float	$fv		Future Value
	 * @param	int		$type	Payment type: 0 = at the end of each period, 1 = at the beginning of each period
	 * @return	float
	 */
	public static function NPER($rate = 0, $pmt = 0, $pv = 0, $fv = 0, $type = 0) {
		$rate	= self::flattenSingleValue($rate);
		$pmt	= self::flattenSingleValue($pmt);
		$pv		= self::flattenSingleValue($pv);
		$fv		= self::flattenSingleValue($fv);
		$type	= self::flattenSingleValue($type);

		// Validate parameters
		if ($type != 0 && $type != 1) {
			return self::$_errorCodes['num'];
		}

		// Calculate
		if (!is_null($rate) && $rate != 0) {
			if ($pmt == 0 && $pv == 0) {
				return self::$_errorCodes['num'];
			}
			return log(($pmt * (1 + $rate * $type) / $rate - $fv) / ($pv + $pmt * (1 + $rate * $type) / $rate)) / log(1 + $rate);
		} else {
			if ($pmt == 0) {
				return self::$_errorCodes['num'];
			}
			return (-$pv -$fv) / $pmt;
		}
	}

	/**
	 * NPV
	 *
	 * Returns the Net Present Value of a cash flow series given a discount rate.
	 *
	 * @param	float	Discount interest rate
	 * @param	array	Cash flow series
	 * @return	float
	 */
	public static function NPV() {
		// Return value
		$returnValue = 0;

		// Loop trough arguments
		$aArgs = self::flattenArray(func_get_args());

		// Calculate
		$rate = array_shift($aArgs);
		for ($i = 1; $i <= count($aArgs); ++$i) {
			// Is it a numeric value?
			if (is_numeric($aArgs[$i - 1])) {
				$returnValue += $aArgs[$i - 1] / pow(1 + $rate, $i);
			}
		}

		// Return
		return $returnValue;
	}

	/**
	 * ACCRINT
	 *
	 * Computes the accrued interest for a security that pays periodic interest.
	 *
	 * @param	int		$issue
	 * @param	int		$firstInterest
	 * @param	int		$settlement
	 * @param	int		$rate
	 * @param	int		$par
	 * @param	int		$frequency
	 * @param	int		$basis
	 * @return  int		The accrued interest for a security that pays periodic interest.
	 */
	/*
	public static function ACCRINT($issue = 0, $firstInterest = 0, $settlement = 0, $rate = 0, $par = 1000, $frequency = 1, $basis = 0) {
		$issue			= self::flattenSingleValue($issue);
		$firstInterest	= self::flattenSingleValue($firstInterest);
		$settlement	= self::flattenSingleValue($settlement);
		$rate			= self::flattenSingleValue($rate);
		$par			= self::flattenSingleValue($par);
		$frequency		= self::flattenSingleValue($frequency);
		$basis			= self::flattenSingleValue($basis);

		// Perform checks
		if ($issue >= $settlement || $rate <= 0 || $par <= 0 || !($frequency == 1 || $frequency == 2 || $frequency == 4) || $basis < 0 || $basis > 4) return self::$_errorCodes['num'];

		// Calculate value
		return $par * ($rate / $frequency) *
	}
	*/

	/**
	 * SLN
	 *
	 * Returns the straight-line depreciation of an asset for one period
	 *
	 * @param	cost		Initial cost of the asset
	 * @param	salvage		Value at the end of the depreciation
	 * @param	life		Number of periods over which the asset is depreciated
	 * @return	float
	 */
	public static function SLN($cost, $salvage, $life) {
		$cost		= self::flattenSingleValue($cost);
		$salvage	= self::flattenSingleValue($salvage);
		$life		= self::flattenSingleValue($life);

		// Calculate
		if ((is_numeric($cost)) && (is_numeric($salvage)) && (is_numeric($life))) {
			if ($life < 0) {
				return self::$_errorCodes['num'];
			}
			return ($cost - $salvage) / $life;
		}
		return self::$_errorCodes['value'];
	}

	/**
	 * CELL_ADDRESS
	 *
	 * Returns the straight-line depreciation of an asset for one period
	 *
	 * @param	row			Row number to use in the cell reference
	 * @param	column		Column number to use in the cell reference
	 * @param	relativity	Flag indicating the type of reference to return
	 * @param	sheetText	Name of worksheet to use
	 * @return	string
	 */
	public static function CELL_ADDRESS($row, $column, $relativity=1, $referenceStyle=True, $sheetText='') {
		$row		= self::flattenSingleValue($row);
		$column		= self::flattenSingleValue($column);
		$relativity	= self::flattenSingleValue($relativity);
		$sheetText	= self::flattenSingleValue($sheetText);

		if ($sheetText > '') {
			if (strpos($sheetText,' ') !== False) { $sheetText = "'".$sheetText."'"; }
			$sheetText .='!';
		}
		if (!$referenceStyle) {
			if (($relativity == 2) || ($relativity == 4)) { $column = '['.$column.']'; }
			if (($relativity == 3) || ($relativity == 4)) { $row = '['.$row.']'; }
			return $sheetText.'R'.$row.'C'.$column;
		} else {
			$rowRelative = $columnRelative = '$';
			$column = PHPExcel_Cell::stringFromColumnIndex($column-1);
			if (($relativity == 2) || ($relativity == 4)) { $columnRelative = ''; }
			if (($relativity == 3) || ($relativity == 4)) { $rowRelative = ''; }
			return $sheetText.$columnRelative.$column.$rowRelative.$row;
		}
	}

	public static function CHOOSE() {
		$chooseArgs = func_get_args();
		$chosenEntry = self::flattenSingleValue(array_shift($chooseArgs));
		$entryCount = count($chooseArgs) - 1;

		if ((is_numeric($chosenEntry)) && (!is_bool($chosenEntry))) {
			$chosenEntry--;
		} else {
			return self::$_errorCodes['value'];
		}
		$chosenEntry = floor($chosenEntry);
		if (($chosenEntry <= 0) || ($chosenEntry > $entryCount)) {
			return self::$_errorCodes['value'];
		}

		if (is_array($chooseArgs[$chosenEntry])) {
			return self::flattenArray($chooseArgs[$chosenEntry]);
		} else {
			return $chooseArgs[$chosenEntry];
		}
	}
	/**
	 * MATCH
	 * The MATCH function searches for a specified item in a range of cells
	 * @param	lookup_value	The value that you want to match in lookup_array
	 * @param	lookup_array	The range of cells being searched
	 * @param	match_type		The number -1, 0, or 1. -1 means above, 0 means exact match, 1 means  below. If match_type is 1 or -1, the list has to be ordered.
	 * @return	integer		the relative position of the found item
	 */
	public static function MATCH($lookup_value, $lookup_array, $match_type=1) {

		// flatten the lookup_array
		$lookup_array = self::flattenArray($lookup_array);
		
		// flatten lookup_value since it may be a cell reference to a value or the value itself
		$lookup_value = self::flattenSingleValue($lookup_value);
		
		// MATCH is not case sensitive
		$lookup_value = strtolower($lookup_value);
		
		/*
		echo "--------------------<br>looking for $lookup_value in <br>";
		print_r($lookup_array);
		echo "<br>";
		//return 1;
		/**/
		
		// **
		// check inputs
		// **
		// lookup_value type has to be number, text, or logical values
		if (!is_numeric($lookup_value) && !is_string($lookup_value) && !is_bool($lookup_value)){
			// error: lookup_array should contain only number, text, or logical values
			//echo "error: lookup_array should contain only number, text, or logical values<br>";
			return self::$_errorCodes['na'];
		}
		
		// match_type is 0, 1 or -1
		if ($match_type!==0 && $match_type!==-1 && $match_type!==1){
			// error: wrong value for match_type
			//echo "error: wrong value for match_type<br>";
			return self::$_errorCodes['na'];
		}		
		
		// lookup_array should not be empty
		if (sizeof($lookup_array)<=0){
			// error: empty range
			//echo "error: empty range ".sizeof($lookup_array)."<br>";
			return self::$_errorCodes['na'];
		}		

		// lookup_array should contain only number, text, or logical values
		for ($i=0;$i<sizeof($lookup_array);$i++){
			// check the type of the value
			if (!is_numeric($lookup_array[$i]) && !is_string($lookup_array[$i]) && !is_bool($lookup_array[$i])){
				// error: lookup_array should contain only number, text, or logical values
				//echo "error: lookup_array should contain only number, text, or logical values<br>";
				return self::$_errorCodes['na'];
			}
			// convert tpo lowercase
			if (is_string($lookup_array[$i]))
				$lookup_array[$i] = strtolower($lookup_array[$i]);
		}

		// if match_type is 1 or -1, the list has to be ordered
		if($match_type==1 || $match_type==-1){
			// **
			// iniitialization
			// store the last value
			$iLastValue=$lookup_array[0];
			// **
			// loop on the cells
			for ($i=0;$i<sizeof($lookup_array);$i++){
				// check ascending order
				if(($match_type==1 && $lookup_array[$i]<$iLastValue)
					// OR check descending order
					|| ($match_type==-1 && $lookup_array[$i]>$iLastValue)){
					// error: list is not ordered correctly
					//echo "error: list is not ordered correctly<br>";
					return self::$_errorCodes['na'];
				}
			}
		}
		// **
		// find the match
		// **
		// loop on the cells
		for ($i=0; $i < sizeof($lookup_array); $i++){
			// if match_type is 0 <=> find the first value that is exactly equal to lookup_value
			if ($match_type==0 && $lookup_array[$i]==$lookup_value){
				// this is the exact match
				return $i+1;
			}
			// if match_type is -1 <=> find the smallest value that is greater than or equal to lookup_value
			if ($match_type==-1 && $lookup_array[$i] < $lookup_value){
				if ($i<1){
					// 1st cell was allready smaller than the lookup_value
					break;
				}
				else
					// the previous cell was the match
					return $i;
			}
			// if match_type is 1 <=> find the largest value that is less than or equal to lookup_value
			if ($match_type==1 && $lookup_array[$i] > $lookup_value){
				if ($i<1){
					// 1st cell was allready bigger than the lookup_value
					break;
				}
				else
					// the previous cell was the match
					return $i;
			}
		}
		// unsuccessful in finding a match, return #N/A error value
		//echo "unsuccessful in finding a match<br>";
		return self::$_errorCodes['na'];
	}
	/**
	 * Uses an index to choose a value from a reference or array
	 * implemented: Return the value of a specified cell or array of cells	Array form
	 * not implemented: Return a reference to specified cells	Reference form
	 *
	 * @param	range_array	a range of cells or an array constant
	 * @param	row_num		selects the row in array from which to return a value. If row_num is omitted, column_num is required.
	 * @param	column_num	selects the column in array from which to return a value. If column_num is omitted, row_num is required.	
	 */
	public static function INDEX($range_array,$row_num=null,$column_num=null) {
		// **
		// check inputs
		// **
		// at least one of row_num and column_num is required
		if ($row_num==null && $column_num==null){
			// error: row_num and column_num are both undefined
			//echo "error: row_num and column_num are both undefined<br>";
			return self::$_errorCodes['value'];
		}
		// default values for row_num and column_num
		if ($row_num==null){
			$row_num = 1;
		}
		if ($column_num==null){
			$column_num = 1;
		}
		
		/* debug 
		print_r($range_array);
		echo "<br>$row_num , $column_num<br>";
		/**/
		
		// row_num and column_num may not have negative values 
		if (($row_num!=null && $row_num < 0) || ($column_num!=null && $column_num < 0)) {
			// error: row_num or column_num has negative value
			//echo "error: row_num or column_num has negative value<br>";
			return self::$_errorCodes['value'];
		}
		// **
		// convert column and row numbers into array indeces
		// **
		// array is zero based
		$column_num--;
		$row_num--;
		
		// retrieve the columns
		$columnKeys = array_keys($range_array);

		// retrieve the rows
		$rowKeys = array_keys($range_array[$columnKeys[0]]);
		
		// test ranges
		if ($column_num >= sizeof($columnKeys)){
			// error: column_num is out of range
			//echo "error: column_num is out of range - $column_num > ".sizeof($columnKeys)."<br>";
			return self::$_errorCodes['reference'];
		}
		if ($row_num >= sizeof($rowKeys)){
			// error: row_num is out of range
			//echo "error: row_num is out of range - $row_num > ".sizeof($rowKeys)."<br>";
			return self::$_errorCodes['reference'];
		}
		// compute and return result
		return $range_array[$columnKeys[$column_num]][$rowKeys[$row_num]];
	}

/*	public static function INDEX($arrayValues,$rowNum = 0,$columnNum = 0) {

		if (($rowNum < 0) || ($columnNum < 0)) {
			return self::$_errorCodes['value'];
		}

		$columnKeys = array_keys($arrayValues);
		$rowKeys = array_keys($arrayValues[$columnKeys[0]]);
		if ($columnNum > count($columnKeys)) {
			return self::$_errorCodes['value'];
		} elseif ($columnNum == 0) {
			if ($rowNum == 0) {
				return $arrayValues;
			}
			$rowNum = $rowKeys[--$rowNum];
			$returnArray = array();
			foreach($arrayValues as $arrayColumn) {
				$returnArray[] = $arrayColumn[$rowNum];
			}
			return $returnArray;
		}
		$columnNum = $columnKeys[--$columnNum];
		if ($rowNum > count($rowKeys)) {
			return self::$_errorCodes['value'];
		} elseif ($rowNum == 0) {
			return $arrayValues[$columnNum];
		}
		$rowNum = $rowKeys[--$rowNum];

		return $arrayValues[$columnNum][$rowNum];
	}
*/
	/**
	 * SYD
	 *
	 * Returns the sum-of-years' digits depreciation of an asset for a specified period.
	 *
	 * @param	cost		Initial cost of the asset
	 * @param	salvage		Value at the end of the depreciation
	 * @param	life		Number of periods over which the asset is depreciated
	 * @param	period		Period
	 * @return	float
	 */
	public static function SYD($cost, $salvage, $life, $period) {
		$cost		= self::flattenSingleValue($cost);
		$salvage	= self::flattenSingleValue($salvage);
		$life		= self::flattenSingleValue($life);
		$period		= self::flattenSingleValue($period);

		// Calculate
		if ((is_numeric($cost)) && (is_numeric($salvage)) && (is_numeric($life)) && (is_numeric($period))) {
			if (($life < 1) || ($salvage < $life)) {
				return self::$_errorCodes['num'];
			}
			return (($cost - $salvage) * ($life - $period + 1) * 2) / ($life * ($life + 1));
		}
		return self::$_errorCodes['value'];
	}

	/**
	* VLOOKUP
	* The VLOOKUP function searches for value in the left-most column of lookup_array and returns the value in the same row based on the index_number.
	* @param	lookup_value	The value that you want to match in lookup_array
	* @param	lookup_array	The range of cells being searched
	* @param	index_number	The column number in table_array from which the matching value must be returned. The first column is 1.
	* @param	not_exact_match	Determines if you are looking for an exact match based on lookup_value.
	* @return	mixed			The value of the found cell
	*/
	public static function VLOOKUP($lookup_value, $lookup_array, $index_number, $not_exact_match=true) {
		// index_number must be greater than or equal to 1
		if ($index_number < 1) {
			return self::$_errorCodes['value'];
		}
		
		// index_number must be less than or equal to the number of columns in lookup_array
		if ($index_number > count($lookup_array)) {
			return self::$_errorCodes['reference'];
		}
		
		// re-index lookup_array with numeric keys starting at 1
		array_unshift($lookup_array, array());
		$lookup_array = array_slice(array_values($lookup_array), 1, count($lookup_array), true);
		
		// look for an exact match
		$row_number = array_search($lookup_value, $lookup_array[1]);
		
		// if an exact match is required, we have what we need to return an appropriate response
		if ($not_exact_match == false) {
			if ($row_number === false) {
				return self::$_errorCodes['na'];
			} else {
				return $lookup_array[$index_number][$row_number];
			}
		}
		
		// TODO: The VLOOKUP spec in Excel states that, at this point, we should search for
		// the highest value that is less than lookup_value. However, documentation on how string
		// values should be treated here is sparse.
		return self::$_errorCodes['na'];
	}
	
	/**
	 * Flatten multidemensional array
	 *
	 * @param	array	$array	Array to be flattened
	 * @return  array	Flattened array
	 */
	public static function flattenArray($array) {
		$arrayValues = array();

		foreach ($array as $value) {
			if (is_scalar($value)) {
				$arrayValues[] = self::flattenSingleValue($value);
			} elseif (is_array($value)) {
				$arrayValues = array_merge($arrayValues, self::flattenArray($value));
			} else {
				$arrayValues[] = $value;
			}
		}

		return $arrayValues;
	}

	/**
	 * Convert an array with one element to a flat value
	 *
	 * @param	mixed		$value		Array or flat value
	 * @return	mixed
	 */
	public static function flattenSingleValue($value = '') {
		if (is_array($value)) {
			$value = self::flattenSingleValue(array_pop($value));
		}
		return $value;
	}
}


//
//	There are a few mathematical functions that aren't available on all versions of PHP for all platforms
//	These functions aren't available in Windows implementations of PHP prior to version 5.3.0
//	So we test if they do exist for this version of PHP/operating platform; and if not we create them
//
if (!function_exists('acosh')) {
	function acosh($x) {
		return 2 * log(sqrt(($x + 1) / 2) + sqrt(($x - 1) / 2));
	}
}

if (!function_exists('asinh')) {
	function asinh($x) {
		return log($x + sqrt(1 + $x * $x));
	}
}

if (!function_exists('atanh')) {
	function atanh($x) {
		return (log(1 + $x) - log(1 - $x)) / 2;
	}
}

?>
