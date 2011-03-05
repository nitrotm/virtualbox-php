<?php

class HDD extends AbstractMediumObject {
	public function __construct($values = array()) {
		parent::__construct($values);
	}


	public function __get($name) {
		switch ($name) {
		case 'size':
			return ($this->get($name, '0') / 1024 / 1024);

		case 'base':
			if ($this->has('base')) {
				return Repository::findHdd($this->get('base'));
			}
			return FALSE;

		case 'parent':
			if ($this->has('parent')) {
				return Repository::findHdd($this->get('parent'));
			}
			return FALSE;
		}
		return parent::__get($name);
	}


	public function duplicate($path = FALSE) {
		return Repository::cloneHdd($this, $path);
	}

	public function compact() {
		if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'modifyhd', $this->path, '--compact')) == 0) {
			$this->loaded = FALSE;
			return TRUE;
		}
		return FALSE;
	}

	public function close() {
		if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'closemedium', 'disk', $this->path)) == 0) {
			$this->loaded = FALSE;
			return TRUE;
		}
		return FALSE;
	}

	public function destroy() {
		if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'closemedium', 'disk', $this->path, '--delete')) == 0) {
			$this->loaded = FALSE;
			return TRUE;
		}
		return FALSE;
	}


	protected function onChange($values) {
		$modifyhd = array('-q', 'modifyhd', $this->path);
		foreach ($values as $name => $value) {
			switch ($name) {
			case 'type':
				if ($this->get($name) != $value) {
					$modifyhd[] = '--type';
					$modifyhd[] = $value;
				}
				break;

			case 'autoreset':
				$value = $value ? 'on' : 'off';
				if ($this->get($name, 'off') != $value) {
					$modifyhd[] = '--autoreset';
					$modifyhd[] = $value;
				}
				break;
			}
		}
		if (sizeof($modifyhd) > 3) {
			return (voidExec(VIRTUALBOX_MGT_BIN, $modifyhd) == 0);
		}
		return FALSE;
	}

	protected function onRefresh() {
		return Repository::visitVariables(
			new SimpleXMLElement(
				captureExec(VIRTUALBOX_XML_BIN, array('--base', BASE_PATH, '--hdd', $this->values['path']))
			)
		);
	}
}

?>