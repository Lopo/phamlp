<?php
namespace PHPSass\Tree;

/**
 * EachNode class file.
 * The syntax is:
 * <pre>@each <var> in <list><pre>.
 *
 * <list> is comma+space separated
 * <var> is available to the rest of the script following evaluation
 * and has the value that terminated the loop.
 *
 * @author  Pavol (Lopo) Hluchy <lopo@losys.eu>
 * @copyright  Copyright (c) 2011 Lopo
 * @license  http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */

/**
 * EachNode class.
 * Represents a Sass @each loop.
 */
class EachNode
extends Node
{
	const MATCH='/@each\s+[!\$](.+?)in\s+(.+)$/i';
	const VARIABLE=1;
	const IN=2;

	/** @var string variable name for the loop */
	private $variable;
	/** @var string expression that provides the loop values */
	private $in;


	/**
	 * @param object $token source token
	 * @throws EachNodeException
	 */
	public function __construct($token)
	{
		parent::__construct($token);
		if (!preg_match(self::MATCH, $token->source, $matches)) {
			throw new EachNodeException('Invalid @each directive', $this);
			}
		$this->variable=trim($matches[self::VARIABLE]);
		$this->in=$matches[self::IN];
	}

	/**
	 * Parse this node.
	 *
	 * @param Context $context the context in which this node is parsed
	 * @return array parsed child nodes
	 */
	public function parse($context)
	{
		$children=array();

		if ($this->variable && $this->in) {
			$context=new Context($context);

			list($in, $sep)=\PHPSass\Script\Literals\SassList::_parse_list($this->in, 'auto', TRUE, $context);
			foreach ($in as $var) {
				$context->setVariable($this->variable, $var);
				$children=array_merge($children, $this->parseChildren($context));
				}
			}
		$context->merge();

		return $children;
	}
}
