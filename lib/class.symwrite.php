<?php

	function SymWrite($section) {
		return new SymWrite($section);
	}

	class SymWrite extends SymQuery {
		protected $filterable = false;
		protected $writes = array();

		public function set($field, $data) {
			$this->writes[] = self::buildFieldWriter($field, $data, $this->section);

			return $this;
		}

		public function write() {
			$section = $this->section->get('object');
			$entry = null;
			$entry_data = array();

			//	If it's an existing entry we want to load that entry object
			foreach ($this->writes as $write) {
				$field = $write->get('object');
				$field_data = $write->get('data');

				if($field == SymQuery::SYSTEM_ID) {
					$existing = SymQuery::$em->fetch($field_data, $section->get('id'));

					if(is_array($existing) && !empty($existing)) {
						$entry = current($existing);
						break;
					}
				}
			}

			//	If $entry is null, then it's a new entry, so fill it with some default metadata
			if(is_null($entry)) {
				$entry = SymQuery::$em->create();
				$author = SymQuery::$symphony->Author;

				// Build default entry data:
				$entry->set('section_id', $section->get('id'));
				if (is_object($author)) $entry->set('author_id', $author->get('id'));
				$entry->set('creation_date', DateTimeObj::get('Y-m-d H:i:s'));
				$entry->set('creation_date_gmt', DateTimeObj::getGMT('Y-m-d H:i:s'));
			}

			foreach ($this->writes as $write) {
				$field = $write->get('object');
				$field_data = $write->get('data');

				if ($field instanceof Field) {
					$field_handle = $field->get('element_name');

					$entry_data[$field_handle] = $field_data;
				}
			}

			if (__ENTRY_FIELD_ERROR__ == $entry->checkPostData($entry_data, $errors, ($entry->get('id') ? true : false))) {
				$validation_errors = array();

				foreach($errors as $field_id => $message) {
					if(!in_array($field_id, SymQuery::$field_cache)) {
						SymQuery::$field_cache[$field_id] = SymQuery::$fm->fetch($field_id, $section->get('id'));
					}

					$validation_errors[$field_id] = array(
						'field' => SymQuery::$field_cache[$field_id],
						'error' => $message
					);
				}

				$error = new SymWriteException('Unable to validate entry.');
				$error->setValidationErrors($validation_errors);

				throw $error;
			}

			if (__ENTRY_OK__ != $entry->setDataFromPost($entry_data, $error, false, ($entry->get('id') ? true : false))) {
				throw new SymQueryException(sprintf(
					'Unable to save entry: %s', $error
				));
			}

			$entry->commit();

			return $entry;
		}
	}

	class SymWriteException extends SymQueryException {
		protected $validation_errors = array();

		/**
		* Get an array containing any validation errors
		*
		* @return	Array
		*/
		public function getValidationErrors() {
			return $this->validation_errors;
		}

		/**
		* Set the array of validation errors
		*
		* @param	$errors		Array
		*/
		public function setValidationErrors(Array $errors) {
			$this->validation_errors = $errors;
		}
	}

?>