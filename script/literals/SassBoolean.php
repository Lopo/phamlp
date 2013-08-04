<?php

/**
 * SassBoolean class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */
require_once 'SassLiteral.php';

/**
 * SassBoolean class.
 */
class SassBoolean
extends SassLiteral
{
	/**@#+
	 * Regex for matching and extracting booleans
	 */
	const MATCH='/^(true|false)\b/';


	/**
	 * @param string value of the boolean type
	 * @throws SassBooleanException
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
			throw new SassBooleanException('Invalid SassBoolean', SassScriptParser::$context->node);
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
	 * @return SassBoolean
	 */
	public function nth($i)
	{
		if ($i==1 && isset($this->value)) {
			return new SassBoolean($this->value);
			}

		return new SassBoolean(FALSE);
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
