<?php
namespace PHPSass\Tree;

/**
 * Node exception classes.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * NodeException class.
 */
class NodeException
extends \PHPSass\Exception
{
}

/**
 * ContextException class.
 */
class ContextException
extends NodeException
{
}

/**
 * CommentNodeException class.
 */
class CommentNodeException
extends NodeException
{
}

/**
 * DebugNodeException class.
 */
class DebugNodeException
extends NodeException
{
}

/**
 * DirectiveNodeException class.
 */
class DirectiveNodeException
extends NodeException
{
}

/**
 * EachNodeException class.
 */
class EachNodeException
extends NodeException
{
}

/**
 * ExtendNodeException class.
 */
class ExtendNodeException
extends NodeException
{
}

/**
 * ForNodeException class.
 */
class ForNodeException
extends NodeException
{
}

/**
 * FunctionDefinitionNodeException class.
 */
class FunctionDefinitionNodeException
extends NodeException
{
}

/**
 * IfNodeException class.
 */
class IfNodeException
extends NodeException
{
}

/**
 * ImportNodeException class.
 */
class ImportNodeException
extends NodeException
{
}

/**
 * MixinDefinitionNodeException class.
 */
class MixinDefinitionNodeException
extends NodeException
{
}

/**
 * MixinNodeException class.
 */
class MixinNodeException
extends NodeException
{
}

/**
 * PropertyNodeException class.
 */
class PropertyNodeException
extends NodeException
{
}

/**
 * RuleNodeException class.
 */
class RuleNodeException
extends NodeException
{
}

/**
 * VariableNodeException class.
 */
class VariableNodeException
extends NodeException
{
}

/**
 * WhileNodeException class.
 */
class WhileNodeException
extends NodeException
{
}
