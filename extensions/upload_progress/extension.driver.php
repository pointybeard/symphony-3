<?php

	class Extension_Upload_Progress implements iExtension {
		public function about() {
			return (object)array(
				'name'			=> 'Upload Progress',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-04-20',
				'author'		=> (object)array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description'	=> 'Add an upload progress indicator to the bottom of each page.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/administration/',
					'delegate'	=> 'AdminPagePreGenerate',
					'callback'	=> 'adminPagePreGenerate'
				)
			);
		}
		
		public function adminPagePreGenerate() {
			$page = Symphony::Parent()->Page;
			$page->insertNodeIntoHead($page->createStylesheetElement(URL . '/extensions/upload_progress/assets/publish.css'));
			$page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/upload_progress/assets/publish.js'));
		}
	}
	
	return 'Extension_Upload_Progress';