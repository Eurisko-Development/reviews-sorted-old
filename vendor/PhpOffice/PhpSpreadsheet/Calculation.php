<?php

namespace PhpOffice\PhpSpreadsheet;

if (!defined('CALCULATION_REGEXP_CELLREF')) {
    //    Test for support of \P (multibyte options) in PCRE
    if (defined('PREG_BAD_UTF8_ERROR')) {
        //    Cell reference (cell or range of cells, with or without a sheet reference)
        define('CALCULATION_REGEXP_CELLREF', '((([^\s,!&%^\/\*\+<>=-]*)|(\'[^\']*\')|(\"[^\"]*\"))!)?\$?([a-z]{1,3})\$?(\d{1,7})');
        //    Named Range of cells
        define('CALCULATION_REGEXP_NAMEDRANGE', '((([^\s,!&%^\/\*\+<>=-]*)|(\'[^\']*\')|(\"[^\"]*\"))!)?([_A-Z][_A-Z0-9\.]*)');
    } else {
        //    Cell reference (cell or range of cells, with or without a sheet reference)
        define('CALCULATION_REGEXP_CELLREF', '(((\w*)|(\'[^\']*\')|(\"[^\"]*\"))!)?\$?([a-z]{1,3})\$?(\d+)');
        //    Named Range of cells
        define('CALCULATION_REGEXP_NAMEDRANGE', '(((\w*)|(\'.*\')|(\".*\"))!)?([_A-Z][_A-Z0-9\.]*)');
    }
}

/**
 * Copyright (c) 2006 - 2016 PhpSpreadsheet
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
 * @category   PhpSpreadsheet
 * @copyright  Copyright (c) 2006 - 2016 PhpSpreadsheet (https://github.com/PHPOffice/PhpSpreadsheet)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    ##VERSION##, ##DATE##
 */
class Calculation
{
    /** Constants                */
/** Regular Expressions        */
    //    Numeric operand
    const CALCULATION_REGEXP_NUMBER = '[-+]?\d*\.?\d+(e[-+]?\d+)?';
    //    String operand
    const CALCULATION_REGEXP_STRING = '"(?:[^"]|"")*"';
    //    Opening bracket
    const CALCULATION_REGEXP_OPENBRACE = '\(';
    //    Function (allow for the old @ symbol that could be used to prefix a function, but we'll ignore it)
    const CALCULATION_REGEXP_FUNCTION = '@?([A-Z][A-Z0-9\.]*)[\s]*\(';
    //    Cell reference (cell or range of cells, with or without a sheet reference)
    const CALCULATION_REGEXP_CELLREF = CALCULATION_REGEXP_CELLREF;
    //    Named Range of cells
    const CALCULATION_REGEXP_NAMEDRANGE = CALCULATION_REGEXP_NAMEDRANGE;
    //    Error
    const CALCULATION_REGEXP_ERROR = '\#[A-Z][A-Z0_\/]*[!\?]?';

    /** constants */
    const RETURN_ARRAY_AS_ERROR = 'error';
    const RETURN_ARRAY_AS_VALUE = 'value';
    const RETURN_ARRAY_AS_ARRAY = 'array';

    private static $returnArrayAsType = self::RETURN_ARRAY_AS_VALUE;

    /**
     * Instance of this class
     *
     * @var \PhpOffice\PhpSpreadsheet\Calculation
     */
    private static $instance;

    /**
     * Instance of the spreadsheet this Calculation Engine is using
     *
     * @var PhpSpreadsheet
     */
    private $spreadsheet;

    /**
     * List of instances of the calculation engine that we've instantiated for individual spreadsheets
     *
     * @var \PhpOffice\PhpSpreadsheet\Calculation[]
     */
    private static $spreadsheetSets;

    /**
     * Calculation cache
     *
     * @var array
     */
    private $calculationCache = [];

    /**
     * Calculation cache enabled
     *
     * @var bool
     */
    private $calculationCacheEnabled = true;

    /**
     * List of operators that can be used within formulae
     * The true/false value indicates whether it is a binary operator or a unary operator
     *
     * @var array
     */
    private static $operators = [
        '+' => true, '-' => true, '*' => true, '/' => true,
        '^' => true, '&' => true, '%' => false, '~' => false,
        '>' => true, '<' => true, '=' => true, '>=' => true,
        '<=' => true, '<>' => true, '|' => true, ':' => true,
    ];

    /**
     * List of binary operators (those that expect two operands)
     *
     * @var array
     */
    private static $binaryOperators = [
        '+' => true, '-' => true, '*' => true, '/' => true,
        '^' => true, '&' => true, '>' => true, '<' => true,
        '=' => true, '>=' => true, '<=' => true, '<>' => true,
        '|' => true, ':' => true,
    ];

    /**
     * The debug log generated by the calculation engine
     *
     * @var CalcEngine\Logger
     */
    private $debugLog;

    /**
     * Flag to determine how formula errors should be handled
     *        If true, then a user error will be triggered
     *        If false, then an exception will be thrown
     *
     * @var bool
     */
    public $suppressFormulaErrors = false;

    /**
     * Error message for any error that was raised/thrown by the calculation engine
     *
     * @var string
     */
    public $formulaError = null;

    /**
     * An array of the nested cell references accessed by the calculation engine, used for the debug log
     *
     * @var array of string
     */
    private $cyclicReferenceStack;

    private $cellStack = [];

    /**
     * Current iteration counter for cyclic formulae
     * If the value is 0 (or less) then cyclic formulae will throw an exception,
     *    otherwise they will iterate to the limit defined here before returning a result
     *
     * @var int
     */
    private $cyclicFormulaCounter = 1;

    private $cyclicFormulaCell = '';

    /**
     * Number of iterations for cyclic formulae
     *
     * @var int
     */
    public $cyclicFormulaCount = 1;

    /**
     * Epsilon Precision used for comparisons in calculations
     *
     * @var float
     */
    private $delta = 0.1e-12;

    /**
     * The current locale setting
     *
     * @var string
     */
    private static $localeLanguage = 'en_us'; //    US English    (default locale)

    /**
     * List of available locale settings
     * Note that this is read for the locale subdirectory only when requested
     *
     * @var string[]
     */
    private static $validLocaleLanguages = [
        'en', //    English        (default language)
    ];

    /**
     * Locale-specific argument separator for function arguments
     *
     * @var string
     */
    private static $localeArgumentSeparator = ',';
    private static $localeFunctions = [];

    /**
     * Locale-specific translations for Excel constants (True, False and Null)
     *
     * @var string[]
     */
    public static $localeBoolean = [
        'TRUE' => 'TRUE',
        'FALSE' => 'FALSE',
        'NULL' => 'NULL',
    ];

    /**
     * Excel constant string translations to their PHP equivalents
     * Constant conversion from text name/value to actual (datatyped) value
     *
     * @var string[]
     */
    private static $excelConstants = [
        'TRUE' => true,
        'FALSE' => false,
        'NULL' => null,
    ];

