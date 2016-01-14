<?php
namespace PHPSass\Renderers;

/**
 * CompactRenderer class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * CompactRenderer class.
 * Each CSS rule takes up only one line, with every property defined on that
 * line. Nested rules are placed next to each other with no newline, while
 * groups of rules have newlines between them.
 */
class CompactRenderer
extends CompressedRenderer
{
	const DEBUG_INFO_RULE='@media -sass-debug-info';
	const DEBUG_INFO_PROPERTY='font-family';


	/**
	 * Renders the brace between the selectors and the properties
	 *
	 * @return string the brace between the selectors and the properties
	 */
	protected function between()
	{
		return ' { ';
	}

	/**
	 * Renders the brace at the end of the rule
	 *
	 * @return string the brace between the rule and its properties
	 */
	protected function end()
	{
		return " }\n";
	}

	/**
	 * Renders a comment.
	 * Comments preceeding a rule are on their own line.
	 * Comments within a rule are on the same line as the rule.
	 *
	 * @param \PHPSass\Tree\Node the node being rendered
	 * @return string the rendered comment
	 */
	public function renderComment($node)
	{
		if ($node->isInvisible(Renderer::STYLE_COMPACT)) {
			return '';
			}
		$nl= $node->parent instanceof \PHPSass\Tree\RuleNode
			? ''
			: "\n";

		return "$nl/* ".join(' ', explode("\n", $node->value))." */$nl";
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
		return str_replace("\n", '', parent::renderDirective($node, $properties))
			."\n";
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
		return join(' ', $properties);
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
			? ' !important'
			: '';

		return "{$node->name}: {$node->value}{$node->important};";
	}

	/**
	 * Renders a rule.
	 *
	 * @param \PHPSass\Tree\Node the node being rendered
	 * @param array rule properties
	 * @param string rendered rules
	 * @return string the rendered rule
	 */
	public function renderRule($node, $properties, $rules)
	{
//		$ruleSeparator=', ';
//		$lineSeparator=' ';
//		$ruleIndent='';
//		$perRuleIndent='';
//		$totalIndent='';
//		$joinedRules=$rules->
//		foreach ($rules as $seq) {
//			$rulePart=\PHPSass\Script\Functions::join((array)$seq);
//			}
		return $this->renderDebug($node)
				.parent::renderRule($node, $properties, str_replace("\n\n", "\n", $rules))
				."\n";
	}

	/**
	 * Renders debug information.
	 * If the node has the debug_info options set TRUE the line number and filename
	 * are rendered in a format compatible with
	 * {@link https://addons.mozilla.org/en-US/firefox/addon/103988/ FireSass}.
	 * Else if the node has the line_numbers option set TRUE the line number and filename are rendered in a comment.
	 *
	 * @param \PHPSass\Tree\Node the node being rendered
	 * @return string the debug information
	 */
	protected function renderDebug($node)
	{
		$indent=$this->getIndent($node);
		$debug='';

		if ($node->getDebug_info()) {
			$debug=$indent.self::DEBUG_INFO_RULE.'{'
				.'filename{'.self::DEBUG_INFO_PROPERTY.':'.preg_replace('/([^-\w])/', '\\\\\1', "file://{$node->filename}").';}'
				.'line{'.self::DEBUG_INFO_PROPERTY.":'{$node->line}';}"
				."}\n";
			}
		elseif ($node->getLine_numbers()) {
			$debug="$indent/* line {$node->line} {$node->filename} */\n";
			}

		return $debug;
	}

	/**
	 * Renders rule selectors.
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
		return join(', ', $selectors);
	}
}
