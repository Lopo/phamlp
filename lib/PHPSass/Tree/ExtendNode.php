<?php
namespace PHPSass\Tree;

/**
 * ExtendNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * ExtendNode class.
 * Represents a Sass @debug or @warn directive.
 */
class ExtendNode
extends Node
{
	const IDENTIFIER='@';
	const MATCH='/^@extend\s+(.+)/i';
	const VALUE=1;

	/** @var string the directive */
	private $value;


	/**
	 * @param object $token source token
	 */
	public function __construct($token)
	{
		parent::__construct($token);
		preg_match(self::MATCH, $token->source, $matches);
		$this->value=$matches[self::VALUE];
	}

	/**
	 * Parse this node.
	 *
	 * @param Context $context
	 * @return array An empty array
	 */
	public function parse($context)
	{
		# resolve selectors in relation to variables
		# allows extend inside nested loops.
		$this->root->extend($this->value, $this->parent->resolveSelectors($context));

		return array();
	}
}