    // PhpSpreadsheet functions
    private static $phpSpreadsheetFunctions = [
        'ABS' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'abs',
            'argumentCount' => '1',
        ],
        'ACCRINT' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::ACCRINT',
            'argumentCount' => '4-7',
        ],
        'ACCRINTM' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::ACCRINTM',
            'argumentCount' => '3-5',
        ],
        'ACOS' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'acos',
            'argumentCount' => '1',
        ],
        'ACOSH' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'acosh',
            'argumentCount' => '1',
        ],
        'ADDRESS' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::cellAddress',
            'argumentCount' => '2-5',
        ],
        'AMORDEGRC' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::AMORDEGRC',
            'argumentCount' => '6,7',
        ],
        'AMORLINC' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::AMORLINC',
            'argumentCount' => '6,7',
        ],
        'AND' => [
            'category' => Calculation\Categories::CATEGORY_LOGICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Logical::logicalAnd',
            'argumentCount' => '1+',
        ],
        'AREAS' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '1',
        ],
        'ASC' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '1',
        ],
        'ASIN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'asin',
            'argumentCount' => '1',
        ],
        'ASINH' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'asinh',
            'argumentCount' => '1',
        ],
        'ATAN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'atan',
            'argumentCount' => '1',
        ],
        'ATAN2' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::ATAN2',
            'argumentCount' => '2',
        ],
        'ATANH' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'atanh',
            'argumentCount' => '1',
        ],
        'AVEDEV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::AVEDEV',
            'argumentCount' => '1+',
        ],
        'AVERAGE' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::AVERAGE',
            'argumentCount' => '1+',
        ],
        'AVERAGEA' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::AVERAGEA',
            'argumentCount' => '1+',
        ],
        'AVERAGEIF' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::AVERAGEIF',
            'argumentCount' => '2,3',
        ],
        'AVERAGEIFS' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '3+',
        ],
        'BAHTTEXT' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '1',
        ],
        'BESSELI' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::BESSELI',
            'argumentCount' => '2',
        ],
        'BESSELJ' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::BESSELJ',
            'argumentCount' => '2',
        ],
        'BESSELK' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::BESSELK',
            'argumentCount' => '2',
        ],
        'BESSELY' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::BESSELY',
            'argumentCount' => '2',
        ],
        'BETADIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::BETADIST',
            'argumentCount' => '3-5',
        ],
        'BETAINV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::BETAINV',
            'argumentCount' => '3-5',
        ],
        'BIN2DEC' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::BINTODEC',
            'argumentCount' => '1',
        ],
        'BIN2HEX' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::BINTOHEX',
            'argumentCount' => '1,2',
        ],
        'BIN2OCT' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::BINTOOCT',
            'argumentCount' => '1,2',
        ],
        'BINOMDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::BINOMDIST',
            'argumentCount' => '4',
        ],
        'CEILING' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::CEILING',
            'argumentCount' => '2',
        ],
        'CELL' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '1,2',
        ],
        'CHAR' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::CHARACTER',
            'argumentCount' => '1',
        ],
        'CHIDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::CHIDIST',
            'argumentCount' => '2',
        ],
        'CHIINV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::CHIINV',
            'argumentCount' => '2',
        ],
        'CHITEST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '2',
        ],
        'CHOOSE' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::CHOOSE',
            'argumentCount' => '2+',
        ],
        'CLEAN' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::TRIMNONPRINTABLE',
            'argumentCount' => '1',
        ],
        'CODE' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::ASCIICODE',
            'argumentCount' => '1',
        ],
        'COLUMN' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::COLUMN',
            'argumentCount' => '-1',
            'passByReference' => [true],
        ],
        'COLUMNS' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::COLUMNS',
            'argumentCount' => '1',
        ],
        'COMBIN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::COMBIN',
            'argumentCount' => '2',
        ],
        'COMPLEX' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::COMPLEX',
            'argumentCount' => '2,3',
        ],
        'CONCATENATE' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::CONCATENATE',
            'argumentCount' => '1+',
        ],
        'CONFIDENCE' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::CONFIDENCE',
            'argumentCount' => '3',
        ],
        'CONVERT' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::CONVERTUOM',
            'argumentCount' => '3',
        ],
        'CORREL' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::CORREL',
            'argumentCount' => '2',
        ],
        'COS' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'cos',
            'argumentCount' => '1',
        ],
        'COSH' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'cosh',
            'argumentCount' => '1',
        ],
        'COUNT' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::COUNT',
            'argumentCount' => '1+',
        ],
        'COUNTA' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::COUNTA',
            'argumentCount' => '1+',
        ],
        'COUNTBLANK' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::COUNTBLANK',
            'argumentCount' => '1',
        ],
        'COUNTIF' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::COUNTIF',
            'argumentCount' => '2',
        ],
        'COUNTIFS' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '2',
        ],
        'COUPDAYBS' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::COUPDAYBS',
            'argumentCount' => '3,4',
        ],
        'COUPDAYS' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::COUPDAYS',
            'argumentCount' => '3,4',
        ],
        'COUPDAYSNC' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::COUPDAYSNC',
            'argumentCount' => '3,4',
        ],
        'COUPNCD' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::COUPNCD',
            'argumentCount' => '3,4',
        ],
        'COUPNUM' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::COUPNUM',
            'argumentCount' => '3,4',
        ],
        'COUPPCD' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::COUPPCD',
            'argumentCount' => '3,4',
        ],
        'COVAR' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::COVAR',
            'argumentCount' => '2',
        ],
        'CRITBINOM' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::CRITBINOM',
            'argumentCount' => '3',
        ],
        'CUBEKPIMEMBER' => [
            'category' => Calculation\Categories::CATEGORY_CUBE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '?',
        ],
        'CUBEMEMBER' => [
            'category' => Calculation\Categories::CATEGORY_CUBE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '?',
        ],
        'CUBEMEMBERPROPERTY' => [
            'category' => Calculation\Categories::CATEGORY_CUBE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '?',
        ],
        'CUBERANKEDMEMBER' => [
            'category' => Calculation\Categories::CATEGORY_CUBE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '?',
        ],
        'CUBESET' => [
            'category' => Calculation\Categories::CATEGORY_CUBE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '?',
        ],
        'CUBESETCOUNT' => [
            'category' => Calculation\Categories::CATEGORY_CUBE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '?',
        ],
        'CUBEVALUE' => [
            'category' => Calculation\Categories::CATEGORY_CUBE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '?',
        ],
        'CUMIPMT' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::CUMIPMT',
            'argumentCount' => '6',
        ],
        'CUMPRINC' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::CUMPRINC',
            'argumentCount' => '6',
        ],
        'DATE' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::DATE',
            'argumentCount' => '3',
        ],
        'DATEDIF' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::DATEDIF',
            'argumentCount' => '2,3',
        ],
        'DATEVALUE' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::DATEVALUE',
            'argumentCount' => '1',
        ],
        'DAVERAGE' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DAVERAGE',
            'argumentCount' => '3',
        ],
        'DAY' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::DAYOFMONTH',
            'argumentCount' => '1',
        ],
        'DAYS360' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::DAYS360',
            'argumentCount' => '2,3',
        ],
        'DB' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::DB',
            'argumentCount' => '4,5',
        ],
        'DCOUNT' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DCOUNT',
            'argumentCount' => '3',
        ],
        'DCOUNTA' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DCOUNTA',
            'argumentCount' => '3',
        ],
        'DDB' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::DDB',
            'argumentCount' => '4,5',
        ],
        'DEC2BIN' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::DECTOBIN',
            'argumentCount' => '1,2',
        ],
        'DEC2HEX' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::DECTOHEX',
            'argumentCount' => '1,2',
        ],
        'DEC2OCT' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::DECTOOCT',
            'argumentCount' => '1,2',
        ],
        'DEGREES' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'rad2deg',
            'argumentCount' => '1',
        ],
        'DELTA' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::DELTA',
            'argumentCount' => '1,2',
        ],
        'DEVSQ' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::DEVSQ',
            'argumentCount' => '1+',
        ],
        'DGET' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DGET',
            'argumentCount' => '3',
        ],
        'DISC' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::DISC',
            'argumentCount' => '4,5',
        ],
        'DMAX' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DMAX',
            'argumentCount' => '3',
        ],
        'DMIN' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DMIN',
            'argumentCount' => '3',
        ],
        'DOLLAR' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::DOLLAR',
            'argumentCount' => '1,2',
        ],
        'DOLLARDE' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::DOLLARDE',
            'argumentCount' => '2',
        ],
        'DOLLARFR' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::DOLLARFR',
            'argumentCount' => '2',
        ],
        'DPRODUCT' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DPRODUCT',
            'argumentCount' => '3',
        ],
        'DSTDEV' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DSTDEV',
            'argumentCount' => '3',
        ],
        'DSTDEVP' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DSTDEVP',
            'argumentCount' => '3',
        ],
        'DSUM' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DSUM',
            'argumentCount' => '3',
        ],
        'DURATION' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '5,6',
        ],
        'DVAR' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DVAR',
            'argumentCount' => '3',
        ],
        'DVARP' => [
            'category' => Calculation\Categories::CATEGORY_DATABASE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Database::DVARP',
            'argumentCount' => '3',
        ],
        'EDATE' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::EDATE',
            'argumentCount' => '2',
        ],
        'EFFECT' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::EFFECT',
            'argumentCount' => '2',
        ],
        'EOMONTH' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::EOMONTH',
            'argumentCount' => '2',
        ],
        'ERF' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::ERF',
            'argumentCount' => '1,2',
        ],
        'ERFC' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::ERFC',
            'argumentCount' => '1',
        ],
        'ERROR.TYPE' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::errorType',
            'argumentCount' => '1',
        ],
        'EVEN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::EVEN',
            'argumentCount' => '1',
        ],
        'EXACT' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '2',
        ],
        'EXP' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'exp',
            'argumentCount' => '1',
        ],
        'EXPONDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::EXPONDIST',
            'argumentCount' => '3',
        ],
        'FACT' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::FACT',
            'argumentCount' => '1',
        ],
        'FACTDOUBLE' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::FACTDOUBLE',
            'argumentCount' => '1',
        ],
        'FALSE' => [
            'category' => Calculation\Categories::CATEGORY_LOGICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Logical::FALSE',
            'argumentCount' => '0',
        ],
        'FDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '3',
        ],
        'FIND' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::SEARCHSENSITIVE',
            'argumentCount' => '2,3',
        ],
        'FINDB' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::SEARCHSENSITIVE',
            'argumentCount' => '2,3',
        ],
        'FINV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '3',
        ],
        'FISHER' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::FISHER',
            'argumentCount' => '1',
        ],
        'FISHERINV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::FISHERINV',
            'argumentCount' => '1',
        ],
        'FIXED' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::FIXEDFORMAT',
            'argumentCount' => '1-3',
        ],
        'FLOOR' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::FLOOR',
            'argumentCount' => '2',
        ],
        'FORECAST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::FORECAST',
            'argumentCount' => '3',
        ],
        'FREQUENCY' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '2',
        ],
        'FTEST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '2',
        ],
        'FV' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::FV',
            'argumentCount' => '3-5',
        ],
        'FVSCHEDULE' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::FVSCHEDULE',
            'argumentCount' => '2',
        ],
        'GAMMADIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::GAMMADIST',
            'argumentCount' => '4',
        ],
        'GAMMAINV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::GAMMAINV',
            'argumentCount' => '3',
        ],
        'GAMMALN' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::GAMMALN',
            'argumentCount' => '1',
        ],
        'GCD' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::GCD',
            'argumentCount' => '1+',
        ],
        'GEOMEAN' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::GEOMEAN',
            'argumentCount' => '1+',
        ],
        'GESTEP' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::GESTEP',
            'argumentCount' => '1,2',
        ],
        'GETPIVOTDATA' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '2+',
        ],
        'GROWTH' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::GROWTH',
            'argumentCount' => '1-4',
        ],
        'HARMEAN' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::HARMEAN',
            'argumentCount' => '1+',
        ],
        'HEX2BIN' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::HEXTOBIN',
            'argumentCount' => '1,2',
        ],
        'HEX2DEC' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::HEXTODEC',
            'argumentCount' => '1',
        ],
        'HEX2OCT' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::HEXTOOCT',
            'argumentCount' => '1,2',
        ],
        'HLOOKUP' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::HLOOKUP',
            'argumentCount' => '3,4',
        ],
        'HOUR' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::HOUROFDAY',
            'argumentCount' => '1',
        ],
        'HYPERLINK' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::HYPERLINK',
            'argumentCount' => '1,2',
            'passCellReference' => true,
        ],
        'HYPGEOMDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::HYPGEOMDIST',
            'argumentCount' => '4',
        ],
        'IF' => [
            'category' => Calculation\Categories::CATEGORY_LOGICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Logical::statementIf',
            'argumentCount' => '1-3',
        ],
        'IFERROR' => [
            'category' => Calculation\Categories::CATEGORY_LOGICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Logical::IFERROR',
            'argumentCount' => '2',
        ],
        'IMABS' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMABS',
            'argumentCount' => '1',
        ],
        'IMAGINARY' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMAGINARY',
            'argumentCount' => '1',
        ],
        'IMARGUMENT' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMARGUMENT',
            'argumentCount' => '1',
        ],
        'IMCONJUGATE' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMCONJUGATE',
            'argumentCount' => '1',
        ],
        'IMCOS' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMCOS',
            'argumentCount' => '1',
        ],
        'IMDIV' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMDIV',
            'argumentCount' => '2',
        ],
        'IMEXP' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMEXP',
            'argumentCount' => '1',
        ],
        'IMLN' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMLN',
            'argumentCount' => '1',
        ],
        'IMLOG10' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMLOG10',
            'argumentCount' => '1',
        ],
        'IMLOG2' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMLOG2',
            'argumentCount' => '1',
        ],
        'IMPOWER' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMPOWER',
            'argumentCount' => '2',
        ],
        'IMPRODUCT' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMPRODUCT',
            'argumentCount' => '1+',
        ],
        'IMREAL' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMREAL',
            'argumentCount' => '1',
        ],
        'IMSIN' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMSIN',
            'argumentCount' => '1',
        ],
        'IMSQRT' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMSQRT',
            'argumentCount' => '1',
        ],
        'IMSUB' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMSUB',
            'argumentCount' => '2',
        ],
        'IMSUM' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::IMSUM',
            'argumentCount' => '1+',
        ],
        'INDEX' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::INDEX',
            'argumentCount' => '1-4',
        ],
        'INDIRECT' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::INDIRECT',
            'argumentCount' => '1,2',
            'passCellReference' => true,
        ],
        'INFO' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '1',
        ],
        'INT' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::INT',
            'argumentCount' => '1',
        ],
        'INTERCEPT' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::INTERCEPT',
            'argumentCount' => '2',
        ],
        'INTRATE' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::INTRATE',
            'argumentCount' => '4,5',
        ],
        'IPMT' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::IPMT',
            'argumentCount' => '4-6',
        ],
        'IRR' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::IRR',
            'argumentCount' => '1,2',
        ],
        'ISBLANK' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::isBlank',
            'argumentCount' => '1',
        ],
        'ISERR' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::IS_ERR',
            'argumentCount' => '1',
        ],
        'ISERROR' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::IS_ERROR',
            'argumentCount' => '1',
        ],
        'ISEVEN' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::isEven',
            'argumentCount' => '1',
        ],
        'ISLOGICAL' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::isLogical',
            'argumentCount' => '1',
        ],
        'ISNA' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::isNa',
            'argumentCount' => '1',
        ],
        'ISNONTEXT' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::isNonText',
            'argumentCount' => '1',
        ],
        'ISNUMBER' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::isNumber',
            'argumentCount' => '1',
        ],
        'ISODD' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::isOdd',
            'argumentCount' => '1',
        ],
        'ISPMT' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::ISPMT',
            'argumentCount' => '4',
        ],
        'ISREF' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '1',
        ],
        'ISTEXT' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::isText',
            'argumentCount' => '1',
        ],
        'JIS' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '1',
        ],
        'KURT' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::KURT',
            'argumentCount' => '1+',
        ],
        'LARGE' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::LARGE',
            'argumentCount' => '2',
        ],
        'LCM' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::LCM',
            'argumentCount' => '1+',
        ],
        'LEFT' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::LEFT',
            'argumentCount' => '1,2',
        ],
        'LEFTB' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::LEFT',
            'argumentCount' => '1,2',
        ],
        'LEN' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::STRINGLENGTH',
            'argumentCount' => '1',
        ],
        'LENB' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::STRINGLENGTH',
            'argumentCount' => '1',
        ],
        'LINEST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::LINEST',
            'argumentCount' => '1-4',
        ],
        'LN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'log',
            'argumentCount' => '1',
        ],
        'LOG' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::logBase',
            'argumentCount' => '1,2',
        ],
        'LOG10' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'log10',
            'argumentCount' => '1',
        ],
        'LOGEST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::LOGEST',
            'argumentCount' => '1-4',
        ],
        'LOGINV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::LOGINV',
            'argumentCount' => '3',
        ],
        'LOGNORMDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::LOGNORMDIST',
            'argumentCount' => '3',
        ],
        'LOOKUP' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::LOOKUP',
            'argumentCount' => '2,3',
        ],
        'LOWER' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::LOWERCASE',
            'argumentCount' => '1',
        ],
        'MATCH' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::MATCH',
            'argumentCount' => '2,3',
        ],
        'MAX' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::MAX',
            'argumentCount' => '1+',
        ],
        'MAXA' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::MAXA',
            'argumentCount' => '1+',
        ],
        'MAXIF' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::MAXIF',
            'argumentCount' => '2+',
        ],
        'MDETERM' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::MDETERM',
            'argumentCount' => '1',
        ],
        'MDURATION' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '5,6',
        ],
        'MEDIAN' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::MEDIAN',
            'argumentCount' => '1+',
        ],
        'MEDIANIF' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '2+',
        ],
        'MID' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::MID',
            'argumentCount' => '3',
        ],
        'MIDB' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::MID',
            'argumentCount' => '3',
        ],
        'MIN' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::MIN',
            'argumentCount' => '1+',
        ],
        'MINA' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::MINA',
            'argumentCount' => '1+',
        ],
        'MINIF' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::MINIF',
            'argumentCount' => '2+',
        ],
        'MINUTE' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::MINUTE',
            'argumentCount' => '1',
        ],
        'MINVERSE' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::MINVERSE',
            'argumentCount' => '1',
        ],
        'MIRR' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::MIRR',
            'argumentCount' => '3',
        ],
        'MMULT' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::MMULT',
            'argumentCount' => '2',
        ],
        'MOD' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::MOD',
            'argumentCount' => '2',
        ],
        'MODE' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::MODE',
            'argumentCount' => '1+',
        ],
        'MONTH' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::MONTHOFYEAR',
            'argumentCount' => '1',
        ],
        'MROUND' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::MROUND',
            'argumentCount' => '2',
        ],
        'MULTINOMIAL' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::MULTINOMIAL',
            'argumentCount' => '1+',
        ],
        'N' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::N',
            'argumentCount' => '1',
        ],
        'NA' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::NA',
            'argumentCount' => '0',
        ],
        'NEGBINOMDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::NEGBINOMDIST',
            'argumentCount' => '3',
        ],
        'NETWORKDAYS' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::NETWORKDAYS',
            'argumentCount' => '2+',
        ],
        'NOMINAL' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::NOMINAL',
            'argumentCount' => '2',
        ],
        'NORMDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::NORMDIST',
            'argumentCount' => '4',
        ],
        'NORMINV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::NORMINV',
            'argumentCount' => '3',
        ],
        'NORMSDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::NORMSDIST',
            'argumentCount' => '1',
        ],
        'NORMSINV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::NORMSINV',
            'argumentCount' => '1',
        ],
        'NOT' => [
            'category' => Calculation\Categories::CATEGORY_LOGICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Logical::NOT',
            'argumentCount' => '1',
        ],
        'NOW' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::DATETIMENOW',
            'argumentCount' => '0',
        ],
        'NPER' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::NPER',
            'argumentCount' => '3-5',
        ],
        'NPV' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::NPV',
            'argumentCount' => '2+',
        ],
        'OCT2BIN' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::OCTTOBIN',
            'argumentCount' => '1,2',
        ],
        'OCT2DEC' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::OCTTODEC',
            'argumentCount' => '1',
        ],
        'OCT2HEX' => [
            'category' => Calculation\Categories::CATEGORY_ENGINEERING,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Engineering::OCTTOHEX',
            'argumentCount' => '1,2',
        ],
        'ODD' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::ODD',
            'argumentCount' => '1',
        ],
        'ODDFPRICE' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '8,9',
        ],
        'ODDFYIELD' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '8,9',
        ],
        'ODDLPRICE' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '7,8',
        ],
        'ODDLYIELD' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '7,8',
        ],
        'OFFSET' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::OFFSET',
            'argumentCount' => '3-5',
            'passCellReference' => true,
            'passByReference' => [true],
        ],
        'OR' => [
            'category' => Calculation\Categories::CATEGORY_LOGICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Logical::logicalOr',
            'argumentCount' => '1+',
        ],
        'PEARSON' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::CORREL',
            'argumentCount' => '2',
        ],
        'PERCENTILE' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::PERCENTILE',
            'argumentCount' => '2',
        ],
        'PERCENTRANK' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::PERCENTRANK',
            'argumentCount' => '2,3',
        ],
        'PERMUT' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::PERMUT',
            'argumentCount' => '2',
        ],
        'PHONETIC' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '1',
        ],
        'PI' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'pi',
            'argumentCount' => '0',
        ],
        'PMT' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::PMT',
            'argumentCount' => '3-5',
        ],
        'POISSON' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::POISSON',
            'argumentCount' => '3',
        ],
        'POWER' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::POWER',
            'argumentCount' => '2',
        ],
        'PPMT' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::PPMT',
            'argumentCount' => '4-6',
        ],
        'PRICE' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::PRICE',
            'argumentCount' => '6,7',
        ],
        'PRICEDISC' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::PRICEDISC',
            'argumentCount' => '4,5',
        ],
        'PRICEMAT' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::PRICEMAT',
            'argumentCount' => '5,6',
        ],
        'PROB' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '3,4',
        ],
        'PRODUCT' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::PRODUCT',
            'argumentCount' => '1+',
        ],
        'PROPER' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::PROPERCASE',
            'argumentCount' => '1',
        ],
        'PV' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::PV',
            'argumentCount' => '3-5',
        ],
        'QUARTILE' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::QUARTILE',
            'argumentCount' => '2',
        ],
        'QUOTIENT' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::QUOTIENT',
            'argumentCount' => '2',
        ],
        'RADIANS' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'deg2rad',
            'argumentCount' => '1',
        ],
        'RAND' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::RAND',
            'argumentCount' => '0',
        ],
        'RANDBETWEEN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::RAND',
            'argumentCount' => '2',
        ],
        'RANK' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::RANK',
            'argumentCount' => '2,3',
        ],
        'RATE' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::RATE',
            'argumentCount' => '3-6',
        ],
        'RECEIVED' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::RECEIVED',
            'argumentCount' => '4-5',
        ],
        'REPLACE' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::REPLACE',
            'argumentCount' => '4',
        ],
        'REPLACEB' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::REPLACE',
            'argumentCount' => '4',
        ],
        'REPT' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => 'str_repeat',
            'argumentCount' => '2',
        ],
        'RIGHT' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::RIGHT',
            'argumentCount' => '1,2',
        ],
        'RIGHTB' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::RIGHT',
            'argumentCount' => '1,2',
        ],
        'ROMAN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::ROMAN',
            'argumentCount' => '1,2',
        ],
        'ROUND' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'round',
            'argumentCount' => '2',
        ],
        'ROUNDDOWN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::ROUNDDOWN',
            'argumentCount' => '2',
        ],
        'ROUNDUP' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::ROUNDUP',
            'argumentCount' => '2',
        ],
        'ROW' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::ROW',
            'argumentCount' => '-1',
            'passByReference' => [true],
        ],
        'ROWS' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::ROWS',
            'argumentCount' => '1',
        ],
        'RSQ' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::RSQ',
            'argumentCount' => '2',
        ],
        'RTD' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '1+',
        ],
        'SEARCH' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::SEARCHINSENSITIVE',
            'argumentCount' => '2,3',
        ],
        'SEARCHB' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::SEARCHINSENSITIVE',
            'argumentCount' => '2,3',
        ],
        'SECOND' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::SECOND',
            'argumentCount' => '1',
        ],
        'SERIESSUM' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SERIESSUM',
            'argumentCount' => '4',
        ],
        'SIGN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SIGN',
            'argumentCount' => '1',
        ],
        'SIN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'sin',
            'argumentCount' => '1',
        ],
        'SINH' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'sinh',
            'argumentCount' => '1',
        ],
        'SKEW' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::SKEW',
            'argumentCount' => '1+',
        ],
        'SLN' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::SLN',
            'argumentCount' => '3',
        ],
        'SLOPE' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::SLOPE',
            'argumentCount' => '2',
        ],
        'SMALL' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::SMALL',
            'argumentCount' => '2',
        ],
        'SQRT' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'sqrt',
            'argumentCount' => '1',
        ],
        'SQRTPI' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SQRTPI',
            'argumentCount' => '1',
        ],
        'STANDARDIZE' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::STANDARDIZE',
            'argumentCount' => '3',
        ],
        'STDEV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::STDEV',
            'argumentCount' => '1+',
        ],
        'STDEVA' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::STDEVA',
            'argumentCount' => '1+',
        ],
        'STDEVP' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::STDEVP',
            'argumentCount' => '1+',
        ],
        'STDEVPA' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::STDEVPA',
            'argumentCount' => '1+',
        ],
        'STEYX' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::STEYX',
            'argumentCount' => '2',
        ],
        'SUBSTITUTE' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::SUBSTITUTE',
            'argumentCount' => '3,4',
        ],
        'SUBTOTAL' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SUBTOTAL',
            'argumentCount' => '2+',
        ],
        'SUM' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SUM',
            'argumentCount' => '1+',
        ],
        'SUMIF' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SUMIF',
            'argumentCount' => '2,3',
        ],
        'SUMIFS' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SUMIFS',
            'argumentCount' => '3+',
        ],
        'SUMPRODUCT' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SUMPRODUCT',
            'argumentCount' => '1+',
        ],
        'SUMSQ' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SUMSQ',
            'argumentCount' => '1+',
        ],
        'SUMX2MY2' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SUMX2MY2',
            'argumentCount' => '2',
        ],
        'SUMX2PY2' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SUMX2PY2',
            'argumentCount' => '2',
        ],
        'SUMXMY2' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::SUMXMY2',
            'argumentCount' => '2',
        ],
        'SYD' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::SYD',
            'argumentCount' => '4',
        ],
        'T' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::RETURNSTRING',
            'argumentCount' => '1',
        ],
        'TAN' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'tan',
            'argumentCount' => '1',
        ],
        'TANH' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => 'tanh',
            'argumentCount' => '1',
        ],
        'TBILLEQ' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::TBILLEQ',
            'argumentCount' => '3',
        ],
        'TBILLPRICE' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::TBILLPRICE',
            'argumentCount' => '3',
        ],
        'TBILLYIELD' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::TBILLYIELD',
            'argumentCount' => '3',
        ],
        'TDIST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::TDIST',
            'argumentCount' => '3',
        ],
        'TEXT' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::TEXTFORMAT',
            'argumentCount' => '2',
        ],
        'TIME' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::TIME',
            'argumentCount' => '3',
        ],
        'TIMEVALUE' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::TIMEVALUE',
            'argumentCount' => '1',
        ],
        'TINV' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::TINV',
            'argumentCount' => '2',
        ],
        'TODAY' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::DATENOW',
            'argumentCount' => '0',
        ],
        'TRANSPOSE' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::TRANSPOSE',
            'argumentCount' => '1',
        ],
        'TREND' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::TREND',
            'argumentCount' => '1-4',
        ],
        'TRIM' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::TRIMSPACES',
            'argumentCount' => '1',
        ],
        'TRIMMEAN' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::TRIMMEAN',
            'argumentCount' => '2',
        ],
        'TRUE' => [
            'category' => Calculation\Categories::CATEGORY_LOGICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Logical::TRUE',
            'argumentCount' => '0',
        ],
        'TRUNC' => [
            'category' => Calculation\Categories::CATEGORY_MATH_AND_TRIG,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\MathTrig::TRUNC',
            'argumentCount' => '1,2',
        ],
        'TTEST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '4',
        ],
        'TYPE' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::TYPE',
            'argumentCount' => '1',
        ],
        'UPPER' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::UPPERCASE',
            'argumentCount' => '1',
        ],
        'USDOLLAR' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '2',
        ],
        'VALUE' => [
            'category' => Calculation\Categories::CATEGORY_TEXT_AND_DATA,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\TextData::VALUE',
            'argumentCount' => '1',
        ],
        'VAR' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::VARFunc',
            'argumentCount' => '1+',
        ],
        'VARA' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::VARA',
            'argumentCount' => '1+',
        ],
        'VARP' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::VARP',
            'argumentCount' => '1+',
        ],
        'VARPA' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::VARPA',
            'argumentCount' => '1+',
        ],
        'VDB' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '5-7',
        ],
        'VERSION' => [
            'category' => Calculation\Categories::CATEGORY_INFORMATION,
            'functionCall' => 'Calculation\Categories::VERSION',
            'argumentCount' => '0',
        ],
        'VLOOKUP' => [
            'category' => Calculation\Categories::CATEGORY_LOOKUP_AND_REFERENCE,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\LookupRef::VLOOKUP',
            'argumentCount' => '3,4',
        ],
        'WEEKDAY' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::WEEKDAY',
            'argumentCount' => '1,2',
        ],
        'WEEKNUM' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::WEEKNUM',
            'argumentCount' => '1,2',
        ],
        'WEIBULL' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::WEIBULL',
            'argumentCount' => '4',
        ],
        'WORKDAY' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::WORKDAY',
            'argumentCount' => '2+',
        ],
        'XIRR' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::XIRR',
            'argumentCount' => '2,3',
        ],
        'XNPV' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::XNPV',
            'argumentCount' => '3',
        ],
        'YEAR' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::YEAR',
            'argumentCount' => '1',
        ],
        'YEARFRAC' => [
            'category' => Calculation\Categories::CATEGORY_DATE_AND_TIME,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\DateTime::YEARFRAC',
            'argumentCount' => '2,3',
        ],
        'YIELD' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => 'Calculation\Categories::DUMMY',
            'argumentCount' => '6,7',
        ],
        'YIELDDISC' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::YIELDDISC',
            'argumentCount' => '4,5',
        ],
        'YIELDMAT' => [
            'category' => Calculation\Categories::CATEGORY_FINANCIAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Financial::YIELDMAT',
            'argumentCount' => '5,6',
        ],
        'ZTEST' => [
            'category' => Calculation\Categories::CATEGORY_STATISTICAL,
            'functionCall' => '\\PhpOffice\\PhpSpreadsheet\\Calculation\\Statistical::ZTEST',
            'argumentCount' => '2-3',
        ],
    ];

    //    Internal functions used for special control purposes
    private static $controlFunctions = [
        'MKMATRIX' => [
            'argumentCount' => '*',
            'functionCall' => 'self::mkMatrix',
        ],
    ];

    public function __construct(Spreadsheet $spreadsheet = null)
    {
        $this->delta = 1 * pow(10, 0 - ini_get('precision'));

        $this->spreadsheet = $spreadsheet;
        $this->cyclicReferenceStack = new CalcEngine\CyclicReferenceStack();
        $this->_debugLog = new CalcEngine\Logger($this->cyclicReferenceStack);
    }

    private static function loadLocales()
    {
        $localeFileDirectory = PHPSPREADSHEET_ROOT . 'PhpSpreadsheet/locale/';
        foreach (glob($localeFileDirectory . '/*', GLOB_ONLYDIR) as $filename) {
            $filename = substr($filename, strlen($localeFileDirectory) + 1);
            if ($filename != 'en') {
                self::$validLocaleLanguages[] = $filename;
            }
        }
    }

    /**
     * Get an instance of this class
     *
     * @param   Spreadsheet $spreadsheet  Injected spreadsheet for working with a PhpSpreadsheet Spreadsheet object,
     *                                    or NULL to create a standalone claculation engine
     * @return Calculation
     */
    public static function getInstance(Spreadsheet $spreadsheet = null)
    {
        if ($spreadsheet !== null) {
            $instance = $spreadsheet->getCalculationEngine();
            if (isset($instance)) {
                return $instance;
            }
        }

        if (!isset(self::$instance) || (self::$instance === null)) {
            self::$instance = new \PhpOffice\PhpSpreadsheet\Calculation();
        }

        return self::$instance;
    }

    /**
     * Unset an instance of this class
     *
     * @param   Spreadsheet $spreadsheet  Injected spreadsheet identifying the instance to unset
     */
    public function __destruct()
    {
        $this->workbook = null;
    }

    /**
     * Flush the calculation cache for any existing instance of this class
     *        but only if a \PhpOffice\PhpSpreadsheet\Calculation instance exists
     */
    public function flushInstance()
    {
        $this->clearCalculationCache();
    }

    /**
     * Get the debuglog for this claculation engine instance
     *
     * @return CalcEngine\Logger
     */
    public function getDebugLog()
    {
        return $this->_debugLog;
    }

    /**
     * __clone implementation. Cloning should not be allowed in a Singleton!
     *
     * @throws    Calculation\Exception
     */
    final public function __clone()
    {
        throw new Calculation\Exception('Cloning the calculation engine is not allowed!');
    }

    /**
     * Return the locale-specific translation of TRUE
     *
     * @return     string        locale-specific translation of TRUE
     */
    public static function getTRUE()
    {
        return self::$localeBoolean['TRUE'];
    }

    /**
     * Return the locale-specific translation of FALSE
     *
     * @return     string        locale-specific translation of FALSE
     */
    public static function getFALSE()
    {
        return self::$localeBoolean['FALSE'];
    }

    /**
     * Set the Array Return Type (Array or Value of first element in the array)
     *
     * @param     string    $returnType            Array return type
     * @return     bool                    Success or failure
     */
    public static function setArrayReturnType($returnType)
    {
        if (($returnType == self::RETURN_ARRAY_AS_VALUE) ||
            ($returnType == self::RETURN_ARRAY_AS_ERROR) ||
            ($returnType == self::RETURN_ARRAY_AS_ARRAY)) {
            self::$returnArrayAsType = $returnType;

            return true;
        }

        return false;
    }

    /**
     * Return the Array Return Type (Array or Value of first element in the array)
     *
     * @return     string        $returnType            Array return type
     */
    public static function getArrayReturnType()
    {
        return self::$returnArrayAsType;
    }

    /**
     * Is calculation caching enabled?
     *
     * @return bool
     */
    public function getCalculationCacheEnabled()
    {
        return $this->calculationCacheEnabled;
    }

    /**
     * Enable/disable calculation cache
     *
     * @param bool $pValue
     */
    public function setCalculationCacheEnabled($pValue = true)
    {
        $this->calculationCacheEnabled = $pValue;
        $this->clearCalculationCache();
    }

    /**
     * Enable calculation cache
     */
    public function enableCalculationCache()
    {
        $this->setCalculationCacheEnabled(true);
    }

    /**
     * Disable calculation cache
     */
    public function disableCalculationCache()
    {
        $this->setCalculationCacheEnabled(false);
    }

    /**
     * Clear calculation cache
     */
    public function clearCalculationCache()
    {
        $this->calculationCache = [];
    }

    /**
     * Clear calculation cache for a specified worksheet
     *
     * @param string $worksheetName
     */
    public function clearCalculationCacheForWorksheet($worksheetName)
    {
        if (isset($this->calculationCache[$worksheetName])) {
            unset($this->calculationCache[$worksheetName]);
        }
    }

    /**
     * Rename calculation cache for a specified worksheet
     *
     * @param string $fromWorksheetName
     * @param string $toWorksheetName
     */
    public function renameCalculationCacheForWorksheet($fromWorksheetName, $toWorksheetName)
    {
        if (isset($this->calculationCache[$fromWorksheetName])) {
            $this->calculationCache[$toWorksheetName] = &$this->calculationCache[$fromWorksheetName];
            unset($this->calculationCache[$fromWorksheetName]);
        }
    }

    /**
     * Get the currently defined locale code
     *
     * @return string
     */
    public function getLocale()
    {
        return self::$localeLanguage;
    }

    /**
     * Set the locale code
     *
     * @param string $locale  The locale to use for formula translation
     * @return bool
     */
    public function setLocale($locale = 'en_us')
    {
        //    Identify our locale and language
        $language = $locale = strtolower($locale);
        if (strpos($locale, '_') !== false) {
            list($language) = explode('_', $locale);
        }

        if (count(self::$validLocaleLanguages) == 1) {
            self::loadLocales();
        }
        //    Test whether we have any language data for this language (any locale)
        if (in_array($language, self::$validLocaleLanguages)) {
            //    initialise language/locale settings
            self::$localeFunctions = [];
            self::$localeArgumentSeparator = ',';
            self::$localeBoolean = ['TRUE' => 'TRUE', 'FALSE' => 'FALSE', 'NULL' => 'NULL'];
            //    Default is English, if user isn't requesting english, then read the necessary data from the locale files
            if ($locale != 'en_us') {
                //    Search for a file with a list of function names for locale
                $functionNamesFile = PHPSPREADSHEET_ROOT . 'PhpSpreadsheet' . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $locale) . DIRECTORY_SEPARATOR . 'functions';
                if (!file_exists($functionNamesFile)) {
                    //    If there isn't a locale specific function file, look for a language specific function file
                    $functionNamesFile = PHPSPREADSHEET_ROOT . 'PhpSpreadsheet' . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'functions';
                    if (!file_exists($functionNamesFile)) {
                        return false;
                    }
                }
                //    Retrieve the list of locale or language specific function names
                $localeFunctions = file($functionNamesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($localeFunctions as $localeFunction) {
                    list($localeFunction) = explode('##', $localeFunction); //    Strip out comments
                    if (strpos($localeFunction, '=') !== false) {
                        list($fName, $lfName) = explode('=', $localeFunction);
                        $fName = trim($fName);
                        $lfName = trim($lfName);
                        if ((isset(self::$phpSpreadsheetFunctions[$fName])) && ($lfName != '') && ($fName != $lfName)) {
                            self::$localeFunctions[$fName] = $lfName;
                        }
                    }
                }
                //    Default the TRUE and FALSE constants to the locale names of the TRUE() and FALSE() functions
                if (isset(self::$localeFunctions['TRUE'])) {
                    self::$localeBoolean['TRUE'] = self::$localeFunctions['TRUE'];
                }
                if (isset(self::$localeFunctions['FALSE'])) {
                    self::$localeBoolean['FALSE'] = self::$localeFunctions['FALSE'];
                }

                $configFile = PHPSPREADSHEET_ROOT . 'PhpSpreadsheet' . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $locale) . DIRECTORY_SEPARATOR . 'config';
                if (!file_exists($configFile)) {
                    $configFile = PHPSPREADSHEET_ROOT . 'PhpSpreadsheet' . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'config';
                }
                if (file_exists($configFile)) {
                    $localeSettings = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($localeSettings as $localeSetting) {
                        list($localeSetting) = explode('##', $localeSetting); //    Strip out comments
                        if (strpos($localeSetting, '=') !== false) {
                            list($settingName, $settingValue) = explode('=', $localeSetting);
                            $settingName = strtoupper(trim($settingName));
                            switch ($settingName) {
                                case 'ARGUMENTSEPARATOR':
                                    self::$localeArgumentSeparator = trim($settingValue);
                                    break;
                            }
                        }
                    }
                }
            }

            self::$functionReplaceFromExcel = self::$functionReplaceToExcel =
            self::$functionReplaceFromLocale = self::$functionReplaceToLocale = null;
            self::$localeLanguage = $locale;

            return true;
        }

        return false;
    }

    public static function translateSeparator($fromSeparator, $toSeparator, $formula, &$inBraces)
    {
        $strlen = mb_strlen($formula);
        for ($i = 0; $i < $strlen; ++$i) {
            $chr = mb_substr($formula, $i, 1);
            switch ($chr) {
                case '{':
                    $inBraces = true;
                    break;
                case '}':
                    $inBraces = false;
                    break;
                case $fromSeparator:
                    if (!$inBraces) {
                        $formula = mb_substr($formula, 0, $i) . $toSeparator . mb_substr($formula, $i + 1);
                    }
            }
        }

        return $formula;
    }

    /**
     * @param string $fromSeparator
     * @param string $toSeparator
     */
    private static function translateFormula($from, $to, $formula, $fromSeparator, $toSeparator)
    {
        //    Convert any Excel function names to the required language
        if (self::$localeLanguage !== 'en_us') {
            $inBraces = false;
            //    If there is the possibility of braces within a quoted string, then we don't treat those as matrix indicators
            if (strpos($formula, '"') !== false) {
                //    So instead we skip replacing in any quoted strings by only replacing in every other array element after we've exploded
                //        the formula
                $temp = explode('"', $formula);
                $i = false;
                foreach ($temp as &$value) {
                    //    Only count/replace in alternating array entries
                    if ($i = !$i) {
                        $value = preg_replace($from, $to, $value);
                        $value = self::translateSeparator($fromSeparator, $toSeparator, $value, $inBraces);
                    }
                }
                unset($value);
                //    Then rebuild the formula string
                $formula = implode('"', $temp);
            } else {
                //    If there's no quoted strings, then we do a simple count/replace
                $formula = preg_replace($from, $to, $formula);
                $formula = self::translateSeparator($fromSeparator, $toSeparator, $formula, $inBraces);
            }
        }

        return $formula;
    }

    private static $functionReplaceFromExcel = null;
    private static $functionReplaceToLocale = null;

    public function _translateFormulaToLocale($formula)
    {
        if (self::$functionReplaceFromExcel === null) {
            self::$functionReplaceFromExcel = [];
            foreach (array_keys(self::$localeFunctions) as $excelFunctionName) {
                self::$functionReplaceFromExcel[] = '/(@?[^\w\.])' . preg_quote($excelFunctionName) . '([\s]*\()/Ui';
            }
            foreach (array_keys(self::$localeBoolean) as $excelBoolean) {
                self::$functionReplaceFromExcel[] = '/(@?[^\w\.])' . preg_quote($excelBoolean) . '([^\w\.])/Ui';
            }
        }

        if (self::$functionReplaceToLocale === null) {
            self::$functionReplaceToLocale = [];
            foreach (array_values(self::$localeFunctions) as $localeFunctionName) {
                self::$functionReplaceToLocale[] = '$1' . trim($localeFunctionName) . '$2';
            }
            foreach (array_values(self::$localeBoolean) as $localeBoolean) {
                self::$functionReplaceToLocale[] = '$1' . trim($localeBoolean) . '$2';
            }
        }

        return self::translateFormula(self::$functionReplaceFromExcel, self::$functionReplaceToLocale, $formula, ',', self::$localeArgumentSeparator);
    }

    private static $functionReplaceFromLocale = null;
    private static $functionReplaceToExcel = null;

    public function _translateFormulaToEnglish($formula)
    {
        if (self::$functionReplaceFromLocale === null) {
            self::$functionReplaceFromLocale = [];
            foreach (array_values(self::$localeFunctions) as $localeFunctionName) {
                self::$functionReplaceFromLocale[] = '/(@?[^\w\.])' . preg_quote($localeFunctionName) . '([\s]*\()/Ui';
            }
            foreach (array_values(self::$localeBoolean) as $excelBoolean) {
                self::$functionReplaceFromLocale[] = '/(@?[^\w\.])' . preg_quote($excelBoolean) . '([^\w\.])/Ui';
            }
        }

        if (self::$functionReplaceToExcel === null) {
            self::$functionReplaceToExcel = [];
            foreach (array_keys(self::$localeFunctions) as $excelFunctionName) {
                self::$functionReplaceToExcel[] = '$1' . trim($excelFunctionName) . '$2';
            }
            foreach (array_keys(self::$localeBoolean) as $excelBoolean) {
                self::$functionReplaceToExcel[] = '$1' . trim($excelBoolean) . '$2';
            }
        }

        return self::translateFormula(self::$functionReplaceFromLocale, self::$functionReplaceToExcel, $formula, self::$localeArgumentSeparator, ',');
    }

    public static function localeFunc($function)
    {
        if (self::$localeLanguage !== 'en_us') {
            $functionName = trim($function, '(');
            if (isset(self::$localeFunctions[$functionName])) {
                $brace = ($functionName != $function);
                $function = self::$localeFunctions[$functionName];
                if ($brace) {
                    $function .= '(';
                }
            }
        }

        return $function;
    }

    /**
     * Wrap string values in quotes
     *
     * @param mixed $value
     * @return mixed
     */
    public static function wrapResult($value)
    {
        if (is_string($value)) {
            //    Error values cannot be "wrapped"
            if (preg_match('/^' . self::CALCULATION_REGEXP_ERROR . '$/i', $value, $match)) {
                //    Return Excel errors "as is"
                return $value;
            }
            //    Return strings wrapped in quotes
            return '"' . $value . '"';
        //    Convert numeric errors to NaN error
        } elseif ((is_float($value)) && ((is_nan($value)) || (is_infinite($value)))) {
            return Calculation\Functions::NAN();
        }

        return $value;
    }

    /**
     * Remove quotes used as a wrapper to identify string values
     *
     * @param mixed $value
     * @return mixed
     */
    public static function unwrapResult($value)
    {
        if (is_string($value)) {
            if ((isset($value{0})) && ($value{0} == '"') && (substr($value, -1) == '"')) {
                return substr($value, 1, -1);
            }
        //    Convert numeric errors to NAN error
        } elseif ((is_float($value)) && ((is_nan($value)) || (is_infinite($value)))) {
            return Calculation\Functions::NAN();
        }

        return $value;
    }

    /**
     * Calculate cell value (using formula from a cell ID)
     * Retained for backward compatibility
     *
     * @param    Cell    $pCell    Cell to calculate
     * @throws    Calculation\Exception
     * @return    mixed
     */
    public function calculate(Cell $pCell = null)
    {
        try {
            return $this->calculateCellValue($pCell);
        } catch (Exception $e) {
            throw new Calculation\Exception($e->getMessage());
        }
    }

    /**
     * Calculate the value of a cell formula
     *
     * @param    Cell    $pCell        Cell to calculate
     * @param    bool            $resetLog    Flag indicating whether the debug log should be reset or not
     * @throws    Calculation\Exception
     * @return    mixed
     */
    public function calculateCellValue(Cell $pCell = null, $resetLog = true)
    {
        if ($pCell === null) {
            return null;
        }

        $returnArrayAsType = self::$returnArrayAsType;
        if ($resetLog) {
            //    Initialise the logging settings if requested
            $this->formulaError = null;
            $this->_debugLog->clearLog();
            $this->cyclicReferenceStack->clear();
            $this->cyclicFormulaCounter = 1;

            self::$returnArrayAsType = self::RETURN_ARRAY_AS_ARRAY;
        }

        //    Execute the calculation for the cell formula
        $this->cellStack[] = [
            'sheet' => $pCell->getWorksheet()->getTitle(),
            'cell' => $pCell->getCoordinate(),
        ];
        try {
            $result = self::unwrapResult($this->_calculateFormulaValue($pCell->getValue(), $pCell->getCoordinate(), $pCell));
            $cellAddress = array_pop($this->cellStack);
            $this->spreadsheet->getSheetByName($cellAddress['sheet'])->getCell($cellAddress['cell']);
        } catch (Exception $e) {
            $cellAddress = array_pop($this->cellStack);
            $this->spreadsheet->getSheetByName($cellAddress['sheet'])->getCell($cellAddress['cell']);
            throw new Calculation\Exception($e->getMessage());
        }

        if ((is_array($result)) && (self::$returnArrayAsType != self::RETURN_ARRAY_AS_ARRAY)) {
            self::$returnArrayAsType = $returnArrayAsType;
            $testResult = Calculation\Functions::flattenArray($result);
            if (self::$returnArrayAsType == self::RETURN_ARRAY_AS_ERROR) {
                return Calculation\Functions::VALUE();
            }
            //    If there's only a single cell in the array, then we allow it
            if (count($testResult) != 1) {
                //    If keys are numeric, then it's a matrix result rather than a cell range result, so we permit it
                $r = array_keys($result);
                $r = array_shift($r);
                if (!is_numeric($r)) {
                    return Calculation\Functions::VALUE();
                }
                if (is_array($result[$r])) {
                    $c = array_keys($result[$r]);
                    $c = array_shift($c);
                    if (!is_numeric($c)) {
                        return Calculation\Functions::VALUE();
                    }
                }
            }
            $result = array_shift($testResult);
        }
        self::$returnArrayAsType = $returnArrayAsType;

        if ($result === null) {
            return 0;
        } elseif ((is_float($result)) && ((is_nan($result)) || (is_infinite($result)))) {
            return Calculation\Functions::NAN();
        }

        return $result;
    }

    /**
     * Validate and parse a formula string
     *
     * @param    string        $formula        Formula to parse
     * @throws    Calculation\Exception
     * @return    array
     */
    public function parseFormula($formula)
    {
        //    Basic validation that this is indeed a formula
        //    We return an empty array if not
        $formula = trim($formula);
        if ((!isset($formula{0})) || ($formula{0} != '=')) {
            return [];
        }
        $formula = ltrim(substr($formula, 1));
        if (!isset($formula{0})) {
            return [];
        }

        //    Parse the formula and return the token stack
        return $this->_parseFormula($formula);
    }

    /**
     * Calculate the value of a formula
     *
     * @param    string            $formula    Formula to parse
     * @param    string            $cellID        Address of the cell to calculate
     * @param    Cell    $pCell        Cell to calculate
     * @throws    Calculation\Exception
     * @return    mixed
     */
    public function calculateFormula($formula, $cellID = null, Cell $pCell = null)
    {
        //    Initialise the logging settings
        $this->formulaError = null;
        $this->_debugLog->clearLog();
        $this->cyclicReferenceStack->clear();

        if ($this->spreadsheet !== null && $cellID === null && $pCell === null) {
            $cellID = 'A1';
            $pCell = $this->spreadsheet->getActiveSheet()->getCell($cellID);
        } else {
            //    Disable calculation cacheing because it only applies to cell calculations, not straight formulae
            //    But don't actually flush any cache
            $resetCache = $this->getCalculationCacheEnabled();
            $this->calculationCacheEnabled = false;
        }

        //    Execute the calculation
        try {
            $result = self::unwrapResult($this->_calculateFormulaValue($formula, $cellID, $pCell));
        } catch (Exception $e) {
            throw new Calculation\Exception($e->getMessage());
        }

        if ($this->spreadsheet === null) {
            //    Reset calculation cacheing to its previous state
            $this->calculationCacheEnabled = $resetCache;
        }

        return $result;
    }

    public function getValueFromCache($cellReference, &$cellValue)
    {
        // Is calculation cacheing enabled?
        // Is the value present in calculation cache?
        $this->_debugLog->writeDebugLog('Testing cache value for cell ', $cellReference);
        if (($this->calculationCacheEnabled) && (isset($this->calculationCache[$cellReference]))) {
            $this->_debugLog->writeDebugLog('Retrieving value for cell ', $cellReference, ' from cache');
            // Return the cached result
            $cellValue = $this->calculationCache[$cellReference];

            return true;
        }

        return false;
    }

    /**
     * @param string $cellReference
     */
    public function saveValueToCache($cellReference, $cellValue)
    {
        if ($this->calculationCacheEnabled) {
            $this->calculationCache[$cellReference] = $cellValue;
        }
    }

    /**
     * Parse a cell formula and calculate its value
     *
     * @param    string            $formula    The formula to parse and calculate
     * @param    string            $cellID        The ID (e.g. A3) of the cell that we are calculating
     * @param    Cell    $pCell        Cell to calculate
     * @throws   Calculation\Exception
     * @return   mixed
     */
    public function _calculateFormulaValue($formula, $cellID = null, Cell $pCell = null)
    {
        $cellValue = null;

        //    Basic validation that this is indeed a formula
        //    We simply return the cell value if not
        $formula = trim($formula);
        if ($formula{0} != '=') {
            return self::wrapResult($formula);
        }
        $formula = ltrim(substr($formula, 1));
        if (!isset($formula{0})) {
            return self::wrapResult($formula);
        }

        $pCellParent = ($pCell !== null) ? $pCell->getWorksheet() : null;
        $wsTitle = ($pCellParent !== null) ? $pCellParent->getTitle() : "\x00Wrk";
        $wsCellReference = $wsTitle . '!' . $cellID;

        if (($cellID !== null) && ($this->getValueFromCache($wsCellReference, $cellValue))) {
            return $cellValue;
        }

        if (($wsTitle{0} !== "\x00") && ($this->cyclicReferenceStack->onStack($wsCellReference))) {
            if ($this->cyclicFormulaCount <= 0) {
                $this->cyclicFormulaCell = '';

                return $this->raiseFormulaError('Cyclic Reference in Formula');
            } elseif ($this->cyclicFormulaCell === $wsCellReference) {
                ++$this->cyclicFormulaCounter;
                if ($this->cyclicFormulaCounter >= $this->cyclicFormulaCount) {
                    $this->cyclicFormulaCell = '';

                    return $cellValue;
                }
            } elseif ($this->cyclicFormulaCell == '') {
                if ($this->cyclicFormulaCounter >= $this->cyclicFormulaCount) {
                    return $cellValue;
                }
                $this->cyclicFormulaCell = $wsCellReference;
            }
        }

        //    Parse the formula onto the token stack and calculate the value
        $this->cyclicReferenceStack->push($wsCellReference);
        $cellValue = $this->processTokenStack($this->_parseFormula($formula, $pCell), $cellID, $pCell);
        $this->cyclicReferenceStack->pop();

        // Save to calculation cache
        if ($cellID !== null) {
            $this->saveValueToCache($wsCellReference, $cellValue);
        }

        //    Return the calculated value
        return $cellValue;
    }

    /**
     * Ensure that paired matrix operands are both matrices and of the same size
     *
     * @param    mixed        &$operand1    First matrix operand
     * @param    mixed        &$operand2    Second matrix operand
     * @param    int        $resize        Flag indicating whether the matrices should be resized to match
     *                                        and (if so), whether the smaller dimension should grow or the
     *                                        larger should shrink.
     *                                            0 = no resize
     *                                            1 = shrink to fit
     *                                            2 = extend to fit
     */
    private static function checkMatrixOperands(&$operand1, &$operand2, $resize = 1)
    {
        //    Examine each of the two operands, and turn them into an array if they aren't one already
        //    Note that this function should only be called if one or both of the operand is already an array
        if (!is_array($operand1)) {
            list($matrixRows, $matrixColumns) = self::getMatrixDimensions($operand2);
            $operand1 = array_fill(0, $matrixRows, array_fill(0, $matrixColumns, $operand1));
            $resize = 0;
        } elseif (!is_array($operand2)) {
            list($matrixRows, $matrixColumns) = self::getMatrixDimensions($operand1);
            $operand2 = array_fill(0, $matrixRows, array_fill(0, $matrixColumns, $operand2));
            $resize = 0;
        }

        list($matrix1Rows, $matrix1Columns) = self::getMatrixDimensions($operand1);
        list($matrix2Rows, $matrix2Columns) = self::getMatrixDimensions($operand2);
        if (($matrix1Rows == $matrix2Columns) && ($matrix2Rows == $matrix1Columns)) {
            $resize = 1;
        }

        if ($resize == 2) {
            //    Given two matrices of (potentially) unequal size, convert the smaller in each dimension to match the larger
            self::resizeMatricesExtend($operand1, $operand2, $matrix1Rows, $matrix1Columns, $matrix2Rows, $matrix2Columns);
        } elseif ($resize == 1) {
            //    Given two matrices of (potentially) unequal size, convert the larger in each dimension to match the smaller
            self::resizeMatricesShrink($operand1, $operand2, $matrix1Rows, $matrix1Columns, $matrix2Rows, $matrix2Columns);
        }

        return [$matrix1Rows, $matrix1Columns, $matrix2Rows, $matrix2Columns];
    }

    /**
     * Read the dimensions of a matrix, and re-index it with straight numeric keys starting from row 0, column 0
     *
     * @param    mixed        &$matrix        matrix operand
     * @return    int[]        An array comprising the number of rows, and number of columns
     */
    private static function getMatrixDimensions(&$matrix)
    {
        $matrixRows = count($matrix);
        $matrixColumns = 0;
        foreach ($matrix as $rowKey => $rowValue) {
            $matrixColumns = max(count($rowValue), $matrixColumns);
            if (!is_array($rowValue)) {
                $matrix[$rowKey] = [$rowValue];
            } else {
                $matrix[$rowKey] = array_values($rowValue);
            }
        }
        $matrix = array_values($matrix);

        return [$matrixRows, $matrixColumns];
    }

    /**
     * Ensure that paired matrix operands are both matrices of the same size
     *
     * @param    mixed        &$matrix1        First matrix operand
     * @param    mixed        &$matrix2        Second matrix operand
     * @param    int        $matrix1Rows    Row size of first matrix operand
     * @param    int        $matrix1Columns    Column size of first matrix operand
     * @param    int        $matrix2Rows    Row size of second matrix operand
     * @param    int        $matrix2Columns    Column size of second matrix operand
     */
    private static function resizeMatricesShrink(&$matrix1, &$matrix2, $matrix1Rows, $matrix1Columns, $matrix2Rows, $matrix2Columns)
    {
        if (($matrix2Columns < $matrix1Columns) || ($matrix2Rows < $matrix1Rows)) {
            if ($matrix2Rows < $matrix1Rows) {
                for ($i = $matrix2Rows; $i < $matrix1Rows; ++$i) {
                    unset($matrix1[$i]);
                }
            }
            if ($matrix2Columns < $matrix1Columns) {
                for ($i = 0; $i < $matrix1Rows; ++$i) {
                    for ($j = $matrix2Columns; $j < $matrix1Columns; ++$j) {
                        unset($matrix1[$i][$j]);
                    }
                }
            }
        }

        if (($matrix1Columns < $matrix2Columns) || ($matrix1Rows < $matrix2Rows)) {
            if ($matrix1Rows < $matrix2Rows) {
                for ($i = $matrix1Rows; $i < $matrix2Rows; ++$i) {
                    unset($matrix2[$i]);
                }
            }
            if ($matrix1Columns < $matrix2Columns) {
                for ($i = 0; $i < $matrix2Rows; ++$i) {
                    for ($j = $matrix1Columns; $j < $matrix2Columns; ++$j) {
                        unset($matrix2[$i][$j]);
                    }
                }
            }
        }
    }

    /**
     * Ensure that paired matrix operands are both matrices of the same size
     *
     * @param    mixed        &$matrix1    First matrix operand
     * @param    mixed        &$matrix2    Second matrix operand
     * @param    int        $matrix1Rows    Row size of first matrix operand
     * @param    int        $matrix1Columns    Column size of first matrix operand
     * @param    int        $matrix2Rows    Row size of second matrix operand
     * @param    int        $matrix2Columns    Column size of second matrix operand
     */
    private static function resizeMatricesExtend(&$matrix1, &$matrix2, $matrix1Rows, $matrix1Columns, $matrix2Rows, $matrix2Columns)
    {
        if (($matrix2Columns < $matrix1Columns) || ($matrix2Rows < $matrix1Rows)) {
            if ($matrix2Columns < $matrix1Columns) {
                for ($i = 0; $i < $matrix2Rows; ++$i) {
                    $x = $matrix2[$i][$matrix2Columns - 1];
                    for ($j = $matrix2Columns; $j < $matrix1Columns; ++$j) {
                        $matrix2[$i][$j] = $x;
                    }
                }
            }
            if ($matrix2Rows < $matrix1Rows) {
                $x = $matrix2[$matrix2Rows - 1];
                for ($i = 0; $i < $matrix1Rows; ++$i) {
                    $matrix2[$i] = $x;
                }
            }
        }

        if (($matrix1Columns < $matrix2Columns) || ($matrix1Rows < $matrix2Rows)) {
            if ($matrix1Columns < $matrix2Columns) {
                for ($i = 0; $i < $matrix1Rows; ++$i) {
                    $x = $matrix1[$i][$matrix1Columns - 1];
                    for ($j = $matrix1Columns; $j < $matrix2Columns; ++$j) {
                        $matrix1[$i][$j] = $x;
                    }
                }
            }
            if ($matrix1Rows < $matrix2Rows) {
                $x = $matrix1[$matrix1Rows - 1];
                for ($i = 0; $i < $matrix2Rows; ++$i) {
                    $matrix1[$i] = $x;
                }
            }
        }
    }

    /**
     * Format details of an operand for display in the log (based on operand type)
     *
     * @param    mixed        $value    First matrix operand
     * @return    mixed
     */
    private function showValue($value)
    {
        if ($this->_debugLog->getWriteDebugLog()) {
            $testArray = Calculation\Functions::flattenArray($value);
            if (count($testArray) == 1) {
                $value = array_pop($testArray);
            }

            if (is_array($value)) {
                $returnMatrix = [];
                $pad = $rpad = ', ';
                foreach ($value as $row) {
                    if (is_array($row)) {
                        $returnMatrix[] = implode($pad, array_map([$this, 'showValue'], $row));
                        $rpad = '; ';
                    } else {
                        $returnMatrix[] = $this->showValue($row);
                    }
                }

                return '{ ' . implode($rpad, $returnMatrix) . ' }';
            } elseif (is_string($value) && (trim($value, '"') == $value)) {
                return '"' . $value . '"';
            } elseif (is_bool($value)) {
                return ($value) ? self::$localeBoolean['TRUE'] : self::$localeBoolean['FALSE'];
            }
        }

        return Calculation\Functions::flattenSingleValue($value);
    }

    /**
     * Format type and details of an operand for display in the log (based on operand type)
     *
     * @param    mixed        $value    First matrix operand
     * @return    string|null
     */
    private function showTypeDetails($value)
    {
        if ($this->_debugLog->getWriteDebugLog()) {
            $testArray = Calculation\Functions::flattenArray($value);
            if (count($testArray) == 1) {
                $value = array_pop($testArray);
            }

            if ($value === null) {
                return 'a NULL value';
            } elseif (is_float($value)) {
                $typeString = 'a floating point number';
            } elseif (is_int($value)) {
                $typeString = 'an integer number';
            } elseif (is_bool($value)) {
                $typeString = 'a boolean';
            } elseif (is_array($value)) {
                $typeString = 'a matrix';
            } else {
                if ($value == '') {
                    return 'an empty string';
                } elseif ($value{0} == '#') {
                    return 'a ' . $value . ' error';
                } else {
                    $typeString = 'a string';
                }
            }

            return $typeString . ' with a value of ' . $this->showValue($value);
        }
    }

    private function convertMatrixReferences($formula)
    {
        static $matrixReplaceFrom = ['{', ';', '}'];
        static $matrixReplaceTo = ['MKMATRIX(MKMATRIX(', '),MKMATRIX(', '))'];

        //    Convert any Excel matrix references to the MKMATRIX() function
        if (strpos($formula, '{') !== false) {
            //    If there is the possibility of braces within a quoted string, then we don't treat those as matrix indicators
            if (strpos($formula, '"') !== false) {
                //    So instead we skip replacing in any quoted strings by only replacing in every other array element after we've exploded
                //        the formula
                $temp = explode('"', $formula);
                //    Open and Closed counts used for trapping mismatched braces in the formula
                $openCount = $closeCount = 0;
                $i = false;
                foreach ($temp as &$value) {
                    //    Only count/replace in alternating array entries
                    if ($i = !$i) {
                        $openCount += substr_count($value, '{');
                        $closeCount += substr_count($value, '}');
                        $value = str_replace($matrixReplaceFrom, $matrixReplaceTo, $value);
                    }
                }
                unset($value);
                //    Then rebuild the formula string
                $formula = implode('"', $temp);
            } else {
                //    If there's no quoted strings, then we do a simple count/replace
                $openCount = substr_count($formula, '{');
                $closeCount = substr_count($formula, '}');
                $formula = str_replace($matrixReplaceFrom, $matrixReplaceTo, $formula);
            }
            //    Trap for mismatched braces and trigger an appropriate error
            if ($openCount < $closeCount) {
                if ($openCount > 0) {
                    return $this->raiseFormulaError("Formula Error: Mismatched matrix braces '}'");
                } else {
                    return $this->raiseFormulaError("Formula Error: Unexpected '}' encountered");
                }
            } elseif ($openCount > $closeCount) {
                if ($closeCount > 0) {
                    return $this->raiseFormulaError("Formula Error: Mismatched matrix braces '{'");
                } else {
                    return $this->raiseFormulaError("Formula Error: Unexpected '{' encountered");
                }
            }
        }

        return $formula;
    }

    private static function mkMatrix()
    {
        return func_get_args();
    }

    //    Binary Operators
    //    These operators always work on two values
    //    Array key is the operator, the value indicates whether this is a left or right associative operator
    private static $operatorAssociativity = [
        '^' => 0, //    Exponentiation
        '*' => 0, '/' => 0, //    Multiplication and Division
        '+' => 0, '-' => 0, //    Addition and Subtraction
        '&' => 0, //    Concatenation
        '|' => 0, ':' => 0, //    Intersect and Range
        '>' => 0, '<' => 0, '=' => 0, '>=' => 0, '<=' => 0, '<>' => 0, //    Comparison
    ];

    //    Comparison (Boolean) Operators
    //    These operators work on two values, but always return a boolean result
    private static $comparisonOperators = ['>' => true, '<' => true, '=' => true, '>=' => true, '<=' => true, '<>' => true];

    //    Operator Precedence
    //    This list includes all valid operators, whether binary (including boolean) or unary (such as %)
    //    Array key is the operator, the value is its precedence
    private static $operatorPrecedence = [
        ':' => 8, //    Range
        '|' => 7, //    Intersect
        '~' => 6, //    Negation
        '%' => 5, //    Percentage
        '^' => 4, //    Exponentiation
        '*' => 3, '/' => 3, //    Multiplication and Division
        '+' => 2, '-' => 2, //    Addition and Subtraction
        '&' => 1, //    Concatenation
        '>' => 0, '<' => 0, '=' => 0, '>=' => 0, '<=' => 0, '<>' => 0, //    Comparison
    ];

    // Convert infix to postfix notation
    private function _parseFormula($formula, Cell $pCell = null)
    {
        if (($formula = $this->convertMatrixReferences(trim($formula))) === false) {
            return false;
        }

        //    If we're using cell caching, then $pCell may well be flushed back to the cache (which detaches the parent worksheet),
        //        so we store the parent worksheet so that we can re-attach it when necessary
        $pCellParent = ($pCell !== null) ? $pCell->getWorksheet() : null;

        $regexpMatchString = '/^(' . self::CALCULATION_REGEXP_FUNCTION .
                                '|' . self::CALCULATION_REGEXP_CELLREF .
                                '|' . self::CALCULATION_REGEXP_NUMBER .
                                '|' . self::CALCULATION_REGEXP_STRING .
                                '|' . self::CALCULATION_REGEXP_OPENBRACE .
                                '|' . self::CALCULATION_REGEXP_NAMEDRANGE .
                                '|' . self::CALCULATION_REGEXP_ERROR .
                                ')/si';

        //    Start with initialisation
        $index = 0;
        $stack = new Calculation\Token\Stack();
        $output = [];
        $expectingOperator = false; //    We use this test in syntax-checking the expression to determine when a
                                                    //        - is a negation or + is a positive operator rather than an operation
        $expectingOperand = false; //    We use this test in syntax-checking the expression to determine whether an operand
                                                    //        should be null in a function call
        //    The guts of the lexical parser
        //    Loop through the formula extracting each operator and operand in turn
        while (true) {
            $opCharacter = $formula{$index};    //    Get the first character of the value at the current index position
            if ((isset(self::$comparisonOperators[$opCharacter])) && (strlen($formula) > $index) && (isset(self::$comparisonOperators[$formula{$index + 1}]))) {
                $opCharacter .= $formula{++$index};
            }

            //    Find out if we're currently at the beginning of a number, variable, cell reference, function, parenthesis or operand
            $isOperandOrFunction = preg_match($regexpMatchString, substr($formula, $index), $match);

            if ($opCharacter == '-' && !$expectingOperator) {                //    Is it a negation instead of a minus?
                $stack->push('Unary Operator', '~'); //    Put a negation on the stack
                ++$index; //        and drop the negation symbol
            } elseif ($opCharacter == '%' && $expectingOperator) {
                $stack->push('Unary Operator', '%'); //    Put a percentage on the stack
                ++$index;
            } elseif ($opCharacter == '+' && !$expectingOperator) {            //    Positive (unary plus rather than binary operator plus) can be discarded?
                ++$index; //    Drop the redundant plus symbol
            } elseif ((($opCharacter == '~') || ($opCharacter == '|')) && (!$isOperandOrFunction)) {    //    We have to explicitly deny a tilde or pipe, because they are legal
                return $this->raiseFormulaError("Formula Error: Illegal character '~'"); //        on the stack but not in the input expression
            } elseif ((isset(self::$operators[$opCharacter]) or $isOperandOrFunction) && $expectingOperator) {    //    Are we putting an operator on the stack?
                while ($stack->count() > 0 &&
                    ($o2 = $stack->last()) &&
                    isset(self::$operators[$o2['value']]) &&
                    @(self::$operatorAssociativity[$opCharacter] ? self::$operatorPrecedence[$opCharacter] < self::$operatorPrecedence[$o2['value']] : self::$operatorPrecedence[$opCharacter] <= self::$operatorPrecedence[$o2['value']])) {
                    $output[] = $stack->pop(); //    Swap operands and higher precedence operators from the stack to the output
                }
                $stack->push('Binary Operator', $opCharacter); //    Finally put our current operator onto the stack
                ++$index;
                $expectingOperator = false;
            } elseif ($opCharacter == ')' && $expectingOperator) {            //    Are we expecting to close a parenthesis?
                $expectingOperand = false;
                while (($o2 = $stack->pop()) && $o2['value'] != '(') {        //    Pop off the stack back to the last (
                    if ($o2 === null) {
                        return $this->raiseFormulaError('Formula Error: Unexpected closing brace ")"');
                    } else {
                        $output[] = $o2;
                    }
                }
                $d = $stack->last(2);
                if (preg_match('/^' . self::CALCULATION_REGEXP_FUNCTION . '$/i', $d['value'], $matches)) {    //    Did this parenthesis just close a function?
                    $functionName = $matches[1]; //    Get the function name
                    $d = $stack->pop();
                    $argumentCount = $d['value']; //    See how many arguments there were (argument count is the next value stored on the stack)
                    $output[] = $d; //    Dump the argument count on the output
                    $output[] = $stack->pop(); //    Pop the function and push onto the output
                    if (isset(self::$controlFunctions[$functionName])) {
                        $expectedArgumentCount = self::$controlFunctions[$functionName]['argumentCount'];
                        $functionCall = self::$controlFunctions[$functionName]['functionCall'];
                    } elseif (isset(self::$phpSpreadsheetFunctions[$functionName])) {
                        $expectedArgumentCount = self::$phpSpreadsheetFunctions[$functionName]['argumentCount'];
                        $functionCall = self::$phpSpreadsheetFunctions[$functionName]['functionCall'];
                    } else {    // did we somehow push a non-function on the stack? this should never happen
                        return $this->raiseFormulaError('Formula Error: Internal error, non-function on stack');
                    }
                    //    Check the argument count
                    $argumentCountError = false;
                    if (is_numeric($expectedArgumentCount)) {
                        if ($expectedArgumentCount < 0) {
                            if ($argumentCount > abs($expectedArgumentCount)) {
                                $argumentCountError = true;
                                $expectedArgumentCountString = 'no more than ' . abs($expectedArgumentCount);
                            }
                        } else {
                            if ($argumentCount != $expectedArgumentCount) {
                                $argumentCountError = true;
                                $expectedArgumentCountString = $expectedArgumentCount;
                            }
                        }
                    } elseif ($expectedArgumentCount != '*') {
                        $isOperandOrFunction = preg_match('/(\d*)([-+,])(\d*)/', $expectedArgumentCount, $argMatch);
                        switch ($argMatch[2]) {
                            case '+':
                                if ($argumentCount < $argMatch[1]) {
                                    $argumentCountError = true;
                                    $expectedArgumentCountString = $argMatch[1] . ' or more ';
                                }
                                break;
                            case '-':
                                if (($argumentCount < $argMatch[1]) || ($argumentCount > $argMatch[3])) {
                                    $argumentCountError = true;
                                    $expectedArgumentCountString = 'between ' . $argMatch[1] . ' and ' . $argMatch[3];
                                }
                                break;
                            case ',':
                                if (($argumentCount != $argMatch[1]) && ($argumentCount != $argMatch[3])) {
                                    $argumentCountError = true;
                                    $expectedArgumentCountString = 'either ' . $argMatch[1] . ' or ' . $argMatch[3];
                                }
                                break;
                        }
                    }
                    if ($argumentCountError) {
                        return $this->raiseFormulaError("Formula Error: Wrong number of arguments for $functionName() function: $argumentCount given, " . $expectedArgumentCountString . ' expected');
                    }
                }
                ++$index;
            } elseif ($opCharacter == ',') {            //    Is this the separator for function arguments?
                while (($o2 = $stack->pop()) && $o2['value'] != '(') {        //    Pop off the stack back to the last (
                    if ($o2 === null) {
                        return $this->raiseFormulaError('Formula Error: Unexpected ,');
                    } else {
                        $output[] = $o2; // pop the argument expression stuff and push onto the output
                    }
                }
                //    If we've a comma when we're expecting an operand, then what we actually have is a null operand;
                //        so push a null onto the stack
                if (($expectingOperand) || (!$expectingOperator)) {
                    $output[] = ['type' => 'NULL Value', 'value' => self::$excelConstants['NULL'], 'reference' => null];
                }
                // make sure there was a function
                $d = $stack->last(2);
                if (!preg_match('/^' . self::CALCULATION_REGEXP_FUNCTION . '$/i', $d['value'], $matches)) {
                    return $this->raiseFormulaError('Formula Error: Unexpected ,');
                }
                $d = $stack->pop();
                $stack->push($d['type'], ++$d['value'], $d['reference']); // increment the argument count
                $stack->push('Brace', '('); // put the ( back on, we'll need to pop back to it again
                $expectingOperator = false;
                $expectingOperand = true;
                ++$index;
            } elseif ($opCharacter == '(' && !$expectingOperator) {
                $stack->push('Brace', '(');
                ++$index;
            } elseif ($isOperandOrFunction && !$expectingOperator) {    // do we now have a function/variable/number?
                $expectingOperator = true;
                $expectingOperand = false;
                $val = $match[1];
                $length = strlen($val);

                if (preg_match('/^' . self::CALCULATION_REGEXP_FUNCTION . '$/i', $val, $matches)) {
                    $val = preg_replace('/\s/u', '', $val);
                    if (isset(self::$phpSpreadsheetFunctions[strtoupper($matches[1])]) || isset(self::$controlFunctions[strtoupper($matches[1])])) {    // it's a function
                        $stack->push('Function', strtoupper($val));
                        $ax = preg_match('/^\s*(\s*\))/ui', substr($formula, $index + $length), $amatch);
                        if ($ax) {
                            $stack->push('Operand Count for Function ' . strtoupper($val) . ')', 0);
                            $expectingOperator = true;
                        } else {
                            $stack->push('Operand Count for Function ' . strtoupper($val) . ')', 1);
                            $expectingOperator = false;
                        }
                        $stack->push('Brace', '(');
                    } else {    // it's a var w/ implicit multiplication
                        $output[] = ['type' => 'Value', 'value' => $matches[1], 'reference' => null];
                    }
                } elseif (preg_match('/^' . self::CALCULATION_REGEXP_CELLREF . '$/i', $val, $matches)) {
                    //    Watch for this case-change when modifying to allow cell references in different worksheets...
                    //    Should only be applied to the actual cell column, not the worksheet name

                    //    If the last entry on the stack was a : operator, then we have a cell range reference
                    $testPrevOp = $stack->last(1);
                    if ($testPrevOp['value'] == ':') {
                        //    If we have a worksheet reference, then we're playing with a 3D reference
                        if ($matches[2] == '') {
                            //    Otherwise, we 'inherit' the worksheet reference from the start cell reference
                            //    The start of the cell range reference should be the last entry in $output
                            $startCellRef = $output[count($output) - 1]['value'];
                            preg_match('/^' . self::CALCULATION_REGEXP_CELLREF . '$/i', $startCellRef, $startMatches);
                            if ($startMatches[2] > '') {
                                $val = $startMatches[2] . '!' . $val;
                            }
                        } else {
                            return $this->raiseFormulaError('3D Range references are not yet supported');
                        }
                    }

                    $output[] = ['type' => 'Cell Reference', 'value' => $val, 'reference' => $val];
                } else {    // it's a variable, constant, string, number or boolean
                    //    If the last entry on the stack was a : operator, then we may have a row or column range reference
                    $testPrevOp = $stack->last(1);
                    if ($testPrevOp['value'] == ':') {
                        $startRowColRef = $output[count($output) - 1]['value'];
                        $rangeWS1 = '';
                        if (strpos('!', $startRowColRef) !== false) {
                            list($rangeWS1, $startRowColRef) = explode('!', $startRowColRef);
                        }
                        if ($rangeWS1 != '') {
                            $rangeWS1 .= '!';
                        }
                        $rangeWS2 = $rangeWS1;
                        if (strpos('!', $val) !== false) {
                            list($rangeWS2, $val) = explode('!', $val);
                        }
                        if ($rangeWS2 != '') {
                            $rangeWS2 .= '!';
                        }
                        if ((is_integer($startRowColRef)) && (ctype_digit($val)) &&
                            ($startRowColRef <= 1048576) && ($val <= 1048576)) {
                            //    Row range
                            $endRowColRef = ($pCellParent !== null) ? $pCellParent->getHighestColumn() : 'XFD'; //    Max 16,384 columns for Excel2007
                            $output[count($output) - 1]['value'] = $rangeWS1 . 'A' . $startRowColRef;
                            $val = $rangeWS2 . $endRowColRef . $val;
                        } elseif ((ctype_alpha($startRowColRef)) && (ctype_alpha($val)) &&
                            (strlen($startRowColRef) <= 3) && (strlen($val) <= 3)) {
                            //    Column range
                            $endRowColRef = ($pCellParent !== null) ? $pCellParent->getHighestRow() : 1048576; //    Max 1,048,576 rows for Excel2007
                            $output[count($output) - 1]['value'] = $rangeWS1 . strtoupper($startRowColRef) . '1';
                            $val = $rangeWS2 . $val . $endRowColRef;
                        }
                    }

                    $localeConstant = false;
                    if ($opCharacter == '"') {
                        //    UnEscape any quotes within the string
                        $val = self::wrapResult(str_replace('""', '"', self::unwrapResult($val)));
                    } elseif (is_numeric($val)) {
                        if ((strpos($val, '.') !== false) || (stripos($val, 'e') !== false) || ($val > PHP_INT_MAX) || ($val < -PHP_INT_MAX)) {
                            $val = (float) $val;
                        } else {
                            $val = (integer) $val;
                        }
                    } elseif (isset(self::$excelConstants[trim(strtoupper($val))])) {
                        $excelConstant = trim(strtoupper($val));
                        $val = self::$excelConstants[$excelConstant];
                    } elseif (($localeConstant = array_search(trim(strtoupper($val)), self::$localeBoolean)) !== false) {
                        $val = self::$excelConstants[$localeConstant];
                    }
                    $details = ['type' => 'Value', 'value' => $val, 'reference' => null];
                    if ($localeConstant) {
                        $details['localeValue'] = $localeConstant;
                    }
                    $output[] = $details;
                }
                $index += $length;
            } elseif ($opCharacter == '$') {    // absolute row or column range
                ++$index;
            } elseif ($opCharacter == ')') {    // miscellaneous error checking
                if ($expectingOperand) {
                    $output[] = ['type' => 'NULL Value', 'value' => self::$excelConstants['NULL'], 'reference' => null];
                    $expectingOperand = false;
                    $expectingOperator = true;
                } else {
                    return $this->raiseFormulaError("Formula Error: Unexpected ')'");
                }
            } elseif (isset(self::$operators[$opCharacter]) && !$expectingOperator) {
                return $this->raiseFormulaError("Formula Error: Unexpected operator '$opCharacter'");
            } else {    // I don't even want to know what you did to get here
                return $this->raiseFormulaError('Formula Error: An unexpected error occured');
            }
            //    Test for end of formula string
            if ($index == strlen($formula)) {
                //    Did we end with an operator?.
                //    Only valid for the % unary operator
                if ((isset(self::$operators[$opCharacter])) && ($opCharacter != '%')) {
                    return $this->raiseFormulaError("Formula Error: Operator '$opCharacter' has no operands");
                } else {
                    break;
                }
            }
            //    Ignore white space
            while (($formula{$index} == "\n") || ($formula{$index} == "\r")) {
                ++$index;
            }
            if ($formula{$index} == ' ') {
                while ($formula{$index} == ' ') {
                    ++$index;
                }
                //    If we're expecting an operator, but only have a space between the previous and next operands (and both are
                //        Cell References) then we have an INTERSECTION operator
                if (($expectingOperator) && (preg_match('/^' . self::CALCULATION_REGEXP_CELLREF . '.*/Ui', substr($formula, $index), $match)) &&
                    ($output[count($output) - 1]['type'] == 'Cell Reference')) {
                    while ($stack->count() > 0 &&
                        ($o2 = $stack->last()) &&
                        isset(self::$operators[$o2['value']]) &&
                        @(self::$operatorAssociativity[$opCharacter] ? self::$operatorPrecedence[$opCharacter] < self::$operatorPrecedence[$o2['value']] : self::$operatorPrecedence[$opCharacter] <= self::$operatorPrecedence[$o2['value']])) {
                        $output[] = $stack->pop(); //    Swap operands and higher precedence operators from the stack to the output
                    }
                    $stack->push('Binary Operator', '|'); //    Put an Intersect Operator on the stack
                    $expectingOperator = false;
                }
            }
        }

        while (($op = $stack->pop()) !== null) {    // pop everything off the stack and push onto output
            if ((is_array($op) && $op['value'] == '(') || ($op === '(')) {
                return $this->raiseFormulaError("Formula Error: Expecting ')'"); // if there are any opening braces on the stack, then braces were unbalanced
            }
            $output[] = $op;
        }

        return $output;
    }

    private static function dataTestReference(&$operandData)
    {
        $operand = $operandData['value'];
        if (($operandData['reference'] === null) && (is_array($operand))) {
            $rKeys = array_keys($operand);
            $rowKey = array_shift($rKeys);
            $cKeys = array_keys(array_keys($operand[$rowKey]));
            $colKey = array_shift($cKeys);
            if (ctype_upper($colKey)) {
                $operandData['reference'] = $colKey . $rowKey;
            }
        }

        return $operand;
    }

    // evaluate postfix notation

    /**
     * @param string $cellID
     */
    private function processTokenStack($tokens, $cellID = null, Cell $pCell = null)
    {
        if ($tokens == false) {
            return false;
        }

        //    If we're using cell caching, then $pCell may well be flushed back to the cache (which detaches the parent cell collection),
        //        so we store the parent cell collection so that we can re-attach it when necessary
        $pCellWorksheet = ($pCell !== null) ? $pCell->getWorksheet() : null;
        $pCellParent = ($pCell !== null) ? $pCell->getParent() : null;
        $stack = new Calculation\Token\Stack();

        //    Loop through each token in turn
        foreach ($tokens as $tokenData) {
            $token = $tokenData['value'];
            // if the token is a binary operator, pop the top two values off the stack, do the operation, and push the result back on the stack
            if (isset(self::$binaryOperators[$token])) {
                //    We must have two operands, error if we don't
                if (($operand2Data = $stack->pop()) === null) {
                    return $this->raiseFormulaError('Internal error - Operand value missing from stack');
                }
                if (($operand1Data = $stack->pop()) === null) {
                    return $this->raiseFormulaError('Internal error - Operand value missing from stack');
                }

                $operand1 = self::dataTestReference($operand1Data);
                $operand2 = self::dataTestReference($operand2Data);

                //    Log what we're doing
                if ($token == ':') {
                    $this->_debugLog->writeDebugLog('Evaluating Range ', $this->showValue($operand1Data['reference']), ' ', $token, ' ', $this->showValue($operand2Data['reference']));
                } else {
                    $this->_debugLog->writeDebugLog('Evaluating ', $this->showValue($operand1), ' ', $token, ' ', $this->showValue($operand2));
                }

                //    Process the operation in the appropriate manner
                switch ($token) {
                    //    Comparison (Boolean) Operators
                    case '>':            //    Greater than
                    case '<':            //    Less than
                    case '>=':            //    Greater than or Equal to
                    case '<=':            //    Less than or Equal to
                    case '=':            //    Equality
                    case '<>':            //    Inequality
                        $this->executeBinaryComparisonOperation($cellID, $operand1, $operand2, $token, $stack);
                        break;
                    //    Binary Operators
                    case ':':            //    Range
                        $sheet1 = $sheet2 = '';
                        if (strpos($operand1Data['reference'], '!') !== false) {
                            list($sheet1, $operand1Data['reference']) = explode('!', $operand1Data['reference']);
                        } else {
                            $sheet1 = ($pCellParent !== null) ? $pCellWorksheet->getTitle() : '';
                        }
                        if (strpos($operand2Data['reference'], '!') !== false) {
                            list($sheet2, $operand2Data['reference']) = explode('!', $operand2Data['reference']);
                        } else {
                            $sheet2 = $sheet1;
                        }
                        if ($sheet1 == $sheet2) {
                            if ($operand1Data['reference'] === null) {
                                if ((trim($operand1Data['value']) != '') && (is_numeric($operand1Data['value']))) {
                                    $operand1Data['reference'] = $pCell->getColumn() . $operand1Data['value'];
                                } elseif (trim($operand1Data['reference']) == '') {
                                    $operand1Data['reference'] = $pCell->getCoordinate();
                                } else {
                                    $operand1Data['reference'] = $operand1Data['value'] . $pCell->getRow();
                                }
                            }
                            if ($operand2Data['reference'] === null) {
                                if ((trim($operand2Data['value']) != '') && (is_numeric($operand2Data['value']))) {
                                    $operand2Data['reference'] = $pCell->getColumn() . $operand2Data['value'];
                                } elseif (trim($operand2Data['reference']) == '') {
                                    $operand2Data['reference'] = $pCell->getCoordinate();
                                } else {
                                    $operand2Data['reference'] = $operand2Data['value'] . $pCell->getRow();
                                }
                            }

                            $oData = array_merge(explode(':', $operand1Data['reference']), explode(':', $operand2Data['reference']));
                            $oCol = $oRow = [];
                            foreach ($oData as $oDatum) {
                                $oCR = Cell::coordinateFromString($oDatum);
                                $oCol[] = Cell::columnIndexFromString($oCR[0]) - 1;
                                $oRow[] = $oCR[1];
                            }
                            $cellRef = Cell::stringFromColumnIndex(min($oCol)) . min($oRow) . ':' . Cell::stringFromColumnIndex(max($oCol)) . max($oRow);
                            if ($pCellParent !== null) {
                                $cellValue = $this->extractCellRange($cellRef, $this->spreadsheet->getSheetByName($sheet1), false);
                            } else {
                                return $this->raiseFormulaError('Unable to access Cell Reference');
                            }
                            $stack->push('Cell Reference', $cellValue, $cellRef);
                        } else {
                            $stack->push('Error', Calculation\Functions::REF(), null);
                        }
                        break;
                    case '+':            //    Addition
                        $this->executeNumericBinaryOperation($cellID, $operand1, $operand2, $token, 'plusEquals', $stack);
                        break;
                    case '-':            //    Subtraction
                        $this->executeNumericBinaryOperation($cellID, $operand1, $operand2, $token, 'minusEquals', $stack);
                        break;
                    case '*':            //    Multiplication
                        $this->executeNumericBinaryOperation($cellID, $operand1, $operand2, $token, 'arrayTimesEquals', $stack);
                        break;
                    case '/':            //    Division
                        $this->executeNumericBinaryOperation($cellID, $operand1, $operand2, $token, 'arrayRightDivide', $stack);
                        break;
                    case '^':            //    Exponential
                        $this->executeNumericBinaryOperation($cellID, $operand1, $operand2, $token, 'power', $stack);
                        break;
                    case '&':            //    Concatenation
                        //    If either of the operands is a matrix, we need to treat them both as matrices
                        //        (converting the other operand to a matrix if need be); then perform the required
                        //        matrix operation
                        if (is_bool($operand1)) {
                            $operand1 = ($operand1) ? self::$localeBoolean['TRUE'] : self::$localeBoolean['FALSE'];
                        }
                        if (is_bool($operand2)) {
                            $operand2 = ($operand2) ? self::$localeBoolean['TRUE'] : self::$localeBoolean['FALSE'];
                        }
                        if ((is_array($operand1)) || (is_array($operand2))) {
                            //    Ensure that both operands are arrays/matrices
                            self::checkMatrixOperands($operand1, $operand2, 2);
                            try {
                                //    Convert operand 1 from a PHP array to a matrix
                                $matrix = new Shared\JAMA\Matrix($operand1);
                                //    Perform the required operation against the operand 1 matrix, passing in operand 2
                                $matrixResult = $matrix->concat($operand2);
                                $result = $matrixResult->getArray();
                            } catch (Exception $ex) {
                                $this->_debugLog->writeDebugLog('JAMA Matrix Exception: ', $ex->getMessage());
                                $result = '#VALUE!';
                            }
                        } else {
                            $result = '"' . str_replace('""', '"', self::unwrapResult($operand1, '"') . self::unwrapResult($operand2, '"')) . '"';
                        }
                        $this->_debugLog->writeDebugLog('Evaluation Result is ', $this->showTypeDetails($result));
                        $stack->push('Value', $result);
                        break;
                    case '|':            //    Intersect
                        $rowIntersect = array_intersect_key($operand1, $operand2);
                        $cellIntersect = $oCol = $oRow = [];
                        foreach (array_keys($rowIntersect) as $row) {
                            $oRow[] = $row;
                            foreach ($rowIntersect[$row] as $col => $data) {
                                $oCol[] = Cell::columnIndexFromString($col) - 1;
                                $cellIntersect[$row] = array_intersect_key($operand1[$row], $operand2[$row]);
                            }
                        }
                        $cellRef = Cell::stringFromColumnIndex(min($oCol)) . min($oRow) . ':' . Cell::stringFromColumnIndex(max($oCol)) . max($oRow);
                        $this->_debugLog->writeDebugLog('Evaluation Result is ', $this->showTypeDetails($cellIntersect));
                        $stack->push('Value', $cellIntersect, $cellRef);
                        break;
                }

            // if the token is a unary operator, pop one value off the stack, do the operation, and push it back on
            } elseif (($token === '~') || ($token === '%')) {
                if (($arg = $stack->pop()) === null) {
                    return $this->raiseFormulaError('Internal error - Operand value missing from stack');
                }
                $arg = $arg['value'];
                if ($token === '~') {
                    $this->_debugLog->writeDebugLog('Evaluating Negation of ', $this->showValue($arg));
                    $multiplier = -1;
                } else {
                    $this->_debugLog->writeDebugLog('Evaluating Percentile of ', $this->showValue($arg));
                    $multiplier = 0.01;
                }
                if (is_array($arg)) {
                    self::checkMatrixOperands($arg, $multiplier, 2);
                    try {
                        $matrix1 = new Shared\JAMA\Matrix($arg);
                        $matrixResult = $matrix1->arrayTimesEquals($multiplier);
                        $result = $matrixResult->getArray();
                    } catch (Exception $ex) {
                        $this->_debugLog->writeDebugLog('JAMA Matrix Exception: ', $ex->getMessage());
                        $result = '#VALUE!';
                    }
                    $this->_debugLog->writeDebugLog('Evaluation Result is ', $this->showTypeDetails($result));
                    $stack->push('Value', $result);
                } else {
                    $this->executeNumericBinaryOperation($cellID, $multiplier, $arg, '*', 'arrayTimesEquals', $stack);
                }
            } elseif (preg_match('/^' . self::CALCULATION_REGEXP_CELLREF . '$/i', $token, $matches)) {
                $cellRef = null;
                if (isset($matches[8])) {
                    if ($pCell === null) {
                        //                        We can't access the range, so return a REF error
                        $cellValue = Calculation\Functions::REF();
                    } else {
                        $cellRef = $matches[6] . $matches[7] . ':' . $matches[9] . $matches[10];
                        if ($matches[2] > '') {
                            $matches[2] = trim($matches[2], "\"'");
                            if ((strpos($matches[2], '[') !== false) || (strpos($matches[2], ']') !== false)) {
                                //    It's a Reference to an external spreadsheet (not currently supported)
                                return $this->raiseFormulaError('Unable to access External Workbook');
                            }
                            $matches[2] = trim($matches[2], "\"'");
                            $this->_debugLog->writeDebugLog('Evaluating Cell Range ', $cellRef, ' in worksheet ', $matches[2]);
                            if ($pCellParent !== null) {
                                $cellValue = $this->extractCellRange($cellRef, $this->spreadsheet->getSheetByName($matches[2]), false);
                            } else {
                                return $this->raiseFormulaError('Unable to access Cell Reference');
                            }
                            $this->_debugLog->writeDebugLog('Evaluation Result for cells ', $cellRef, ' in worksheet ', $matches[2], ' is ', $this->showTypeDetails($cellValue));
                        } else {
                            $this->_debugLog->writeDebugLog('Evaluating Cell Range ', $cellRef, ' in current worksheet');
                            if ($pCellParent !== null) {
                                $cellValue = $this->extractCellRange($cellRef, $pCellWorksheet, false);
                            } else {
                                return $this->raiseFormulaError('Unable to access Cell Reference');
                            }
                            $this->_debugLog->writeDebugLog('Evaluation Result for cells ', $cellRef, ' is ', $this->showTypeDetails($cellValue));
                        }
                    }
                } else {
                    if ($pCell === null) {
                        //                        We can't access the cell, so return a REF error
                        $cellValue = Calculation\Functions::REF();
                    } else {
                        $cellRef = $matches[6] . $matches[7];
                        if ($matches[2] > '') {
                            $matches[2] = trim($matches[2], "\"'");
                            if ((strpos($matches[2], '[') !== false) || (strpos($matches[2], ']') !== false)) {
                                //    It's a Reference to an external spreadsheet (not currently supported)
                                return $this->raiseFormulaError('Unable to access External Workbook');
                            }
                            $this->_debugLog->writeDebugLog('Evaluating Cell ', $cellRef, ' in worksheet ', $matches[2]);
                            if ($pCellParent !== null) {
                                $cellSheet = $this->spreadsheet->getSheetByName($matches[2]);
                                if ($cellSheet && $cellSheet->cellExists($cellRef)) {
                                    $cellValue = $this->extractCellRange($cellRef, $this->spreadsheet->getSheetByName($matches[2]), false);
                                    $pCell->attach($pCellParent);
                                } else {
                                    $cellValue = null;
                                }
                            } else {
                                return $this->raiseFormulaError('Unable to access Cell Reference');
                            }
                            $this->_debugLog->writeDebugLog('Evaluation Result for cell ', $cellRef, ' in worksheet ', $matches[2], ' is ', $this->showTypeDetails($cellValue));
                        } else {
                            $this->_debugLog->writeDebugLog('Evaluating Cell ', $cellRef, ' in current worksheet');
                            if ($pCellParent->isDataSet($cellRef)) {
                                $cellValue = $this->extractCellRange($cellRef, $pCellWorksheet, false);
                                $pCell->attach($pCellParent);
                            } else {
                                $cellValue = null;
                            }
                            $this->_debugLog->writeDebugLog('Evaluation Result for cell ', $cellRef, ' is ', $this->showTypeDetails($cellValue));
                        }
                    }
                }
                $stack->push('Value', $cellValue, $cellRef);

            // if the token is a function, pop arguments off the stack, hand them to the function, and push the result back on
            } elseif (preg_match('/^' . self::CALCULATION_REGEXP_FUNCTION . '$/i', $token, $matches)) {
                $functionName = $matches[1];
                $argCount = $stack->pop();
                $argCount = $argCount['value'];
                if ($functionName != 'MKMATRIX') {
                    $this->_debugLog->writeDebugLog('Evaluating Function ', self::localeFunc($functionName), '() with ', (($argCount == 0) ? 'no' : $argCount), ' argument', (($argCount == 1) ? '' : 's'));
                }
                if ((isset(self::$phpSpreadsheetFunctions[$functionName])) || (isset(self::$controlFunctions[$functionName]))) {    // function
                    if (isset(self::$phpSpreadsheetFunctions[$functionName])) {
                        $functionCall = self::$phpSpreadsheetFunctions[$functionName]['functionCall'];
                        $passByReference = isset(self::$phpSpreadsheetFunctions[$functionName]['passByReference']);
                        $passCellReference = isset(self::$phpSpreadsheetFunctions[$functionName]['passCellReference']);
                    } elseif (isset(self::$controlFunctions[$functionName])) {
                        $functionCall = self::$controlFunctions[$functionName]['functionCall'];
                        $passByReference = isset(self::$controlFunctions[$functionName]['passByReference']);
                        $passCellReference = isset(self::$controlFunctions[$functionName]['passCellReference']);
                    }
                    // get the arguments for this function
                    $args = $argArrayVals = [];
                    for ($i = 0; $i < $argCount; ++$i) {
                        $arg = $stack->pop();
                        $a = $argCount - $i - 1;
                        if (($passByReference) &&
                            (isset(self::$phpSpreadsheetFunctions[$functionName]['passByReference'][$a])) &&
                            (self::$phpSpreadsheetFunctions[$functionName]['passByReference'][$a])) {
                            if ($arg['reference'] === null) {
                                $args[] = $cellID;
                                if ($functionName != 'MKMATRIX') {
                                    $argArrayVals[] = $this->showValue($cellID);
                                }
                            } else {
                                $args[] = $arg['reference'];
                                if ($functionName != 'MKMATRIX') {
                                    $argArrayVals[] = $this->showValue($arg['reference']);
                                }
                            }
                        } else {
                            $args[] = self::unwrapResult($arg['value']);
                            if ($functionName != 'MKMATRIX') {
                                $argArrayVals[] = $this->showValue($arg['value']);
                            }
                        }
                    }
                    //    Reverse the order of the arguments
                    krsort($args);
                    if (($passByReference) && ($argCount == 0)) {
                        $args[] = $cellID;
                        $argArrayVals[] = $this->showValue($cellID);
                    }

                    if ($functionName != 'MKMATRIX') {
                        if ($this->_debugLog->getWriteDebugLog()) {
                            krsort($argArrayVals);
                            $this->_debugLog->writeDebugLog('Evaluating ', self::localeFunc($functionName), '( ', implode(self::$localeArgumentSeparator . ' ', Calculation\Functions::flattenArray($argArrayVals)), ' )');
                        }
                    }

                    //    Process the argument with the appropriate function call
                    if ($passCellReference) {
                        $args[] = $pCell;
                    }
                    if (strpos($functionCall, '::') !== false) {
                        $result = call_user_func_array(explode('::', $functionCall), $args);
                    } else {
                        foreach ($args as &$arg) {
                            $arg = Calculation\Functions::flattenSingleValue($arg);
                        }
                        unset($arg);
                        $result = call_user_func_array($functionCall, $args);
                    }
                    if ($functionName != 'MKMATRIX') {
                        $this->_debugLog->writeDebugLog('Evaluation Result for ', self::localeFunc($functionName), '() function call is ', $this->showTypeDetails($result));
                    }
                    $stack->push('Value', self::wrapResult($result));
                }
            } else {
                // if the token is a number, boolean, string or an Excel error, push it onto the stack
                if (isset(self::$excelConstants[strtoupper($token)])) {
                    $excelConstant = strtoupper($token);
                    $stack->push('Constant Value', self::$excelConstants[$excelConstant]);
                    $this->_debugLog->writeDebugLog('Evaluating Constant ', $excelConstant, ' as ', $this->showTypeDetails(self::$excelConstants[$excelConstant]));
                } elseif ((is_numeric($token)) || ($token === null) || (is_bool($token)) || ($token == '') || ($token{0} == '"') || ($token{0} == '#')) {
                    $stack->push('Value', $token);
                // if the token is a named range, push the named range name onto the stack
                } elseif (preg_match('/^' . self::CALCULATION_REGEXP_NAMEDRANGE . '$/i', $token, $matches)) {
                    $namedRange = $matches[6];
                    $this->_debugLog->writeDebugLog('Evaluating Named Range ', $namedRange);
                    $cellValue = $this->extractNamedRange($namedRange, ((null !== $pCell) ? $pCellWorksheet : null), false);
                    $pCell->attach($pCellParent);
                    $this->_debugLog->writeDebugLog('Evaluation Result for named range ', $namedRange, ' is ', $this->showTypeDetails($cellValue));
                    $stack->push('Named Range', $cellValue, $namedRange);
                } else {
                    return $this->raiseFormulaError("undefined variable '$token'");
                }
            }
        }
        // when we're out of tokens, the stack should have a single element, the final result
        if ($stack->count() != 1) {
            return $this->raiseFormulaError('internal error');
        }
        $output = $stack->pop();
        $output = $output['value'];

//        if ((is_array($output)) && (self::$returnArrayAsType != self::RETURN_ARRAY_AS_ARRAY)) {
//            return array_shift(Calculation\Functions::flattenArray($output));
//        }
        return $output;
    }

    private function validateBinaryOperand($cellID, &$operand, &$stack)
    {
        if (is_array($operand)) {
            if ((count($operand, COUNT_RECURSIVE) - count($operand)) == 1) {
                do {
                    $operand = array_pop($operand);
                } while (is_array($operand));
            }
        }
        //    Numbers, matrices and booleans can pass straight through, as they're already valid
        if (is_string($operand)) {
            //    We only need special validations for the operand if it is a string
            //    Start by stripping off the quotation marks we use to identify true excel string values internally
            if ($operand > '' && $operand{0} == '"') {
                $operand = self::unwrapResult($operand);
            }
            //    If the string is a numeric value, we treat it as a numeric, so no further testing
            if (!is_numeric($operand)) {
                //    If not a numeric, test to see if the value is an Excel error, and so can't be used in normal binary operations
                if ($operand > '' && $operand{0} == '#') {
                    $stack->push('Value', $operand);
                    $this->_debugLog->writeDebugLog('Evaluation Result is ', $this->showTypeDetails($operand));

                    return false;
                } elseif (!Shared\StringHelper::convertToNumberIfFraction($operand)) {
                    //    If not a numeric or a fraction, then it's a text string, and so can't be used in mathematical binary operations
                    $stack->push('Value', '#VALUE!');
                    $this->_debugLog->writeDebugLog('Evaluation Result is a ', $this->showTypeDetails('#VALUE!'));

                    return false;
                }
            }
        }

        //    return a true if the value of the operand is one that we can use in normal binary operations
        return true;
    }

    private function executeBinaryComparisonOperation($cellID, $operand1, $operand2, $operation, &$stack, $recursingArrays = false)
    {
        //    If we're dealing with matrix operations, we want a matrix result
        if ((is_array($operand1)) || (is_array($operand2))) {
            $result = [];
            if ((is_array($operand1)) && (!is_array($operand2))) {
                foreach ($operand1 as $x => $operandData) {
                    $this->_debugLog->writeDebugLog('Evaluating Comparison ', $this->showValue($operandData), ' ', $operation, ' ', $this->showValue($operand2));
                    $this->executeBinaryComparisonOperation($cellID, $operandData, $operand2, $operation, $stack);
                    $r = $stack->pop();
                    $result[$x] = $r['value'];
                }
            } elseif ((!is_array($operand1)) && (is_array($operand2))) {
                foreach ($operand2 as $x => $operandData) {
                    $this->_debugLog->writeDebugLog('Evaluating Comparison ', $this->showValue($operand1), ' ', $operation, ' ', $this->showValue($operandData));
                    $this->executeBinaryComparisonOperation($cellID, $operand1, $operandData, $operation, $stack);
                    $r = $stack->pop();
                    $result[$x] = $r['value'];
                }
            } else {
                if (!$recursingArrays) {
                    self::checkMatrixOperands($operand1, $operand2, 2);
                }
                foreach ($operand1 as $x => $operandData) {
                    $this->_debugLog->writeDebugLog('Evaluating Comparison ', $this->showValue($operandData), ' ', $operation, ' ', $this->showValue($operand2[$x]));
                    $this->executeBinaryComparisonOperation($cellID, $operandData, $operand2[$x], $operation, $stack, true);
                    $r = $stack->pop();
                    $result[$x] = $r['value'];
                }
            }
            //    Log the result details
            $this->_debugLog->writeDebugLog('Comparison Evaluation Result is ', $this->showTypeDetails($result));
            //    And push the result onto the stack
            $stack->push('Array', $result);

            return true;
        }

        //    Simple validate the two operands if they are string values
        if (is_string($operand1) && $operand1 > '' && $operand1{0} == '"') {
            $operand1 = self::unwrapResult($operand1);
        }
        if (is_string($operand2) && $operand2 > '' && $operand2{0} == '"') {
            $operand2 = self::unwrapResult($operand2);
        }

        // Use case insensitive comparaison if not OpenOffice mode
        if (Calculation\Functions::getCompatibilityMode() != Calculation\Functions::COMPATIBILITY_OPENOFFICE) {
            if (is_string($operand1)) {
                $operand1 = strtoupper($operand1);
            }
            if (is_string($operand2)) {
                $operand2 = strtoupper($operand2);
            }
        }

        $useLowercaseFirstComparison = is_string($operand1) && is_string($operand2) && Calculation\Functions::getCompatibilityMode() == Calculation\Functions::COMPATIBILITY_OPENOFFICE;

        //    execute the necessary operation
        switch ($operation) {
            //    Greater than
            case '>':
                if ($useLowercaseFirstComparison) {
                    $result = $this->strcmpLowercaseFirst($operand1, $operand2) > 0;
                } else {
                    $result = ($operand1 > $operand2);
                }
                break;
            //    Less than
            case '<':
                if ($useLowercaseFirstComparison) {
                    $result = $this->strcmpLowercaseFirst($operand1, $operand2) < 0;
                } else {
                    $result = ($operand1 < $operand2);
                }
                break;
            //    Equality
            case '=':
                if (is_numeric($operand1) && is_numeric($operand2)) {
                    $result = (abs($operand1 - $operand2) < $this->delta);
                } else {
                    $result = strcmp($operand1, $operand2) == 0;
                }
                break;
            //    Greater than or equal
            case '>=':
                if (is_numeric($operand1) && is_numeric($operand2)) {
                    $result = ((abs($operand1 - $operand2) < $this->delta) || ($operand1 > $operand2));
                } elseif ($useLowercaseFirstComparison) {
                    $result = $this->strcmpLowercaseFirst($operand1, $operand2) >= 0;
                } else {
                    $result = strcmp($operand1, $operand2) >= 0;
                }
                break;
            //    Less than or equal
            case '<=':
                if (is_numeric($operand1) && is_numeric($operand2)) {
                    $result = ((abs($operand1 - $operand2) < $this->delta) || ($operand1 < $operand2));
                } elseif ($useLowercaseFirstComparison) {
                    $result = $this->strcmpLowercaseFirst($operand1, $operand2) <= 0;
                } else {
                    $result = strcmp($operand1, $operand2) <= 0;
                }
                break;
            //    Inequality
            case '<>':
                if (is_numeric($operand1) && is_numeric($operand2)) {
                    $result = (abs($operand1 - $operand2) > 1E-14);
                } else {
                    $result = strcmp($operand1, $operand2) != 0;
                }
                break;
        }

        //    Log the result details
        $this->_debugLog->writeDebugLog('Evaluation Result is ', $this->showTypeDetails($result));
        //    And push the result onto the stack
        $stack->push('Value', $result);

        return true;
    }

    /**
     * Compare two strings in the same way as strcmp() except that lowercase come before uppercase letters
     * @param    string    $str1    First string value for the comparison
     * @param    string    $str2    Second string value for the comparison
     * @return   int
     */
    private function strcmpLowercaseFirst($str1, $str2)
    {
        $inversedStr1 = Shared\StringHelper::strCaseReverse($str1);
        $inversedStr2 = Shared\StringHelper::strCaseReverse($str2);

        return strcmp($inversedStr1, $inversedStr2);
    }

    /**
     * @param string $matrixFunction
     */
    private function executeNumericBinaryOperation($cellID, $operand1, $operand2, $operation, $matrixFunction, &$stack)
    {
        //    Validate the two operands
        if (!$this->validateBinaryOperand($cellID, $operand1, $stack)) {
            return false;
        }
        if (!$this->validateBinaryOperand($cellID, $operand2, $stack)) {
            return false;
        }

        //    If either of the operands is a matrix, we need to treat them both as matrices
        //        (converting the other operand to a matrix if need be); then perform the required
        //        matrix operation
        if ((is_array($operand1)) || (is_array($operand2))) {
            //    Ensure that both operands are arrays/matrices of the same size
            self::checkMatrixOperands($operand1, $operand2, 2);

            try {
                //    Convert operand 1 from a PHP array to a matrix
                $matrix = new Shared\JAMA\Matrix($operand1);
                //    Perform the required operation against the operand 1 matrix, passing in operand 2
                $matrixResult = $matrix->$matrixFunction($operand2);
                $result = $matrixResult->getArray();
            } catch (Exception $ex) {
                $this->_debugLog->writeDebugLog('JAMA Matrix Exception: ', $ex->getMessage());
                $result = '#VALUE!';
            }
        } else {
            if ((Calculation\Functions::getCompatibilityMode() != Calculation\Functions::COMPATIBILITY_OPENOFFICE) &&
                ((is_string($operand1) && !is_numeric($operand1) && strlen($operand1) > 0) ||
                 (is_string($operand2) && !is_numeric($operand2) && strlen($operand2) > 0))) {
                $result = Calculation\Functions::VALUE();
            } else {
                //    If we're dealing with non-matrix operations, execute the necessary operation
                switch ($operation) {
                    //    Addition
                    case '+':
                        $result = $operand1 + $operand2;
                        break;
                    //    Subtraction
                    case '-':
                        $result = $operand1 - $operand2;
                        break;
                    //    Multiplication
                    case '*':
                        $result = $operand1 * $operand2;
                        break;
                    //    Division
                    case '/':
                        if ($operand2 == 0) {
                            //    Trap for Divide by Zero error
                            $stack->push('Value', '#DIV/0!');
                            $this->_debugLog->writeDebugLog('Evaluation Result is ', $this->showTypeDetails('#DIV/0!'));

                            return false;
                        } else {
                            $result = $operand1 / $operand2;
                        }
                        break;
                    //    Power
                    case '^':
                        $result = pow($operand1, $operand2);
                        break;
                }
            }
        }

        //    Log the result details
        $this->_debugLog->writeDebugLog('Evaluation Result is ', $this->showTypeDetails($result));
        //    And push the result onto the stack
        $stack->push('Value', $result);

        return true;
    }

    // trigger an error, but nicely, if need be
    protected function raiseFormulaError($errorMessage)
    {
        $this->formulaError = $errorMessage;
        $this->cyclicReferenceStack->clear();
        if (!$this->suppressFormulaErrors) {
            throw new Calculation\Exception($errorMessage);
        }
        trigger_error($errorMessage, E_USER_ERROR);
    }

    /**
     * Extract range values
     *
     * @param    string      &$pRange    String based range representation
     * @param    Worksheet   $pSheet        Worksheet
     * @param    bool     $resetLog    Flag indicating whether calculation log should be reset or not
     * @throws   Calculation\Exception
     * @return   mixed       Array of values in range if range contains more than one element. Otherwise, a single value is returned.
     */
    public function extractCellRange(&$pRange = 'A1', Worksheet $pSheet = null, $resetLog = true)
    {
        // Return value
        $returnValue = [];

        if ($pSheet !== null) {
            $pSheetName = $pSheet->getTitle();
            if (strpos($pRange, '!') !== false) {
                list($pSheetName, $pRange) = Worksheet::extractSheetTitle($pRange, true);
                $pSheet = $this->spreadsheet->getSheetByName($pSheetName);
            }

            // Extract range
            $aReferences = Cell::extractAllCellReferencesInRange($pRange);
            $pRange = $pSheetName . '!' . $pRange;
            if (!isset($aReferences[1])) {
                //    Single cell in range
                sscanf($aReferences[0], '%[A-Z]%d', $currentCol, $currentRow);
                $cellValue = null;
                if ($pSheet->cellExists($aReferences[0])) {
                    $returnValue[$currentRow][$currentCol] = $pSheet->getCell($aReferences[0])->getCalculatedValue($resetLog);
                } else {
                    $returnValue[$currentRow][$currentCol] = null;
                }
            } else {
                // Extract cell data for all cells in the range
                foreach ($aReferences as $reference) {
                    // Extract range
                    sscanf($reference, '%[A-Z]%d', $currentCol, $currentRow);
                    $cellValue = null;
                    if ($pSheet->cellExists($reference)) {
                        $returnValue[$currentRow][$currentCol] = $pSheet->getCell($reference)->getCalculatedValue($resetLog);
                    } else {
                        $returnValue[$currentRow][$currentCol] = null;
                    }
                }
            }
        }

        return $returnValue;
    }

    /**
     * Extract range values
     *
     * @param    string       &$pRange    String based range representation
     * @param    Worksheet    $pSheet        Worksheet
     * @param    bool      $resetLog    Flag indicating whether calculation log should be reset or not
     * @throws   Calculation\Exception
     * @return   mixed        Array of values in range if range contains more than one element. Otherwise, a single value is returned.
     */
    public function extractNamedRange(&$pRange = 'A1', Worksheet $pSheet = null, $resetLog = true)
    {
        // Return value
        $returnValue = [];

        if ($pSheet !== null) {
            $pSheetName = $pSheet->getTitle();
            if (strpos($pRange, '!') !== false) {
                list($pSheetName, $pRange) = Worksheet::extractSheetTitle($pRange, true);
                $pSheet = $this->spreadsheet->getSheetByName($pSheetName);
            }

            // Named range?
            $namedRange = NamedRange::resolveRange($pRange, $pSheet);
            if ($namedRange !== null) {
                $pSheet = $namedRange->getWorksheet();
                $pRange = $namedRange->getRange();
                $splitRange = Cell::splitRange($pRange);
                //    Convert row and column references
                if (ctype_alpha($splitRange[0][0])) {
                    $pRange = $splitRange[0][0] . '1:' . $splitRange[0][1] . $namedRange->getWorksheet()->getHighestRow();
                } elseif (ctype_digit($splitRange[0][0])) {
                    $pRange = 'A' . $splitRange[0][0] . ':' . $namedRange->getWorksheet()->getHighestColumn() . $splitRange[0][1];
                }
            } else {
                return Calculation\Functions::REF();
            }

            // Extract range
            $aReferences = Cell::extractAllCellReferencesInRange($pRange);
            if (!isset($aReferences[1])) {
                //    Single cell (or single column or row) in range
                list($currentCol, $currentRow) = Cell::coordinateFromString($aReferences[0]);
                $cellValue = null;
                if ($pSheet->cellExists($aReferences[0])) {
                    $returnValue[$currentRow][$currentCol] = $pSheet->getCell($aReferences[0])->getCalculatedValue($resetLog);
                } else {
                    $returnValue[$currentRow][$currentCol] = null;
                }
            } else {
                // Extract cell data for all cells in the range
                foreach ($aReferences as $reference) {
                    // Extract range
                    list($currentCol, $currentRow) = Cell::coordinateFromString($reference);
                    $cellValue = null;
                    if ($pSheet->cellExists($reference)) {
                        $returnValue[$currentRow][$currentCol] = $pSheet->getCell($reference)->getCalculatedValue($resetLog);
                    } else {
                        $returnValue[$currentRow][$currentCol] = null;
                    }
                }
            }
        }

        return $returnValue;
    }

    /**
     * Is a specific function implemented?
     *
     * @param    string    $pFunction    Function Name
     * @return    bool
     */
    public function isImplemented($pFunction = '')
    {
        $pFunction = strtoupper($pFunction);
        if (isset(self::$phpSpreadsheetFunctions[$pFunction])) {
            return self::$phpSpreadsheetFunctions[$pFunction]['functionCall'] != 'Calculation\Categories::DUMMY';
        } else {
            return false;
        }
    }

    /**
     * Get a list of all implemented functions as an array of function objects
     *
     * @return    array of Calculation\Categories
     */
    public function listFunctions()
    {
        $returnValue = [];

        foreach (self::$phpSpreadsheetFunctions as $functionName => $function) {
            if ($function['functionCall'] != 'Calculation\Categories::DUMMY') {
                $returnValue[$functionName] = new Calculation\Categories(
                    $function['category'],
                    $functionName,
                    $function['functionCall']
                );
            }
        }

        return $returnValue;
    }

    /**
     * Get a list of all Excel function names
     *
     * @return    array
     */
    public function listAllFunctionNames()
    {
        return array_keys(self::$phpSpreadsheetFunctions);
    }

    /**
     * Get a list of implemented Excel function names
     *
     * @return    array
     */
    public function listFunctionNames()
    {
        $returnValue = [];
        foreach (self::$phpSpreadsheetFunctions as $functionName => $function) {
            if ($function['functionCall'] != 'Calculation\Categories::DUMMY') {
                $returnValue[] = $functionName;
            }
        }

        return $returnValue;
    }
}
