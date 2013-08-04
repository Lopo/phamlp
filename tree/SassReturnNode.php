<?php

/**
 * SassReturnNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * SassReturnNode class.
 * Represents a Return.
 */
class SassReturnNode
extends SassNode
{
	const NODE_IDENTIFIER='+';
	const MATCH='/^(@return\s+)(.*)$/i';
	const IDENTIFIER=1;
	const STATEMENT=2;

	/** @var string statement to execute and return */
	private $statement;


	/**
	 * @param object $token source token
	 */
	public function __construct($token)
	{
		parent::__construct($token);
		preg_match(self::MATCH, $token->source, $matches);

		if (empty($matches)) {
			return new SassBoolean(FALSE);
			}

		$this->statement=$matches[self::STATEMENT];
	}

	/**
	 * Parse this node.
	 * Set passed arguments and any optional arguments not passed to their
	 * defaults, then render the children of the return definition.
	 *
	 * @param SassContext $pcontext the context in which this node is parsed
	 * @throws SassReturn
	 */
	public function parse($pcontext)
	{
		$return=$this;
		$context=new SassContext($pcontext);
		$statement=$this->statement;

		$parent=$this->parent->parent->parser;
		$script=$this->parent->parent->script;
		$lexer=$script->lexer;

		$result=$script->evaluate($statement, $context);

		throw new SassReturn($result);
	}

	/**
	 * Returns a value indicating if the token represents this type of node.
	 *
	 * @param object $token
	 * @return bool TRUE if the token represents this type of node, FALSE if not
	 */
	public static function isa($token)
	{
		return $token->source[0]===self::NODE_IDENTIFIER;
	}

}


class SassReturn
extends Exception
{
	/**
	 * @param string $value
	 */
	public function __construct($value)
	{
		$this->value=$value;
	}
}
