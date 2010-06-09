<?php

	class SymQuery {
		// Where modes:
		const FILTER_AND = 'and';
		const FILTER_OR = 'or';

		// Sort directions:
		const SORT_ASC = 'asc';
		const SORT_DESC = 'desc';

		// Special columns:
		const SYSTEM_ID = 'system:id';
		const SYSTEM_DATE = 'system:date';

		protected static $field_cache = array();
		protected static $section_cache = array();
		protected static $ready = false;
		protected static $db = null;
		protected static $em = null;
		protected static $fm = null;
		protected static $sm = null;
		protected static $symphony = null;

		/**
		* Prepare an object that stores the field and data to filter results with.
		*
		* @param	$field			Field|String		The field object or name to write to.
		* @param	$filter			Mixed				The field filter to use.
		* @param	$mode			SymQuery::FILTER_*	The filter mode to use.
		* @param	$section		SymQueryResource	The section to filter with.
		* @return	SymQueryResource
		*/
		protected static function buildFieldFilter($field, $filter, $mode, SymQueryResource $section) {
			$section = $section->get('object');
			$resource = new SymQueryResource();
			$resource->set('mode', $mode);
			$resource->set('filter', $filter);

			if (!$section instanceof Section) {
				throw new Exception('No section specified.');
			}

			if ($mode != self::FILTER_AND and $mode != self::FILTER_OR) {
				throw new Exception(sprintf(
					'Invalid mode %s for where statement.',
					var_export($mode, true)
				));
			}

			if ($field == self::SYSTEM_DATE) {
				throw new Exception(sprintf(
					'Invalid column %s for where statement.',
					var_export($field, true)
				));
			}

			if ($field instanceof Field) {
				$id = $field->get('id');

				if (isset(self::$field_cache[$id])) {
					$field = self::$field_cache[$id];
				}

				$resource->set('object', $field);
			}

			else if ($field == self::SYSTEM_ID) {
				$resource->set('object', $field);
			}

			else {
				$result = false;
				$section_id = $section->get('id');

				$id = is_int($field) ? $field : self::$fm->fetchFieldIDFromElementName($field, $section_id);

				if ($id > 0) {
					if (isset(self::$field_cache[$id])) {
						$result = self::$field_cache[$id];
					}
					else {
						$result = self::$fm->fetch($id, $section_id);
					}
				}

				if ($result === false) {
					throw new Exception(sprintf(
						'Unable to find field %s in section %s.',
						var_export($field, true),
						var_export($section->get('handle'), true)
					));
				}

				$resource->set('object', $result);
			}

			self::$field_cache[$id] = $resource->get('object');

			return $resource;
		}

		/**
		* Prepare an object that stores the field to read from.
		*
		* @param	$field			Field|String		The field object or name to read from.
		* @param	$section		SymQueryResource	The section to write with.
		* @return	SymQueryResource
		*/
		protected static function buildFieldReader($field, SymQueryResource $section) {
			$resource = new SymQueryResource();
			$section = $section->get('object');

			if (!$section instanceof Section) {
				throw new Exception('No section specified.');
			}

			if ($field instanceof Field) {
				$id = $field->get('id');

				if (isset(self::$field_cache[$id])) {
					$field = self::$field_cache[$id];
				}

				$resource->set('object', $field);
			}

			else if ($field == self::SYSTEM_ID or $field == self::SYSTEM_DATE) {
				$resource->set('object', $field);
			}

			else {
				$result = false;
				$section_id = $section->get('id');

				if(is_int($field)) {
					$id = $field;
				}
				else {
					$parts = preg_split('/:\s*/', $field, 2);
					$resource->set('mode', @$parts[1]);
					$name = $parts[0];

					$id = self::$fm->fetchFieldIDFromElementName($name, $section_id);
				}

				if ($id > 0) {
					if (isset(self::$field_cache[$id])) {
						$result = self::$field_cache[$id];
					}
					else {
						$result = self::$fm->fetch($id, $section_id);
					}
				}

				if ($result === false) {
					throw new Exception(sprintf(
						'Unable to find field %s in section %s.',
						var_export($field, true),
						var_export($section->get('handle'), true)
					));
				}

				$resource->set('object', $result);
			}

			self::$field_cache[$id] = $resource->get('object');

			return $resource;
		}

		/**
		* Prepare an object that stores the field to write to and data to write.
		*
		* @param	$field			Field|String		The field object or name to write to.
		* @param	$data			Mixed				The data to write to the field.
		* @param	$section		SymQueryResource	The section to write with.
		* @return	SymQueryResource
		*/
		protected static function buildFieldWriter($field, $data, SymQueryResource $section) {
			$section = $section->get('object');
			$resource = new SymQueryResource();
			$resource->set('data', $data);

			// TODO: Make sure data is validated against field:

			if (!$section instanceof Section) {
				throw new Exception('No section specified.');
			}

			if ($field == self::SYSTEM_DATE) {
				throw new Exception(sprintf(
					'Invalid column %s for where statement.',
					var_export($field, true)
				));
			}

			if ($field instanceof Field) {
				$id = $field->get('id');

				if (isset(self::$field_cache[$id])) {
					$field = self::$field_cache[$id];
				}

				$resource->set('object', $field);
			}

			else if ($field == self::SYSTEM_ID) {
				$resource->set('object', $field);
			}

			else {
				$result = false;
				$section_id = $section->get('id');

				$id = is_int($field) ? $field : self::$fm->fetchFieldIDFromElementName($field, $section_id);

				if ($id > 0) {
					if (isset(self::$field_cache[$id])) {
						$result = self::$field_cache[$id];
					}
					else {
						$result = self::$fm->fetch($id, $section_id);
					}
				}

				if ($result === false) {
					throw new Exception(sprintf(
						'Unable to find field %s in section %s.',
						var_export($field, true),
						var_export($section->get('handle'), true)
					));
				}

				$resource->set('object', $result);
			}

			self::$field_cache[$id] = $resource->get('object');

			return $resource;
		}

		/**
		* Prepare an object that stores the field to sort by.
		*
		* @param	$field			Field|String		The field object or name to sort by.
		* @param	$direction		SymQuery::SORT_*	The direction to sort.
		* @param	$section		SymQueryResource	The section to write with.
		* @return	SymQueryResource
		*/
		protected static function buildFieldSorter($field, $direction, SymQueryResource $section) {
			$section = $section->get('object');
			$resource = new SymQueryResource();
			$resource->set('direction', strtolower($direction));

			if (!$section instanceof Section) {
				throw new Exception('No section specified.');
			}

			if (strtolower($direction) != 'asc' and strtolower($direction) != 'desc') {
				throw new Exception(sprintf(
					'Invalid sort direction %s for field %s.',
					var_export($direction, true),
					var_export($field, true)
				));
			}

			if ($field instanceof Field) {
				$id = $field->get('id');

				if (isset(self::$field_cache[$id])) {
					$field = self::$field_cache[$id];
				}

				$resource->set('object', $field);
			}

			else if ($field == self::SYSTEM_ID) {
				$resource->set('object', $field);
			}

			else {
				$result = false;
				$section_id = $section->get('id');

				$id = is_int($field) ? $field : self::$fm->fetchFieldIDFromElementName($field, $section_id);

				if ($id > 0) {
					if (isset(self::$field_cache[$id])) {
						$result = self::$field_cache[$id];
					}
					else {
						$result = self::$fm->fetch($id, $section_id);
					}
				}

				if ($result === false) {
					throw new Exception(sprintf(
						'Unable to find field %s in section %s.',
						var_export($field, true),
						var_export($section->get('handle'), true)
					));
				}

				$resource->set('object', $result);
			}

			self::$field_cache[$id] = $resource->get('object');

			return $resource;
		}

		/**
		* Prepare an object that represents a Symphony section.
		*
		* @param	$section		Section|String		The section object or name to use.
		* @return	SymQueryResource
		*/
		protected static function buildSection($section) {
			$resource = new SymQueryResource();

			if ($section instanceof Section) {
				$id = $section->get('id');

				if (isset(self::$section_cache[$id])) {
					$section = self::$section_cache[$id];
				}

				$resource->set('object', $section);
			}

			else {
				$id = (is_int($section)) ? $section : self::$sm->fetchIDFromHandle($section);

				if ($id > 0) {
					if (isset(self::$section_cache[$id])) {
						$result = self::$section_cache[$id];
					}
					else {
						$result = self::$sm->fetch($id);
					}
				}

				if (is_null($result)) {
					throw new Exception(sprintf(
						'Unable to find section %s.', var_export($section, true)
					));
				}

				$resource->set('object', $result);
			}

			self::$section_cache[$id] = $resource->get('object');

			return $resource;
		}

		protected $section = null;

		public function __construct($section) {
			if (class_exists('Frontend')) {
				$symphony = Frontend::instance();
			}
			else {
				$symphony = Administration::instance();
			}

			if (!self::$ready) {
				self::$db = Symphony::Database();
				self::$em = new EntryManager($symphony);
				self::$fm = new FieldManager($symphony);
				self::$sm = new SectionManager($symphony);
				self::$symphony = $symphony;
			}

			$this->section = self::buildSection($section);
		}
	}

	class SymQueryException extends Exception {

	}

?>