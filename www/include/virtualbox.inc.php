<?php

require_once('config.inc.php');
require_once('utils.inc.php');
require_once('objects.inc.php');
require_once('os.inc.php');
require_once('dvd.inc.php');
require_once('fdd.inc.php');
require_once('hdd.inc.php');
require_once('machine.inc.php');


class Repository {
    private static $system = array();
    private static $oses = array();
    private static $dvds = array();
    private static $fdds = array();
    private static $hdds = array();
    private static $machines = array();
    private static $ovas = array();


    public static function getSystem($key) {
        if (isset(self::$system[$key])) {
            return self::$system[$key];
        }
        return FALSE;
    }


    public static function listOses() {
        return array_values(self::$oses);
    }

    public static function getOs($id) {
        if (isset(self::$oses[$id])) {
            return self::$oses[$id];
        }
        return new OS(array('id' => $id));
    }


    public static function listDvds() {
        return array_values(self::$dvds);
    }

    public static function getDvd($path) {
        if (isset(self::$dvds[$path])) {
            return self::$dvds[$path];
        }
        self::$dvds[$path] = self::visitDvd(
            new SimpleXMLElement(
                captureExec(VIRTUALBOX_XML_BIN, array('--base', BASE_PATH, '--dvd', $path))
            )
        );
        return self::$dvds[$path];
    }


    public static function listFdds() {
        return array_values(self::$fdds);
    }

    public static function getFdd($path) {
        if (isset(self::$fdds[$path])) {
            return self::$fdds[$path];
        }
        self::$fdds[$path] = self::visitFdd(
            new SimpleXMLElement(
                captureExec(VIRTUALBOX_XML_BIN, array('--base', BASE_PATH, '--fdd', $path))
            )
        );
        return self::$fdds[$path];
    }


    public static function listHdds() {
        return array_values(self::$hdds);
    }

    public static function getHdd($path) {
        if (isset(self::$hdds[$path])) {
            return self::$hdds[$path];
        }
        self::$hdds[$path] = self::visitHdd(
            new SimpleXMLElement(
                captureExec(VIRTUALBOX_XML_BIN, array('--base', BASE_PATH, '--hdd', $path))
            )
        );
        return self::$hdds[$path];
    }

    public static function findHdd($id) {
        foreach (self::$hdds as $hdd) {
            if ($hdd->id == $id) {
                return $hdd;
            }
        }
        return new HDD();
    }

