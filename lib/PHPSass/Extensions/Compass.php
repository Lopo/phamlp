<?php
namespace PHPSass\Extensions;

use PHPSass\Script\Literals\Boolean,
	PHPSass\Script\Literals\Number,
	PHPSass\Script\Literals\String,
	PHPSass\Script\Literals\Colour,
	PHPSass\Script\Functions;

class Compass
implements ExtensionInterface
{
	/** @var string */
	public static $filesFolder='stylesheets';
	/** @var array */
	public static $filePaths=NULL;
	/** @var array List with alias functions in Compass */
	public static $functions=array(
		'resolve-path',
		'adjust-lightness',
		'scale-lightness',
		'adjust-saturation',
		'scale-saturation',
		'scale-color-value',
		'is-position',
		'is-position-list',
		'opposite-position',
		'-webkit',
		'-moz',
		'-o',
		'-ms',
		'-svg',
		'-pie',
		'-css2',
		'owg',
		'prefixed',
		'prefix',
		'elements-of-type',
		'enumerate',
		'font-files',
		'image-width',
		'image-height',
		'inline-image',
		'inline-font-files',
		'blank',
		'compact',
		'-compass-nth',
		'-compass-list',
		'-compass-list',
		'-compass-space-list',
		'-compass-list-size',
		'-compass-slice',
		'first-value-of',
		'nest',
		'append-selector',
		'headers',
		'pi',
		'sin',
		'cos',
		'tan',
		'comma-list',
		'prefixed-for-transition',
		'stylesheet-url',
		'font-url',
		'image-url',
		'shade',
		'tint'
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
			$func=create_function('$c', 'return strtoupper($c[1]);');
			$function=preg_replace_callback('/-([a-z])/', $func, $function);
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
		$root= ($last_letter=='\\' || $last_letter=='/')? $root : $root.DIRECTORY_SEPARATOR;

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
	 * @param mixed $callerImport
	 * @param \PHPSass\Parser $parser
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
			self::$filePaths=self::getFilesArray(dirname(__FILE__).'/'.self::$filesFolder.'/');
			}
		if (isset(self::$filePaths[$alias])) {
			return self::$filePaths[$alias];
			}
	}

	/**
	 * Resolves requires to the compass namespace (eg namespace/css3/border-radius)
	 *
	 * @param string $file
	 * @return string|FALSE
	 */
	public static function compassResolvePath($file)
	{
		if ($file{0}=='/') {
			return $file;
			}
		if (!$path=realpath($file)) {
			$path=\PHPSass\Script\ScriptFunction::$context->node->token->filename;
			$path=substr($path, 0, strrpos($path, '/')).'/';
			$path=$path.$file;
			$last='';
			while ($path!=$last) {
				$last=$path;
				$path=preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
				}
			$path=realpath($path);
			}
		if ($path) {
			return $path;
			}

		return FALSE;
	}

	/**
	 * @param string $file
	 * @return Number
	 */
	public static function compassImageWidth($file)
	{
		if ($info=self::compassImageInfo($file)) {
			return new Number($info[0].'px');
			}

		return new Number('0px');
	}

	/**
	 * @param string $file
	 * @return Number
	 */
	public static function compassImageHeight($file)
	{
		if ($info=self::compassImageInfo($file)) {
			return new Number($info[1].'px');
			}

		return new Number('0px');
	}

	/**
	 * @param string $file
	 * @return array|FALSE
	 */
	public static function compassImageInfo($file)
	{
		if ($path=self::compassResolvePath($file)) {
			if ($info=getimagesize($path)) {
				return $info;
				}
			}

		return FALSE;
	}

	/**
	 * @param string $file
	 * @param string $mime
	 * @return String
	 */
	public static function compassInlineImage($file, $mime=NULL)
	{
		if ($path=self::compassUrl($file, TRUE, FALSE)) {
			$info=getimagesize($path);
			$mime=$info['mime'];
			$data=base64_encode(file_get_contents($path));
			# todo - do not return encoded if file size > 32kb

			return new String("url('data:$mime;base64,$data')");
			}

		return new String('');
	}

	/**
	 * @param string $file
	 * @return String
	 */
	public static function compassInlineFontFiles($file)
	{
		$args=func_get_args();
		$files=array();
		$mimes=array(
			'otf' => 'font.opentype',
			'ttf' => 'font.truetype',
			'woff' => 'font.woff',
			'off' => 'font.openfont',
			);

		while (count($args)) {
			$path=self::compassResolvePath(array_shift($args));
			$data=base64_encode(file_get_contents($path));
			$format=array_shift($args);

			$ext=array_pop(explode('.', $file));
			if (isset($mimes[$ext])) {
				$mime=$mimes[$ext];
				}
			else {
				continue;
				}

			$files[]="url('data:$mime;base64,$data') format('$format')";
			}

		return new String(implode(', ', $files));
	}

	/**
	 * @param mixed $object
	 * @return Boolean
	 */
	public static function compassBlank($object)
	{
		if (is_object($object)) {
			$object=$object->value;
			}
		$result=FALSE;
		if (is_bool($object)) {
			$result=!$object;
			}
		if (is_string($object)) {
			$result=(strlen(trim($object, ' ,'))===0);
			}

		return new Boolean($result);
	}

	/**
	 * @return String
	 */
	public static function compassCompact()
	{
		$sep=', ';

		$args=func_get_args();
		$list=array();

		// remove blank entries
		// append non-blank entries to list
		foreach ($args as $k => $v) {
			$string= is_object($v)
				? (isset($v->value)? $v->value : FALSE)
				: (string)$v;
			if (empty($string) || $string=='false') {
				unset($args[$k]);
				continue;
				}
			$list[]=$string;
			}

		return new String(implode($sep, $list));
	}

	/**
	 * @return Boolean
	 */
	public static function compassCompassNth()
	{
		$args=func_get_args();
		$place=array_pop($args);
		$list=array();
		foreach ($args as $arg) {
			$list=array_merge($list, self::compassList($arg));
			}

		if ($place=='first') {
			$place=0;
			}
		if ($place=='last') {
			$place=count($list) - 1;
			}

		if (isset($list[$place])) {
			return current(\PHPSass\Script\Lexer::$instance->lex($list[$place], new \PHPSass\Tree\Context));
			}

		return new Boolean(FALSE);
	}

	/**
	 * @return String
	 */
	public static function compassCompassList()
	{
		$args=func_get_args();
		$list=array();
		foreach ($args as $arg) {
			$list=array_merge($list, self::compassList($arg));
			}

		return new String(implode(', ', $list));
	}

	/**
	 * @return String
	 */
	public static function compassCompassSpaceList()
	{
		$args=func_get_args();
		$list=self::compassList($args, ',');

		return new String(implode(' ', $list));
	}

	/**
	 * @return Number
	 */
	public static function compassCompassListSize()
	{
		$args=func_get_args();
		$list=self::compassList($args, ',');

		return new Number(count($list));
	}

	/**
	 * @param array $list
	 * @param int $start
	 * @param int $end
	 * @return string
	 */
	public static function compassCompassListSlice($list, $start, $end)
	{
		$args=func_get_args();
		$end=array_pop($args);
		$start=array_pop($args);
		$list=self::compassList($args, ',');

		return implode(',', array_slice($list, $start, $end));
	}

	/**
	 * @return Boolean
	 */
	public static function compassFirstValueOf()
	{
		$args=array();
		$args[]='first';

		return call_user_func_array('self::compassCompassNth', $args);
	}

	/**
	 * @param mixed $list
	 * @param string $seperator
	 * @return array
	 */
	public static function compassList($list, $seperator=',')
	{
		if (is_object($list)) {
			$list=$list->value;
			}
		if (is_array($list)) {
			$newlist=array();
			foreach ($list as $listlet) {
				$newlist=array_merge($newlist, self::compassList($listlet, $seperator));
				}
			$list=implode(', ', $newlist);
			}

		$out=array();
		$size= $braces= 0;
		$stack='';
		for ($i=0; $i<strlen($list); $i++) {
			$char=substr($list, $i, 1);
			switch ($char) {
				case '(':
					$braces++;
					$stack.=$char;
					break;
				case ')':
					$braces--;
					$stack.=$char;
					break;
				case $seperator:
					if ($braces===0) {
						$out[]=$stack;
						$stack='';
						$size++;
						break;
					}
				default:
					$stack.=$char;
				}
			}
		$out[]=$stack;

		return $out;
	}

	/**
	 * http://compass-style.org/reference/compass/helpers/selectors/#nest
	 *
	 * @return String
	 */
	public static function compassNest()
	{
		$args=func_get_args();
		$output=explode(',', array_pop($args));

		for ($i=count($args)-1; $i>=0; $i--) {
			$current=explode(',', $args[$i]);
			$size=count($output);
			foreach ($current as $selector) {
				for ($j=0; $j < $size; $j++) {
					$output[]=trim($selector).' '.trim($output[$j]);
					}
				}
			$output=array_slice($output, $size);
			}

		return new String(implode(', ', $output));
	}

	/**
	 * @param string $initial
	 * @param string $new
	 * @return String
	 */
	public static function compassAppendSelector($initial, $new)
	{
		$list=explode(',', $initial);
		foreach ($list as $k => $selector) {
			$list[$k]=trim($selector).$new;
			}

		return new String(implode(', ', $list));
	}

	/**
	 * @param mixed $from
	 * @param mixed $to
	 * @return String
	 */
	public static function compassHeaders($from=FALSE, $to=FALSE)
	{
		if (is_object($from)) {
			$from=$from->value;
			}
		if (is_object($to)) {
			$to=$to->value;
			}

		if (!$from || !is_numeric($from)) {
			$from=1;
			}
		if (!$to || !is_numeric($to)) {
			$to=6;
			}

		$from=(int)$from;
		$to=(int)$to;

		$output=array();
		for ($i=$from; $i<=$to; $i++) {
			$output[]='h'.$i;
			}

		return new String(implode(', ', $output));
	}

	public static function compassCommaList()
	{
		print_r(func_get_args());
		die;
	}

	public static function compassPrefixedForTransition($prefix, $property)
	{
	}

	/**
	 * @return float
	 */
	public static function compassPi()
	{
		return pi();
	}

	/**
	 * @param float $number
	 * @return Number
	 */
	public static function compassSin($number)
	{
		return new Number(sin($number));
	}

	/**
	 * @param float $number
	 * @return Number
	 */
	public static function compassCos($number)
	{
		return new Number(sin($number));
	}

	/**
	 * @param float $number
	 * @return Number
	 */
	public static function compassTan($number)
	{
		return new Number(sin($number));
	}

