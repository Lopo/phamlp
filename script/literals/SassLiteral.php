<?php

/**
 * SassLiteral class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */
require_once 'SassLiteralExceptions.php';

/**
 * SassLiteral class.
 * Base class for all Sass literals.
 * Sass data types are extended from this class and these override the operation
 * methods to provide the appropriate semantics.
 */
abstract class SassLiteral
{
	/** @var array maps class names to data types */
	public static $typeOf=array(
		'SassBoolean' => 'bool',
		'SassColour' => 'color',
		'SassNumber' => 'number',
		'SassString' => 'string',
		'SassList' => 'list'
		);
	/** @var mixed value of the literal type */
	public $value;


	/**
	 * @param string $value of the literal type
	 * @param $context
	 */
	public function __construct($value=NULL, $context)
	{
		$this->value=$value;
		$this->context=$context;
	}

	/**
	 * @param string $name of property to get
	 * @return mixed return value of getter function
	 * @throws SassLiteralException
	 */
	public function __get($name)
	{
		$getter='get'.ucfirst($name);
		if (method_exists($this, $getter)) {
			return $this->$getter();
			}
		throw new SassLiteralException('No getter function for '.$name, SassScriptParser::$context->node);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->toString();
	}

	/**
	 * Returns the boolean representation of the value of this
	 *
	 * @return bool the boolean representation of the value of this
	 */
	public function toBoolean()
	{
		return (bool)$this->value || $this->value===NULL;
	}

	/**
	 * Returns the type of this
	 *
	 * @return string the type of this
	 */
	public function getTypeOf()
	{
		return self::$typeOf[get_class($this)];
	}

	/**
	 * Returns the value of this
	 *
	 * @return mixed the value of this
	 * @throws SassLiteralException
	 */
	public function getValue()
	{
		throw new SassLiteralException('Child classes must override this method', SassScriptParser::$context->node);
	}

	/**
	 * @return array
	 */
	public function getChildren()
	{
		return array();
	}

	/**
	 * Adds a child object to this.
	 *
	 * @param SassLiteral $sassLiteral the child object
	 */
	public function addChild($sassLiteral)
	{
		$this->children[]=$sassLiteral;
	}

	/**
	 * SassScript '+' operation.
	 *
	 * @param SassLiteral $sassLiteral value to add
	 * @return SassString the string values of this and other with no seperation
	 */
	public function op_plus($other)
	{
		return new SassString($this->toString().$other->toString());
	}

	/**
	 * SassScript '-' operation.
	 *
	 * @param SassLiteral $other value to subtract
	 * @return SassString the string values of this and other seperated by '-'
	 */
	public function op_minus($other)
	{
		return new SassString($this->toString().'-'.$other->toString());
	}

	/**
	 * SassScript '*' operation.
	 *
	 * @param SassLiteral $other value to multiply by
	 * @return SassString the string values of this and other seperated by '*'
	 */
	public function op_times($other)
	{
		return new SassString($this->toString().'*'.$other->toString());
	}

	/**
	 * SassScript '/' operation.
	 *
	 * @param SassLiteral $other value to divide by
	 * @return SassString the string values of this and other seperated by '/'
	 */
	public function op_div($other)
	{
		return new SassString($this->toString().' / '.$other->toString());
	}

	/**
	 * SassScript '%' operation.
	 *
	 * @param SassLiteral $other value to take the modulus of
	 * @return SassLiteral result
	 * @throws SassLiteralException if modulo not supported for the data type
	 */
	public function op_modulo($other)
	{
		throw new SassLiteralException(get_class($this).' does not support Modulus', SassScriptParser::$context->node);
	}

	/**
	 * Bitwise AND the value of other and this value
	 *
	 * @param string $other value to bitwise AND with
	 * @return string result
	 * @throws SassLiteralException if bitwise AND not supported for the data type
	 */
	public function op_bw_and($other)
	{
		throw new SassLiteralException(get_class($this).' does not support Bitwise AND', SassScriptParser::$context->node);
	}

	/**
	 * Bitwise OR the value of other and this value
	 *
	 * @param SassNumber $other value to bitwise OR with
	 * @return string result
	 * @throws SassLiteralException if bitwise OR not supported for the data type
	 */
	public function op_bw_or($other)
	{
		throw new SassLiteralException(get_class($this).' does not support Bitwise OR', SassScriptParser::$context->node);
	}

	/**
	 * Bitwise XOR the value of other and the value of this
	 *
	 * @param SassNumber $other value to bitwise XOR with
	 * @return string result
	 * @throws SassLiteralException if bitwise XOR not supported for the data type
	 */
	public function op_bw_xor($other)
	{
		throw new SassLiteralException(get_class($this).' does not support Bitwise XOR', SassScriptParser::$context->node);
	}

	/**
	 * Bitwise NOT the value of other and the value of this
	 *
	 * @param SassNumber $other value to bitwise NOT with
	 * @return string result
	 * @throws SassLiteralException if bitwise NOT not supported for the data type
	 */
	public function op_bw_not()
	{
		throw new SassLiteralException(get_class($this).' does not support Bitwise NOT', SassScriptParser::$context->node);
	}

	/**
	 * Shifts the value of this left by the number of bits given in value
	 *
	 * @param SassNumber $other amount to shift left by
	 * @return string result
	 * @throws SassLiteralException if bitwise Shift Left not supported for the data type
	 */
	public function op_shiftl($other)
	{
		throw new SassLiteralException(get_class($this).' does not support Bitwise Shift Left', SassScriptParser::$context->node);
	}

