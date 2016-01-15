<?php
namespace PHPSass\Tree;

/**
 * MediaNode class file.
 * @author      Richard Lyon
 * @copyright   none
 * @license     http://phamlp.googlecode.com/files/license.txt
 */

/**
 * MediaNode class.
 * Represents a CSS @media directive
 */
class MediaNode
extends Node
{
	const IDENTIFIER='@';
	const MATCH='/^@(media|supports)\s+(.+?)\s*;?$/';
	const MEDIA=1;

	public $token;
	/** @var string */
	private $media;
	/**
	 * @var array parameters for the message;
	 * only used by internal warning messages
	 */
	private $params;
	/** @var bool TRUE if this is a warning */
	private $warning;


	/**
	 * @param object $token source token
	 * @param mixed string: an internally generated warning message about the source
	 * bool: the source token is a @Media or @warn directive containing the message; True if this is a @warn directive
	 * @param array parameters for the message
	 */
	public function __construct($token)
	{
		parent::__construct($token);

		preg_match(self::MATCH, $token->source, $matches);
		$this->token=$token;
		$this->media=$matches[self::MEDIA];
	}

	/**
	 * Parse this node.
	 * This raises an error.
	 *
	 * @param Context $context
	 * @return array An empty array
	 */
	public function parse($context)
	{
		// If we are in a loop, function or mixin then the parent isn't what should
		// go inside the media node.  Walk up the parent tree to find the rule node
		// to put inside the media node or the root node if the media node should be
		// at the root.
		$parent=$this->parent;
		while (!($parent instanceOf RuleNode) && !($parent instanceOf RootNode)) {
			$parent=$parent->parent;
			}

		// Make a copy of the token before parsing in case we are in a loop and it contains variables
		$token=clone $this->token;
		$token->source=DirectiveNode::interpolate_nonstrict($token->source, $context);

		$node=new RuleNode($token, $context);
		$node->root=$parent->root;

		$rule=clone $parent;
		$rule->root=$node->root;
		$rule->children=$this->children;

		$try=$rule->parse($context);
		if (is_array($try)) {
			$rule->children=$try;
			}
		// Tests were failing with this, but I'm not sure if we cover every case.
		//else if (is_object($try) && method_exists($try, 'render')) {
		//  $rule = $try;
		//}

		$node->children=[new \PHPSass\Script\Literals\SassString($rule->render($context))];

		return [$node];
	}
}
