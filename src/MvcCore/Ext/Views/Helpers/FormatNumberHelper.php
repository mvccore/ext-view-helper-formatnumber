<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Ext\Views\Helpers;

/**
 * Responsibility - format number by explicitly given arguments or by default configured arguments.
 * - Formatting processed by `Intl` extension if installed or by `\number_format()` and `\localeconv()` fallback.
 * - Possibility to define default decimal points value to not define it every time using `FormatNumber()` call.
 * - Possibility to define any argument to create `Intl` number formatter instance in every call or globally by default setters.
 * - Possibility to define any argument for `number_format()` and `\localeconv()` fallback in every call or globally by default setters.
 * - If there is used formatting fallback and no locale formatting conventions are defined, system locale settings is automatically
 *   configured by request language and request locale and by system locale settings are defined locale formatting conventions.
 * - Fallback result string always returned in response encoding, in UTF-8 by default.
 *
 * @see http://php.net/manual/en/numberformatter.create.php
 * @see http://php.net/manual/en/numberformatter.format.php
 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatstyle
 * @see http://www.icu-project.org/apiref/icu4c/classDecimalFormat.html#details
 * @see http://www.icu-project.org/apiref/icu4c/classRuleBasedNumberFormat.html#details
 * @see http://php.net/manual/en/numberformatter.setattribute.php
 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatattribute
 * @see http://php.net/manual/en/numberformatter.settextattribute.php
 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformattextattribute
 * @see http://php.net/manual/en/function.number-format.php
 * @see http://php.net/manual/en/function.localeconv.php
 * @method static \MvcCore\Ext\Views\Helpers\FormatNumberHelper GetInstance()
 */
class FormatNumberHelper extends \MvcCore\Ext\Views\Helpers\InternationalizedHelper {

	/**
	 * MvcCore Extension - View Helper - Assets - version:
	 * Comparison by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.3.0';

	/**
	 * If this static property is set - helper is possible
	 * to configure as singleton before it's used for first time.
	 * Example:
	 *	`\MvcCore\Ext\View\Helpers\FormatNumber::GetInstance()`
	 * @var \MvcCore\Ext\Views\Helpers\FormatNumberHelper
	 */
	protected static $instance;

	/**
	 * Default numerics count after decimal point.
	 * @var int|NULL
	 */
	protected $defaultDecimalsCount = 2;

	/**
	 * Default style of the formatting, one of the format style constants in second link.
	 * If `\NumberFormatter::PATTERN_DECIMAL` or `\NumberFormatter::PATTERN_RULEBASED`
	 * is passed then the number format is opened using the given pattern, which must
	 * conform to the syntax described in » ICU DecimalFormat documentation or
	 * » ICU RuleBasedNumberFormat documentation, respectively.
	 * @see http://php.net/manual/en/numberformatter.create.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatstyle
	 * @see http://www.icu-project.org/apiref/icu4c/classDecimalFormat.html#details
	 * @see http://www.icu-project.org/apiref/icu4c/classRuleBasedNumberFormat.html#details
	 * @var int
	 */
	protected $intlDefaultStyle = 1; // 1 means `\NumberFormatter::DEFAULT_STYLE`

	/**
	 * Default pattern string if the chosen style requires a pattern.
	 * @see http://php.net/manual/en/numberformatter.create.php
	 * @var string|NULL
	 */
	protected $intlDefaultPattern = NULL;

	/**
	 * Default set of numeric attribute(s) associated with the formatter.
	 * Array with keys describing number formatter constants and with values describing specific values.
	 * @see http://php.net/manual/en/numberformatter.setattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatattribute
	 * @var int[]
	 */
	protected $intlDefaultAttributes = [];

	/**
	 * Default set of text attribute(s) associated with the formatter.
	 * Array with keys describing number formatter constants and with values describing specific values.
	 * @see http://php.net/manual/en/numberformatter.settextattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformattextattribute
	 * @var int[]
	 */
	protected $intlDefaultTextAttributes = [];

	/**
	 * System `setlocale()` category to set up system locale automatically in `parent::SetView()` method.
	 * This property is used only for fallback if formatting is not by `Intl` extension.
	 * @var \int[]
	 */
	protected $localeCategories = [LC_NUMERIC];

