<?php

/**
 * SassElseNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * SassElseNode class.
 * Represents Sass Else If and Else statements.
 * Else If and Else statement nodes are chained below the If statement node.
 */
class SassElseNode
extends SassIfNode
{
	/**
	 * @param object $token source token
	 */
	public function __construct($token)
	{
		parent::__construct($token, FALSE);
	}
}
