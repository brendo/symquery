<?php
	
	function SymRead($section) {
		return new SymRead($section);
	}
	
	/**
	* SymRead.
	*/
	class SymRead extends SymQuery {
		protected function buildReadQuery(&$selects, &$wheres, &$joins) {
			$section = $this->section->get('object');
			
			// Find desired fields:
			foreach ($this->selects as $select) {
				$field = $select->get('object');
				
				if (!$field instanceof Field) continue;
				
				$field_handle = $field->get('element_name');
				
				if (in_array($field_handle, $selects)) continue;
				
				$selects[] = $field_handle;
			}
			
			// Build query:
			foreach ($this->wheres as $index => $where) {
				$current_wheres = $current_joins = null;
				$field = $where->get('object');
				$mode = $where->get('mode');
				$filter = $where->get('filter');
				$prefix = null;
				
				if ($index and $mode == SymQuery::FILTER_OR) {
					$prefix = 'OR';
				}
				
				else if ($index and $mode == SymQuery::FILTER_AND) {
					$prefix = 'AND';
				}
				
				if (!is_array($filter)) $filter = array($filter);
				
				// System ID:
				if ($field == SymQuery::SYSTEM_ID) {
					if (!empty($filter)) {
						$wheres .= sprintf(
							'%s e.id IN(%s) ', $prefix, implode(', ', $filter)
						);
					}
					
					// Selects nothing, but required for a valid query:
					else {
						$wheres .= sprintf(
							'%s e.id = 0 ', $prefix
						);
					}
				}
				
				// A real field:
				else {
					$field->buildDSRetrivalSQL($filter, $current_joins, $current_wheres, false);
					
					$current_wheres = sprintf(
						"%s (1 %s) ", $prefix, trim($current_wheres)
					);
					
					$joins .= $current_joins;
					$wheres .= $current_wheres;
				}
			}
			
			if ($wheres) $wheres = 'AND ' . $wheres;
		}
		
		public $wheres = array();
		public $selects = array();
		public $length = 20;
		public $page = 1;
		public $sort = null;
		
		/**
		* Specify a field to get
		* 
		* @param	$field		Field|String		Field object or name as a string.
		* @throws	SymQueryExeption
		*/
		public function get($field) {
			$this->selects[] = self::buildFieldReader($field, $this->section);
			
			return $this;
		}
		
		/**
		* Get all possible fields
		* 
		* @throws	SymQueryExeption
		*/
		public function getAll() {
			$section = $this->section->get('object');
			$this->selects = array();
			$fields = $section->fetchFields();
			
			if (is_array($fields)) foreach ($fields as $field) {
				$resource = new SymQueryResource();
				$resource->set('object', $field);
				
				$this->selects[] = $resource;
			}
			
			return $this;
		}
		
		/**
		* Clear fields to get
		*/
		public function getClear() {
			$this->selects = array();
			
			return $this;
		}
		
		/**
		* Filter agains a field
		* 
		* @param	$field		Field|String		Field object or name as a string.
		* @param	$filter		Mixed				Data to filter with.
		* @param	$mode		SymQuery::FILTER_*	Type of filter to use, AND or OR.
		* @throws	SymQueryExeption
		*/
		public function where($field, $filter, $mode = SymQuery::FILTER_AND) {
			$this->wheres[] = self::buildFieldFilter($field, $filter, $mode, $this->section);
			
			return $this;
		}
		
		/**
		* Clear filters to use
		*/
		public function whereClear() {
			$this->wheres = array();
			
			return $this;
		}
		
		/**
		* Order the results
		* 
		* @param	$field		Field|String		Field object or name as a string.
		* @param	$direction	String				Direction to sort, ASC or DESC.
		* @throws	SymQueryExeption
		*/
		public function orderBy($field, $direction) {
			$this->sort = self::buildFieldSorter($field, $direction, $this->section);
			
			return $this;
		}
		
		/**
		* Specify how many entries should be returned per page
		* 
		* @param	$length		Integer				How many entries are to appear per page.
		*/
		public function perPage($length) {
			$length = (integer)$length;
			
			if ($length < 1) $length = 1;
			
			$this->length = $length;
			
			return $this;
		}
		
		/**
		* Specify what page of entries to return
		* 
		* @param	$page		Integer				What page of entries to return.
		*/
		public function page($page) {
			$page = (integer)$page;
			
			if ($page < 1) $page = 1;
			
			$this->page = $page;
			
			return $this;
		}
		
		/**
		* Count matching entries
		* 
		* @throws	SymQueryExeption
		* @return	Integer
		*/
		public function count($result_distinct = true) {
			if (!$this->section instanceof SymQueryResource) {
				throw new SymQueryException('No section specified.');
			}
			
			$section = $this->section->get('object');
			$wheres = $joins = null;
			$selects = array();
			
			// Build the query:
			$this->buildReadQuery($selects, $wheres, $joins);
			
			return (integer)SymQuery::$em->fetchCount(
				$section->get('id'), $wheres, $joins
			);
		}
		
		/**
		* Read matching entries as an entry object iterator.
		* 
		* @return	SymReadResultEntryIterator
		* @throws	SymQueryExeption
		*/
		public function readEntryIterator() {
			return $this->read(new SymReadResultEntryIterator());
		}
		
		/**
		* Read matching entries as a raw data iterator.
		* 
		* @return	SymReadResultDataIterator
		* @throws	SymQueryExeption
		*/
		public function readDataIterator() {
			return $this->read(new SymReadResultDataIterator());
		}
		
		/**
		* Read matching entries as an XMLElement
		* 
		* @param	$root_element	String				The name of the root XML element.
		* @throws	SymQueryExeption
		* @return	SymReadResultXMLElement
		*/
		public function readXMLElement($root_element = 'symread') {
			return $this->read(new SymReadResultXMLElement($root_element));
		}
		
		/**
		* Read matching entries as a DOMDocument
		* 
		* @param	$root_element	String				The name of the root XML element.
		* @throws	SymQueryExeption
		* @return	SymReadResultDOMDocument
		*/
		public function readDOMDocument($root_element = 'symread') {
			return $this->read(new SymReadResultDOMDocument($root_element));
		}
		
		/**
		* Read matching entries
		* 
		* @param	$result_object	SymReadResult		An object that builds the results as a particular type.
		* @throws	SymQueryExeption
		* @param	SymReadResult
		*/
		public function read(SymReadResult $result_object, $result_distinct = true) {
			if (!$this->section instanceof SymQueryResource) {
				throw new SymQueryException('No section specified.');
			}
			
			$section = $this->section->get('object');
			$wheres = $joins = null;
			$selects = array();
			
			// Build the query:
			$this->buildReadQuery($selects, $wheres, $joins);
			
			// Configure sorting:
			SymQuery::$em->setFetchSorting('id', 'desc');
			
			if ($this->sort) {
				$sort_field = $this->sort->get('object');
				$sort_direction = $this->sort->get('direction');
				
				if ($sort_field == SymQuery::SYSTEM_ID) {
					$sort_field = 'id';
				}
				
				else if ($sort_field == SymQuery::SYSTEM_DATE) {
					$sort_field = 'date';
				}
				
				else if ($sort_field instanceof Field) {
					$sort_field = $sort_field->get('id');
				}
				
				SymQuery::$em->setFetchSortingField($sort_field);
				SymQuery::$em->setFetchSortingDirection($sort_direction);
			}
			
			$result = SymQuery::$em->fetchByPage(
				$this->page, $section->get('id'), $this->length,
				$wheres, $joins, $result_distinct, false, true, $selects
			);
			
			$entries = $result['records'];
			$pagination = array(
				'total-entries'	=> (int)$result['total-entries'], 
				'total-pages'	=> (int)$result['total-pages'], 
				'per-page'		=> (int)$result['limit'], 
				'current-page'	=> (int)$this->page
			);
			
			return $result_object->processResults($this, $section, $pagination, $entries);
		}
	}
	
	/**
	* SymReadResult
	*/
	interface SymReadResult {
		public function processResults(SymRead $query, Section $section, Array $pagination, Array $entries);
	}
	
	/**
	* Read entries as an XMLElement
	*/
	class SymReadResultXMLElement extends XMLElement implements SymReadResult {
		public function processResults(SymRead $query, Section $section, Array $pagination, Array $entries) {
			$section_xml = new XMLElement('section', $section->get('name'));
			$section_xml->setAttribute('id', $section->get('id'));
			$section_xml->setAttribute('handle', $section->get('handle'));
			$this->appendChild($section_xml);
			
			$this->appendChild(General::buildPaginationElement(
				$pagination['total-entries'],
				$pagination['total-pages'],
				$pagination['per-page'],
				$pagination['current-page']
			));
			
			foreach ($entries as $entry) {
				$entry_xml = new XMLElement('entry');
				$entry_xml->setAttribute('id', $entry->get('id'));
				
				foreach ($query->selects as $select) {
					$field = $select->get('object');
					
					if ($field == SymQuery::SYSTEM_DATE) {
						$entry_xml->appendChild(General::createXMLDateObject(
							strtotime($entry->creationDate), 'system-date'
						));
					}
					
					if (!$field instanceof Field) continue;
					
					$data = $entry->getData($field->get('id'));
					
					if ($select->has('mode')) {
						$field->appendFormattedElement($entry_xml, $data, false, $select->get('mode'));
					}
					
					else {
						$field->appendFormattedElement($entry_xml, $data, false);
					}
				}
				
				$this->appendChild($entry_xml);
			}
			
			return $this;
		}
	}
	
	/**
	* Read entries as a DOMDocument
	*/
	class SymReadResultDOMDocument extends DOMDocument implements SymReadResult {
		protected $root_element = null;
		
		public function __construct($root_element) {
			parent::__construct();
			
			$this->formatOutput = true;
			$this->root_element = $root_element;
		}
		
		public function processResults(SymRead $query, Section $section, Array $pagination, Array $entries) {
			$root_element = $this->createElement($this->root_element);
			$section_element = $this->createElement('section');
			$pagination_element = $this->createElement('pagination');
			
			// Build section information:
			$section_element->setAttribute('id', $section->get('id'));
			$section_element->setAttribute('handle', $section->get('handle'));
			$section_element->appendChild($this->createTextNode($section->get('name')));
			$root_element->appendChild($section_element);
			
			// Build pagination information:
			$pagination_element->setAttribute('total-entries', $pagination['total-entries']);
			$pagination_element->setAttribute('total-pages', $pagination['total-pages']);
			$pagination_element->setAttribute('entries-per-page', $pagination['entries-per-page']);
			$pagination_element->setAttribute('current-page', $pagination['current-page']);
			$root_element->appendChild($pagination_element);
			
			foreach ($entries as $entry) {
				$entry_xml = new XMLElement('entry');
				$entry_xml->setAttribute('id', $entry->get('id'));
				
				foreach ($query->selects as $select) {
					$field = $select->get('object');
					
					if ($field == SymQuery::SYSTEM_DATE) {
						$entry_xml->appendChild(General::createXMLDateObject(
							strtotime($entry->creationDate), 'system-date'
						));
					}
					
					if (!$field instanceof Field) continue;
					
					$data = $entry->getData($field->get('id'));
					
					if ($select->has('mode')) {
						$field->appendFormattedElement($entry_xml, $data, false, $select->get('mode'));
					}
					
					else {
						$field->appendFormattedElement($entry_xml, $data, false);
					}
				}
				
				$fragment = $this->createDocumentFragment();
				$fragment->appendXML($entry_xml->generate());
				
				$root_element->appendChild($fragment);
			}
			
			$this->appendChild($root_element);
			
			return $this;
		}
		
		public function toXMLElement() {
			$from = $this->documentElement;
			$to = new XMLElement($from->tagName);
			
			$this->toXMLElementLoop($from, $to);
			
			return $to;
		}
		
		protected function toXMLElementLoop($from, $to) {
			if ($from->attributes->length) foreach ($from->attributes as $attribute) {
				$to->setAttribute($attribute->name, General::sanitize($attribute->value));
			}
			
			if ($from->childNodes->length) foreach ($from->childNodes as $child) {
				if ($child instanceof DOMElement) {
					$current = new XMLElement($child->tagName);
					$this->toXMLElementLoop($child, $current);
					$to->appendChild($current);
				}
				
				else if ($child instanceof DOMText) {
					$to->setValue(General::sanitize($child->wholeText));
				}
			}
		}
	}
	
	/**
	* Read entries with an iterator
	*/
	class SymReadResultIterator extends SymQueryResource implements Iterator {
		protected $data = array();
		
		public function rewind() {
			reset($this->data['entries']);
		}
		
		public function current() {
			return current($this->data['entries']);
		}
		
		public function key() {
			return key($this->data['entries']);
		}
		
		public function next() {
			return next($this->data['entries']);
		}
		
		public function valid() {
			return $this->current() !== false;
		}
	}
	
	/**
	* Read entries as objects with an iterator
	*/
	class SymReadResultEntryIterator extends SymReadResultIterator implements SymReadResult {
		public function processResults(SymRead $query, Section $section, Array $pagination, Array $entries) {
			$this->data = array(
				'section'	=> array(
					'id'		=> (integer)$section->get('id'),
					'handle'	=> $section->get('handle'),
					'name'		=> $section->get('name')
				),
				'pagination'	=> $pagination,
				'entries'		=> $entries
			);
			
			return $this;
			exit;
		}
	}
	
	/**
	* Read entries as arrays with an iterator
	*/
	class SymReadResultDataIterator extends SymReadResultIterator implements SymReadResult {
		public function processResults(SymRead $query, Section $section, Array $pagination, Array $entries) {
			$this->data = array(
				'section'	=> array(
					'id'		=> (integer)$section->get('id'),
					'handle'	=> $section->get('handle'),
					'name'		=> $section->get('name')
				),
				'pagination'	=> $pagination,
				'entries'		=> array()
			);
			
			foreach ($entries as $entry) {
				$entry_id = (integer)$entry->get('id');
				$entry_date = strtotime($entry->creationDate);
				$entry_array = array();
				
				foreach ($query->selects as $select) {
					$field = $select->get('object');
					
					if ($field == SymQuery::SYSTEM_ID) {
						$entry_array[SymQuery::SYSTEM_ID] = $entry_id;
					}
					
					if ($field == SymQuery::SYSTEM_DATE) {
						$entry_array[SymQuery::SYSTEM_DATE] = array(
							'value'		=> DateTimeObj::get('c', $entry_date),
							'local'		=> strtotime(DateTimeObj::get('c', $entry_date)),
							'gmt'		=> strtotime(DateTimeObj::getGMT('c', $entry_date))
						);
					}
					
					else if ($field instanceof Field) {
						$field_id = (integer)$field->get('id');
						$field_handle = $field->get('element_name');
						
						$entry_array[$field_handle] = $entry->getData($field_id);
					}
				}
				
				$this->data['entries'][] = $entry_array;
			}
			
			return $this;
		}
	}

?>