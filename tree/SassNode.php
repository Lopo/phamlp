<?php

/**
 * SassNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */
require_once 'SassContext.php';
require_once 'SassCommentNode.php';
require_once 'SassDebugNode.php';
require_once 'SassDirectiveNode.php';
require_once 'SassImportNode.php';
require_once 'SassMixinNode.php';
require_once 'SassMixinDefinitionNode.php';
require_once 'SassPropertyNode.php';
require_once 'SassRootNode.php';
require_once 'SassRuleNode.php';
require_once 'SassVariableNode.php';
require_once 'SassExtendNode.php';
require_once 'SassEachNode.php';
require_once 'SassForNode.php';
require_once 'SassIfNode.php';
require_once 'SassElseNode.php';
require_once 'SassWhileNode.php';
require_once 'SassNodeExceptions.php';

require_once 'SassFunctionDefinitionNode.php';
require_once 'SassReturnNode.php';
require_once 'SassContentNode.php';
require_once 'SassWarnNode.php';
require_once 'SassMediaNode.php';

/**
 * SassNode class.
 * Base class for all Sass nodes.
 */
class SassNode
{
	/** @var SassNode parent of this node */
	public $parent;
	/** @var SassNode root node */
	public $root;
	/** @var array children of this node */
	public $children=array();
	/** @var object source token */
	public $token;


	/**
	 * @param object $token source token
	 */
	public function __construct($token)
	{
		$this->token=$token;
	}

	/**
	 * @param string $name of property to get
	 * @return mixed return value of getter function
	 * @throws SassNodeException
	 */
	public function __get($name)
	{
		$getter='get'.ucfirst($name);
		if (method_exists($this, $getter)) {
			return $this->$getter();
			}
		throw new SassNodeException('No getter function for '.$name, $this);
	}

	/**
	 * @param string $name of property to set
	 * @return mixed $value of property
	 * @return SassNode this node
	 * @throws SassNodeException
	 */
	public function __set($name, $value)
	{
		$setter='set'.ucfirst($name);
		if (method_exists($this, $setter)) {
			$this->$setter($value);

			return $this;
			}
		throw new SassNodeException('No setter function for '.$name, $this);
	}

	/**
	 * Resets children when cloned
	 * @see parse
	 */
	public function __clone()
	{
		$this->children=array();
	}

	/**
	 * Return a value indicating if this node has a parent
	 *
	 * @return bool
	 */
	public function hasParent()
	{
		return !empty($this->parent);
	}

	/**
	 * Returns the node's parent
	 *
	 * @return array the node's parent
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Adds a child to this node.
	 *
	 * @param SassNode $child
	 * @return SassNode the child to add
	 * @throws SassException
	 */
	public function addChild($child)
	{
		if ($child instanceof SassElseNode) {
			if (!$this->lastChild instanceof SassIfNode) {
				throw new SassException('@else(if) directive must come after @(else)if', $child);
				}
			$this->lastChild->addElse($child);
			}
		else {
			$this->children[]=$child;
			$child->parent=$this;
			$child->root=$this->root;
			}
		// The child will have children if a debug node has been added
		foreach ($child->children as $grandchild) {
			$grandchild->root=$this->root;
			}
	}

	/**
	 * Returns a value indicating if this node has children
	 *
	 * @return bool TRUE if the node has children, FALSE if not
	 */
	public function hasChildren()
	{
		return !empty($this->children);
	}

	/**
	 * Returns the node's children
	 *
	 * @return array the node's children
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * Returns a value indicating if this node is a child of the passed node.
	 * This just checks the levels of the nodes. If this node is at a greater
	 * level than the passed node if is a child of it.
	 *
	 * @param SassNode $node
	 * @return bool TRUE if the node is a child of the passed node, FALSE if not
	 */
	public function isChildOf($node)
	{
		return $this->level>$node->level;
	}

	/**
	 * Returns the last child node of this node.
	 *
	 * @return SassNode the last child node of this node
	 */
	public function getLastChild()
	{
		return $this->children[count($this->children)-1];
	}

	/**
	 * Returns the level of this node.
	 *
	 * @return int the level of this node
	 */
	public function getLevel()
	{
		return $this->token->level;
	}

	/**
	 * Returns the source for this node
	 *
	 * @return string the source for this node
	 */
	public function getSource()
	{
		return $this->token->source;
	}

	/**
	 * Returns the debug_info option setting for this node
	 *
	 * @return bool the debug_info option setting for this node
	 */
	public function getDebug_info()
	{
		return $this->parser->debug_info;
	}

