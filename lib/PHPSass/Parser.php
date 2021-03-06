<?php
namespace PHPSass;

/**
 * Parser class file.
 * See the {@link http://sass-lang.com/docs Sass documentation} for details of Sass.
 *
 * Credits:
 * This is a port of Sass to PHP. All the genius comes from the people that invented and develop Sass;
 * in particular:
 * + {@link http://hamptoncatlin.com/ Hampton Catlin},
 * + {@link http://nex-3.com/ Nathan Weizenbaum},
 * + {@link http://chriseppstein.github.com/ Chris Eppstein}
 *
 * The bugs are mine. Please report any found at {@link http://code.google.com/p/phamlp/issues/list}
 *
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Parser class.
 * Parses {@link http://sass-lang.com/ .sass and .sccs} files.
 */
class Parser
{
	/**#@+
	 * Default option values
	 */
	const BEGIN_COMMENT='/';
	const BEGIN_COMMENT_STRLEN=1;
	const BEGIN_CSS_COMMENT='/*';
	const BEGIN_CSS_COMMENT_STRLEN=2;
	const END_CSS_COMMENT='*/';
	const END_CSS_COMMENT_STRLEN=2;
	const BEGIN_SASS_COMMENT='//';
	const BEGIN_SASS_COMMENT_STRLEN=2;
	const BEGIN_INTERPOLATION='#';
	const BEGIN_INTERPOLATION_STRLEN=1;
	const BEGIN_INTERPOLATION_BLOCK='#{';
	const BEGIN_INTERPOLATION_BLOCK_STRLEN=2;
	const BEGIN_BLOCK='{';
	const BEGIN_BLOCK_STRLEN=1;
	const END_BLOCK='}';
	const END_BLOCK_STRLEN=1;
	const END_STATEMENT=';';
	const END_STATEMENT_STRLEN=1;
	const DOUBLE_QUOTE='"';
	const DOUBLE_QUOTE_STRLEN=1;
	const SINGLE_QUOTE="'";
	const SINGLE_QUOTE_STRLEN=1;

