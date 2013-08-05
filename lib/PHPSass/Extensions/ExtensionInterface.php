<?php
namespace PHPSass\Extensions;

interface ExtensionInterface
{
	/**
	 * @param string $namespace
	 * @return array
	 */
    public static function getFunctions($namespace);

	/**
	 * @param mixed $filename
	 * @param type $parser
	 * @param string $syntax
	 */
    public static function resolveExtensionPath($filename, $parser, $syntax='scss');
}