	/**
	 * Numeric formatting information by system locale settings.
	 * There are used all keys defined in property `$this->defaultLocaleConventions;`.
	 * This property is used only for fallback if formatting is not by `Intl` extension.
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @var \stdClass|NULL
	 */
	protected $localeConventions = NULL;

	/**
	 * Default locale conventions used for `Intl` formatting fallback,
	 * when is not possible to configure system locale value
	 * and when there is necessary to define some default formatting rules.
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @var array|\stdClass
	 */
	protected $defaultLocaleConventions = [
		'decimal_point'		=> '.',	// numbers decimal point
		'thousands_sep'		=> ',',	// numbers thousands separator
		'mon_decimal_point'	=> '.',	// money decimal point
		'mon_thousands_sep'	=> ',',	// money thousands separator
		'int_curr_symbol'	=> 'USD',// international currency symbol for `Intl` extension
		'currency_symbol'	=> '$',	// text currency symbol for fallback formatting
		'frac_digits'		=> 2,	// decimals count
		'positive_sign'		=> '',	// positive sign character
		'negative_sign'		=> '-',	// negative sign character
		'p_cs_precedes'		=> 1,	// 1 - currency before negative value
		'n_cs_precedes'		=> 1,	// 1 - currency before positive value
		'p_sep_by_space'	=> 0,	// 0 - no space between currency and positive value
		'n_sep_by_space'	=> 0,	// 0 - no space between currency and negative value
		'p_sign_posn'		=> 3,	// 3 - sign string immediately before currency
		'n_sign_posn'		=> 3,	// 3 - sign string immediately before currency
	];

	/**
	 * Set default numerics count after decimal point.
	 * @param int $defaultDecimalsCount
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumberHelper
	 */
	public function SetDefaultDecimalsCount ($defaultDecimalsCount = 2) {
		$this->defaultDecimalsCount = $defaultDecimalsCount;
		return $this;
	}

	/**
	 * Set default style of the formatting, one of the format style constants if second link.
	 * If `\NumberFormatter::PATTERN_DECIMAL` or `\NumberFormatter::PATTERN_RULEBASED`
	 * is passed then the number format is opened using the given pattern, which must
	 * conform to the syntax described in » ICU DecimalFormat documentation or
	 * » ICU RuleBasedNumberFormat documentation, respectively.
	 * This setter is used for `Intl` number formatter.
	 * @see http://php.net/manual/en/numberformatter.create.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatstyle
	 * @see http://www.icu-project.org/apiref/icu4c/classDecimalFormat.html#details
	 * @see http://www.icu-project.org/apiref/icu4c/classRuleBasedNumberFormat.html#details
	 * @param int $intlDefaultStyle
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumberHelper
	 */
	public function SetIntlDefaultStyle ($intlDefaultStyle = 1) {// 1 means `\NumberFormatter::DEFAULT_STYLE`
		$this->intlDefaultStyle = $intlDefaultStyle;
		return $this;
	}

	/**
	 * Set default pattern string if the chosen style requires a pattern.
	 * This setter is used for `Intl` number formatter.
	 * @see http://php.net/manual/en/numberformatter.create.php
	 * @param string $intlDefaultPattern
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumberHelper
	 */
	public function SetIntlDefaultPattern ($intlDefaultPattern = '') {
		$this->intlDefaultPattern = $intlDefaultPattern;
		return $this;
	}

	/**
	 * Set default set of numeric attribute(s) associated with `Intl` number formatter.
	 * Array with keys describing number formatter constants and with values describing specific values.
	 * @see http://php.net/manual/en/numberformatter.setattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatattribute
	 * @param array $intlDefaultAttributes
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumberHelper
	 */
	public function SetIntlDefaultAttributes ($intlDefaultAttributes = []) {
		$this->intlDefaultAttributes = $intlDefaultAttributes;
		return $this;
	}

	/**
	 * Set default set of text attribute(s) associated with `Intl` number formatter.
	 * Array with keys describing number formatter constants and with values describing specific values.
	 * @see http://php.net/manual/en/numberformatter.settextattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformattextattribute
	 * @param array $intlDefaultTextAttributes
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumberHelper
	 */
	public function SetIntlDefaultTextAttributes ($intlDefaultTextAttributes = []) {
		$this->intlDefaultTextAttributes = $intlDefaultTextAttributes;
		return $this;
	}

