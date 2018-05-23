<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Views\Helpers;

/**
 * Responsibility - format number by explicitly given arguments or by default configured arguments.
 * - Formating processed by `Intl` extension if installed or by `\number_format()` and `\localeconv()` fallback.
 * - Possiblity to define default decimal points value to not define it every time using `FormatNumber()` call.
 * - Possiblity to define any argument to create `Intl` number formater instance in every call or globaly by default setters.
 * - Possiblity to define any argument for `number_format()` and `\localeconv()` fallback in every call or globaly by default setters.
 * - If there is used formating fallback and no locale formating conventions are defined, system locale settings is automaticly
 *   configured by request language and request locale and by system locale settings are defined locale formating conventions.
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
 */
class FormatNumber extends \MvcCore\Ext\Views\Helpers\Internationalized
{
	/**
	 * MvcCore Extension - View Helper - Assets - version:
	 * Comparation by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.0.0-alpha';

	/**
	 * If this static property is set - helper is possible
	 * to configure as singleton before it's used for first time.
	 * Example:
	 *	`\MvcCore\Ext\View\Helpers\FormatNumber::GetInstance()`
	 * @var \MvcCore\Ext\Views\Helpers\FormatNumber
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
	 * Array with keys describing number formater constants and with values describing specific values.
	 * @see http://php.net/manual/en/numberformatter.setattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatattribute
	 * @var int[]
	 */
	protected $intlDefaultAttributes = array();

	/**
	 * Default set of text attribute(s) associated with the formatter.
	 * Array with keys describing number formater constants and with values describing specific values.
	 * @see http://php.net/manual/en/numberformatter.settextattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformattextattribute
	 * @var int[]
	 */
	protected $intlDefaultTextAttributes = array();

	/**
	 * System `setlocale()` category to set up system locale automaticly in `parent::SetView()` method.
	 * This property is used only for fallback if formating is not by `Intl` extension.
	 * @var \int[]
	 */
	protected $localeCategories = array(LC_NUMERIC);

	/**
	 * Numeric formatting information by system locale settings.
	 * There are used all keys defined in property `$this->defaultLocaleConventions;`.
	 * This property is used only for fallback if formating is not by `Intl` extension.
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @var \stdClass
	 */
	protected $localeConventions = array();

	/**
	 * Default locale conventions used for `Intl` formating fallback,
	 * when is not possible to configure system locale value
	 * and when there is necessary to define some default formating rules.
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @var string[]
	 */
	protected $defaultLocaleConventions = array(
		'decimal_point'		=> '.',	// numbers decimal point
		'thousands_sep'		=> ',',	// numbers thousands separator
		'mon_decimal_point'	=> '.',	// money decimal point
		'mon_thousands_sep'	=> ',',	// money thousands separator
		'int_curr_symbol'	=> 'USD',// international currency symbol for `Intl` extension
		'currency_symbol'	=> '$',	// text currency symbol for fallback formating
		'frac_digits'		=> 2,	// decimals count
		'positive_sign'		=> '',	// positive sign character
		'negative_sign'		=> '-',	// negative sign character
		'p_cs_precedes'		=> 1,	// 1 - currency before negative value
		'n_cs_precedes'		=> 1,	// 1 - currency before positive value
		'p_sep_by_space'	=> 0,	// 0 - no space between currency and positive value
		'n_sep_by_space'	=> 0,	// 0 - no space between currency and negative value
		'p_sign_posn'		=> 3,	// 3 - sign string immediately before currency
		'n_sign_posn'		=> 3,	// 3 - sign string immediately before currency
	);

