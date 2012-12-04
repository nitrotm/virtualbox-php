<?php

abstract class AbstractObject {
	protected $values;
	protected $loaded;


	protected function __construct($values = array()) {
		$this->values = $values;
		$this->loaded = TRUE;
	}


	public function __isset($name) {
		return $this->has($name);
	}

	public function __get($name) {
		return $this->get($name, FALSE);
	}

	public function __set($name, $value) {
		if ($this->onChange(array($name => $value))) {
			$this->loaded = FALSE;
		}
	}


	public function set($values) {
		if ($this->onChange($values)) {
			$this->loaded = FALSE;
		}
	}


	protected function onChange($values) {
		$changed = FALSE;
		foreach ($values as $name => $value) {
			if ($this->values[$name] != $value) {
				$this->values[$name] = $value;
				$changed = TRUE;
			}
		}
		return $changed;
	}

	protected function onRefresh() {
		return $this->values;
	}


	protected function has($key, $value = NULL) {
		$this->refresh();

		if (isset($this->values[$key])) {
			if ($value !== NULL) {
				return ($this->values[$key] === $value);
			}
			return TRUE;
		}
		return FALSE;
	}

	protected function get($key, $default = '') {
		$this->refresh();

		if (isset($this->values[$key])) {
			return $this->values[$key];
		}
		return $default;
	}

	protected function startsWith($key, $prefix) {
		$value = $this->get($key);
		return (strpos($value, $prefix) === 0);
	}

	protected function endsWith($key, $suffix) {
		$value = $this->get($key);
		return (strrpos($value, $suffix) === strlen($value) - strlen($suffix));
	}

	protected function refresh() {
		if (!$this->loaded) {
			$this->loaded = TRUE;

			$this->values = $this->onRefresh();
		}
	}
}


abstract class AbstractMediumObject extends AbstractObject {
	protected function __construct($values = array()) {
		parent::__construct($values);
	}


	public function __get($name) {
		switch ($name) {
		case 'shortname':
			$value = $this->get('name');
			if (strpos($value, '.vdi') !== FALSE) {
				return substr($value, 0, strpos($value, '.vdi'));
			}
			return $value;

		case 'time':
			if ($this->has('path')) {
				return filemtime($this->get('path'));
			}
			return 0;

		case 'autoreset':
			return $this->get('autoreset', 'off');
		}
		return parent::__get($name);
	}


	public function exists() {
		return (strlen($this->path) > 0 && file_exists($this->path));
	}

	public abstract function close();

	public abstract function destroy();
}

?>