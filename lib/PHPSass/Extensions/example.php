<?php

/**
 * @param mixed $file
 * @param \PHPSass\Parser $parser
 * @return array
 */
function loadCallback($file, $parser)
{
    $paths=array();
    foreach ($parser->extensions as $extensionName) {
        $namespace=ucwords(preg_replace('/[^0-9a-z]+/', '_', strtolower($extensionName)));
        $extensionPath='./'.$namespace.'/'.$namespace.'.php';
        if (file_exists($extensionPath)) {
            require_once($extensionPath);
            $hook=$namespace.'::resolveExtensionPath';
            $returnPath=call_user_func($hook, $file, $parser);
            if (!empty($returnPath)) {
                $paths[]=$returnPath;
				}
	        }
		}

    return $paths;
}

/**
 * @param array $extensions
 * @return array
 */
function getFunctions($extensions)
{
    $output=array();
    if (!empty($extensions)) {
        foreach ($extensions as $namespace => $class) {
			$output=array_merge($output, call_user_func($class.'::getFunctions', $namespace));
			}
		}

    return $output;
}

$file='example.scss';
require_once '../../../vendor/autoload.php';

try {
	$options=array(
		'style' => 'expanded',
		'cache' => FALSE,
		'syntax' => 'scss',
		'debug' => FALSE,
		'debug_info' => FALSE,
		'load_path_functions' => array('loadCallback'),
		'load_paths' => array(dirname($file)),
		'functions' => getFunctions(array('Compass' => '\PHPSass\Extensions\Compass', 'Own' => '\PHPSass\Extensions\Own')),
		'extensions' => array('Compass', 'Own')
		);
	// Execute the compiler.
	$parser=new \PHPSass\Parser($options);
	print $parser->toCss($file);
	}
catch (\Exception $e) {
	print "body::before {
	  display: block;
	  padding: 5px;
	  white-space: pre;
	  font-family: monospace;
	  font-size: 8pt;
	  line-height: 17px;
	  overflow: hidden;
	  content: '".$e->getMessage()."';
	}";
	}