	/** @var Parser Static holder for last instance of a Parser */
	public static $instance;
	/**
	 * @var string the character used for indenting
	 * @see indentChars
	 * @see indentSpaces
	 */
	public $indentChar;
	/** @var array allowable characters for indenting */
	public $indentChars=[' ', "\t"];
	/**
	 * @var int number of spaces for indentation.
	 * Used to calculate {@link Level} if {@link indentChar} is space.
	 */
	public $indentSpaces=2;
	/** @var array source */
	public $source;
	/**#@+
	 * Option
	 */
	/** @var string */
	public $basepath;
	/**
	 * @var bool When TRUE the line number and file where a selector is defined
	 * is emitted into the compiled CSS in a format that can be understood by the
	 * {@link https://addons.mozilla.org/en-US/firefox/addon/103988/ FireSass Firebug extension}.
	 * Disabled when using the compressed output style.
	 *
	 * Defaults to FALSE.
	 * @see style
	 */
	public $debug_info;
	/**
	 * @var string The filename of the file being rendered.
	 * This is used solely for reporting errors.
	 */
	public $filename;
	/**
	 * function:
	 * @var array An array of (function_name => callback) items.
	 */
	public static $functions;
	/**
	 * @var int The number of the first line of the Sass template. Used for
	 * reporting line numbers for errors. This is useful to set if the Sass template is embedded.
	 *
	 * Defaults to 1.
	 */
	public $line;
	/**
	 * @var bool When TRUE the line number and filename where a selector is
	 * defined is emitted into the compiled CSS as a comment. Useful for debugging
	 * especially when using imports and mixins.
	 * Disabled when using the compressed output style or the debug_info option.
	 *
	 * Defaults to FALSE.
	 * @see debug_info
	 * @see style
	 */
	public $line_numbers;
	/**
	 * @var array An array of filesystem paths which should be searched for
	 * Sass templates imported with the @import directive.
	 *
	 * Defaults to './sass-templates'.
	 */
	public $load_paths;
	/** @var */
	public $load_path_functions;
	/**
	 * @var string Forces the document to use one syntax for
	 * properties. If the correct syntax isn't used, an error is thrown.
	 * Value can be:
	 * + new - forces the use of a colon or equals sign after the property name.
	 * For example   color: #0f3 or width: $main_width.
	 * + old -  forces the use of a colon before the property name.
	 * For example: :color #0f3 or :width = $main_width.
	 *
	 * By default, either syntax is valid.
	 *
	 * Ignored for SCSS files which alaways use the new style.
	 */
	public $property_syntax;
	/**
	 * @var bool When set to TRUE, causes warnings to be disabled.
	 * Defaults to FALSE.
	 */
	public $quiet;
	/**
	 * @var array listing callbacks for @warn and @debug directives.
	 * Callbacks are executed by call_user_func and thus must conform
	 * to that standard.
	 */
	public $callbacks;
	/**
	 * @var string the style of the CSS output.
	 * Value can be:
	 * + nested - Nested is the default Sass style, because it reflects the
	 * structure of the document in much the same way Sass does. Each selector
	 * and rule has its own line with indentation is based on how deeply the rule
	 * is nested. Nested style is very useful when looking at large CSS files as
	 * it allows you to very easily grasp the structure of the file without
	 * actually reading anything.
	 * + expanded - Expanded is the typical human-made CSS style, with each selector
	 * and property taking up one line. Selectors are not indented; properties are
	 * indented within the rules.
	 * + compact - Each CSS rule takes up only one line, with every property defined
	 * on that line. Nested rules are placed with each other while groups of rules
	 * are separated by a blank line.
	 * + compressed - Compressed has no whitespace except that necessary to separate
	 * selectors and properties. It's not meant to be human-readable.
	 *
	 * Defaults to 'nested'.
	 */
	public $style;
	/**
	 * @var string The syntax of the input file.
	 * 'sass' for the indented syntax and 'scss' for the CSS-extension syntax.
	 *
	 * This is set automatically when parsing a file, else defaults to 'sass'.
	 */
	public $syntax;
	/** @var int */
	private $_tokenLevel=0;
	/**
	 * If enabled it causes exceptions to be thrown on errors. This can be
	 * useful for tracking down a bug in your sourcefile but will cause a
	 * site to break if used in production unless the parser in wrapped in
	 * a try/catch structure.
	 *
	 * Defaults to FALSE
	 * @var bool
	 */
	public $debug=FALSE;
	/**
	 * If set, save compiled css to disk, in the directory specified by cache_location,
	 * only recompiling if the source file is newer than the cache.
	 * @var string
	 */
	public $cache_location=NULL;


	/**
	 * Sets parser options
	 *
	 * @param array $options
	 * @throws \PHPSass\Exception
	 */
	public function __construct($options=[])
	{
		if (!is_array($options)) {
			if (isset($options['debug']) && $options['debug']) {
				throw new Exception('Options must be an array');
				}
			$options= count((array)$options)
				? (array)$options
				: [];
			}
		unset($options['language']);

		$basepath=$_SERVER['PHP_SELF'];
		$basepath=substr($basepath, 0, strrpos($basepath, '/')+1);

		$defaultOptions=[
			'basepath' => $basepath,
			'debug_info' => FALSE,
			'filename' => ['dirname' => '', 'basename' => ''],
			'functions' => [],
			'load_paths' => [],
			'load_path_functions' => [],
			'line' => 1,
			'line_numbers' => FALSE,
			'style' => Renderers\Renderer::STYLE_NESTED,
			'syntax' => File::SASS,
			'debug' => FALSE,
			'quiet' => FALSE,
			'cache_location' => NULL,
			'callbacks' => [
				'warn' => FALSE,
				'debug' => FALSE,
				],
			];

		if (isset(self::$instance)) {
			$defaultOptions['load_paths']=self::$instance->load_paths;
			}

		// Ensure that the cache_location path includes a trailing slash
		if (!empty($options['cache_location']) && substr($options['cache_location'], -1)!='/') {
			$options['cache_location']=$options['cache_location'].'/';
			}

		$options=array_merge($defaultOptions, $options);

		// We don't want to allow setting of internal only property syntax value
		if (isset($options['property_syntax']) && $options['property_syntax']=='scss') {
			unset($options['property_syntax']);
			}

		self::$instance=$this;
		self::$functions=$options['functions'];
		unset($options['functions']);

		foreach ($options as $name => $value) {
			$this->$name=$value;
			}

		if (!$this->property_syntax && $this->syntax==File::SCSS) {
			$this->property_syntax='scss';
			}

		$GLOBALS['SassParser_debug']=$this->debug;
	}

