<?php
namespace PHPSass\Script;

/**
 * Operation class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Operation class.
 * The operation to perform.
 */
class Operation
{
	const MATCH='/^(\(|\)|\+|-|\*|\/|%|<=|>=|<|>|==|!=|=|#{|}|,|and\b|or\b|xor\b|not\b)/';

	/**
	 * @var array map symbols to tokens.
	 * A token is function, associativity, precedence, number of operands
	 */
	public static $operators=[
		'*' => ['times', 'l', 8, 2],
		'/' => ['div', 'l', 8, 2],
		'%' => ['modulo', 'l', 8, 2],
		'+' => ['plus', 'l', 7, 2],
		'-' => ['minus', 'l', 7, 2],
		'<<' => ['shiftl', 'l', 6, 2],
		'>>' => ['shiftr', 'l', 6, 2],
		'<=' => ['lte', 'l', 5, 2],
		'>=' => ['gte', 'l', 5, 2],
		'<' => ['lt', 'l', 5, 2],
		'>' => ['gt', 'l', 5, 2],
		'==' => ['eq', 'l', 4, 2],
		'!=' => ['neq', 'l', 4, 2],
		'and' => ['and', 'l', 3, 2],
		'or' => ['or', 'l', 3, 2],
		'xor' => ['xor', 'l', 3, 2],
		'not' => ['not', 'l', 4, 1], # precedence higher than and.
		'=' => ['assign', 'l', 2, 2],
		')' => ['rparen', 'l', 10],
		'(' => ['lparen', 'l', 10],
		',' => ['comma', 'l', 0, 2],
		'#{' => ['begin_interpolation'],
		'}' => ['end_interpolation'],
		];
	/**
	 * @var array operators with meaning in uquoted strings;
	 * selectors, property names and values
	 */
	public static $inStrOperators=[',', '#{', ')', '('];
	/** @var array default operator token. */
	public static $defaultOperator=['concat', 'l', 0, 2];
	/** @var string operator for this operation */
	private $operator;
	/** @var string associativity of the operator; left or right */
	private $associativity;
	/** @var int precedence of the operator */
	private $precedence;
	/** @var int number of operands required by the operator */
	private $operandCount=0;


	/**
	 * @param mixed $operation string: operator symbol; array: operator token
	 */
	public function __construct($operation)
	{
		if (is_string($operation)) {
			$operation=self::$operators[$operation];
			}
		$this->operator=$operation[0];
		if (isset($operation[1])) {
			$this->associativity=$operation[1];
			$this->precedence=$operation[2];
			$this->operandCount= isset($operation[3])
				? $operation[3]
				: 0;
			}
	}

	/**
	 * Getter function for properties
	 *
	 * @param string $name of property
	 * @return mixed value of the property
	 * @throws OperationException if the property does not exist
	 */
	public function __get($name)
	{
		if (property_exists($this, $name)) {
			return $this->$name;
			}
		throw new OperationException('Unknown property: '.$name, Parser::$context->node);
	}

	/**
	 * Performs this operation.
	 *
	 * @param array $operands for the operation. The operands are Literals
	 * @return Literals\Literal the result of the operation
	 * @throws OperationException if the oprand count is incorrect or the operation is undefined
	 */
	public function perform($operands)
	{
		if (count($operands)!==$this->operandCount) {
			throw new OperationException('Incorrect operand count for '.get_class($operands[0]).'; expected '.$this->operandCount.', received '.count($operands), Parser::$context->node);
			}

		if (!count($operands)) {
			return $operands;
			}

		// fix a bug of unknown origin
		foreach ($operands as $i => $op) {
			if (!is_object($op)) {
				$operands[]=NULL;
				unset($operands[$i]);
				}
			}
		$operands=array_values($operands);

		if (count($operands)>1 && $operands[1]===NULL) {
			$operation='op_unary_'.$this->operator;
			}
		else {
			$operation='op_'.$this->operator;
			if ($this->associativity=='l') {
				$operands=array_reverse($operands);
				}
			}

		if (method_exists($operands[0], $operation)) {
			$op=clone $operands[0];

			return $op->$operation(!empty($operands[1])? $operands[1] : NULL);
			}

		# avoid failures in case of null operands
		$count=count($operands);
		foreach ($operands as $i => $op) {
			if ($op===NULL) {
				$count--;
				}
			}

		if ($count) {
			throw new OperationException('Undefined operation "'.$operation.'" for '.get_class($operands[0]), Parser::$context->node);
			}
	}

	/**
	 * Returns a value indicating if a token of this type can be matched at the start of the subject string.
	 *
	 * @param string $subject the subject string
	 * @return mixed match at the start of the string or FALSE if no match
	 */
	public static function isa($subject)
	{
		# begins with a "/x", almost always a path without quotes.
		if (preg_match('/^\/[^0-9\.\-\s]+/', $subject)) {
			return FALSE;
			}

		return preg_match(self::MATCH, $subject, $matches)
			? trim($matches[1])
			: FALSE;
	}

	/**
	 * Converts the operation back into it's SASS representation
	 *
	 * @return string
	 */
	public function __toString()
	{
		foreach (Operation::$operators as $char => $operator) {
			if ($operator[0]==trim($this->operator)) {
				return $char;
				}
			}
	}
}
