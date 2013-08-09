<!-- Just load this in a browser and the tests will run! -->
<html>
	<head>
		<title>PHPSass Test Suite</title>
		<link rel="stylesheet" type="text/css" href="test.css">
	</head>
	<body>
		<?php
		/* Testing for Sassy.
		 *  Looks in tests* and compiles any .sass/.scss files
		 *  and compares to them to their twin .css files by
		 *  filename.
		 *
		 *  That is, if we have three files:
		 *     test.scss
		 *     test.sass
		 *     test.css
		 *
		 *  The tester will compile test.scss and test.sass seperately
		 *  and compare their outputs both to each other and to test.css
		 *
		 *  Testing is eased by stripping out all whitespace, which may
		 *  introduce bugs of their own.
		 */
		require_once 'vendor/autoload.php';

		$test_dir='./tests/files';

		$files=find_files($test_dir);
		foreach ($files['by_name'] as $name => $test) {
			if (isset($_GET['name']) && $name!=$_GET['name']) {
				continue;
				}
			if (isset($_GET['skip']) && $name && preg_match('/(^|,)('.preg_quote($name).')(,|$)/', $_GET['skip'])) {
				continue;
				}
			if (count($test)>1) {
				$result=test_files($test, $test_dir);

				if ($result===TRUE) {
					print "\n\t<p class='pass'><em>PASS</em> $name</p>";
					}
				else {
					print "\n\t<p class='fail'><em>FAIL</em> $name</p>";
					print "<pre>$result</pre>";
					}
				flush();
				}
			}

		/**
		 * @param array $files
		 * @param string $dir
		 * @return bool
		 */
		function test_files($files, $dir='.')
		{
			sort($files);
			$tmpdir=sys_get_temp_dir();
			if (substr($tmpdir, -1)==DIRECTORY_SEPARATOR) {
				$tmpdir=substr($tmpdir, 0, -1);
				}
			foreach ($files as $i => $file) {
				$name=explode('.', $file);
				$ext=array_pop($name);

				$fn='parse_'.$ext;
				if (function_exists($fn)) {
					try {
						$result=$fn($dir.'/'.$file);
						}
					catch (\Exception $e) {
						$result=$e->__toString();
						}
					file_put_contents($tmpdir.'/scss_test_'.$i, trim($result)."\n");
					}
				}

			$diff=exec('diff -ibwB '.escapeshellarg($tmpdir.'/scss_test_0').' '.escapeshellarg($tmpdir.'/scss_test_1'), $out);
			if (count($out)) {
				if (isset($_GET['full'])) {
					$out[]="\n\n\n".$result;
					}
				return implode("\n", $out);
				}
			return TRUE;
		}

		/**
		 * @param string $file
		 * @return string
		 */
		function parse_scss($file)
		{
			return __parse($file, 'scss');
		}

		/**
		 * @param string $file
		 * @return string
		 */
		function parse_sass($file)
		{
			return __parse($file, 'sass');
		}

		/**
		 * @param string $file
		 * @return string
		 */
		function parse_css($file)
		{
			return file_get_contents($file);
		}

		/**
		 * @param string $file
		 * @param string $syntax
		 * @param string $style
		 * @return string
		 */
		function __parse($file, $syntax, $style='nested')
		{
			$options=array(
				'style' => $style,
				'cache' => FALSE,
				'syntax' => $syntax,
				'debug' => FALSE,
				'callbacks' => array(
					'warn' => 'cb_warn',
					'debug' => 'cb_debug',
					),
				);
			// Execute the compiler.
			$parser=new \PHPSass\Parser($options);
			return $parser->toCss($file);
		}

		/**
		 * @param mixed $message
		 * @param \PHPSass\Context $context
		 */
		function cb_warn($message, $context)
		{
			print "<p class='warn'>WARN : ";
			print_r($message);
			print '</p>';
		}

		/**
		 * @param mixed $message
		 */
		function cb_debug($message)
		{
			print "<p class='debug'>DEBUG : ";
			print_r($message);
			print '</p>';
		}

		/**
		 * @param string $dir
		 * @return array
		 */
		function find_files($dir)
		{
			$op=opendir($dir);
			$return=array(
				'by_type' => array(),
				'by_name' => array()
				);
			if ($op) {
				while (FALSE!==($file=readdir($op))) {
					if (substr($file, 0, 1)=='.') {
						continue;
						}
					$name=explode('.', $file);
					$ext=array_pop($name);
					$return['by_type'][$ext]=$file;
					$name=implode('.', $name);
					if (!isset($return['by_name'][$name])) {
						$return['by_name'][$name]=array();
						}
					$return['by_name'][$name][]=$name.'.'.$ext;
					}
				}
			asort($return['by_name']);
			asort($return['by_type']);
			return $return;
		}
		?>
	</body>
</html>
