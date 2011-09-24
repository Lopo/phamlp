<?php
/* SVN FILE: $Id$ */
/**
 * SassReturnNode class file.
 *
 * This file is backport of Lohini version
 *
 * @copyright (c) 2010, 2011 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 * @author Lopo <lopo@lohini.net>
 *
 * @package PHamlP
 * @subpackage Sass.tree
 */

/**
 * SassReturnNode class.
 * Represents Sass return statements.
 * Return statement nodes are chained below the Function statement node.
 */
class ReturnNode extends Node {
	const MATCH = '/^@return\s+(.+)$/i';
	const EXPRESSION = 1;

	/**
	 * @var string expression to evaluate
	 */
	private $expression;


	/**
	 * @param object $token source token
	 */
	public function __construct($token)	{
		parent::__construct($token);
		preg_match(self::MATCH, $token->source, $matches);
		$this->expression = $matches[self::EXPRESSION];
	}

	/**
	 * Parse this node.
	 * @param SassContext $context the context in which this node is parsed
	 * @return array parsed child nodes
	 */
	public function parse($context)	{
//		$this->evaluate($this->expression, $context);
		$this->parseChildren($context); // Parse any warnings
		return array();
	}

	public function getExpression()	{
		return $this->expression;
	}
}

