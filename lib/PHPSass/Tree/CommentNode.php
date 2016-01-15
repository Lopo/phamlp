<?php
namespace PHPSass\Tree;

/**
 * CommentNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * CommentNode class.
 * Represents a CSS comment.
 */
class CommentNode
extends Node
{
	const COMMENT_CHAR='/';
	const SASS_COMMENT_CHAR='/';
	const SASS_LOUD_COMMENT_CHAR='!';
	const CSS_COMMENT_CHAR='*';
	const MATCH='%^/\*\s*?(.*?)\s*?(\*/)?$%s';
	const COMMENT=1;
	const TYPE_SILENT=0; // unused: removed in \PHPSass\File::get_file_contents()
	const TYPE_NORMAL=1;
	const TYPE_LOUD=2;

	/** @var string */
	private $value;
	/** @var int */
	public $type=CommentNode::TYPE_NORMAL;


	/**
	 * @param object source token
	 */
	public function __construct($token)
	{
		parent::__construct($token);
		$silent= $token->source{1}==self::SASS_COMMENT_CHAR;
		$loud= !$silent && $token->source{2}==self::SASS_LOUD_COMMENT_CHAR;
		preg_match(self::MATCH, $token->source, $matches);
		$value= $silent
				? [$token->source]
				: [];
		$this->value=$matches[self::COMMENT];
		if ($silent) {
			$this->type=self::TYPE_SILENT;
			}
		elseif ($loud) {
			$this->type=self::TYPE_LOUD;
			$this->value=substr($this->value, 1);
			}
	}

	/**
	 * Returns TRUE if this is a slient comment
	 * or the current style doesn't render comments
	 *
	 * Comments starting with ! are never invisible (and the ! is removed from the output.)
	 *
	 * @param string $style
	 * @return bool
	 */
	public function isInvisible($style)
	{
		switch ($this->type) {
			case self::TYPE_LOUD:
				return FALSE;
			case self::TYPE_SILENT:
				return TRUE;
			default:
				return $style==\PHPSass\Renderers\Renderer::STYLE_COMPRESSED;
			}
	}

	/**
	 * @return string
	 */
	protected function getValue()
	{
		return $this->value;
	}

	/**
	 * Parse this node.
	 *
	 * @param Context $context
	 * @return array the parsed node - an empty array
	 */
	public function parse($context)
	{
		return [$this];
	}

	/**
	 * Render this node.
	 *
	 * @return string the rendered node
	 */
	public function render()
	{
		return $this->renderer->renderComment($this);
	}

	/**
	 * Returns a value indicating if the token represents this type of node.
	 *
	 * @param object $token
	 * @return bool TRUE if the token represents this type of node, FALSE if not
	 */
	public static function isa($token)
	{
		return $token->source{0}===self::COMMENT_CHAR
				&& in_array($token->source{1}, [self::CSS_COMMENT_CHAR, self::SASS_COMMENT_CHAR]);
	}
}
