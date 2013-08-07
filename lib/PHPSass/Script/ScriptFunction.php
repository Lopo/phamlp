<?php
namespace PHPSass\Script;

/**
 * ScriptFunction class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

use PHPSass\Tree;

/**
 * ScriptFunction class.
 * Preforms a Script function.
 */
class ScriptFunction
{
	/**@#+
	 * Regexes for matching and extracting functions and arguments
	 */
	const MATCH='/^(((-\w)|(\w))[-\w:.]*)\(/';
	const MATCH_FUNC='/^((?:(?:-\w)|(?:\w))[-\w:.]*)\((.*)\)/';
	const SPLIT_ARGS='/\s*((?:[\'"].*?["\'])|(?:.+?(?:\(.*\).*?)?))\s*(?:,|$)/';
	const NAME=1;
	const ARGS=2;

	private $name;
	private $args;
	public static $context;


	/**
	 * @param string $name name of the function
	 * @param array $args arguments for the function
	 */
	public function __construct($name, $args)
	{
		$this->name=$name;
		$this->args=$args;
	}

	/**
	 * @param mixed $input
	 * @return string
	 */
	private function process_arguments($input)
	{
		if (is_array($input)) {
			$output=array();
			foreach ($input as $k => $token) {
				$output[$k]=trim($this->process_arguments($token), '\'"');
				}

			return $output;
			}

		$token=$input;
		if ($token===NULL) {
			return ' ';
			}
		if (!is_object($token)) {
			return (string)$token;
			}
		if (method_exists($token, 'toString')) {
			return $token->toString();
			}
		if (method_exists($token, '__toString')) {
			return $token->__toString();
			}
		if (method_exists($token, 'perform')) {
			return $token->perform();
			}

		return '';
	}

	/**
	 * Evaluates the function.
	 * Look for a user defined function first - this allows users to override
	 * pre-defined functions, then try the pre-defined functions.
	 *
	 * @return mixed Function the value of this Function
	 */
	public function perform()
	{
		self::$context=new Tree\Context(Parser::$context);

		$name=preg_replace('/[^a-z0-9_]/', '_', strtolower($this->name));
		$args=$this->process_arguments($this->args);

		foreach ($this->args as $k => $v) {
			if (!is_numeric($k)) {
				self::$context->setVariable($k, $v);
				}
			}

		try {
			if (Parser::$context->hasFunction($this->name)) {
				return Parser::$context->getFunction($this->name)->execute(Parser::$context, $this->args);
				}
			if (Parser::$context->hasFunction($name)) {
				return Parser::$context->getFunction($name)->execute(Parser::$context, $this->args);
				}
			}
		catch (\Exception $e) {
			throw $e;
			}

		if (isset(\PHPSass\Parser::$functions) && count(\PHPSass\Parser::$functions)) {
			foreach (\PHPSass\Parser::$functions as $fn => $callback) {
				if (($fn==$name || $fn==$this->name) && is_callable($callback)) {
					$result=call_user_func_array($callback, $args);
					if (!is_object($result)) {
						$lexed=Lexer::$instance->lex($result, self::$context);
						if (count($lexed)===1) {
							return $lexed[0];
							}

						return new Literals\String(implode('', $this->process_arguments($lexed)));
						}

					return $result;
					}
				}
			}

		if (method_exists('PHPSass\Script\Functions', $name) || method_exists('PHPSass\Script\Functions', $name='_'.$name)) {
			$sig=self::get_reflection(array('PHPSass\Script\Functions', $name));
			list($args)=self::fill_parameters($sig, $this->args, Parser::$context, $this);

			return call_user_func_array(array('PHPSass\Script\Functions', $name), $args);
			}

		foreach ($this->args as $i => $arg) {
			if (is_object($arg) && isset($arg->quote)) {
				$args[$i]=$arg->toString();
				}
			if (!is_numeric($i) && Parser::$context->hasVariable($i)) {
				$args[$i]=Parser::$context->getVariable($i);
				}
			}

		// CSS function: create a String that will emit the function into the CSS
		return new Literals\String($this->name.'('.join(', ', $args).')');
	}

	/**
	 * Imports files in the specified directory.
	 *
	 * @param string $dir path to directory to import
	 * @return array filenames imported
	 */
	private function import($dir)
	{
		$files=array();

		foreach (array_slice(scandir($dir), 2) as $file) {
			if (is_file($dir.DIRECTORY_SEPARATOR.$file)) {
				$files[]=$file;
				require_once($dir.DIRECTORY_SEPARATOR.$file);
				}
			}

		return $files;
	}

