<?php
namespace PHPSass\Script\Literals;

/**
 * Boolean class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Boolean class.
 */
class Boolean
extends Literal
{
	/**@#+
	 * Regex for matching and extracting booleans
	 */
	const MATCH='/^(true|false)\b/';


	/**
	 * @param string value of the boolean type
	 * @throws BooleanException
	 */
	public function __construct($value)
	{
		if (is_bool($value)) {
			$this->value=$value;
			}
		elseif ($value==='true' || $value==='false') {
			$this->value= ($value==='true');
			}
		else {
			throw new BooleanException('Invalid Boolean', \PHPSass\Script\Parser::$context->node);
			}
	}

	/**
	 * Returns the value of this boolean.
	 *
	 * @return bool the value of this boolean
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Returns a string representation of the value.
	 *
	 * @return string string representation of the value.
	 */
	public function toString()
	{
		return $this->getValue()
			? 'true'
			: 'false';
	}

	/**
	 * @return int
	 */
	public function length()
	{
		return 1;
	}

	/**
	 * @param int $i
	 * @return Boolean
	 */
	public function nth($i)
	{
		if ($i==1 && isset($this->value)) {
			return new Boolean($this->value);
			}

		return new Boolean(FALSE);
	}

	/**
	 * Returns a value indicating if a token of this type can be matched at
	 * the start of the subject string.
	 *
	 * @param string the subject string
	 * @return mixed match at the start of the string or FALSE if no match
	 */
	public static function isa($subject)
	{
		return preg_match(self::MATCH, $subject, $matches)
			? $matches[0]
			: FALSE;
	}
}
