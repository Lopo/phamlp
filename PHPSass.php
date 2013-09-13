#!/usr/bin/env php
<?php
namespace PHPSass;

require_once __DIR__.'/vendor/autoload.php';

$stdin=$argc<2;
$stdout=$argc<3;
$args=array($argv[0]);

$parseOptions=function() use ($argc, $argv, &$stdin, &$stdout, &$args) {
	$options=array(
		'syntax' => File::SASS,
		'style' => Renderers\Renderer::STYLE_NESTED,
		'diskcache' => FALSE
		);
	foreach ($opts=getopt('st:qglI:ch', array('stdin', 'sass', 'scss', 'style:', 'quiet', 'debug-info', 'line-numbers', 'load-path:', 'diskcache', 'help')) as $arg => $val) {
		switch ($arg) {
			case 's':
			case 'stdin':
				$stdin=TRUE;
				break;
			case 'sass':
				$options['syntax']=File::SASS;
				break;
			case 'scss':
				$options['syntax']=File::SCSS;
				break;
			case 't':
			case 'style':
				$options['style']=$val;
				break;
			case 'q':
			case 'quiet':
				$options['quiet']=TRUE;
				break;
			case 'g':
			case 'debug-info':
				$options['debug_info']=TRUE;
				break;
			case 'l':
			case 'line-numbers':
				$options['line_numbers']=TRUE;
				break;
			case 'I':
			case 'load-path':
				$options['load_paths'][]=$val;
				break;
			case 'c':
			case 'diskcache':
				$options['disk_cache']=TRUE;
				break;
			case 'h':
			case 'help':
			default:
				echo <<<HELP
Usage: {$argv[0]} [options] [INPUT] [OUTPUT]

Description:
  Converts SCSS or Sass files to CSS.

Options:
    -s, --stdin                      Read input from standard input instead of an input file
        --sass                       Use the Indented syntax.
        --scss                       Use the CSS-superset SCSS syntax.
    -t, --style NAME                 Output style. Can be nested (default), compact, compressed, or expanded.
    -q, --quiet                      Silence warnings and status messages during compilation.
    -g, --debug-info                 Emit extra information in the generated CSS that can be used by the FireSass Firebug plugin.
    -l, --line-numbers               Emit comments in the generated CSS indicating the corresponding source line.
    -I, --load-path PATH             Add a sass import path.
    -h, --help                       Show this message

HELP;
				exit(0);
			}
		}

	$args=$argv;
	$pruneargv=array();
	foreach ($opts as $option => $value) {
		foreach ($args as $key => $chunk) {
			$regex='/^'.(isset($option[1])? '--' : '-').$option.'/';
			if ($chunk==$value && $args[$key-1][0]=='-' || preg_match($regex, $chunk)) {
				array_push($pruneargv, $key);
				}
			}
		}
	while ($key=array_pop($pruneargv)) {
		unset($args[$key]);
		}
	$args=array_merge($args);
	return $options;
	};

/**
 * Compile a Sass or SCSS string to CSS.
 * Defaults to SCSS.
 * 
 * @param string $contents The contents of the Sass file.
 * @param array $options
 * @return string
 */
function compile($contents, array $options=array())
{
	if (!isset($options['syntax'])) {
		$options['syntax']=\PHPSass\File::SCSS;
		}
	$engine=new \PHPSass\Parser($options);
	return $engine->toCss($contents, FALSE);
}

/**
 * Compile a Sass or SCSS string to CSS.
 * Defaults to SCSS.
 * 
 * @param string $filename The path to the Sass, SCSS, or CSS file on disk.
 * @param array $options
 * @return string
 */
function compileFile($filename, array $options=array())
{
	if (!is_readable($filename) && !is_file($filename)) {
		echo 'Errno::ENOENT: No such file or directory - '.$filename;
		exit(1);
		}
	if (!isset($options['syntax'])) {
		$options['syntax']=\PHPSass\File::SCSS;
		}
	$engine=new \PHPSass\Parser($options);
	return $engine->toCss($filename);
}

$context=$parseOptions();
if ($stdin || count($args)<2) {
	$input='';
	stream_set_blocking(STDIN, TRUE);
	while ($line=fgets(STDIN)) {
		$input.=$line;
		}
	$output=compile($input, $context);
	}
elseif (!$stdin) {
	$output=compileFile($args[1], $context);
	}

if (isset($args[2])) {
	file_put_contents($args[2], $output);
	exit(0);
	}
if ($stdin && isset($args[1])) {
	file_put_contents($args[1], $output);
	exit(0);
	}
echo $output;
