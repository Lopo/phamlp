<?php
namespace PHPSass\Script;

/**
 * Parser class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Parser class.
 * Parses Script. Script is lexed into {@link http://en.wikipedia.org/wiki/Reverse_Polish_notation Reverse Polish notation} by the Lexer and
 *  the calculated result returned.
 */
class Parser
{
	const MATCH_INTERPOLATION='/(?<!\\\\)#\{(.*?)\}/';
	const DEFAULT_ENV=0;
	const CSS_RULE=1;
	const CSS_PROPERTY=2;

	/** @var Context Used for error reporting */
	public static $context;
	/** @var Lexer the lexer object */
	public $lexer;
	/** @var Lexer Hold a copy of a parser available to the general public. */
	public static $instance;


	public function __construct()
	{
		$this->lexer=new Lexer($this);
		self::$instance=$this;
	}

	/**
	 * Replace interpolated Script contained in '#{}' with the parsed value.
	 *
	 * @param string $string the text to interpolate
	 * @param Context $context the context in which the string is interpolated
	 * @return string the interpolated text
	 */
	public function interpolate($string, $context)
	{
		$string=str_replace("\\\\", "\x1d", $string);
		for ($i=0, $n=preg_match_all(self::MATCH_INTERPOLATION, $string, $matches); $i<$n; $i++) {
			$var=$this->evaluate($matches[1][$i], $context);

			$var= $var instanceOf Literals\SassString
				? $var->value
				: $var->toString();

			if (preg_match('/^unquote\((["\'])(.*)\1\)$/', $var, $match)) {
				$val=$match[2];
				}
			elseif ($var=='""') {
				$val='';
				}
			elseif (preg_match('/^(["\'])(.*)\1$/', $var, $match)) {
				$val=$match[2];
				}
			else {
				$val=$var;
				}
			$matches[1][$i]=$val;
			}

		return str_replace("\x1d", "\\", str_replace($matches[0], $matches[1], $string));
	}

	/**
	 * Evaluate a Script.
	 *
	 * @param string $expression to parse
	 * @param Context $context the context in which the expression is evaluated
	 * @param int $environment the environment in which the expression is evaluated
	 * @return Literals\Literal parsed value
	 */
	public function evaluate($expression, $context, $environment=self::DEFAULT_ENV)
	{
		self::$context=$context;
		$operands=array();

		$tokens=$this->parse($expression, $context, $environment);

		while (count($tokens)) {
			$token=array_shift($tokens);
			if ($token instanceof ScriptFunction) {
				$perform=$token->perform();
				array_push($operands, $perform);
				}
			elseif ($token instanceof Literals\Literal) {
				if ($token instanceof Literals\SassString) {
					$token=new Literals\SassString($this->interpolate($token->toString(), self::$context));
					}
				array_push($operands, $token);
				}
			else {
				$args=array();
				for ($i=0, $c=$token->operandCount; $i<$c; $i++) {
					$args[]=array_pop($operands);
					}
				array_push($operands, $token->perform($args));
				}
			}

		return self::makeSingular($operands);
	}

	/**
	 * Parse Script to a set of tokens in RPN using the Shunting Yard Algorithm.
	 *
	 * @param string $expression to parse
	 * @param Context $context the context in which the expression is parsed
	 * @param int $environment the environment in which the expression is parsed
	 * @return array tokens in RPN
	 * @throws ParserException
	 */
	public function parse($expression, $context, $environment=self::DEFAULT_ENV)
	{
		$outputQueue= $operatorStack= array();
		$parenthesis=0;

		$tokens=$this->lexer->lex($expression, $context);

		foreach ($tokens as $i => $token) {
			// If two literals/expessions are seperated by whitespace use the concat operator
			if (empty($token)) {
				if (isset($tokens[$i+1])) {
					if ($i>0
						&& (!$tokens[$i-1] instanceof Operation || $tokens[$i-1]->operator===Operation::$operators[')'][0])
						&& (!$tokens[$i+1] instanceof Operation || $tokens[$i+1]->operator===Operation::$operators['('][0])
						) {
						$token=new Operation(Operation::$defaultOperator, $context);
						}
					else {
						continue;
						}
					}
				}
			elseif ($token instanceof Variable) {
				$token=$token->evaluate($context);
				$environment=self::DEFAULT_ENV;
				}

			// If the token is a number or function add it to the output queue.
			if ($token instanceof Literals\Literal || $token instanceof ScriptFunction) {
				if ($environment===self::CSS_PROPERTY && $token instanceof Number && !$parenthesis) {
					$token->inExpression=FALSE;
					}
				array_push($outputQueue, $token);
				}
			// If the token is an operation
			elseif ($token instanceof Operation) {
				// If the token is a left parenthesis push it onto the stack.
				if ($token->operator==Operation::$operators['('][0]) {
					array_push($operatorStack, $token);
					$parenthesis++;
					}
				// If the token is a right parenthesis:
				elseif ($token->operator==Operation::$operators[')'][0]) {
					$parenthesis--;
					while ($c=count($operatorStack)) {
						// If the token at the top of the stack is a left parenthesis
						if ($operatorStack[$c-1]->operator==Operation::$operators['('][0]) {
							// Pop the left parenthesis from the stack, but not onto the output queue.
							array_pop($operatorStack);
							break;
							}
						// else pop the operator off the stack onto the output queue.
						array_push($outputQueue, array_pop($operatorStack));
						}
					// If the stack runs out without finding a left parenthesis
					// there are mismatched parentheses.
					if ($c<=0) {
						array_push($outputQueue, new Literals\SassString(')'));
						break;
						}
					}
				// the token is an operator, o1, so:
				else {
					// while there is an operator, o2, at the top of the stack
					while ($c=count($operatorStack)) {
						$operation=$operatorStack[$c-1];
						// if o2 is left parenthesis, or
						// the o1 has left associativty and greater precedence than o2, or
						// the o1 has right associativity and lower or equal precedence than o2
						if (($operation->operator==Operation::$operators['('][0])
							|| ($token->associativity=='l' && $token->precedence>$operation->precedence)
							|| ($token->associativity=='r' && $token->precedence<=$operation->precedence)
							) {
							break; // stop checking operators
							}
						//pop o2 off the stack and onto the output queue
						array_push($outputQueue, array_pop($operatorStack));
						}
					// push o1 onto the stack
					array_push($operatorStack, $token);
					}
				}
			}

		// When there are no more tokens
		while ($c=count($operatorStack)) { // While there are operators on the stack:
			if ($operatorStack[$c-1]->operator!==Operation::$operators['('][0]) {
				array_push($outputQueue, array_pop($operatorStack));
				}
			else {
				throw new ParserException('Unmatched parentheses', $context->node);
				}
			}

		return $outputQueue;
	}

	/**
	 * Reduces a set down to a singular form
	 *
	 * @param array $operands
	 * @return mixed
	 */
	public static function makeSingular($operands)
	{
		if (count($operands)==1) {
			return $operands[0];
			}

		$result=NULL;
		foreach ($operands as $i => $operand) {
			if (is_object($operand)) {
				if (!$result) {
					$result=$operand;
					continue;
					}
				$result= $result instanceOf Literals\SassString
					? $result->op_concat($operand)
					: $result->op_plus($operand);
				}
			else {
				$string=new Literals\SassString(' ');
				$result= !$result
					? $string
					: $result->op_plus($string);
				}
			}

		return $result
			? $result
			: array_shift($operands);
	}
}
