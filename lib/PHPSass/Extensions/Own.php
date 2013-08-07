<?php
namespace PHPSass\Extensions;

class Own
implements ExtensionInterface
{
	/** @var string */
	public static $filesFolder='css';
	/** @var array */
	public static $filePaths=NULL;
	/** @var array List with alias functions in Own */
	public static $functions=array(
		'demo-function',
		);


	/**
	 * @param string $namespace
	 * @return array
	 */
	public static function getFunctions($namespace)
	{
		$output=array();
		foreach (self::$functions as $function) {
			$originalFunction=$function;
			$function[0]=strtoupper($function[0]);
			$function=preg_replace_callback('/-([a-z])/', function ($c) {return strtoupper($c[1]);}, $function);
			$output[$originalFunction]=__CLASS__.'::'.strtolower($namespace).$function;
			}

		return $output;
	}

	/**
	 * Returns an array with all files in $root path recursively and assign each array Key with clean alias
	 *
	 * @param string $root
	 * @return array
	 */
	public static function getFilesArray($root)
	{
		$alias=array();
		$directories=array();
		$last_letter=$root[strlen($root)-1];
		$root= ($last_letter=='\\' || $last_letter=='/')
			? $root
			: $root.DIRECTORY_SEPARATOR;

		$directories[]=$root;

		while (sizeof($directories)) {
			$dir=array_pop($directories);
			if ($handle=opendir($dir)) {
				while (FALSE!==($file=readdir($handle))) {
					if ($file=='.' || $file=='..') {
						continue;
						}
					$file=$dir.$file;
					if (is_dir($file)) {
						$directory_path=$file.DIRECTORY_SEPARATOR;
						array_push($directories, $directory_path);
						}
					elseif (is_file($file)) {
						$key=basename($file);
						$alias[substr($key, 1, strpos($key, '.')-1)]=$file;
						}
					}
				closedir($handle);
				}
			}

		return $alias;
	}

	/**
	 * Implementation of hook_resolve_path_NAMESPACE().
	 *
	 * @param string $callerImport
	 * @param type $parser
	 * @param string $syntax
	 * @return string
	 */
	public static function resolveExtensionPath($callerImport, $parser, $syntax='scss')
	{
		$alias=str_replace('/_', '/', str_replace(array('.scss', '.sass'), '', $callerImport));
		if (strrpos($alias, '/')!==FALSE) {
			$alias=substr($alias, strrpos($alias, '/')+1);
			}
		if (self::$filePaths==NULL) {
			self::$filePaths=self::getFilesArray(__DIR__.'/'.self::$filesFolder.'/');
			}
		if (isset(self::$filePaths[$alias])) {
			return self::$filePaths[$alias];
			}
	}

	/**
	 * @return String
	 */
	public static function ownDemoFunction()
	{
		return new \PHPSass\Script\Literals\String("'This is my own Demo Function'");
	}
}