	/**
	 * Returns a value indicating if a token of this type can be matched at the start of the subject string.
	 *
	 * @param string $subject the subject string
	 * @return mixed match at the start of the string or FALSE if no match
	 */
	public static function isa($subject)
	{
		if (!preg_match(self::MATCH, $subject, $matches)) {
			return FALSE;
			}

		$match=$matches[0];
		$paren=1;
		$strpos=strlen($match);
		$strlen=strlen($subject);
		$subject_str=(string)$subject;

		while ($paren && $strpos<$strlen) {
			$c=$subject_str[$strpos++];

			$match.=$c;
			if ($c==='(') {
				$paren++;
				}
			elseif ($c===')') {
				$paren--;
				}
			}

		return $match;
	}

	/**
	 * @param string $string
	 * @param bool $include_null
	 * @param Context $context
	 * @return array
	 */
	public static function extractArgs($string, $include_null=TRUE, $context)
	{
		$args=array();
		$arg='';
		$paren= $strpos= 0;
		$strlen=strlen($string);

		$list=Literals\SassList::_build_list($string, ',');
		$return=array();

		foreach ($list as $k => $value) {
			if (substr($value, -3, 3)=='...' && preg_match(Tree\VariableNode::MATCH, substr($value, 0, -3).':', $match)) {
				$list=new Literals\SassList($context->getVariable($match[Tree\VariableNode::NAME]));
				if (count($list->value)>1) {
					$return=array_merge($return, $list->value);
					continue;
					}
				}

			if (strpos($value, ':')!==FALSE && preg_match(Tree\VariableNode::MATCH, $value, $match)) {
				$return[$match[Tree\VariableNode::NAME]]=$match[Tree\VariableNode::VALUE];
				}
			elseif (substr($value, 0, 1)=='$' && $include_null) {
				$return[str_replace('$', '', $value)]=NULL;
				}
			elseif ($include_null || $value!==NULL) {
				$return[]=$value;
				}
			}

		return $return;
	}

	/**
	 * @param mixed $method
	 * @return array
	 */
	public static function get_reflection($method)
	{
		if (is_array($method)) {
			$class=new \ReflectionClass($method[0]);
			$function=$class->getMethod($method[1]);
			}
		else {
			$function=new \ReflectionFunction($method);
			}

		$return=array();
		foreach ($function->getParameters() as $parameter) {
			$default= $parameter->isDefaultValueAvailable()
				? $parameter->getDefaultValue()
				: NULL;
			if ($default!==NULL) {
				$parsed=is_bool($default)
					? new Literals\Boolean($default)
					: Parser::$instance->evaluate($default, new Tree\Context);
				$parsed= $parsed===NULL
					? new Literals\String($default)
					: $parsed;
				}
			else {
				$parsed=$default;
				}
			$return[$parameter->getName()]=$parsed; # we evaluate the defaults to get Sass objects.
			}

		return $return;
	}

	/**
	 * @param array $required
	 * @param array $provided
	 * @param Tree\Context $context
	 * @param object $source
	 * @return array
	 * @throws Tree\MixinNodeException
	 */
	public static function fill_parameters($required, $provided, $context, $source)
	{
		$context=new Tree\Context($context);
		$_required=array_merge(array(), $required); // need to array_merge?
		$fill=$_required;

		foreach ($required as $name => $default) {
			// we require that named variables provide a default.
			if (isset($provided[$name]) && $default!==NULL) {
				$_required[$name]=$provided[$name];
				unset($provided[$name]);
				unset($required[$name]);
				}
			}

		// print_r(array($required, $provided, $_required));
		$provided_copy=$provided;

		foreach ($required as $name => $default) {
			if ($default===NULL && strpos($name, '=')!==FALSE) {
				list($name, $default)=explode('=', $name);
				$name=trim($name);
				$default=trim($default);
				}
			if (count($provided)) {
				$arg=array_shift($provided);
				}
			elseif ($default!==NULL) {
				$arg=$default;

				// for mixins with default values that refer to other arguments
				// (e.g. border-radius($topright: 0, $bottomright: $topright, $bottomleft: $topright, $topleft: $topright)
				if (is_string($default) && $default[0]=='$') {
					$referred=trim(trim($default, '$'));
					$pos=array_search($referred, array_keys($required));
					if ($pos!==FALSE && array_key_exists($pos, $provided_copy)) {
						$arg=$provided_copy[$pos];
						}
					}
				}
			else {
				throw new Tree\MixinNodeException("Function::$name: Required variable ($name) not given.\nFunction defined: ".$source->token->filename.'::'.$source->token->line."\nFunction used", $source);
				}
			// splats
			if (substr($name, -3, 3)=='...') {
				unset($_required[$name]);
				$name=substr($name, 0, -3);
				$_required[$name]=new Literals\SassList('', ',');
				$_required[$name]->value=array_merge(array($arg), $provided);
				continue;
				}
			else {
				$_required[$name]=$arg;
				}
			}

		$_required=array_merge($_required, $provided); // any remaining args get tacked onto the end

		foreach ($_required as $key => $value) {
			if (!is_object($value)) {
				$_required[$key]=Parser::$instance->evaluate($value, $context);
				}
			}

		return array($_required, $context);
	}
}
