<?php

class OS extends AbstractObject {
    public function __construct($values = array()) {
        parent::__construct($values);
    }


    public function __get($name) {
        switch ($name) {
        case 'name':
            return $this->description;
        }
        return parent::__get($name);
    }


    protected function onChange($values) {
        return FALSE;
    }

    protected function onRefresh() {
        return $this->values;
    }
}

?>