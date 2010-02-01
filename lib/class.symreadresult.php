<?php
	
	interface SymReadResult {
		public function processResults(SymRead $query, Section $section, Array $pagination, Array $entries);
	}
	
	class SymReadResultCount implements SymReadResult {
		public function processResults(SymRead $query, Section $section, Array $pagination, Array $entries) {
			
		}
	}
	
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
				$to->setAttribute($attribute->name, $attribute->value);
			}
			
			if ($from->childNodes->length) foreach ($from->childNodes as $child) {
				if ($child instanceof DOMElement) {
					$current = new XMLElement($child->tagName);
					$this->toXMLElementLoop($child, $current);
					$to->appendChild($current);
				}
				
				else if ($child instanceof DOMText) {
					$to->setValue($child->wholeText);
				}
			}
		}
	}
	
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