<?php
namespace PHPSass\Tree;

/**
 * RootNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * RootNode class.
 * Also the root node of a document.
 */
class RootNode
extends Node
{
	/** @var \PHPSass\Script\Parser Script parser */
	public $script;
	/** @var \PHPSass\Renderers\Renderer the renderer for this node */
	public $renderer;
	/** @var \PHPSass\Parser */
	public $parser;
	/** @var array extenders for this tree in the form extendee=>extender */
	public $extenders=array();
	/** @var Node Extend_parent - for resolving extends across imported files. */
	public $extend_parent=NULL;


	/**
	 * @param \PHPSass\Parser Sass parser
	 */
	public function __construct($parser)
	{
		parent::__construct((object)array(
				'source' => '',
				'level' => -1,
				'filename' => $parser->filename,
				'line' => 0,
				));
		$this->parser=$parser;
		$this->script=new \PHPSass\Script\Parser;
		$this->renderer=\PHPSass\Renderers\Renderer::getRenderer($parser->style);
		$this->root=$this;
	}

	/**
	 * Parses this node and its children into the render tree.
	 * Dynamic nodes are evaluated, files imported, etc.
	 * Only static nodes for rendering are in the resulting tree.
	 *
	 * @param Context $context the context in which this node is parsed
	 * @return Node root node of the render tree
	 */
	public function parse($context)
	{
		$node=clone $this;
		$node->children=$this->parseChildren($context);

		return $node;
	}

	/**
	 * Render this node.
	 *
	 * @param Context $context
	 * @return string the rendered node
	 */
	public function render($context=NULL)
	{
		$context=new Context($context);
		$node=$this->parse($context);
		$output='';
		foreach ($node->children as $child) {
			$output.=$child->render();
			}

		return $output;
	}

	/**
	 * @param string $extendee
	 * @param array $selectors
	 * @return array
	 */
	public function extend($extendee, $selectors)
	{
		if ($this->extend_parent && method_exists($this->extend_parent, 'extend')) {
			return $this->extend_parent->extend($extendee, $selectors);
			}
		$this->extenders[$extendee]= isset($this->extenders[$extendee])
			? array_merge($this->extenders[$extendee], $selectors)
			: $selectors;
	}

	/**
	 * @return array
	 */
	public function getExtenders()
	{
		if ($this->extend_parent && method_exists($this->extend_parent, 'getExtenders')) {
			return $this->extend_parent->getExtenders();
			}

		return $this->extenders;
	}

	/**
	 * Returns a value indicating if the line represents this type of node.
	 * Child classes must override this method.
	 *
	 * @param $line
	 * @throws NodeException if not overriden
	 */
	public static function isa($line)
	{
		throw new NodeException('Child classes must override this method');
	}
}
