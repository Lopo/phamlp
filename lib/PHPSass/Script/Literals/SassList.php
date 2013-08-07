<?php
namespace PHPSass\Script\Literals;

/**
 * SassList class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

use PHPSass\Script\Parser as ScriptParser,
	PHPSass\Tree\Context;

/**
 * SassList class.
 */
class SassList
extends Literal
{
	/** @var string */
	public $separator=' ';


	/**
	 * @param string $value of the boolean type
	 * @param string $separator
	 * @throws SassListException
	 */
	public function __construct($value, $separator='auto')
	{
		if (is_array($value)) {
			$this->value=$value;
			$this->separator= $separator=='auto'
				? ', '
				: $separator;
			}
		elseif ($value=='()') {
			$this->value=array();
			$this->separator= $separator=='auto'
				? ', '
				: $separator;
			}
		elseif (list($list, $separator)=$this->_parse_list($value, $separator, TRUE, ScriptParser::$context)) {
			$this->value=$list;
			$this->separator= $separator==','
				? ', '
				: ' ';
			}
		else {
			throw new SassListException('Invalid SassList', ScriptParser::$context->node);
			}
	}

	/**
	 * @param int $i
	 * @return mixed
	 */
	public function nth($i)
	{
		$i--; # SASS uses 1-offset arrays
		if (isset($this->value[$i])) {
			return $this->value[$i];
			}

		return new Boolean(FALSE);
	}

	/**
	 * @return int
	 */
	public function length()
	{
		return count($this->value);
	}

	/**
	 * @param mixed $other
	 * @param string $separator
	 * @throws ListException
	 */
	public function append($other, $separator=NULL)
	{
		if ($separator) {
			$this->separator=$separator;
			}
		if ($other instanceof SassList) {
			$this->value=array_merge($this->value, $other->value);
			}
		elseif ($other instanceof Literal) {
			$this->value[]=$other;
			}
		else {
			throw new SassListException('Appendation can only occur with literals', ScriptParser::$context->node);
			}
	}

	/**
	 * New function index returns the list index of a value within a list. For example: index(1px solid red, solid) returns 2. When the value is not found false is returned.
	 *
	 * @param type $value
	 * @return mixed Number|Boolean
	 */
	public function index($value)
	{
		for ($i=0; $i<count($this->value); $i++) {
			if (trim((string)$value)==trim((string)$this->value[$i])) {
				return new Number($i);
				}
			}

		return new Boolean(FALSE);
	}

	/**
	 * @return array
	 */
	public function getValue()
	{
		$result=array();
		foreach ($this->value as $k => $v) {
			if ($v instanceOf String) {
				$list=$this->_parse_list($v);
				if (count($list[0])>1) {
					if ($list[1]==$this->separator) {
						$result=array_merge($result, $list[0]);
						}
					else {
						$result[]=$v;
						}
					}
				else {
					$result[]=$v;
					}
				}
			else {
				$result[]=$v;
				}
			}
		$this->value=$result;

		return $this->value;
	}

	/**
	 * Returns a string representation of the value.
	 *
	 * @return string string representation of the value.
	 */
	public function toString()
	{
		$aliases=array(
			'comma' => ',',
			'space' => '',
			);
		$this->separator=trim($this->separator);
		if (isset($aliases[$this->separator])) {
			$this->separator=$aliases[$this->separator];
			}

		return implode($this->separator.' ', $this->getValue());
	}

	/**
	 * Returns a value indicating if a token of this type can be matched at the start of the subject string.
	 *
	 * @param string the subject string
	 * @return mixed match at the start of the string or FALSE if no match
	 */
	public static function isa($subject)
	{
		list($list, $separator)=self::_parse_list($subject, 'auto', FALSE);

		return count($list)>1
			? $subject
			: FALSE;
	}

	/**
	 * @param string $list
	 * @param string $separator
	 * @param bool $lex
	 * @param Context $context
	 * @return array
	 */
	public static function _parse_list($list, $separator='auto', $lex=TRUE, $context=NULL)
	{
		if ($lex) {
			$context=new Context($context);
			$list=ScriptParser::$instance->evaluate($list, $context);
			$list=$list->toString();
			}
		if ($separator=='auto') {
			$separator=',';
			$list=$list=self::_build_list($list, ',');
			if (count($list)<2) {
				$separator=' ';
				$list=self::_build_list($list, ' ');
				}
			}
		else {
			$list=self::_build_list($list, $separator);
			}

		if ($lex) {
			$context=new Context($context);
			foreach ($list as $k => $v) {
				$list[$k]=ScriptParser::$instance->evaluate($v, $context);
				}
			}

		return array($list, $separator);
	}

	/**
	 * @param mixed $list
	 * @param string $separator
	 * @return array
	 */
	public static function _build_list($list, $separator=',')
	{
		if (is_object($list)) {
			$list=$list->value;
			}

		if (is_array($list)) {
			$newlist=array();
			foreach ($list as $listlet) {
				list($newlist, $separator)=array_merge($newlist, self::_parse_list($listlet, $separator, FALSE));
				}
			$list=implode(', ', $newlist);
			}

		$out=array();
		$size= $braces= 0;
		$quotes=FALSE;
		$stack='';
		for ($i=0; $i<strlen($list); $i++) {
			$char=substr($list, $i, 1);
			switch ($char) {
				case '"':
				case "'":
					if (!$quotes) {
						$quotes=$char;
						}
					elseif ($quotes && $quotes==$char) {
						$quotes=FALSE;
						}
					$stack.=$char;
					break;
				case '(':
					$braces++;
					$stack.=$char;
					break;
				case ')':
					$braces--;
					$stack.=$char;
					break;
				case $separator:
					if ($braces===0 && !$quotes) {
						$out[]=$stack;
						$stack='';
						$size++;
						break;
					}
				default:
					$stack.=$char;
				}
			}
		if (strlen($stack)) {
			if (($braces || $quotes) && count($out)) {
				$out[count($out)-1].=$stack;
				}
			else {
				$out[]=$stack;
				}
			}

		return array_map(function ($v) {return trim($v, ', ');}, $out);
	}
}
