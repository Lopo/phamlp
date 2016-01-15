<?php
namespace PHPSass\Renderers;

/**
 * CompressedRenderer class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * CompressedRenderer class.
 * Compressed style takes up the minimum amount of space possible, having no
 * whitespace except that necessary to separate selectors and a newline at the
 * end of the file. It's not meant to be human-readable
 */
class CompressedRenderer
extends Renderer
{
	/**
	 * Renders the brace between the selectors and the properties
	 *
	 * @return string the brace between the selectors and the properties
	 */
	protected function between()
	{
		return '{';
	}

	/**
	 * Renders the brace at the end of the rule
	 *
	 * @return string the brace between the rule and its properties
	 */
	protected function end()
	{
		return '}';
	}

	/**
	 * Returns the indent string for the node
	 *
	 * @param \PHPSass\Tree\Node the node to return the indent string for
	 * @return string the indent string for this Node
	 */
	protected function getIndent($node)
	{
		return '';
	}

	/**
	 * Renders a comment.
	 *
	 * @param \PHPSass\Tree\Node the node being rendered
	 * @return string the rendered comment
	 */
	public function renderComment($node)
	{
		if ($node->isInvisible(Renderer::STYLE_COMPRESSED)) {
			return '';
			}

		$nl= $node->parent instanceof \PHPSass\Tree\RuleNode
			? ''
			: "\n";

		return "$nl/* ".join("\n * ", $node->children)." */$nl";
	}

	/**
	 * Renders a directive.
	 *
	 * @param \PHPSass\Tree\Node the node being rendered
	 * @param array properties of the directive
	 * @return string the rendered directive
	 */
	public function renderDirective($node, $properties)
	{
		return $node->directive
				.$this->between()
				.$this->renderProperties($node, $properties)
				.$this->end();
	}

	/**
	 * Renders properties.
	 *
	 * @param \PHPSass\Tree\Node the node being rendered
	 * @param array properties to render
	 * @return string the rendered properties
	 */
	public function renderProperties($node, $properties)
	{
		return join('', $properties);
	}

	/**
	 * Renders a property.
	 *
	 * @param \PHPSass\Tree\Node the node being rendered
	 * @return string the rendered property
	 */
	public function renderProperty($node)
	{
		$node->important= $node->important
			? '!important'
			: '';

		return "{$node->name}:{$node->value}{$node->important};";
	}

	/**
	 * Renders a rule.
	 *
	 * @param \PHPSass\Tree\Node the node being rendered
	 * @param array rule properties
	 * @param string rendered rules
	 * @return string the rendered directive
	 */
	public function renderRule($node, $properties, $rules)
	{
		if ($selectors=$this->renderSelectors($node)) {
			return (!empty($properties)? $selectors.$this->between().$this->renderProperties($node, $properties).$this->end() : '').$rules;
			}
	}

	/**
	 * Renders the rule's selectors
	 *
	 * @param \PHPSass\Tree\Node the node being rendered
	 * @return string the rendered selectors
	 */
	protected function renderSelectors($node)
	{
		$selectors=[];
		foreach ($node->selectors as $selector) {
			if (!$node->isPlaceholder($selector)) {
				$selectors[]=$selector;
				}
			}

		return join(',', $selectors);
	}
}
