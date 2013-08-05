<?php
namespace PHPSass\Renderers;

/**
 * Renderer class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Renderer class.
 */
class Renderer
{
	/**#@+
	 * Output Styles
	 */
	const STYLE_COMPRESSED='compressed';
	const STYLE_COMPACT='compact';
	const STYLE_EXPANDED='expanded';
	const STYLE_NESTED='nested';
	/**#@- */
	const INDENT='  ';


	/**
	 * Returns the renderer for the required render style.
	 *
	 * @param string render style
	 * @return Renderer
	 */
	public static function getRenderer($style)
	{
		switch ($style) {
			case self::STYLE_COMPACT:
				return new CompactRenderer;
			case self::STYLE_COMPRESSED:
				return new CompressedRenderer;
			case self::STYLE_EXPANDED:
				return new ExpandedRenderer;
			case self::STYLE_NESTED:
				return new NestedRenderer;
			}
		}
}