	/**
	 * Set custom number (and money) formatting conventions if you don't want to use
	 * automatically assigned formatting conventions by system locale settings.
	 * You have to define all keys defined in property `$this->defaultLocaleConventions;`.
	 * Use this function only for fallback if formatting is not by `Intl` extension.
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @param array $localeConventions Locale specific number formatting conventions.
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumberHelper
	 */
	public function SetLocaleConventions (array $localeConventions = []) {
		$this->localeConventions = (object) $localeConventions;
		return $this;
	}

	/**
	 * Set default locale conventions used for `Intl` formatting fallback,
	 * when is not possible to configure system locale value
	 * and when there is necessary to define some default formatting rules.
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @param string[] $defaultLocaleConventions
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumberHelper
	 */
	public function SetDefaultLocaleConventions ($defaultLocaleConventions = []) {
		$this->defaultLocaleConventions = (object) $defaultLocaleConventions;
		return $this;
	}

	/**
	 * Format number (first argument) by explicitly given next following arguments
	 * or by default settings configured by it's setters. This function uses two
	 * ways to format numbers:
	 *
	 *	1)	Formatting by `Intl` extension - creating `\NumberFormatter` instance
	 *		and calling `format()` function. You can format first argument by
	 *		explicitly given next following arguments to create formatter instance.
	 *		If there are no next following arguments, there are used default arguments
	 *		to create formatter instance defined by it's helper setters above.
	 *
	 *	2)	Formatting fallback by `number_format()` with explicitly given next
	 *		following arguments to specify decimals count, decimal point and thousands separator.
	 *		If there are no values for decimal point and thousands separator, there is
	 *		used values from protected $this->localeConventions array, which should be defined
	 *		by it's setter method. And if this array is not defined, there is used
	 *		format conventions by system locale settings by request object language and locale.
	 *		This method is used as fallback for `Intl` extension.
	 *
	 * @see http://php.net/manual/en/numberformatter.create.php
	 * @see http://php.net/manual/en/numberformatter.format.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatstyle
	 * @see http://www.icu-project.org/apiref/icu4c/classDecimalFormat.html#details
	 * @see http://www.icu-project.org/apiref/icu4c/classRuleBasedNumberFormat.html#details
	 * @see http://php.net/manual/en/numberformatter.setattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatattribute
	 * @see http://php.net/manual/en/numberformatter.settextattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformattextattribute
	 * @see http://php.net/manual/en/function.number-format.php
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @param int|float|string	$number									The number being formatted.
	 * @param int|NULL			$decimalsCount							Optional, numerics count after decimal point. If `NULL`,
	 *																	there is used configurable property `$this->defaultDecimalsCount`.
	 * @param string|NULL		$formatterStyleOrDecimalPoint			1) Optional, `\NumberFormatter` constant to choose proper localized
	 *																	   formatter (`\NumberFormatter::DECIMAL`, `\NumberFormatter::PERCENT`,
	 *																	   `\NumberFormatter::SPELLOUT`, `\NumberFormatter::DURATION`...).
	 *																	2) Optional, decimal point separator for `number_format()` fallback.
	 *																	   If `NULL`, there is used system locale settings value and if
	 *																	   there are no locale system settings, there is used dot char - `.`.
	 * @param array|string|NULL	$formatterPatternOrThousandsSeparator	1) Optional, number formatter pattern for following style constants:
	 *																	   - `\NumberFormatter::PATTERN_DECIMAL`
	 *																	   - `\NumberFormatter::PATTERN_RULEBASED`
	 *																	2) Optional, thousands separator for `number_format()` fallback.
	 *																	   If `NULL`, there is used system locale settings value and if there
	 *																	   is no system locale settings, there is used comma char - `,`.
	 * @param array|NULL		$formatterAttributes						1) Optional number formatter attributes, for example to max./min.
	 *																	   integer digits etc...
	 * @param array|NULL		$formatterTextAttributes				1) Optional number formatter text attributes.
	 * @return string
	 */
	public function FormatNumber (
		$number = NULL,
		$decimalsCount = NULL,
		$formatterStyleOrDecimalPoint = NULL ,
		$formatterPatternOrThousandsSeparator = NULL,
		$formatterAttributes = NULL,
		$formatterTextAttributes = NULL
	) {
		$numberIsNumeric = is_numeric($number);
		if (!$numberIsNumeric) return (string) $number;
		$valueToFormat = $numberIsNumeric && is_string($number)
			? floatval($number)
			: $number;
		if ($this->intlExtensionFormatting) {
			return $this->formatByIntlNumberFormatter(
				$valueToFormat, $decimalsCount,
				$formatterStyleOrDecimalPoint, $formatterPatternOrThousandsSeparator,
				$formatterAttributes, $formatterTextAttributes
			);
		} else {
			return $this->fallbackFormatByNumberFormat(
				$valueToFormat, $decimalsCount,
				$formatterStyleOrDecimalPoint, $formatterPatternOrThousandsSeparator
			);
		}
	}