	/**
	 * Set default numerics count after decimal point.
	 * @param int $defaultDecimalsCount
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumber
	 */
	public function & SetDefaultDecimalsCount ($defaultDecimalsCount = 2) {
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
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumber
	 */
	public function & SetIntlDefaultStyle ($intlDefaultStyle = 1) {// 1 means `\NumberFormatter::DEFAULT_STYLE`
		$this->intlDefaultStyle = $intlDefaultStyle;
		return $this;
	}

	/**
	 * Set default pattern string if the chosen style requires a pattern.
	 * This setter is used for `Intl` number formatter.
	 * @see http://php.net/manual/en/numberformatter.create.php
	 * @param string $intlDefaultPattern
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumber
	 */
	public function & SetIntlDefaultPattern ($intlDefaultPattern = '') {
		$this->intlDefaultPattern = $intlDefaultPattern;
		return $this;
	}

	/**
	 * Set default set of numeric attribute(s) associated with `Intl` number formatter.
	 * Array with keys describing number formater constants and with values describing specific values.
	 * @see http://php.net/manual/en/numberformatter.setattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatattribute
	 * @param array $intlDefaultAttributes
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumber
	 */
	public function & SetIntlDefaultAttributes ($intlDefaultAttributes = array()) {
		$this->intlDefaultAttributes = $intlDefaultAttributes;
		return $this;
	}

	/**
	 * Set default set of text attribute(s) associated with `Intl` number formatter.
	 * Array with keys describing number formater constants and with values describing specific values.
	 * @see http://php.net/manual/en/numberformatter.settextattribute.php
	 * @see http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformattextattribute
	 * @param array $intlDefaultTextAttributes
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumber
	 */
	public function & SetIntlDefaultTextAttributes ($intlDefaultTextAttributes = array()) {
		$this->intlDefaultTextAttributes = $intlDefaultTextAttributes;
		return $this;
	}

	/**
	 * Set custom number (and money) formatting conventions if you don't want to use
	 * automaticly assigned formating conventions by system locale settings.
	 * You have to define all keys defined in property `$this->defaultLocaleConventions;`.
	 * Use this function only for fallback if formating is not by `Intl` extension.
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @param array $localeConventions Locale specific number formating conventions.
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumber
	 */
	public function & SetLocaleConventions (array $localeConventions = array()) {
		$this->localeConventions = (object) $localeConventions;
		return $this;
	}

	/**
	 * Set default locale conventions used for `Intl` formating fallback,
	 * when is not possible to configure system locale value
	 * and when there is necessary to define some default formating rules.
	 * @see http://php.net/manual/en/function.localeconv.php
	 * @param string[] $defaultLocaleConventions
	 * @return \MvcCore\Ext\Views\Helpers\FormatNumber
	 */
	public function & SetDefaultLocaleConventions ($defaultLocaleConventions = array()) {
		$this->defaultLocaleConventions = (object) $defaultLocaleConventions;
		return $this;
	}

	/**
	 * Format number (first argument) by explicitly given next following arguments
	 * or by default settings configured by it's setters. This function uses two
	 * ways to format numbers:
	 *
	 *	1)	Formating by `Intl` extension - creating `\NumberFormatter` instance
	 *		and calling `format()` function. You can format first argument by
	 *		explicitly given next following arguments to create formater instance.
	 *		If there are no next following arguments, there are used default arguments
	 *		to create formater instance defined by it's helper setters above.
	 *
	 *	2)	Formating fallback by `number_format()` with explicitly given next
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
	 * @param string|NULL		$formaterStyleOrDecimalPoint			1) Optional, `\NumberFormatter` constant to choose proper localized
	 *																	   formater (`\NumberFormatter::DECIMAL`, `\NumberFormatter::PERCENT`,
	 *																	   `\NumberFormatter::SPELLOUT`, `\NumberFormatter::DURATION`...).
	 *																	2) Optional, decimal point separator for `number_format()` fallback.
	 *																	   If `NULL`, there is used system locale settings value and if
	 *																	   there are no locale system settings, there is used dot char - `.`.
	 * @param array|string|NULL	$formaterPatternOrThousandsSeparator	1) Optional, number formater pattern for following style constants:
	 *																	   - `\NumberFormatter::PATTERN_DECIMAL`
	 *																	   - `\NumberFormatter::PATTERN_RULEBASED`
	 *																	2) Optional, thousands separator for `number_format()` fallback.
	 *																	   If `NULL`, there is used system locale settings value and if there
	 *																	   is no system locale settings, there is used comma char - `,`.
	 * @param array|NULL		$formaterAttributes						1) Optional number formater attributes, for example to max./min.
	 *																	   integer digits etc...
	 * @param array|NULL		$formaterTextAttributes					1) Optional number formater text attributes.
	 * @return string
	 */
	public function FormatNumber (
		$number = NULL,
		$decimalsCount = NULL,
		$formaterStyleOrDecimalPoint = NULL ,
		$formaterPatternOrThousandsSeparator = NULL,
		$formaterAttributes = NULL,
		$formaterTextAttributes = NULL
	) {
		$numberIsNumeric = is_numeric($number);
		if (!$numberIsNumeric) return (string) $number;
		$valueToFormat = $numberIsNumeric && is_string($number)
			? floatval($number)
			: $number;
		if ($this->intlExtensionFormating) {
			return $this->formatByIntlNumberFormater(
				$valueToFormat, $decimalsCount,
				$formaterStyleOrDecimalPoint, $formaterPatternOrThousandsSeparator,
				$formaterAttributes, $formaterTextAttributes
			);
		} else {
			return $this->fallbackFormatByNumberFormat(
				$valueToFormat, $decimalsCount,
				$formaterStyleOrDecimalPoint, $formaterPatternOrThousandsSeparator
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
	 * @param int|NULL		$style			`\NumberFormatter` constant to choose proper localized formater.
	 * @param string|NULL	$pattern		Optional pattern for style constants
	 *										`NumberFormatter::PATTERN_DECIMAL` or
	  *										`\NumberFormatter::PATTERN_RULEBASED`.
	 * @param array|NULL	$attributes		Optional formater attributes.
	 * @param array|NULL	$textAttributes	Optional formater text attributes.
	 * @return string
	 */
	protected function formatByIntlNumberFormater (
		$valueToFormat = 0.0,
		$decimalsCount = NULL,
		$style = NULL,
		$pattern = NULL ,
		$attributes = NULL,
		$textAttributes = NULL
	) {
		if ($decimalsCount !== NULL) $decimalsCount = $this->defaultDecimalsCount;
		$attributes = $attributes !== NULL ? $attributes : array() ;
		$attributes[\NumberFormatter::FRACTION_DIGITS] = $decimalsCount;
		$formater = $this->getIntlNumberFormater(
			$this->langAndLocale,
			$style !== NULL
				? $style
				: $this->intlDefaultStyle,
			$pattern !== NULL
				? $pattern
				: $this->intlDefaultPattern,
			$attributes !== NULL
				? $attributes
				: $this->intlDefaultAttributes,
			$textAttributes !== NULL
				? $textAttributes
				: $this->intlDefaultTextAttributes
		);
		return \numfmt_format($formater, $valueToFormat);
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
	protected function & getIntlNumberFormater (
		$langAndLocale = NULL,
		$style = NULL,
		$pattern = NULL,
		$attributes = array(),
		$textAttributes = array()
	) {
		$key = implode('_', array(
			'number',
			serialize(func_get_args())
		));
		if (!isset($this->intlFormaters[$key])) {
			$formater = \numfmt_create(
				$this->langAndLocale, $style, $pattern
			);
			foreach ($attributes as $key => $value)
				\numfmt_set_attribute($formater, $key, $value);
			foreach ($textAttributes as $key => $value)
				\numfmt_set_text_attribute($formater, $key, $value);
			$this->intlFormaters[$key] = & $formater;
		}
		return $this->intlFormaters[$key];
	}

	/**
	 * Format a number with PHP `number_format()` with optionaly given decimals count,
	 * by optionaly given decimal point and by optionaly given thousands separator.
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
		$lc = & $this->localeConventions;
		// decide number to format is possitive or negative
		$negative = $valueToFormat < 0;
		// complete decimals count by given argument or by default fractal digits
		$decimalsCount = $decimalsCount !== NULL
			? $decimalsCount
			: $this->defaultDecimalsCount;
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
		// if formated number is under zero - formating rules will be different
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
			// and currency symbol is always before formated number
			$result = $signSymbol . $result;
		} elseif ($signPosition > 2 && !$currencyBeforeValue) {
			// sign symbol is before/after currency symbol
			// and currency symbol is always after formated number
			$result .= $signSymbol;
		}
		return $this->encode($result);
	}

	/**
	 * Try to set up local conventions by system locale settings
	 * only if there was any success with setting up system locale.
	 * If system locale is not set up properly - use default formating conventions.
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
