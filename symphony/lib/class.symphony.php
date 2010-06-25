<?php

	require_once('class.errorhandler.php');
                 
	require_once('class.dbc.php');
	require_once('class.configuration.php');
	require_once('class.datetimeobj.php');
	require_once('class.log.php');
	//require_once('class.cookie.php');
	require_once('interface.singleton.php');
	require_once('class.cache.php');
                 
	require_once('class.section.php');
	require_once('class.view.php');
	require_once('class.widget.php');
	require_once('class.general.php');
	//require_once('class.user.php');
	require_once('class.xslproc.php');
                 
	require_once('class.extension.php');

	Class SymphonyErrorPageHandler extends GenericExceptionHandler{
		public static function render($e){

			if(is_null($e->getTemplatePath())){
				header('HTTP/1.0 500 Server Error');
				echo '<h1>Symphony Fatal Error</h1><p>'.$e->getMessage().'</p>';
				exit;
			}

			$xml = new DOMDocument('1.0', 'utf-8');
			$xml->formatOutput = true;

			$root = $xml->createElement('data');
			$xml->appendChild($root);

			$root->appendChild($xml->createElement('heading', General::sanitize($e->getHeading())));
			$root->appendChild($xml->createElement('message', General::sanitize(
				$e->getMessageObject() instanceof SymphonyDOMElement ? (string)$e->getMessageObject() : trim($e->getMessage())
			)));
			if(!is_null($e->getDescription())){
				$root->appendChild($xml->createElement('description', General::sanitize($e->getDescription())));
			}

			header('HTTP/1.0 500 Server Error');
			header('Content-Type: text/html; charset=UTF-8');
			header('Symphony-Error-Type: ' . $e->getErrorType());

			foreach($e->getHeaders() as $header){
				header($header);
			}

			$output = parent::__transform($xml, basename($e->getTemplatePath()));

			header(sprintf('Content-Length: %d', strlen($output)));
			echo $output;

			exit;
		}
	}

	Class SymphonyErrorPage extends Exception{

		private $_heading;
		private $_message;
		private $_type;
		private $_headers;
		private $_messageObject;
		private $_help_line;

		public function __construct($message, $heading='Fatal Error', $description=NULL, array $headers=array()){

			$this->_messageObject = NULL;
			if($message instanceof SymphonyDOMElement){
				$this->_messageObject = $message;
				$message = (string)$this->_messageObject;
			}

			parent::__construct($message);

			$this->_heading = $heading;
			$this->_headers = $headers;
			$this->_description = $description;
		}

		public function getMessageObject(){
			return $this->_messageObject;
		}

		public function getHeading(){
			return $this->_heading;
		}

		public function getErrorType(){
			return $this->_template;
		}

		public function getDescription(){
			return $this->_description;
		}

		public function getTemplatePath(){

			$template = NULL;

			if(file_exists(MANIFEST . '/templates/exception.symphony.xsl')){
				$template = MANIFEST . '/templates/exception.symphony.xsl';
			}

			elseif(file_exists(TEMPLATES . '/exception.symphony.xsl')){
				$template = TEMPLATES . '/exception.symphony.xsl';
			}

			return $template;
		}

		public function getHeaders(){
			return $this->_headers;
		}
	}

	Abstract Class Symphony implements Singleton{

		public static $Log;

		protected static $Configuration;
		protected static $Database;

		protected static $_lang;
		protected static $_instance;

		protected function __construct(){

			self::$Configuration = new Configuration;

			DateTimeObj::setDefaultTimezone(self::Configuration()->core()->region->timezone);

			self::$_lang = (self::Configuration()->core()->symphony->lang ? self::Configuration()->core()->symphony->lang : 'en');

			define_safe('__SYM_DATE_FORMAT__', self::Configuration()->core()->region->{'date-format'});
			define_safe('__SYM_TIME_FORMAT__', self::Configuration()->core()->region->{'time-format'});
			define_safe('__SYM_DATETIME_FORMAT__', sprintf('%s %s', __SYM_DATE_FORMAT__, __SYM_TIME_FORMAT__));
			define_safe('ADMIN_URL', sprintf('%s/%s', URL, trim(self::Configuration()->core()->symphony->{'administration-path'}, '/')));

			$this->initialiseLog();

			GenericExceptionHandler::initialise();
			GenericErrorHandler::initialise(self::$Log);

			$this->initialiseDatabase();
			
			Extension::init();
			
			Cache::setDriver(self::Configuration()->core()->{'cache-driver'});

			Lang::loadAll(true);
			
			#### 
			# Delegate: SymphonyInitialisationComplete
			# Description: Symphony object has loaded and created everything else necessary
			Extension::notify('SymphonyInitialisationComplete', '*');

		}

		public function lang(){
			return self::$_lang;
		}

		public static function Configuration(){
			return self::$Configuration;
		}

		public static function Database(){
			return self::$Database;
		}

		public static function Parent() {
			if (class_exists('Administration')) {
				return Administration::instance();
			}

			else {
				return Frontend::instance();
			}
		}

		public function initialiseDatabase(){
			$details = (object)Symphony::Configuration()->db();

			$db = new DBCMySQLProfiler;

			if($details->runtime_character_set_alter == 'yes'){
				$db->character_encoding = $details->character_encoding;
				$db->character_set = $details->character_set;
			}

			$connection_string = sprintf('mysql://%s:%s@%s:%s/%s/',
											$details->user,
											$details->password,
											$details->host,
											$details->port,
											$details->db);

			$db->connect($connection_string);
			$db->prefix = $details->{'table-name-prefix'};

			$db->force_query_caching = NULL;
			if(!is_null($details->disable_query_caching)) $db->force_query_caching = ($details->disable_query_caching == 'yes' ? true : false);

			self::$Database = $db;

			return true;
		}

		public function initialiseLog(){

			self::$Log = new Log(ACTIVITY_LOG);
			self::$Log->setArchive((self::Configuration()->core()->log->archive == '1' ? true : false));
			self::$Log->setMaxSize(intval(self::Configuration()->core()->log->maxsize));

			if(self::$Log->open() == 1){
				self::$Log->writeToLog('Symphony Log', true);
				self::$Log->writeToLog('Version: '. self::Configuration()->core()->symphony->version, true);
				self::$Log->writeToLog('--------------------------------------------', true);
			}

		}

	}

	return 'Symphony';