	/**
	 * Format number by `numfmt_format()` (PHP `Intl` extension) with explicitly given
	 * `$style`, `$pattern`, `$attributes` or `$textAttributes`. If there are no explicitly given
	 * arguments, there are used default values configured by it's setter methods above.
	 * @see http://php.net/manual/en/numberformatter.create.php
	 * @see http://php.net/manual/en/numberformatter.format.php
	 * @param int|float		$valueToFormat	Numeric value to format.
	 * @param int|NULL		$decimalsCount	Optional, numerics count after decimal point. If `NULL`,
	 *										there is used configurable property `$this->defaultDecimalsCount`.
	 * @param int|NULL		$style			`\NumberFormatter` constant to choose proper localized formatter.
	 * @param string|NULL	$pattern		Optional pattern for style constants
	 *										`NumberFormatter::PATTERN_DECIMAL` or
	  *										`\NumberFormatter::PATTERN_RULEBASED`.
	 * @param array|NULL	$attributes		Optional formatter attributes.
	 * @param array|NULL	$textAttributes	Optional formatter text attributes.
	 * @return string
	 */
	protected function formatByIntlNumberFormatter (
		$valueToFormat = 0.0,
		$decimalsCount = NULL,
		$style = NULL,
		$pattern = NULL ,
		$attributes = NULL,
		$textAttributes = NULL
	) {
		if ($decimalsCount === NULL) $decimalsCount = $this->defaultDecimalsCount;
		$attributes = is_array($attributes) 
			? $attributes 
			: $this->intlDefaultAttributes ;
		if (!array_key_exists(\NumberFormatter::MIN_FRACTION_DIGITS, $attributes))
			$attributes[\NumberFormatter::MIN_FRACTION_DIGITS] = $decimalsCount;
		if (!array_key_exists(\NumberFormatter::MAX_FRACTION_DIGITS, $attributes))
			$attributes[\NumberFormatter::MAX_FRACTION_DIGITS] = $decimalsCount;
		$formatter = $this->getIntlNumberFormatter(
			$this->langAndLocale != NULL 
				? $this->langAndLocale
				: $this->defaultLangAndLocale,
			$style !== NULL
				? $style
				: $this->intlDefaultStyle,
			$pattern !== NULL
				? $pattern
				: $this->intlDefaultPattern,
			$attributes,
			$textAttributes !== NULL
				? $textAttributes
				: $this->intlDefaultTextAttributes
		);
		return \numfmt_format($formatter, $valueToFormat);
	}

	/**
	 * Get stored `\NumberFormatter` instance or create new one.
	 * @param string|NULL	$langAndLocale
	 * @param int|NULL		$style
	 * @param int|NULL		$pattern
	 * @param array			$attributes
	 * @param array			$textAttributes
	 * @return \NumberFormatter
	 */
	protected function getIntlNumberFormatter (
		$langAndLocale = NULL,
		$style = NULL,
		$pattern = NULL,
		$attributes = [],
		$textAttributes = []
	) {
		$key = implode('_', [
			'number',
			serialize(func_get_args())
		]);
		if (!isset($this->intlFormatters[$key])) {
			$formatter = \numfmt_create(
				$this->langAndLocale, $style, $pattern
			);
			foreach ($attributes as $key => $value)
				\numfmt_set_attribute($formatter, $key, $value);
			foreach ($textAttributes as $key => $value)
				\numfmt_set_text_attribute($formatter, $key, $value);
			$this->intlFormatters[$key] = $formatter;
		}
		return $this->intlFormatters[$key];
	}

