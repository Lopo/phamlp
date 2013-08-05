<?php
namespace PHPSass\Script\Literals;

/**
 * Sass literal exception classes.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Sass literal exception.
 */
class LiteralException
extends \PHPSass\Script\ParserException
{
}

/**
 * BooleanException class.
 */
class BooleanException
extends LiteralException
{
}

/**
 * ColourException class.
 */
class ColourException
extends LiteralException
{
}

/**
 * NumberException class.
 */
class NumberException
extends LiteralException
{
}

/**
 * StringException class.
 */
class StringException
extends LiteralException
{
}
