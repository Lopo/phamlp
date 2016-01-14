<?php
namespace PHPSass\Tree;

/**
 * Node class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Node class.
 * Base class for all Sass nodes.
 */
class Node
{
	/** @var Node parent of this node */
	public $parent;
	/** @var Node root node */
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
	 * @throws \PHPSass\Tree\NodeException
	 */
	public function __get($name)
	{
		$getter='get'.ucfirst($name);
		if (method_exists($this, $getter)) {
			return $this->$getter();
			}
		throw new NodeException('No getter function for '.$name, $this);
	}

	/**
	 * @param string $name of property to set
	 * @return mixed $value of property
	 * @return Node this node
	 * @throws NodeException
	 */
	public function __set($name, $value)
	{
		$setter='set'.ucfirst($name);
		if (method_exists($this, $setter)) {
			$this->$setter($value);

			return $this;
			}
		throw new NodeException('No setter function for '.$name, $this);
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
	 * @param Node $child
	 * @return Node the child to add
	 * @throws \PHPSass\Exception
	 */
	public function addChild($child)
	{
		if ($child instanceof ElseNode) {
			if (!$this->getLastChild() instanceof IfNode) {
				throw new \PHPSass\Exception('@else(if) directive must come after @(else)if', $child);
				}
			$this->getLastChild()->addElse($child);
			}
		else {
			$this->children[]=$child;
			$child->parent=$this;
			$child->setRoot($this->root);
			}
	}

	public function setRoot($root)
	{
		$this->root=$root;
		foreach ($this->children as $child) {
			$child->setRoot($this->root);
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
	 * @param Node $node
	 * @return bool TRUE if the node is a child of the passed node, FALSE if not
	 */
	public function isChildOf($node)
	{
		return $this->getLevel()>$node->getLevel();
	}

	/**
	 * Returns the last child node of this node.
	 *
	 * @return Node the last child node of this node
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
		return $this->getParser()->debug_info;
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
		return $this->getParser()->line_numbers;
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
	 * @return \PHPSass\Parser the Sass parser
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
		return $this->root->getParser()->propertySyntax;
	}

	/**
	 * Returns the Script parser.
	 *
	 * @return \PHPSass\Script\Parser the Script parser
	 */
	public function getScript()
	{
		return $this->root->script;
	}

	/**
	 * Returns the renderer.
	 *
	 * @return \PHPSass\Renderers\Renderer the renderer
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
		return $this->root->getParser()->style;
	}

	/**
	 * Returns a value indicating whether this node is in a directive
	 *
	 * @param bool TRUE if the node is in a directive, FALSE if not
	 */
	public function inDirective()
	{
		return $this->parent instanceof DirectiveNode
			|| $this->parent instanceof DirectiveNode;
	}

	/**
	 * Returns a value indicating whether this node is in a Script directive
	 *
	 * @param bool TRUE if this node is in a Script directive, FALSE if not
	 */
	public function inSassScriptDirective()
	{
		return $this->parent instanceof EachNode
			|| $this->parent->parent instanceof EachNode
			|| $this->parent instanceof ForNode
			|| $this->parent->parent instanceof ForNode
			|| $this->parent instanceof IfNode
			|| $this->parent->parent instanceof IfNode
			|| $this->parent instanceof WhileNode
			|| $this->parent->parent instanceof WhileNode;
	}

	/**
	 * Evaluates a Script expression.
	 *
	 * @param string $expression to evaluate
	 * @param \PHPSass\Context $context the context in which the expression is evaluated
	 * @param int $x
	 * @return \PHPSass\Script\Literals\Literal value of parsed expression
	 */
	public function evaluate($expression, $context, $x=NULL)
	{
		$context->node=$this;

		return $this->script->evaluate($expression, $context, $x);
	}

	/**
	 * Replace interpolated Script contained in '#{}' with the parsed value.
	 *
	 * @param string $expression the text to interpolate
	 * @param Context $context the context in which the string is interpolated
	 * @return string the interpolated text
	 */
	public function interpolate($expression, $context)
	{
		$context->node=$this;

		return $this->getScript()->interpolate($expression, $context);
	}

	/**
	 * Adds a warning to the node.
	 *
	 * @param string $message warning message
	 * @param array line
	 */
	public function addWarning($message)
	{
		$this->addChild(new DebugNode($this->token, $message));
	}

	/**
	 * Parse the children of the node.
	 *
	 * @param Context $context the context in which the children are parsed
	 * @return array the parsed child nodes
	 */
	public function parseChildren($context)
	{
		$children=array();
		foreach ($this->children as $child) {
			# child could be a Literal /or/ Node
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
	 * @throws NodeException
	 */
	public static function isa($token)
	{
		throw new NodeException('Child classes must override this method');
	}

	/**
	 * @param int $i
	 */
	public function printDebugTree($i=0)
	{
		echo str_repeat(' ', $i*2).get_class($this).' '.$this->getSource()."\n";
		$p=$this->getParent();
		if ($p) {
			echo str_repeat(' ', $i*2).' parent: '.get_class($p)."\n";
			}
		foreach ($this->getChildren() as $c) {
			$c->printDebugTree($i+1);
			}
	}
}
