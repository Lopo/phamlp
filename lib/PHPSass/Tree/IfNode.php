<?php
namespace PHPSass\Tree;

/**
 * IfNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * IfNode class.
 * Represents Sass If, Else If and Else statements.
 * Else If and Else statement nodes are chained below the If statement node.
 */
class IfNode
extends Node
{
	const MATCH_IF='/^@if\s*(.+)$/i';
	const MATCH_ELSE='/@else(\s*if\s*(.+))?/i';
	const IF_EXPRESSION=1;
	const ELSE_IF=1;
	const ELSE_EXPRESSION=2;

	/** @var IfNode the next else node. */
	private $else;
	/** @var string expression to evaluate */
	private $expression;


	/**
	 * @param object $token source token
	 * @param bool $if TRUE for an "if" node, FALSE for an "else if | else" node
	 */
	public function __construct($token, $if=TRUE)
	{
		parent::__construct($token);
		if ($if) {
			preg_match(self::MATCH_IF, $token->source, $matches);
			$this->expression=$matches[IfNode::IF_EXPRESSION];
			}
		else {
			preg_match(self::MATCH_ELSE, $token->source, $matches);
			$this->expression= sizeof($matches)==1
				? NULL
				: $matches[IfNode::ELSE_EXPRESSION];
			}
	}

	/**
	 * Adds an "else" statement to this node.
	 *
	 * @param IfNode $node "else" statement node to add
	 * @return IfNode this node
	 */
	public function addElse($node)
	{
		if ($this->else===NULL) {
			$node->parent=$this;
			$node->root=$this->root;
			$this->else=$node;
			}
		else {
			$this->else->addElse($node);
			}

		return $this;
	}

	/**
	 * Parse this node.
	 *
	 * @param Context $context the context in which this node is parsed
	 * @return array parsed child nodes
	 */
	public function parse($context)
	{
		if ($this->isElse() || $this->evaluate($this->expression, $context)->toBoolean()) {
			return $this->parseChildren($context);
			}
		if (!empty($this->else)) {
			return $this->else->parse($context);
			}
		return [];
	}

	/**
	 * Returns a value indicating if this node is an "else" node.
	 *
	 * @return TRUE if this node is an "else" node, FALSE if this node is an "if" or "else if" node
	 */
	private function isElse()
	{
		return $this->expression=='';
	}
}
