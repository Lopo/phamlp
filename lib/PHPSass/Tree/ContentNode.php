<?php
namespace PHPSass\Tree;

/**
 * ContentNode class file.
 * @author      Richard Lyon
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * ContentNode class.
 * Represents a Content.
 */
class ContentNode
extends Node
{
	const MATCH='/^(@content)(.*)$/i';
	const IDENTIFIER=1;

	/** @var statement to execute and return */
	private $statement;


	/**
	 * @param object $token source token
	 */
	public function __construct($token)
	{
		parent::__construct($token);
		preg_match(self::MATCH, $token->source, $matches);

		if (empty($matches)) {
			return new Boolean(FALSE);
			}
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
		$return=$this;
		$context=new Context($pcontext);

		$children=array();
		foreach ($context->getContent() as $child) {
			$child->parent=$this->parent;
			$ctx=new Context($pcontext->parent);
			$ctx->variables=$pcontext->variables;
			$children=array_merge($children, $child->parse($ctx));
			}

		return $children;
	}

	/**
	 * Contents a value indicating if the token represents this type of node.
	 *
	 * @param object $token
	 * @return bool TRUE if the token represents this type of node, FALSE if not
	 */
	public static function isa($token)
	{
		return $token->source[0]===self::IDENTIFIER;
	}
}