	/**
	 * Shifts the value of this right by the number of bits given in value
	 *
	 * @param SassNumber $other amount to shift right by
	 * @return string result
	 * @throws SassLiteralException if bitwise Shift Right not supported for the data type
	 */
	public function op_shiftr($other)
	{
		throw new SassLiteralException(get_class($this).' does not support Bitwise Shift Right', SassScriptParser::$context->node);
	}

	/**
	 * The SassScript and operation.
	 *
	 * @param SassLiteral $other the value to and with this
	 * @return SassLiteral other if this is boolean TRUE, this if FALSE
	 */
	public function op_and($other)
	{
		return $this->toBoolean()
			? $other
			: $this;
	}

	/**
	 * The SassScript or operation.
	 *
	 * @param SassLiteral $other the value to or with this
	 * @return SassLiteral this if this is boolean TRUE, other if FALSE
	 */
	public function op_or($other)
	{
		return $this->toBoolean()
			? $this
			: $other;
	}

	/**
	 * @param SassLiteral $other
	 * @return SassLiteral
	 */
	public function op_assign($other)
	{
		return $other;
	}

	/**
	 * The SassScript xor operation.
	 *
	 * @param SassLiteral $other the value to xor with this
	 * @return SassBoolean SassBoolean object with the value true if this or other, but not both, are true, false if not
	 */
	public function op_xor($other)
	{
		return new SassBoolean($this->toBoolean() xor $other->toBoolean());
	}

	/**
	 * The SassScript not operation.
	 *
	 * @return SassBoolean SassBoolean object with the value true if the boolean of this is false or false if it is true
	 */
	public function op_not()
	{
		return new SassBoolean(!$this->toBoolean());
	}

	/**
	 * The SassScript > operation.
	 *
	 * @param SassLiteral $other the value to compare to this
	 * @return SassBoolean SassBoolean object with the value true if the values
	 * of this is greater than the value of other, false if it is not
	 */
	public function op_gt($other)
	{
		return new SassBoolean($this->value>$other->value);
	}

	/**
	 * The SassScript >= operation.
	 *
	 * @param SassLiteral $other the value to compare to this
	 * @return SassBoolean SassBoolean object with the value true if the values
	 * of this is greater than or equal to the value of other, false if it is not
	 */
	public function op_gte($other)
	{
		return new SassBoolean($this->value>=$other->value);
	}

	/**
	 * The SassScript < operation.
	 *
	 * @param SassLiteral $other the value to compare to this
	 * @return SassBoolean SassBoolean object with the value true if the values
	 * of this is less than the value of other, false if it is not
	 */
	public function op_lt($other)
	{
		return new SassBoolean($this->value<$other->value);
	}

	/**
	 * The SassScript <= operation.
	 *
	 * @param SassLiteral $other the value to compare to this
	 * @return SassBoolean SassBoolean object with the value true if the values
	 * of this is less than or equal to the value of other, false if it is not
	 */
	public function op_lte($other)
	{
		return new SassBoolean($this->value<=$other->value);
	}

	/**
	 * The SassScript == operation.
	 *
	 * @param SassLiteral $other the value to compare to this
	 * @return SassBoolean SassBoolean object with the value true if this and other are equal, false if they are not
	 */
	public function op_eq($other)
	{
		return new SassBoolean($this==$other);
	}

	/**
	 * The SassScript != operation.
	 *
	 * @param SassLiteral $other the value to compare to this
	 * @return SassBoolean SassBoolean object with the value true if this and other are not equal, false if they are
	 */
	public function op_neq($other)
	{
		return new SassBoolean(!$this->op_eq($other)->toBoolean());
	}

	/**
	 * The SassScript default operation (e.g. $a $b, "foo" "bar").
	 *
	 * @param SassLiteral $other the value to concatenate with a space to this
	 * @return SassString the string values of this and other seperated by " "
	 */
	public function op_concat($other)
	{
		return new SassString($this->toString().' '.$other->toString());
	}

	/**
	 * SassScript ',' operation.
	 *
	 * @param SassLiteral $other the value to concatenate with a comma to this
	 * @return SassString the string values of this and other seperated by ","
	 */
	public function op_comma($other)
	{
		return new SassString($this->toString().', '.$other->toString());
	}

	/**
	 * Asserts that the literal is the expected type
	 *
	 * @param SassLiteral $literal the literal to test
	 * @param string $type expected type
	 * @throws SassScriptFunctionException if value is not the expected type
	 */
	public static function assertType($literal, $type)
	{
		if (!$literal instanceof $type) {
			throw new SassScriptFunctionException(($literal instanceof SassLiteral ? get_class($literal) : 'literal').' must be a '.$type, SassScriptParser::$context->node);
			}
	}

	/**
	 * Asserts that the value of a literal is within the expected range
	 *
	 * @param SassLiteral $literal the literal to test
	 * @param float $min the minimum value
	 * @param float $max the maximum value
	 * @param string $units the units.
	 * @throws SassScriptFunctionException if value is not the expected type
	 */
	public static function assertInRange($literal, $min, $max, $units='')
	{
		if ($literal->value<$min || $literal->value>$max) {
			throw new SassScriptFunctionException($literal->typeOf.' must be between '.$min.$units.' and '.$max.$units.' inclusive', SassScriptParser::$context->node);
			}
	}

	/**
	 * Returns a string representation of the value.
	 *
	 * @return string string representation of the value.
	 */
	abstract public function toString();

	/**
	 * @return string
	 */
	public function render()
	{
		return $this->toString();
	}

	/**
	 * Returns a value indicating if a token of this type can be matched at the start of the subject string.
	 *
	 * @param string $subject the subject string
	 * @return mixed match at the start of the string or FALSE if no match
	 * @throws SassLiteralException
	 */
	public static function isa($subject)
	{
		throw new SassLiteralException('Child classes must override this method');
	}
}
