<?php

	class SymQueryResource {
		protected $data = array();

		public function del($key) {
			$this->has($key, true);

			unset($this->data[$key]);

			return $this;
		}

		public function has($key, $throw_exception = false) {
			$found = array_key_exists($key, $this->data);

			if ($throw_exception and !$found) {
				throw new Exception(sprintf(
					'Unable to find key %s.',
					var_export($key, true)
				));
			}

			return $found;
		}

		public function get($key) {
			$this->has($key, true);

			return $this->data[$key];
		}

		public function set($key, $value) {
			$this->data[$key] = $value;

			return $this;
		}
	}

?>