	/**
	 * @param string $name of property to get
	 * @return mixed return value of getter function
	 * @throws \PHPSass\Exception
	 */
	public function __get($name)
	{
		$getter='get'.ucfirst($name);
		if (method_exists($this, $getter)) {
			return $this->$getter();
			}
		if (property_exists($this, $name)) {
			return $this->$name;
			}
		if ($this->debug) {
			throw new Exception('No getter function for '.$name);
			}
		return NULL;
	}

	/**
	 * @return string
	 */
	public function getBasepath()
	{
		return $this->basepath;
	}

	/**
	 * @return bool
	 */
	public function getDebug_info()
	{
		return $this->debug_info;
	}

	/**
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * @return int
	 */
	public function getLine()
	{
		return $this->line;
	}

	/**
	 * @return string
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * @return bool
	 */
	public function getLine_numbers()
	{
		return $this->line_numbers;
	}

	/**
	 * @return array
	 */
	public function getFunctions()
	{
		return self::$functions;
	}

	/**
	 * @return array
	 */
	public function getLoad_paths()
	{
		return $this->load_paths;
	}

	/**
	 * @return
	 */
	public function getLoad_path_functions()
	{
		return $this->load_path_functions;
	}

	/**
	 * @return string
	 */
	public function getProperty_syntax()
	{
		return $this->property_syntax;
	}

	/**
	 * @return bool
	 */
	public function getQuiet()
	{
		return $this->quiet;
	}

	/**
	 * @return string
	 */
	public function getStyle()
	{
		return $this->style;
	}

	/**
	 * @return string
	 */
	public function getSyntax()
	{
		return $this->syntax;
	}

	/**
	 * @return bool
	 */
	public function getDebug()
	{
		return $this->debug;
	}

	/**
	 * @return array
	 */
	public function getCallbacks()
	{
		return $this->callbacks+[
			'warn' => NULL,
			'debug' => NULL,
			];
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return [
			'callbacks' => $this->callbacks,
			// 'debug' => $this->debug,
			'filename' => $this->filename,
			'functions' => $this->getFunctions(),
			'line' => $this->getLine(),
			'line_numbers' => $this->getLine_numbers(),
			'load_path_functions' => $this->load_path_functions,
			'load_paths' => $this->load_paths,
			'property_syntax' => $this->property_syntax==File::SCSS? NULL : $this->property_syntax,
			'quiet' => $this->quiet,
			'style' => $this->style,
			'syntax' => $this->syntax,
			'cache_location' => $this->cache_location
			];
	}