    public static function createHdd($size) {
        $path = BASE_PATH.'/'.time().'.vdi';
        if (file_exists($path)) {
            return FALSE;
        }
        voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'createhd', '--filename', $path, '--size', $size, '--variant', 'Standard'));
        return self::getHdd($path);
    }

    public static function cloneHdd($hdd, $path = FALSE) {
        if (!$path) {
            $path = BASE_PATH.'/'.time().'.vdi';
        }
        if (file_exists($path)) {
            return FALSE;
        }
        voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'clonehd', $hdd->path, $path, '--variant', 'Standard'));
        return self::getHdd($path);
    }


    public static function listMachines() {
        return array_values(self::$machines);
    }

    public static function getMachine($id) {
        if (isset(self::$machines[$id])) {
            return self::$machines[$id];
        }
        self::$machines[$id] = self::visitMachine(
            new SimpleXMLElement(
                captureExec(VIRTUALBOX_XML_BIN, array('--base', BASE_PATH, '--machine', $id))
            )
        );
        return self::$machines[$id];
    }

    public static function findMachine($path) {
        foreach (self::$machines as $machine) {
            if ($machine->path == $path) {
                return $machine;
            }
        }
        return new Machine();
    }

    public static function createMachine($name, $ostype) {
        $path = BASE_PATH.'/machine/'.$name;
        if (file_exists($path)) {
            return FALSE;
        }

        $lines = simpleExec(VIRTUALBOX_MGT_BIN, array('-q', 'createvm', '--name', $name, '--ostype', $ostype, '--register', '--basefolder', BASE_PATH.'/machine/'));
        foreach ($lines as $line) {
            if (preg_match('/^UUID: ([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})$/', $line, $matches)) {
                $machine = self::getMachine($matches[1]);
                if ($machine->id == $matches[1]) {
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storagectl', $machine->id, '--name', 'FLOPPY', '--add', 'floppy', '--controller', 'I82078')) != 0) {
                        $machine->destroy();
                        return FALSE;
                    }
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storagectl', $machine->id, '--name', 'IDE', '--add', 'ide', '--controller', 'PIIX4')) != 0) {
                        $machine->destroy();
                        return FALSE;
                    }
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storagectl', $machine->id, '--name', 'SATA', '--add', 'sata', '--controller', 'IntelAHCI', '--portcount', '1')) != 0) {
                        $machine->destroy();
                        return FALSE;
                    }
                }
                return $machine;
            }
        }
        return FALSE;
    }

    public static function importMachine($file) {
        $path = BASE_PATH.'/exports/'.$file;
        if (!file_exists($path)) {
            return FALSE;
        }

        $lines = simpleExec(VIRTUALBOX_MGT_BIN, array('-q', 'import', $path, '--options', 'importtovdi'));
        foreach ($lines as $line) {
            if (preg_match('/^UUID: ([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})$/', $line, $matches)) {
                return self::getMachine($matches[1]);
            }
        }
        return FALSE;
    }


    public static function listOVAs() {
        return array_values(self::$ovas);
    }


    public static function refresh() {
        list($system, $oses, $dvds, $fdds, $hdds, $machines) = self::visit(
            new SimpleXMLElement(captureExec(VIRTUALBOX_XML_BIN, array('--base', BASE_PATH)))
        );

        self::$system = $system;

        self::$oses = array();
        foreach ($oses as $os) {
            self::$oses[$os->id] = $os;
        }
        uasort(self::$oses, array('Repository', 'sortByName'));

        self::$dvds = array();
        foreach ($dvds as $dvd) {
            if (file_exists($dvd->path)) {
                self::$dvds[$dvd->path] = $dvd;
            } else {
                voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'closemedium', 'dvd', $dvd->id));
            }
        }
        uasort(self::$dvds, array('Repository', 'sortByName'));

        self::$fdds = array();
        foreach ($fdds as $fdd) {
            if (file_exists($fdd->path)) {
                self::$fdds[$fdd->path] = $fdd;
            } else {
                voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'closemedium', 'floppy', $fdd->id));
            }
        }
        uasort(self::$fdds, array('Repository', 'sortByName'));

        self::$hdds = array();
        foreach ($hdds as $hdd) {
            if (file_exists($hdd->path)) {
                self::$hdds[$hdd->path] = $hdd;
            } else {
                voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'closemedium', 'hdd', $hdd->id));
            }
        }
        uasort(self::$hdds, array('Repository', 'sortByName'));

        self::$machines = array();
        foreach ($machines as $machine) {
            if (file_exists($machine->path)) {
                self::$machines[$machine->id] = $machine;
            } else {
                voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'unregistervm', $machine->id));
            }
        }
        uasort(self::$machines, array('Repository', 'sortByName'));

        self::$ovas = array();
        self::visitExports(BASE_PATH.'/exports');
        sort(self::$ovas);
        return TRUE;
    }

    public static function visit($xml) {
        $system = array();
        $oses = array();
        $dvds = array();
        $fdds = array();
        $hdds = array();
        $machines = array();
        foreach ($xml->children() as $node) {
            switch ($node->getName()) {
            case 'system':
                foreach (self::visitVariables($node) as $key => $value) {
                    $system[$key] = $value;
                }
                break;

            case 'oses':
                foreach ($node->children() as $child) {
                    switch ($child->getName()) {
                    case 'os':
                        $oses[] = self::visitOs($child);
                        break;

                    default:
                        echo("<pre>Unsupported XML element: ".$child->asXML()."\n");
                        break;
                    }
                }
                break;

            case 'dvds':
                foreach ($node->children() as $child) {
                    switch ($child->getName()) {
                    case 'dvd':
                        $dvds[] = self::visitDvd($child);
                        break;

                    default:
                        echo("<pre>Unsupported XML element: ".$child->asXML()."\n");
                        break;
                    }
                }
                break;

            case 'fdds':
                foreach ($node->children() as $child) {
                    switch ($child->getName()) {
                    case 'fdd':
                        $fdds[] = self::visitFdd($child);
                        break;

                    default:
                        echo("<pre>Unsupported XML element: ".$child->asXML()."\n");
                        break;
                    }
                }
                break;

            case 'hdds':
                foreach ($node->children() as $child) {
                    switch ($child->getName()) {
                    case 'hdd':
                        $hdds[] = self::visitHdd($child);
                        break;

                    default:
                        echo("<pre>Unsupported XML element: ".$child->asXML()."\n");
                        break;
                    }
                }
                break;

            case 'machines':
                foreach ($node->children() as $child) {
                    switch ($child->getName()) {
                    case 'machine':
                        $machines[] = self::visitMachine($child);
                        break;

                    default:
                        echo("<pre>Unsupported XML element: ".$child->asXML()."\n");
                        break;
                    }
                }
                break;

            default:
                echo("<pre>Unsupported XML element: ".$node->asXML()."\n");
                break;
            }
        }
        return array($system, $oses, $dvds, $fdds, $hdds, $machines);
    }


    public static function visitOs($node) {
        return new OS(self::visitVariables($node));
    }

    public static function visitDvd($node) {
        return new DVD(self::visitVariables($node));
    }

    public static function visitFdd($node) {
        return new FDD(self::visitVariables($node));
    }

    public static function visitHdd($node) {
        return new HDD(self::visitVariables($node));
    }

    public static function visitMachine($node) {
        return new Machine(self::visitVariables($node));
    }

    public static function visitVariables($node) {
        $vars = array();
        foreach ($node->children() as $child) {
            switch ($child->getName()) {
            case 'variable':
                $vars[strval($child['key'])] = strval($child);
                break;

            default:
                echo("<pre>Unsupported XML element: ".$child->asXML()."\n");
                break;
            }
        }
        return $vars;
    }

    public static function visitExports($path, $subPath = '') {
        if (is_dir($path) && ($dh = opendir($path)) !== FALSE) {
            while (($item = readdir($dh)) !== FALSE) {
                $itemPath = strlen($subPath) > 0 ? $subPath.'/'.$item : $item;
                if (is_dir($path.'/'.$item) && $item != '.' && $item != '..') {
                    self::visitExports($path.'/'.$item, $itemPath);
                } else if (is_file($path.'/'.$item) && strrpos($item, '.ova') === strlen($item) - strlen('.ova')) {
                    self::$ovas[] = $itemPath;
                } else if (is_file($path.'/'.$item) && strrpos($item, '.ovf') === strlen($item) - strlen('.ovf')) {
                    self::$ovas[] = $itemPath;
                }
            }
            closedir($dh);
        }
    }


    public static function sortByName($a, $b) {
        return strcasecmp($a->name, $b->name);
    }
}