	/**
	 * Format a number with PHP `number_format()` with optionally given decimals count,
	 * by optionally given decimal point and by optionally given thousands separator.
	 * If there are no values for decimal point and thousands separator, there is
	 * used values from protected $this->localeConventions array, which should be defined
	 * by it's setter method. And if this array is not defined, there is used
	 * format conventions by system locale settings by request object language and locale.
	 * This method is used as fallback for `Intl` extension.
	 * @see http://php.net/manual/en/function.number-format.php
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @param int|float		$valueToFormat		The number being formatted.
	 * @param int|NULL		$decimalsCount		Optional, numerics count after decimal point,
	 *											If `NULL`, there is used configurable property
	  *											`$this->defaultDecimalsCount`.
	 * @param string|NULL	$decimalPoint		Optional, separator for the decimal point.
	 *											If `NULL`, there is used system locale settings.
	 * @param string|NULL	$thousandsSeparator	Optional, thousands separator. If `NULL`,
	 *											there is used system locale settings.
	 * @return string
	 */
	protected function fallbackFormatByNumberFormat (
		$valueToFormat = 0.0,
		$decimalsCount = NULL,
		$decimalPoint = NULL,
		$thousandsSeparator = NULL
	) {
		if ($this->encodingConversion === NULL) {
			$this->setUpSystemLocaleAndEncodings();
			$this->setUpLocaleConventions();
		}
		$lc = $this->localeConventions;
		// decide number to format is positive or negative
		$negative = $valueToFormat < 0;
		// complete decimals count by given argument or by default fractal digits
		if ($decimalsCount === NULL) $decimalsCount = $this->defaultDecimalsCount;
		// complete decimals point by given argument or by locale conventions
		$decimalPoint = $decimalPoint !== NULL
			? $decimalPoint
			: (isset($lc->decimal_point) ? $lc->decimal_point : '.');
		// complete thousands separator by given argument or by locale conventions
		$thousandsSeparator = $thousandsSeparator !== NULL
			? $thousandsSeparator
			: (isset($lc->thousands_sep) ? $lc->thousands_sep : ',');
		// format absolute value by classic PHPs `number_format()`
		$result = \number_format(
			abs($valueToFormat), $decimalsCount,
			$decimalPoint, $thousandsSeparator
		);
		// if formatted number is under zero - formatting rules will be different
		if ($negative) {
			$currencyBeforeValue  = $lc->n_cs_precedes;
			$signPosition    = $lc->n_sign_posn;
			$signSymbol  = $lc->negative_sign;
		} else {
			$currencyBeforeValue  = $lc->p_cs_precedes;
			$signPosition    = $lc->p_sign_posn;
			$signSymbol  = $lc->positive_sign;
		}
		// add sign character
		if ($signPosition == 0) {
			// negative value by brackets
			$result = "($result)";
		} elseif ($signPosition == 1) {
			// sign symbol is before number and currency
			$result = $signSymbol . $result;
		} elseif ($signPosition == 2) {
			// sign symbol is after number and currency
			$result .= $signSymbol;
		} elseif ($signPosition > 2 && $currencyBeforeValue) {
			// sign symbol is before/after currency symbol
			// and currency symbol is always before formatted number
			$result = $signSymbol . $result;
		} elseif ($signPosition > 2 && !$currencyBeforeValue) {
			// sign symbol is before/after currency symbol
			// and currency symbol is always after formatted number
			$result .= $signSymbol;
		}
		return $this->encode($result);
	}

	/**
	 * Try to set up local conventions by system locale settings
	 * only if there was any success with setting up system locale.
	 * If system locale is not set up properly - use default formatting conventions.
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @return void
	 */
	protected function setUpLocaleConventions () {
		$this->localeConventions = NULL;
		if ($this->systemEncoding !== NULL) {
			$this->localeConventions = (object) localeconv();
		}
		if (!$this->localeConventions || $this->localeConventions->frac_digits == 127)
			// something wrong - use default values for en_US:-(
			$this->localeConventions = (object) $this->defaultLocaleConventions;
		// remove all bracket shit rulez, from `($123,456.789)` to `-$123,456.789`
		if (!$this->localeConventions->n_sign_posn)
			$this->localeConventions->n_sign_posn = 3;
	}
}
