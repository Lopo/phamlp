<?php

/**
 * SassNode exception classes.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */
require_once(dirname(__FILE__).'/../SassException.php');

/**
 * SassNodeException class.
 */
class SassNodeException
extends SassException
{
}

/**
 * SassContextException class.
 */
class SassContextException
extends SassNodeException
{
}

/**
 * SassCommentNodeException class.
 */
class SassCommentNodeException
extends SassNodeException
{
}

/**
 * SassDebugNodeException class.
 */
class SassDebugNodeException
extends SassNodeException
{
}

/**
 * SassDirectiveNodeException class.
 */
class SassDirectiveNodeException
extends SassNodeException
{
}

/**
 * SassEachNodeException class.
 */
class SassEachNodeException
extends SassNodeException
{
}

/**
 * SassExtendNodeException class.
 */
class SassExtendNodeException
extends SassNodeException
{
}

/**
 * SassForNodeException class.
 */
class SassForNodeException
extends SassNodeException
{
}

/**
 * SassFunctionDefinitionNodeException class.
 */
class SassFunctionDefinitionNodeException
extends SassNodeException
{
}

/**
 * SassIfNodeException class.
 */
class SassIfNodeException
extends SassNodeException
{
}

/**
 * SassImportNodeException class.
 */
class SassImportNodeException
extends SassNodeException
{
}

/**
 * SassMixinDefinitionNodeException class.
 */
class SassMixinDefinitionNodeException
extends SassNodeException
{
}

/**
 * SassMixinNodeException class.
 */
class SassMixinNodeException
extends SassNodeException
{
}

/**
 * SassPropertyNodeException class.
 */
class SassPropertyNodeException
extends SassNodeException
{
}

/**
 * SassRuleNodeException class.
 */
class SassRuleNodeException
extends SassNodeException
{
}

/**
 * SassVariableNodeException class.
 */
class SassVariableNodeException
extends SassNodeException
{
}

/**
 * SassWhileNodeException class.
 */
class SassWhileNodeException
extends SassNodeException
{
}
