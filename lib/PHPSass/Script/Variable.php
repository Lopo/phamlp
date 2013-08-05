<?php
namespace PHPSass\Script;

/**
 * Variable class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Variable class.
 */
class Variable
{
	/** Regex for matching and extracting Variables */
	const MATCH='/^(?<!\\\\)(?(?!!important\b)[!\$]([\w-]+))/';

	/** @var string name of variable */
	private $name;


	/**
	 * @param string $value of the Variable type
	 */
	public function __construct($value)
	{
		$this->name=substr($value, 1);
	}

	/**
	 * Returns the Script object for this variable.
	 *
	 * @param \PHPSass\Tree\Context $context of the variable
	 * @return Literals\Literal the Script object for this variable
	 */
	public function evaluate($context)
	{
		return $context->getVariable($this->name);
	}

	/**
	 * Returns a value indicating if a token of this type can be matched at the start of the subject string.
	 *
	 * @param string $subject the subject string
	 * @return mixed match at the start of the string or FALSE if no match
	 */
	public static function isa($subject)
	{
		// we need to do the check as preg_match returns a count of 1 if
		// subject == '!important'; the match being an empty match
		return preg_match(self::MATCH, $subject, $matches)
			? (empty($matches[0])? FALSE : $matches[0])
			: FALSE;
	}
}
