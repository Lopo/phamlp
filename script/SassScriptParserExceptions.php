<?php

/**
 * SassScript Parser exception class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */
require_once(dirname(__FILE__).'/../SassException.php');

/**
 * SassScriptParserException class.
 */
class SassScriptParserException
extends SassException
{
}

/**
 * SassScriptLexerException class.
 */
class SassScriptLexerException
extends SassScriptParserException
{
}

/**
 * SassScriptOperationException class.
 */
class SassScriptOperationException
extends SassScriptParserException
{
}

/**
 * SassScriptFunctionException class.
 */
class SassScriptFunctionException
extends SassScriptParserException
{
}
