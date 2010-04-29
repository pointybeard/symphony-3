<?php

	Class fieldUser extends Field {
		function __construct(){
			parent::__construct();
			$this->_name = __('User');
		}

		public function create(){
			return Symphony::Database()->query(
				sprintf(
					'CREATE TABLE IF NOT EXISTS `tbl_data_%s_%s` (
						`id` int(11) unsigned NOT NULL auto_increment,
						`entry_id` int(11) unsigned NOT NULL,
						`user_id` int(11) unsigned NOT NULL,
						PRIMARY KEY  (`id`),
						KEY `entry_id` (`entry_id`),
						KEY `user_id` (`user_id`)
					)',
					$this->section,
					$this->{'element-name'}
				)
			);
		}

		public function isSortable(){
			return ($this->{'allow-multiple-selection'} == 'yes' ? false : true);
		}

		public function canFilter(){
			return true;
		}

		public function canImport(){
			return true;
		}

		public function canToggleData(){
			return ($this->{'allow-multiple-selection'} == 'yes' ? false : true);
		}

		public function allowDatasourceOutputGrouping(){
			## Grouping follows the same rule as toggling.
			return $this->canToggle();
		}

		/*-------------------------------------------------------------------------
			Utilities:
		-------------------------------------------------------------------------*/

		public function getToggleStates(){

		    $users = new UserIterator;

			$states = array();
			foreach($users as $u){
				$states[$u->id] = $u->getFullName();
			}

			return $states;
		}

		public function toggleEntryData(StdClass $data, $value, Entry $entry=NULL){
			$data['user_id'] = $newState;
			return $data;
		}

		/*-------------------------------------------------------------------------
			Settings:
		-------------------------------------------------------------------------*/

		public function findDefaultSettings(&$fields){
			if(!isset($fields['allow-multiple-selection'])) $fields['allow-multiple-selection'] = 'no';
		}

		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$document = $wrapper->ownerDocument;

			$options_list = $document->createElement('ul');
			$options_list->setAttribute('class', 'options-list');

			$this->appendShowColumnCheckbox($options_list);
			$this->appendRequiredCheckbox($options_list);

			## Allow multiple selection
			$label = Widget::Label(__('Allow selection of multiple users'));
			$input = Widget::Input('allow-multiple-selection', 'yes', 'checkbox');
			if($this->{'allow-multiple-selection'} == 'yes') $input->setAttribute('checked', 'checked');

			$label->prependChild($input);
			$item = $document->createElement('li');
			$item->appendChild($label);
			$options_list->appendChild($item);

			## Default to current logged in user
			$label = Widget::Label(__('Select current user by default'));
			$input = Widget::Input('default-to-current-user', 'yes', 'checkbox');
			if($this->{'default-to-current-user'} == 'yes') $input->setAttribute('checked', 'checked');

			$label->prependChild($input);
			$item = $document->createElement('li');
			$item->appendChild($label);
			$options_list->appendChild($item);

			$wrapper->appendChild($options_list);

		}

		/*-------------------------------------------------------------------------
			Publish:
		-------------------------------------------------------------------------*/

		public function prepareTableValue($data, DOMElement $link=NULL){

			if(!is_array($data)){
				$data = array($data);
			}

			$values = array();
			foreach($data as $d){
				$values[] = $d->user_id;
			}

			$fragment = Symphony::Parent()->Page->createDocumentFragment();

			foreach($values as $user_id){
				if(is_null($user_id)) continue;

				$user = User::load($user_id);

				if($user instanceof User){
					if($fragment->hasChildNodes()) $fragment->appendChild(new DOMText(', '));

					if(is_null($link)){
						$fragment->appendChild(
							Widget::Anchor(
								General::sanitize($user->getFullName()),
								ADMIN_URL . '/system/users/edit/' . $user->id . '/'
							)
						);
					}

					else {
						$link->setValue($user->getFullName());
						$fragment->appendChild($link);
					}
				}
			}

			return (!$fragment->hasChildNodes()) ? __('None') : $fragment;
		}

		public function displayPublishPanel(SymphonyDOMElement $wrapper, MessageStack $errors, Entry $entry = null, $data = null) {

			if(!is_array($data)){
				$data = array($data);
			}

			$selected = array();
			foreach($data as $d){
				if(!($d instanceof StdClass) || !isset($d->user_id)) continue;
				$selected[] = $d->user_id;
			}

			//$callback = Administration::instance()->getPageCallback();

			if ($this->{'default-to-current-user'} == 'yes' && is_null($data)) {
				$selected[] = Administration::instance()->User->id;
			}

		    $users = new UserIterator;

			$options = array();

			if($this->{'required'} == 'yes') {
				$options[] = array(null, false);
			}

			foreach($users as $u){
				$options[] = array($u->id, in_array($u->id, $selected), General::sanitize($u->getFullName()));
			}

			$fieldname = 'fields['.$this->{'element-name'}.']';
			if($this->{'allow-multiple-selection'} == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->label);
			$label->appendChild(Widget::Select($fieldname, $options,
				($this->{'allow-multiple-selection'} == 'yes') ? array('multiple' => 'multiple') : array()
			));

			if ($errors->valid()) {
				$label = Widget::wrapFormElementWithError($label, $errors->current()->message);
			}

			$wrapper->appendChild($label);
		}

		/*-------------------------------------------------------------------------
			Input:
		-------------------------------------------------------------------------*/

		public function processFormData($data, Entry $entry=NULL){

			$result = (object)array(
				'user_id' => NULL
			);

			$result->user_id = $data;

			return $result;
		}

		public function validateData(MessageStack $errors, Entry $entry = null, $data = null) {

			if(!is_array($data)) {
				$data = array($data);
			}

			if ($this->required == 'yes' && (!isset($data[0]->user_id) || strlen(trim($data[0]->user_id)) == 0)){
				$errors->append(
					null, (object)array(
					 	'message' => __("'%s' is a required field.", array($this->label)),
						'code' => self::ERROR_MISSING
					)
				);
				return self::STATUS_ERROR;
			}
			return self::STATUS_OK;
		}

		public function saveData(MessageStack $errors, Entry $entry, $data = null) {
			// Since we are dealing with multiple
			// values, must purge the existing data first
			Symphony::Database()->delete(
				sprintf('tbl_data_%s_%s', $entry->section, $this->{'element-name'}),
				array($entry->id),
				"`entry_id` = %s"
			);

			if(!is_array($data->user_id)){
				$data->user_id = array($data->user_id);
			}
			foreach($data->user_id as $d){
				$d = $this->processFormData($d, $entry);
				parent::saveData($errors, $entry, $d);
			}
			return Field::STATUS_OK;
		}

		/*-------------------------------------------------------------------------
			Output:
		-------------------------------------------------------------------------*/

		public function loadDataFromDatabase(Entry $entry, $expect_multiple = false){
			return parent::loadDataFromDatabase($entry, true);
		}

		public function appendFormattedElement(&$wrapper, $data, $encode=false){
	        if(!is_array($data['user_id'])) $data['user_id'] = array($data['user_id']);

	        $list = $wrapper->ownerDocument->createElement($this->{'element-name'});
	        foreach($data['user_id'] as $user_id){
	            $user = User::load($user_id);
	            $list->appendChild(
					$wrapper->ownerDocument->createElement('item', $user->getFullName(), array(
						'id' => $user->id,
						'username' => $user->username
					))
				);
	        }
	        $wrapper->appendChild($list);
	    }

		/*-------------------------------------------------------------------------
			Filtering:
		-------------------------------------------------------------------------*/

		//	TODO: This field will need to override the DatasourceFilterPanel so that you can actually filter
		//	users on their fields in the sym_users table. Once done, this buildDSRetrivalSQL will have to be updated
		//	to think use those columns. For now this just filters on USER_ID (which is Symphony 2.0.x behaviour)

		public function buildDSRetrivalSQL($filter, &$joins, &$where, $operation_type=DataSource::FILTER_OR) {
			self::$key++;

			$value = DataSource::prepareFilterValue($filter['value']);

			$joins .= sprintf('
				LEFT OUTER JOIN `tbl_data_%2$s_%3$s` AS t%1$s ON (e.id = t%1$s.entry_id)
			', self::$key, $this->section, $this->{'element-name'});

			if ($filter['type'] == 'regex') {
				$where .= sprintf("
						AND (
							t%1\$s.user_id REGEXP '%2\$s'
						)
					",	self::$key,	$value
				);
			}

			else if ($operation_type == DataSource::FILTER_AND) {
				foreach ($value as $v) {
					$where .= sprintf(
						" AND (
							t%1\$s.user_id %2\$s '%3\$s'
						) ",
						self::$key,
						$filter['type'] == 'is-not' ? '<>' : '=',
						$v
					);
				}

			}

			else {
				$where .= sprintf(
					" AND (
						t%1\$s.user_id %2\$s IN ('%3\$s')
					) ",
					self::$key,
					$filter['type'] == 'is-not' ? 'NOT' : NULL,
					implode("', '", $value)
				);
			}

			return true;
		}

		public function buildSortingSQL(&$joins, &$order){
			$joins = '
				LEFT OUTER JOIN `tbl_data_%1$s_%2$s` AS `ed` ON (e.id = ed.entry_id)
				JOIN `tbl_users` AS `u` ON (ed.user_id = u.id)
			';

			$order = 'u.first_name %1$s , u.last_name %1$s';
		}
	}

	return 'fieldUser';