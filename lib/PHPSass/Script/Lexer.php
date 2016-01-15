<?php
namespace PHPSass\Script;

/**
 * Lexer class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Lexer class.
 * Lexes Script into tokens for the parser.
 *
 * Implements a {@link http://en.wikipedia.org/wiki/Shunting-yard_algorithm Shunting-yard algorithm} to provide {@link http://en.wikipedia.org/wiki/Reverse_Polish_notation Reverse Polish notation} output.
 */
class Lexer
{
	const MATCH_WHITESPACE='/^\s+/';

	/** @var Lexer Static holder for last instance of Lexer */
	public static $instance;
	/** @var Parser the parser object */
	public $parser;


	/**
	 * @param Parser $parser
	 */
	public function __construct($parser)
	{
		$this->parser=$parser;
		self::$instance=$this;
	}

	/**
	 * Lex an expression into Script tokens.
	 *
	 * @param string $string expression to lex
	 * @param Context $context the context in which the expression is lexed
	 * @return array tokens
	 */
	public function lex($string, $context)
	{
		// if it's already lexed, just return it as-is
		if (is_object($string)) {
			return [$string];
			}
		if (is_array($string)) {
			return $string;
			}
		$tokens=[];
		// whilst the string is not empty, split it into it's tokens.
		while ($string!==FALSE) {
			if (($match=$this->isWhitespace($string))!==FALSE) {
				$tokens[]=NULL;
				}
			elseif (($match=ScriptFunction::isa($string))!==FALSE) {
				preg_match(ScriptFunction::MATCH_FUNC, $match, $matches);
				$args=[];
				foreach (ScriptFunction::extractArgs($matches[ScriptFunction::ARGS], FALSE, $context) as $key => $expression) {
					$args[$key]=$this->parser->evaluate($expression, $context);
					}
				$tokens[]=new ScriptFunction($matches[ScriptFunction::NAME], $args);
				}
			elseif (($match=Literals\Boolean::isa($string))!==FALSE) {
				$tokens[]=new Literals\Boolean($match);
				}
			elseif (($match=Literals\Colour::isa($string))!==FALSE) {
				$tokens[]=new Literals\Colour($match);
				}
			elseif (($match=Literals\Number::isa($string))!==FALSE) {
				$tokens[]=new Literals\Number($match);
				}
			elseif (($match=Literals\SassString::isa($string))!==FALSE) {
				$stringed=new Literals\SassString($match);
				$tokens[]= (!strlen($stringed->quote) && Literals\SassList::isa($string)!==FALSE && !preg_match("/^\-\w+\-\w+$/", $stringed->value))
					? new Literals\SassList($string)
					: $stringed;
				}
			elseif ($string=='()') {
				$match=$string;
				$tokens[]=new Literals\SassList($match);
				}
			elseif (($match=Operation::isa($string))!==FALSE) {
				$tokens[]=new Operation($match);
				}
			elseif (($match=Variable::isa($string))!==FALSE) {
				$tokens[]=new Variable($match);
				}
			else {
				$_string=$string;
				$match='';
				while (strlen($_string) && !$this->isWhitespace($_string)) {
					foreach (Operation::$inStrOperators as $operator) {
						if (substr($_string, 0, strlen($operator))==$operator) {
							break 2;
							}
						}
					$match.=$_string[0];
					$_string=substr($_string, 1);
					}
				$tokens[]=new Literals\SassString($match);
				}
			$string=substr($string, strlen($match));
			}

		return $tokens;
	}

	/**
	 * Returns a value indicating if a token of this type can be matched at the start of the subject string.
	 *
	 * @param string $subject the subject string
	 * @return mixed match at the start of the string or FALSE if no match
	 */
	public function isWhitespace($subject)
	{
		return preg_match(self::MATCH_WHITESPACE, $subject, $matches)
			? $matches[0]
			: FALSE;
	}
}