	/**
	 * Returns the line number for this node
	 *
	 * @return string the line number for this node
	 */
	public function getLine()
	{
		return $this->token->line;
	}

	/**
	 * Returns the line_numbers option setting for this node
	 *
	 * @return bool the line_numbers option setting for this node
	 */
	public function getLine_numbers()
	{
		return $this->parser->line_numbers;
	}

	/**
	 * Returns the filename for this node
	 *
	 * @return string the filename for this node
	 */
	public function getFilename()
	{
		return $this->token->filename;
	}

	/**
	 * Returns the Sass parser.
	 *
	 * @return SassParser the Sass parser
	 */
	public function getParser()
	{
		return $this->root->parser;
	}

	/**
	 * Returns the property syntax being used.
	 *
	 * @return string the property syntax being used
	 */
	public function getPropertySyntax()
	{
		return $this->root->parser->propertySyntax;
	}

	/**
	 * Returns the SassScript parser.
	 *
	 * @return SassScriptParser the SassScript parser
	 */
	public function getScript()
	{
		return $this->root->script;
	}

	/**
	 * Returns the renderer.
	 *
	 * @return SassRenderer the renderer
	 */
	public function getRenderer()
	{
		return $this->root->renderer;
	}

	/**
	 * Returns the render style of the document tree.
	 *
	 * @return string the render style of the document tree
	 */
	public function getStyle()
	{
		return $this->root->parser->style;
	}

	/**
	 * Returns a value indicating whether this node is in a directive
	 *
	 * @param bool TRUE if the node is in a directive, FALSE if not
	 */
	public function inDirective()
	{
		return $this->parent instanceof SassDirectiveNode
			|| $this->parent instanceof SassDirectiveNode;
	}

	/**
	 * Returns a value indicating whether this node is in a SassScript directive
	 *
	 * @param bool TRUE if this node is in a SassScript directive, FALSE if not
	 */
	public function inSassScriptDirective()
	{
		return $this->parent instanceof SassEachNode
			|| $this->parent->parent instanceof SassEachNode
			|| $this->parent instanceof SassForNode
			|| $this->parent->parent instanceof SassForNode
			|| $this->parent instanceof SassIfNode
			|| $this->parent->parent instanceof SassIfNode
			|| $this->parent instanceof SassWhileNode
			|| $this->parent->parent instanceof SassWhileNode;
	}

	/**
	 * Evaluates a SassScript expression.
	 *
	 * @param string $expression to evaluate
	 * @param SassContext $context the context in which the expression is evaluated
	 * @param int $x
	 * @return SassLiteral value of parsed expression
	 */
	public function evaluate($expression, $context, $x=NULL)
	{
		$context->node=$this;

		return $this->script->evaluate($expression, $context, $x);
	}

	/**
	 * Replace interpolated SassScript contained in '#{}' with the parsed value.
	 *
	 * @param string $expression the text to interpolate
	 * @param SassContext $context the context in which the string is interpolated
	 * @return string the interpolated text
	 */
	public function interpolate($expression, $context)
	{
		$context->node=$this;

		return $this->script->interpolate($expression, $context);
	}

	/**
	 * Adds a warning to the node.
	 *
	 * @param string $message warning message
	 * @param array line
	 */
	public function addWarning($message)
	{
		$this->addChild(new SassDebugNode($this->token, $message));
	}

	/**
	 * Parse the children of the node.
	 *
	 * @param SassContext $context the context in which the children are parsed
	 * @return array the parsed child nodes
	 */
	public function parseChildren($context)
	{
		$children=array();
		foreach ($this->children as $child) {
			# child could be a SassLiteral /or/ SassNode
			$kid= method_exists($child, 'parse')
				? $child->parse($context)
				: array($child);
			$children=array_merge($children, $kid);
			}

		return $children;
	}

	/**
	 * Returns a value indicating if the token represents this type of node.
	 *
	 * @param object token
	 * @return bool TRUE if the token represents this type of node, FALSE if not
	 * @throws SassNodeException
	 */
	public static function isa($token)
	{
		throw new SassNodeException('Child classes must override this method');
	}

	/**
	 * @param int $i
	 */
	public function printDebugTree($i=0)
	{
		echo str_repeat(' ', $i*2).get_class($this).' '.$this->getSource()."\n";
		$p=$this->getParent();
		if ($p) {
			echo str_repeat(' ', $i*2)." parent: ".get_class($p)."\n";
			}
		foreach ($this->getChildren() as $c) {
			$c->printDebugTree($i+1);
			}
	}
}
