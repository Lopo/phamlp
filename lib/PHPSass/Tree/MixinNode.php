<?php
namespace PHPSass\Tree;

/**
 * MixinNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

use PHPSass\Script\ScriptFunction;

/**
 * MixinNode class.
 * Represents a Mixin.
 */
class MixinNode
extends Node
{
	const NODE_IDENTIFIER='+';
	const MATCH='/^(\+|@include\s+)([a-z0-9_-]+)\s*(?:\((.*?)\))?\s*$/i';
	const IDENTIFIER=1;
	const NAME=2;
	const ARGS=3;

	/** @var string name of the mixin */
	private $name;
	/** @var array arguments for the mixin */
	private $args='';


	/**
	 * @param object $token source token
	 * @throws MixinNodeException
	 */
	public function __construct($token)
	{
		parent::__construct($token);
		preg_match(self::MATCH, $token->source, $matches);

		if (!isset($matches[self::NAME])) {
			throw new MixinNodeException('Invalid mixin invocation: ($token->source)', $this);
			}
		$this->name=$matches[self::NAME];
		if (isset($matches[self::ARGS]) && strlen($matches[self::ARGS])) {
			$this->args=$matches[self::ARGS];
			}
	}

	/**
	 * Parse this node.
	 * Set passed arguments and any optional arguments not passed to their
	 * defaults, then render the children of the mixin definition.
	 *
	 * @param Context $pcontext the context in which this node is parsed
	 * @return array the parsed node
	 */
	public function parse($pcontext)
	{
		$mixin=$pcontext->getMixin($this->name);
		$context=new Context($pcontext);
		$context->content=$this->children;
		$argc=count($this->args);
		$count=0;

		$args=ScriptFunction::extractArgs($this->args, FALSE, $context);

		list($arguments)=ScriptFunction::fill_parameters($mixin->args, $args, $context, $this);
		$context->setVariables($arguments);

		$children=array();
		foreach ($mixin->children as $child) {
			$child->parent=$this;
			$children=array_merge($children, $child->parse($context));
			}

		// $context->merge();
		return $children;
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