	/**
	 * Parse a sass file or Sass source code and returns the CSS.
	 *
	 * @param string $source name of source file or Sass source
	 * @param bool $isFile
	 * @return string CSS
	 */
	public function toCss($source, $isFile=TRUE)
	{
		if (!empty($this->cache_location) && $isFile) {
			if (!file_exists($this->cache_location) && !mkdir($this->cache_location, 0755, TRUE)) {
				error_log("PHPSass: Could not create cache directory '{$this->cache_location}'");
				return; 
				}

			$cached_file=$this->cache_location.str_replace('/', '_', $source);
			if (file_exists($cached_file) && filemtime($source)<filemtime($cached_file)) {
				return file_get_contents($cached_file);
				}
			$result=$this->parse($source, $isFile)->render();
			file_put_contents($cached_file, $result);
			return $result;
			}
		return $this->parse($source, $isFile)->render();
	}

	/**
	 * Parse a sass file or Sass source code and returns the document tree that can then be rendered.
	 * The file will be searched for in the directories specified by the load_paths option.
	 *
	 * @param string $source name of source file or Sass source
	 * @param bool $isFile
	 * @return Tree\RootNode Root node of document tree
	 * @throws \PHPSass\Exception
	 */
	public function parse($source, $isFile=TRUE)
	{
		# Richard Lyon - 2011-10-25 - ignore unfound files
		# Richard Lyon - 2011-10-25 - add multiple files to load functions
		if (!$source) {
			return $this->toTree($source);
			}

		if (is_array($source)) {
			$return=NULL;
			foreach ($source as $key => $value) {
				if (is_numeric($key)) {
					$code=$value;
					$type=TRUE;
					}
				else {
					$code=$key;
					$type=$value;
					}
				if ($return===NULL) {
					$return=$this->parse($code, $type);
					}
				else {
					/** @var \PHPSass\Tree\Node $newnode */
					$newNode=$this->parse($code, $type);
					foreach ($newNode->children as $children) {
						array_push($return->children, $children);
						}
					}
				}

			return $return;
			}

		if ($isFile && $files=File::get_file($source, $this)) {
			$files_source='';
			foreach ($files as $file) {
				$this->filename=$file;
				$this->syntax=substr(strrchr($file, '.'), 1);
				if ($this->syntax==File::CSS) {
					$this->property_syntax='css';
					}
				elseif (!$this->property_syntax && $this->syntax==File::SCSS) {
					$this->property_syntax='scss';
					}

				if ($this->syntax!==File::SASS && $this->syntax!==File::SCSS && $this->syntax!==File::CSS) {
					if ($this->debug) {
						throw new Exception('Invalid {what}', ['{what}' => 'syntax option']);
						}

					return FALSE;
					}
				$files_source.=File::get_file_contents($this->filename);
				}

			return $this->toTree($files_source);
			}
		return $this->toTree($source);
	}

	/**
	 * Parse Sass source into a document tree.
	 * If the tree is already created return that.
	 *
	 * @param string $source Sass source
	 * @return Tree\RootNode the root of this document tree
	 */
	public function toTree($source)
	{
		if ($this->syntax===File::SASS) {
			$source=str_replace(["\r\n", "\n\r", "\r"], "\n", $source);
			$this->source=explode("\n", $source);
			$this->setIndentChar();
			}
		else {
			$this->source=$source;
			}
		unset($source);
		$root=new Tree\RootNode($this);
		$this->buildTree($root);

		if (!$this->_tokenLevel && $this->debug) {
			$message= $this->_tokenLevel<0
				? 'Too many closing brackets'
				: 'One or more missing closing brackets';
			throw new Exception($message, $this);
			}

		return $root;
	}

	/**
	 * Builds a parse tree under the parent node.
	 * Called recursivly until the source is parsed.
	 *
	 * @param Tree\Node $parent the node
	 * @return Tree\Node
	 */
	public function buildTree($parent)
	{
		$node=$this->getNode($parent);
		while (is_object($node) && $node->isChildOf($parent)) {
			$parent->addChild($node);
			$node=$this->buildTree($node);
			}

		return $node;
	}

