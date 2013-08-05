<?php
namespace PHPSass\Script;

/**
 * Script Parser exception class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * ParserException class.
 */
class ParserException
extends \PHPSass\Exception
{
}

/**
 * LexerException class.
 */
class LexerException
extends ParserException
{
}

/**
 * OperationException class.
 */
class OperationException
extends ParserException
{
}

/**
 * FunctionException class.
 */
class ScriptFunctionException
extends ParserException
{
}