Repository::refresh();

function boolParam($key, $default = FALSE) {
    if (isset($_REQUEST[$key])) {
        return ($_REQUEST[$key] == 'on');
    }
    return $default;
}
function intParam($key, $default = 0) {
    if (isset($_REQUEST[$key])) {
        return intval($_REQUEST[$key]);
    }
    return $default;
}
function stringParam($key, $default = '') {
    if (isset($_REQUEST[$key])) {
        return strval($_REQUEST[$key]);
    }
    return $default;
}
function arrayParam($key, $default = array()) {
    if (isset($_REQUEST[$key])) {
        if (is_array($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        return array($_REQUEST[$key]);
    }
    return $default;
}
function fddParam($key) {
    if (isset($_REQUEST[$key])) {
        if (strlen($_REQUEST[$key]) > 0) {
            return Repository::getFdd($_REQUEST[$key]);
        }
        return new FDD();
    }
    return NULL;
}
function dvdParam($key) {
    if (isset($_REQUEST[$key])) {
        if (strlen($_REQUEST[$key]) > 0) {
            return Repository::getDvd($_REQUEST[$key]);
        }
        return new DVD();
    }
    return NULL;
}
function hddParam($key) {
    if (isset($_REQUEST[$key]) && strlen($_REQUEST[$key]) > 0) {
        return Repository::getHdd($_REQUEST[$key]);
    }
    return NULL;
}
function machineParam($key) {
    if (isset($_REQUEST[$key]) && strlen($_REQUEST[$key]) > 0) {
        return Repository::getMachine($_REQUEST[$key]);
    }
    return NULL;
}

?>