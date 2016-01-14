<?php
namespace PHPSass\Tree;

/**
 * ImportNode class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

use PHPSass\Script\Literals\SassString;

/**
 * ImportNode class.
 * Represents a CSS Import.
 */
class ImportNode
extends Node
{
	const IDENTIFIER='@';
	const MATCH='/^@import\s+(.+)/i';
	const MATCH_CSS='/^((url)\((.+)\)|.+" \w+|http|.+\.css$)/im';
	const FILES=1;

	/** @var array files to import */
	private $files=array();


	/**
	 * @param object $token source token
	 * @param Node $parent
	 */
	public function __construct($token, $parent)
	{
		parent::__construct($token);
		$this->parent=$parent;
		preg_match(self::MATCH, $token->source, $matches);

		foreach (\PHPSass\Script\Literals\SassList::_build_list($matches[self::FILES]) as $file) {
			$this->files[]=trim($file, '"\'; ');
			}
	}

	/**
	 * Parse this node.
	 * If the node is a CSS import return the CSS import rule.
	 * Else returns the rendered tree for the file.
	 *
	 * @param Context $context the context in which this node is parsed
	 * @return array the parsed node
	 */
	public function parse($context)
	{
		$imported=array();
		foreach ($this->files as $file) {
			if (preg_match(self::MATCH_CSS, $file, $matches)) {
				$file= (isset($matches[2]) && $matches[2]=='url')
					? $matches[1]
					: "url('$file')";

				return array(new SassString("@import $file;"), new SassString("\n"));
				}
			$file=trim($file, '\'"');
			$files=\PHPSass\File::get_file($file, $this->parser);
			$tree=array();
			if ($files) {
				if ($this->token->level>0) {
					$tree=$this->parent;
					while (get_class($tree)!='PHPSass\Tree\RuleNode' && get_class($tree)!='PHPSass\Tree\RootNode' && isset($tree->parent)) {
						$tree=$tree->parent;
						}
					$tree=clone $tree;
					$tree->children=array();
					}
				else {
					$tree=new RootNode($this->parser);
					$tree->extend_parent=$this->parent;
					}

				foreach ($files as $subfile) {
					if (preg_match(self::MATCH_CSS, $subfile)) {
						$tree->addChild(new SassString("@import url('$subfile');"));
						}
					else {
						$this->parser->filename=$subfile;
						$subtree=\PHPSass\File::get_tree($subfile, $this->parser);
						foreach ($subtree->getChildren() as $child) {
							$tree->addChild($child);
							}
						}
					}
				}
			if (!empty($tree)) {
				# parent may be either RootNode (returns an object) or RuleNode (returns an array of nodes)
				# so we parse then try get the children.
				$parsed=$tree->parse($context);
				if (!is_array($parsed) && isset($parsed->children)) {
					$parsed=$parsed->children;
					}
				if (is_array($parsed)) {
					$imported=array_merge($imported, $parsed);
					}
				}
			}

		return $imported;
	}
}
