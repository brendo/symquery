<?php
	
	require_once TOOLKIT . '/class.entrymanager.php';
	require_once TOOLKIT . '/class.fieldmanager.php';
	require_once TOOLKIT . '/class.sectionmanager.php';
	
	require_once EXTENSIONS . '/symquery/lib/class.symquery.php';
	require_once EXTENSIONS . '/symquery/lib/class.symqueryresource.php';
	require_once EXTENSIONS . '/symquery/lib/class.symread.php';
	require_once EXTENSIONS . '/symquery/lib/class.symwrite.php';
	
	class Extension_SymQuery extends Extension {
		public function about() {
			return array(
				'name'			=> 'SymQuery',
				'version'		=> '0.1.0',
				'release-date'	=> '2010-02-01',
				'author'		=> array(
					'name'			=> '<a href="http://rowanlewis.com/">Rowan Lewis</a>, <a href="http://nick-dunn.co.uk/">Nick Dunn</a>',
				),
				'description' => 'SymQuery lets you build read and write queries for Symphony sections.'
			);
		}
	}
	
?>
