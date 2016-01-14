<?php
namespace PHPSass;

/**
 * File class file.
 * File handling utilites.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * File class.
 */
class File
{
	const CSS='css';
	const SASS='sass';
	const SCSS='scss';

	/** @var string */
	public static $path=FALSE;
	/** @var Parser */
	public static $parser=FALSE;


	/**
	 * Returns the parse tree for a file.
	 *
	 * @param string $filename to parse
	 * @param Parser $parser Sass parser
	 * @return Tree\RootNode
	 */
	public static function get_tree($filename, &$parser)
	{
		$contents=self::get_file_contents($filename, $parser);

		$options=array_merge($parser->getOptions(), ['line' => 1]);

		# attempt at cross-syntax imports.
		$ext=substr($filename, strrpos($filename, '.')+1);
		if ($ext==self::SASS || $ext==self::SCSS) {
			$options['syntax']=$ext;
			}

		$dirName=dirname($filename);
		$options['load_paths'][]=$dirName;
		if (!in_array($dirName, $parser->load_paths)) {
			$parser->load_paths[]=dirname($filename);
			}

		$sassParser=new Parser($options);
		$tree=$sassParser->parse($contents, FALSE);

		return $tree;
	}

	/**
	 * @param string $filename
	 * @param Parser $parser
	 * @return mixed
	 */
	public static function get_file_contents($filename, $parser=NULL)
	{
		$content=file_get_contents($filename);//."\n\n "; #add some whitespace to fix bug
		# strip // comments at this stage, with allowances for http:// style locations.
		$content=preg_replace("/(^|\s)\/\/[^\n]+/", '', $content);
		// File::$parser = $parser;
		// File::$path = $filename;
		return $content;
	}

	/**
	 * Returns the full path to a file to parse.
	 * The file is looked for recursively under the load_paths directories
	 * If the filename does not end in .sass or .scss try the current syntax first
	 * then, if a file is not found, try the other syntax.
	 *
	 * @param string $filename to find
	 * @param Parser $parser Sass parser
	 * @param bool $sass_only
	 * @return array of string path(s) to file(s) or FALSE if no such file
	 */
	public static function get_file($filename, &$parser, $sass_only=TRUE)
	{
		$ext=substr($filename, strrpos($filename, '.')+1);
		// if the last char isn't *, and it's not (.sass|.scss|.css)
		if ($sass_only && substr($filename, -1)!='*' && $ext!==self::SASS && $ext!==self::SCSS && $ext!==self::CSS) {
			$sass=self::get_file($filename.'.'.self::SASS, $parser);

			return $sass
				? $sass
				: self::get_file($filename.'.'.self::SCSS, $parser);
			}
		if (is_file($filename)) {
			return [$filename];
			}
		$paths=$parser->load_paths;
		if (is_string($parser->filename) && $path=dirname($parser->filename)) {
			$paths[]=$path;
			if (!in_array($path, $parser->load_paths)) {
				$parser->load_paths[]=$path;
				}
			}
		foreach ($paths as $path) {
			$filePath=self::find_file($filename, realpath($path));
			if ($filePath!==FALSE) {
				if (!is_array($filePath)) {
					return array($filePath);
					}
				return $filePath;
				}
			}
		foreach ($parser->load_path_functions as $function) {
			if (is_callable($function) && $paths=call_user_func($function, $filename, $parser)) {
				return $paths;
			}
		}

		return FALSE;
	}

	/**
	 * Looks for the file recursively in the specified directory.
	 * This will also look for _filename to handle Sass partials.
	 *
	 * @param string $filename to look for
	 * @param string $dir path to directory to look in and under
	 * @return mixed string: full path to file if found, FALSE if not
	 */
	public static function find_file($filename, $dir)
	{
		static $pathCache=[];
		$cacheKey=$filename.'@'.$dir;
		if (isset($pathCache[$cacheKey])) {
			return $pathCache[$cacheKey];
			}
		if (strstr($filename, DIRECTORY_SEPARATOR.'**')) {
			$specialDirectory=$dir.DIRECTORY_SEPARATOR.substr($filename, 0, strpos($filename, DIRECTORY_SEPARATOR.'**'));
			if (is_dir($specialDirectory)) {
				$paths=[];
				foreach (scandir($specialDirectory) as $file) {
					if ($file==='..') {
						continue;
						}
					if (is_dir($specialDirectory.DIRECTORY_SEPARATOR.$file)) {
						$new_filename= $file==='.'
							? str_replace(DIRECTORY_SEPARATOR.'**', '', $filename)
							: str_replace('**', $file, $filename);
						$path=self::find_file($new_filename, $dir);
						if ($path!==FALSE) {
							if (!is_array($path)) {
								$path=[$path];
								}
							$paths=array_merge($paths, $path);
							}
						}
					}
				// cache and return
				return $pathCache[$cacheKey]=$paths;
				}
			}

		if (substr($filename, -2)==DIRECTORY_SEPARATOR.'*') {
			$checkDir=$dir.DIRECTORY_SEPARATOR.substr($filename, 0, strlen($filename)-2);
			if (is_dir($checkDir)) {
				$dir=$checkDir;
				$paths=[];
				foreach (scandir($dir) as $file) {
					if (($file==='.') || ($file==='..')) {
						continue;
						}
					$ext=substr($file, strrpos($file, '.')+1);
					if (substr($file, -1)!='*' && ($ext==self::SASS || $ext==self::SCSS || $ext==self::CSS)) {
						$paths[]=$dir.DIRECTORY_SEPARATOR.$file;
						}
					}
				// cache and return
				return $pathCache[$cacheKey]=$paths;
				}
			}

		foreach ([$filename, str_replace(basename($filename), ('_'.basename($filename)), $filename)] as $file) {
			$checkFile=$dir.DIRECTORY_SEPARATOR.$file;
			if (is_file($checkFile)) {
				return $pathCache[$cacheKey]=realpath($checkFile);
				}
			}

		if (is_dir($dir)) {
			foreach (glob($dir.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) as $deepDir) {
				$path=self::find_file($filename, $deepDir);
				if ($path!==FALSE) {
					return $pathCache[$cacheKey]=$path;
					}
				}
			}

		return $pathCache[$cacheKey]=FALSE;
	}
}
