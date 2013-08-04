<?php
/* SVN FILE: $Id$ */
/**
 * SassCommentNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 * @package      PHamlP
 * @subpackage  Sass.tree
 */

/**
 * SassCommentNode class.
 * Represents a CSS comment.
 * @package      PHamlP
 * @subpackage  Sass.tree
 */
class SassCommentNode extends SassNode
{
  const NODE_IDENTIFIER = '/';
  const MATCH = '%^/\*\s*?(.*?)\s*?(\*/)?$%s';
  const COMMENT = 1;
  const TYPE_SILENT = 0; // unused: removed in SassFile::get_file_contents()
  const TYPE_NORMAL = 1;
  const TYPE_LOUD = 2;

  private $value;
  /** @var int */
  public $type = SassCommentNode::TYPE_NORMAL;

  /**
   * SassCommentNode constructor.
   * @param object source token
   * @return CommentNode
   */
  public function __construct($token)
  {
    parent::__construct($token);
    preg_match(self::MATCH, $token->source, $matches);
    $this->value = $matches[self::COMMENT];
    if (substr($matches[self::COMMENT], 0, 1)=='!') {
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
        return $style == SassRenderer::STYLE_COMPRESSED;
    }
  }

  protected function getValue()
  {
    return $this->value;
  }

  /**
   * Parse this node.
   * @return array the parsed node - an empty array
   */
  public function parse($context)
  {
    return array($this);
  }

  /**
   * Render this node.
   * @return string the rendered node
   */
  public function render()
  {
    return $this->renderer->renderComment($this);
  }

  /**
   * Returns a value indicating if the token represents this type of node.
   * @param object token
   * @return boolean true if the token represents this type of node, false if not
   */
  public static function isa($token)
  {
    return $token->source[0] === self::NODE_IDENTIFIER;
  }
}
