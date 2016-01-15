<?php
namespace PHPSass\Tree;

/**
 * WarnNode class file.
 * @author      Richard Lyon <richthegeek@gmail.com>
 * @copyright   none
 * @license     http://phamlp.googlecode.com/files/license.txt
 */

use PHPSass\Script\Literals,
	PHPSass\Parser;

/**
 * WarnNode class.
 * Represents a Warning.
 */
class WarnNode
extends Node
{
	const NODE_IDENTIFIER='+';
	const MATCH='/^(@warn\s+)(["\']?)(.*?)(["\']?)$/i';
	const IDENTIFIER=1;
	const STATEMENT=3;

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
			return new Literals\Boolean(FALSE);
			}

		$this->statement=$matches[self::STATEMENT];
	}

	/**
	 * Parse this node.
	 * Set passed arguments and any optional arguments not passed to their
	 * defaults, then render the children of the return definition.
	 *
	 * @param Context $pcontext the context in which this node is parsed
	 * @return array the parsed node
	 */
	public function parse($pcontext)
	{
		$context=new Context($pcontext);
		$statement=$this->statement;

		try {
			$statement=$this->evaluate($this->statement, $context)->toString();
			}
		catch (\Exception $e) {
			}

		if (Parser::$instance->options['callbacks']['warn']) {
			call_user_func(Parser::$instance->options['callbacks']['warn'], $statement, $context);
			}

		if (Parser::$instance->getQuiet()) {
			return [new Literals\SassString('')];
			}
		return [new Literals\SassString('/* @warn: '.str_replace('*/', '', $statement).' */')];
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
