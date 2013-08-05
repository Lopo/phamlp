<?php
namespace PHPSass;

/**
 * Sass exception.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Sass exception class.
 */
class Exception
extends \Exception
{
	/**
	 * @param string $message Exception message
	 * @param object $object with source code and meta data
	 */
	public function __construct($message, $object)
	{
		parent::__construct($message.(is_object($object)? ": {$object->filename}::{$object->line}\nSource: {$object->source}" : ''));
	}
}