	/**
	 * Creates and returns the next Node.
	 * The type of Node depends on the content of the Token.
	 *
	 * @param Tree\Node $node
	 * @return Tree\Node|NULL a Node of the appropriate type. NULL when no more source to parse.
	 */
	public function getNode($node)
	{
		$token=$this->getToken();
		if (empty($token)) {
			return NULL;
			}
		switch (TRUE) {
			case Tree\DirectiveNode::isa($token):
				return $this->parseDirective($token, $node);
			case Tree\CommentNode::isa($token):
				return new Tree\CommentNode($token);
			case Tree\VariableNode::isa($token):
				return new Tree\VariableNode($token);
			case Tree\PropertyNode::isa(['token' => $token, 'syntax' => $this->getProperty_syntax()]):
				return new Tree\PropertyNode($token, $this->property_syntax);
			case Tree\FunctionDefinitionNode::isa($token):
				return new Tree\FunctionDefinitionNode($token);
			case Tree\MixinDefinitionNode::isa($token):
				if ($this->syntax===File::SCSS) {
					if ($this->debug) {
						throw new Exception('Mixin definition shortcut not allowed in SCSS', $this);
						}
					return NULL;
					}
				return new Tree\MixinDefinitionNode($token);
			case Tree\MixinNode::isa($token):
				if ($this->syntax===File::SCSS) {
					if ($this->debug) {
						throw new Exception('Mixin include shortcut not allowed in SCSS', $this);
						}
					return NULL;
					}
				return new Tree\MixinNode($token);
			default:
				return new Tree\RuleNode($token);
			}
	}

	/**
	 * Returns a token object that contains the next source statement and meta data about it.
	 *
	 * @return object
	 */
	public function getToken()
	{
		return $this->syntax===File::SASS
			? $this->sass2Token()
			: $this->scss2Token();
	}

	/**
	 * Returns an object that contains the next source statement and meta data about it from SASS source.
	 * Sass statements are passed over. Statements spanning multiple lines, e.g.
	 * CSS comments and selectors, are assembled into a single statement.
	 *
	 * @return object Statement token. NULL if end of source.
	 * @throws \PHPSass\Exception
	 */
	public function sass2Token()
	{
		$statement=''; // source line being tokenised
		$token=NULL;

		while ($token===NULL && !empty($this->source)) {
			while (empty($statement) && is_array($this->source) && !empty($this->source)) {
				$source=array_shift($this->source);
				$statement=trim($source);
				$this->line++;
				}

			if (empty($statement)) {
				break;
				}

			$level=$this->getLevel($source);

			// Comment statements can span multiple lines
			if ($statement[0]===self::BEGIN_COMMENT) {
				// Consume Sass comments
				if (substr($statement, 0, self::BEGIN_SASS_COMMENT_STRLEN)===self::BEGIN_SASS_COMMENT) {
					unset($statement);
					while ($this->getLevel($this->source[0])>$level) {
						array_shift($this->source);
						$this->line++;
						}
					continue;
					}
				// Build CSS comments
				elseif (substr($statement, 0, self::BEGIN_CSS_COMMENT_STRLEN)===self::BEGIN_CSS_COMMENT) {
					while ($this->getLevel($this->source[0])>$level) {
						$statement.="\n".ltrim(array_shift($this->source));
						$this->line++;
						}
					}
				else {
					$this->source=$statement;

					if ($this->debug) {
						throw new Exception('Illegal comment type', $this);
						}
					}
				}
			// Selector statements can span multiple lines
			elseif (substr($statement, -1)===Tree\RuleNode::CONTINUED) {
				// Build the selector statement
				while ($this->getLevel($this->source[0])===$level) {
					$statement.=ltrim(array_shift($this->source));
					$this->line++;
					}
				}

			$token=(object)[
				'source' => $statement,
				'level' => $level,
				'filename' => $this->filename,
				'line' => $this->line-1,
				];
			}

		return $token;
	}

