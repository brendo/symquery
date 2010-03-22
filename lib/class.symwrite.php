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
			$author = SymQuery::$symphony->Author;
			$section = $this->section->get('object');
			$entry = SymQuery::$em->create();
			$entry_data = array();

			// Build default entry data:
			$entry->set('section_id', $section->get('id'));
			if (is_object($author)) $entry->set('author_id', $author->get('id'));
			$entry->set('creation_date', DateTimeObj::get('Y-m-d H:i:s'));
			$entry->set('creation_date_gmt', DateTimeObj::getGMT('Y-m-d H:i:s'));

			foreach ($this->writes as $write) {
				$field = $write->get('object');
				$field_data = $write->get('data');

				if ($field == SymQuery::SYSTEM_ID) {
					$entry->set('id', $field_data);
				}

				else if ($field instanceof Field) {
					$field_handle = $field->get('element_name');

					$entry_data[$field_handle] = $field_data;
				}
			}

			if (__ENTRY_FIELD_ERROR__ == $entry->checkPostData($entry_data, $errors)) {
				$error = new SymWriteException('Unable to validate entry.');
				$error->setValidationErrors($errors);

				throw $error;
			}

			if (__ENTRY_OK__ != $entry->setDataFromPost($entry_data, $error)) {
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