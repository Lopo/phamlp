<?php

/**
 * Sass literal exception classes.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */
require_once(dirname(__FILE__).'/../SassScriptParserExceptions.php');

/**
 * Sass literal exception.
 */
class SassLiteralException
extends SassScriptParserException
{
}

/**
 * SassBooleanException class.
 */
class SassBooleanException
extends SassLiteralException
{
}

/**
 * SassColourException class.
 */
class SassColourException
extends SassLiteralException
{
}

/**
 * SassNumberException class.
 */
class SassNumberException
extends SassLiteralException
{
}

/**
 * SassStringException class.
 */
class SassStringException
extends SassLiteralException
{
}