	/**
	 * Returns the level of the line.
	 * Used for .sass source
	 *
	 * @param string $source the source
	 * @return int the level of the source
	 * @throws \PHPSass\Exception if the source indentation is invalid
	 */
	public function getLevel($source)
	{
		$indent=strlen($source)-strlen(ltrim($source));
		$level=$indent/$this->indentSpaces;
		if (is_float($level)) {
			$level=(int)ceil($level);
			}
		if (!is_int($level) || preg_match("/[^{$this->indentChar}]/", substr($source, 0, $indent))) {
			$this->source=$source;

			if ($this->debug) {
				throw new Exception('Invalid indentation', $this);
				}
			return 0;
			}

		return $level;
	}

	/**
	 * Returns an object that contains the next source statement and meta data about it from SCSS source.
	 *
	 * @return object Statement token. NULL if end of source.
	 * @throws \PHPSass\Exception
	 */
	public function scss2Token()
	{
		static $srcpos=0; // current position in the source stream
		static $srclen; // the length of the source stream

		$statement='';
		$token=NULL;
		if (empty($srclen)) {
			$srclen=strlen($this->source);
			}
		while ($token===NULL && $srcpos<strlen($this->source)) {
			$c=$this->source[$srcpos++];
			switch ($c) {
				case self::BEGIN_COMMENT:
					if (substr($this->source, $srcpos-1, self::BEGIN_SASS_COMMENT_STRLEN)===self::BEGIN_SASS_COMMENT) {
						while ($this->source[$srcpos++]!=="\n") {
							if ($srcpos>=$srclen)
								throw new Exception('Unterminated commend', (object)[
									'source' => $statement,
									'filename' => $this->filename,
									'line' => $this->line,
									]);
							}
						$statement.="\n";
						}
					elseif (substr($this->source, $srcpos-1, self::BEGIN_CSS_COMMENT_STRLEN)===self::BEGIN_CSS_COMMENT) {
						if (ltrim($statement)) {
							if ($this->debug) {
								throw new Exception('Invalid comment', (object)[
									'source' => $statement,
									'filename' => $this->filename,
									'line' => $this->line,
									]);
								}
							}
						$statement.=$c.$this->source[$srcpos++];
						while (substr($this->source, $srcpos, self::END_CSS_COMMENT_STRLEN)!==self::END_CSS_COMMENT) {
							$statement.=$this->source[$srcpos++];
							}
						$srcpos+=self::END_CSS_COMMENT_STRLEN;
						$token=$this->createToken($statement.self::END_CSS_COMMENT);
						}
					else {
						$statement.=$c;
						}
					break;
				case self::DOUBLE_QUOTE:
				case self::SINGLE_QUOTE:
					$statement.=$c;
					while (isset($this->source[$srcpos]) && $this->source[$srcpos]!==$c) {
						$statement.=$this->source[$srcpos++];
						}
					if (isset($this->source[$srcpos+1])) {
						$statement.=$this->source[$srcpos++];
						}
					break;
				case self::BEGIN_INTERPOLATION:
					$statement.=$c;
					if (substr($this->source, $srcpos-1, self::BEGIN_INTERPOLATION_BLOCK_STRLEN)===self::BEGIN_INTERPOLATION_BLOCK) {
						while ($this->source[$srcpos]!==self::END_BLOCK) {
							$statement.=$this->source[$srcpos++];
							}
						$statement.=$this->source[$srcpos++];
						}
					break;
				case self::BEGIN_BLOCK:
				case self::END_BLOCK:
				case self::END_STATEMENT:
					$token=$this->createToken($statement.$c);
					if ($token===NULL) {
						$statement='';
						}
					break;
				default:
					$statement.=$c;
					break;
				}
			}

		if ($token===NULL) {
			$srclen= $srcpos= 0;
			}

		return $token;
	}

