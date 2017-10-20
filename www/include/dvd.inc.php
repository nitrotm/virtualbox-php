<?php

class DVD extends AbstractMediumObject {
    public function __construct($values = array()) {
        parent::__construct($values);
    }


    public function close() {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'closemedium', 'dvd', $this->path)) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function destroy() {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'closemedium', 'dvd', $this->path, '--delete')) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }


    protected function onChange($values) {
        // read-only
        return FALSE;
    }

    protected function onRefresh() {
        return Repository::visitVariables(
            new SimpleXMLElement(
                captureExec(VIRTUALBOX_XML_BIN, array('--base', BASE_PATH, '--dvd', $this->values['path']))
            )
        );
    }
}

?>