# not sure what should happen with these

	/**
	 * @param string $path
	 * @param bool $only_path
	 * @return String
	 */
	public static function compassStylesheetUrl($path, $only_path=FALSE)
	{
		return self::compassUrl($path, $only_path);
	}

	/**
	 * @param string $path
	 * @param bool $only_path
	 * @return String
	 */
	public static function compassFontUrl($path, $only_path=FALSE)
	{
		return self::compassUrl($path, $only_path);
	}

	/**
	 * @param string $path
	 * @param bool $only_path
	 * @return String
	 */
	public static function compassImageUrl($path, $only_path=FALSE)
	{
		return self::compassUrl($path, $only_path);
	}

	/**
	 * @param string $path
	 * @param bool $only_path
	 * @param bool $web_path
	 * @return String
	 * @throws Exception
	 */
	public static function compassUrl($path, $only_path=FALSE, $web_path=TRUE)
	{
		$opath=$path;
		if (!$path=\PHPSass\File::get_file($path, \PHPSass\Parser::$instance, FALSE)) {
			throw new Exception('File not found: '.$opath);
			}

		$path=$path[0];
		if ($web_path) {
			$webroot=realpath($_SERVER['DOCUMENT_ROOT']);
			$path=str_replace($webroot, '', $path);
			}

		if ($only_path) {
			return new String($path);
			}

		return new String("url('$path')");
	}

	/**
	 * @param string $from
	 * @return string
	 */
	public static function compassOppositePosition($from)
	{
		switch ($from) {
			case 'top':
				return 'bottom';
			case 'bottom':
				return 'top';
			case 'right':
				return 'left';
			case 'left':
				return 'right';
			case 'center':
				return 'center';
			}
		return '';
	}

	/**
	 * @param Colour $colour
	 * @param Number $percentage
	 * @return Colour
	 */
	public static function compassShade($colour, $percentage)
	{
		if (!($colour instanceof Colour)) {
			$colour=new Colour($colour);
			}
		if (!($percentage instanceof Number)) {
			$percentage=new Number($percentage);
			}

		return Functions::mix(Functions::rgb(0, 0, 0), $colour, $percentage);
	}

	/**
	 * @param Colour $colour
	 * @param Number $percentage
	 * @return Colour
	 */
	public static function compassTint($colour, $percentage)
	{
		if (!($colour instanceof Colour)) {
			$colour=new Colour($colour);
			}
		if (!($percentage instanceof Number)) {
			$percentage=new Number($percentage);
			}

		return Functions::mix(Functions::rgb(255, 255, 255), $colour, $percentage);
	}
}