	/**
	 * Returns an object that contains the source statement and meta data about it.
	 * If the statement is just and end block we update the meta data and return null.
	 *
	 * @param string $statement source statement
	 * @return object|NULL
	 */
	public function createToken($statement)
	{
		$this->line+=substr_count($statement, "\n");
		$statement=trim($statement);
		if (substr($statement, 0, self::BEGIN_CSS_COMMENT_STRLEN)!==self::BEGIN_CSS_COMMENT) {
			$statement=str_replace(["\n", "\r"], '', $statement);
			}
		$last=substr($statement, -1);
		// Trim the statement removing whitespace, end statement (;), begin block ({), and (unless the statement ends in an interpolation block) end block (})
		$statement=rtrim($statement, ' '.self::BEGIN_BLOCK.self::END_STATEMENT);
		$statement= preg_match('/#\{.+?\}$/i', $statement)
			? $statement
			: rtrim($statement, self::END_BLOCK);
		$token= $statement
				? (object)[
					'source' => $statement,
					'level' => $this->_tokenLevel,
					'filename' => $this->filename,
					'line' => $this->line,
					]
				: NULL;
		$this->_tokenLevel+= $last===self::BEGIN_BLOCK
				? 1
				: ($last===self::END_BLOCK? -1 : 0);

		return $token;
	}

	/**
	 * Parses a directive
	 *
	 * @param stdClass $token to parse
	 * @param Tree\Node $parent node
	 * @return Tree\Node a Sass directive node
	 * @throws \PHPSass\Exception
	 */
	public function parseDirective($token, $parent)
	{
		switch (Tree\DirectiveNode::extractDirective($token)) {
			case '@content':
				return new Tree\ContentNode($token);
			case '@extend':
				return new Tree\ExtendNode($token);
			case '@function':
				return new Tree\FunctionDefinitionNode($token);
			case '@return':
				return new Tree\ReturnNode($token);
			case '@media':
			case '@supports':
				return new Tree\MediaNode($token);
			case '@mixin':
				return new Tree\MixinDefinitionNode($token);
			case '@include':
				return new Tree\MixinNode($token);
			case '@import':
				if ($this->syntax==File::SASS) {
					$i=0;
					$source='';
					while (sizeof($this->source)>$i && empty($source) && isset($this->source[$i+1])) {
						$source=$this->source[$i++];
						}
					if (!empty($source) && $this->getLevel($source)>$token->level) {
						if ($this->debug) {
							throw new Exception('Nesting not allowed beneath @import directive', $token);
							}
						}
					}

				return new Tree\ImportNode($token, $parent);
			case '@each':
				return new Tree\EachNode($token);
			case '@for':
				return new Tree\ForNode($token);
			case '@if':
				return new Tree\IfNode($token);
			case '@else': // handles else and else if directives
				return new Tree\ElseNode($token);
			case '@do':
			case '@while':
				return new Tree\WhileNode($token);
			case '@warn':
				return new Tree\WarnNode($token);
			case '@debug':
				return new Tree\DebugNode($token);
			default:
				return new Tree\DirectiveNode($token);
			}
	}

	/**
	 * Determine the indent character and indent spaces.
	 * The first character of the first indented line determines the character.
	 * If this is a space the number of spaces determines the indentSpaces; this
	 * is always 1 if the indent character is a tab.
	 * Only used for .sass files.
	 *
	 * @throws \PHPSass\Exception if the indent is mixed or the indent character can not be determined
	 */
	public function setIndentChar()
	{
		foreach ($this->source as $l => $source) {
			if (!empty($source) && in_array($source[0], $this->indentChars)) {
				$this->indentChar=$source[0];
				for ($i=0, $len=strlen($source); $i<$len && $source[$i]==$this->indentChar; $i++)
					;
				if ($i<$len && in_array($source[$i], $this->indentChars)) {
					$this->line=++$l;
					$this->source=$source;
					if ($this->debug) {
						throw new Exception('Mixed indentation not allowed', $this);
						}
					}
				$this->indentSpaces= $this->indentChar==' '
					? $i
					: 1;

				return;
				}
			}
		$this->indentChar=' ';
		$this->indentSpaces=2;
	}
}
