<?php

interface ExtensionInterface
{
	/**
	 * @param string $namespace
	 * @return array
	 */
    public static function getFunctions($namespace);

	/**
	 * @param string $filename
	 * @param type $parser
	 * @param string $syntax
	 */
    public static function resolveExtensionPath($filename, $parser, $syntax='scss');
}
