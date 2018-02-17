<?php

class Machine extends AbstractObject {
    public static $vm = array(
        'hwvirtex',
//      'hwvirtexexcl',
        'vtxvpid',
        'nestedpaging',
        'largepages',
        'pagefusion',
        'synthcpu',
        'acpi',
        'ioapic',
        'pae',
        'hpet',
        'rtcuseutc',
        'accelerate2dvideo',
        'accelerate3d'
    );


    public function __construct($values = array()) {
        parent::__construct($values);
    }


    public function __get($name) {
        switch ($name) {
            // booleans
        case 'bioslogofadein':
        case 'bioslogofadeout':
        case 'acpi':
        case 'ioapic':
        case 'pae':
        case 'hpet':
        case 'rtcuseutc':
        case 'hwvirtex':
//      case 'hwvirtexexcl':
        case 'nestedpaging':
        case 'largepages':
        case 'pagefusion':
        case 'vtxvpid':
        case 'synthcpu':
        case 'accelerate2dvideo':
        case 'accelerate3d':
        case 'usb':
        case 'usbehci':
        case 'usbxhci':
        case 'vrde':
            return ($this->get($name, 'off') == 'on');

            // integers
        case 'cpus':
        case 'memory':
        case 'vram':
        case 'bioslogodisplaytime':
        case 'biossystemtimeoffset':
            return intval($this->get($name, '0'));

            // storage controllers
        case 'storage0':
        case 'storage1':
        case 'storage2':
        case 'storage3':
        case 'storage4':
        case 'storage5':
        case 'storage6':
        case 'storage7':
            $i = substr($name, 7);
            if (!$this->has('storagecontrollername'.$i)) {
                break;
            }
            $name = $this->get('storagecontrollername'.$i, '');
            $ports = intval($this->get('storagecontrollerportcount'.$i, '0'));
            $maxdevices = intval($this->get('storagecontrollermaxdeviceperport'.$i, '0'));
            $devices = array();
            for ($j = 0; $j < $ports; $j++) {
                for ($k = 0; $k < $maxdevices; $k++) {
                    $slot = $name.'-'.$j.'-'.$k;
                    if (!$this->has($slot)) {
                        continue;
                    }
                    if ($this->endsWith($slot, '.vdi') || $this->endsWith($slot, '.vmdk')) {
                        $devices[$j][$k] = Repository::getHdd($this->get($slot));
                    } else if ($this->get($slot) == 'emptydrive') {
                        $devices[$j][$k] = new DVD();
                    } else {
                        $devices[$j][$k] = Repository::getDvd($this->get($slot));
                    }
                }
            }
            return array(
                'name' => $name,
                'type' => $this->get('storagecontrollertype'.$i, 'none'),
                'instance' => intval($this->get('storagecontrollerinstance'.$i, '0')),
                'ports' => array(
                    'count' => $ports,
                    'min' => intval($this->get('storagecontrollerminportcount'.$i, '0')),
                    'max' => intval($this->get('storagecontrollermaxportcount'.$i, '0')),
                    'maxdevices' => $maxdevices
                ),
                'devices' => $devices
            );

            // fdd controllers
        case 'fd0':
        case 'fd1':
            $slot = 'FLOPPY-0-0';
            switch ($name) {
            case 'fd0':
                $slot = 'FLOPPY-0-0';
                break;
            case 'fd1':
                $slot = 'FLOPPY-0-1';
                break;
            }
            if (!$this->has($slot)) {
                break;
            }
            if ($this->get($slot) == 'emptydrive') {
                return new FDD();
            }
            return Repository::getFdd($this->get($slot));

            // ide controllers
        case 'ide0':
        case 'ide1':
        case 'ide2':
        case 'ide3':
            $slot = 'IDE-0-0';
            switch ($name) {
            case 'ide0':
                $slot = 'IDE-0-0';
                break;
            case 'ide1':
                $slot = 'IDE-0-1';
                break;
            case 'ide2':
                $slot = 'IDE-1-0';
                break;
            case 'ide3':
                $slot = 'IDE-1-1';
                break;
            }
            if (!$this->has($slot)) {
                break;
            }
            if ($this->endsWith($slot, '.vdi') || $this->endsWith($slot, '.vmdk')) {
                return Repository::getHdd($this->get($slot));
            }
            if ($this->get($slot) == 'emptydrive') {
                return new DVD();
            }
            return Repository::getDvd($this->get($slot));

            // sata controllers
        case 'sata0':
        case 'sata1':
        case 'sata2':
        case 'sata3':
        case 'sata4':
        case 'sata5':
        case 'sata6':
        case 'sata7':
        case 'sata8':
        case 'sata9':
        case 'sata10':
        case 'sata11':
        case 'sata12':
        case 'sata13':
        case 'sata14':
        case 'sata15':
        case 'sata16':
        case 'sata17':
        case 'sata18':
        case 'sata19':
        case 'sata20':
        case 'sata21':
        case 'sata22':
        case 'sata23':
        case 'sata24':
        case 'sata25':
        case 'sata26':
        case 'sata27':
        case 'sata28':
        case 'sata29':
            $i = substr($name, 4);
            $slot = 'SATA-'.$i.'-0';
            if (!$this->has($slot)) {
                break;
            }
            if ($this->endsWith($slot, '.vdi') || $this->endsWith($slot, '.vmdk')) {
                return Repository::getHdd($this->get($slot));
            }
            if ($this->get($slot) == 'emptydrive') {
                return new DVD();
            }
            return Repository::getDvd($this->get($slot));

            // network adapters
        case 'nic0':
        case 'nic1':
        case 'nic2':
        case 'nic3':
        case 'nic4':
        case 'nic5':
        case 'nic6':
        case 'nic7':
            $i = substr($name, 3) + 1;
            switch ($this->get('nic'.$i, 'null')) {
            case 'nat':
                return array(
                    'type' => 'nat',
                    'driver' => $this->get('nictype'.$i, ''),
                    'mac' => $this->get('macaddress'.$i, '000000000000'),
                    'mactext' => self::formatMAC($this->get('macaddress'.$i, '000000000000')),
                    'connected' => $this->get('cableconnected'.$i, 'off') == 'on',
                    'ip' => $this->get('/VirtualBox/GuestInfo/Net/'.($i - 1).'/V4/IP')
                );
            case 'bridged':
                return array(
                    'type' => 'bridged',
                    'driver' => $this->get('nictype'.$i, ''),
                    'mac' => $this->get('macaddress'.$i, '000000000000'),
                    'mactext' => self::formatMAC($this->get('macaddress'.$i, '000000000000')),
                    'adapter' => $this->get('bridgeadapter'.$i, DEFAULT_NET_ADAPTER),
                    'connected' => $this->get('cableconnected'.$i, 'off') == 'on',
                    'ip' => $this->get('/VirtualBox/GuestInfo/Net/'.($i - 1).'/V4/IP')
                );
            case 'intnet':
                return array(
                    'type' => 'intnet',
                    'driver' => $this->get('nictype'.$i, ''),
                    'mac' => $this->get('macaddress'.$i, '000000000000'),
                    'mactext' => self::formatMAC($this->get('macaddress'.$i, '000000000000')),
                    'net' => $this->get('intnet'.$i, 'intnet'),
                    'connected' => $this->get('cableconnected'.$i, 'off') == 'on',
                    'ip' => $this->get('/VirtualBox/GuestInfo/Net/'.($i - 1).'/V4/IP')
                );
            case 'hostonly':
                return array(
                    'type' => 'hostonly',
                    'driver' => $this->get('nictype'.$i, ''),
                    'mac' => $this->get('macaddress'.$i, '000000000000'),
                    'mactext' => self::formatMAC($this->get('macaddress'.$i, '000000000000')),
                    'adapter' => $this->get('hostonlyadapter'.$i, 'vboxnet0'),
                    'connected' => $this->get('cableconnected'.$i, 'off') == 'on',
                    'ip' => $this->get('/VirtualBox/GuestInfo/Net/'.($i - 1).'/V4/IP')
                );
            }
            return array(
                'type' => 'null',
                'connected' => 'off'
            );

            // network configuration
        case 'net0':
        case 'net1':
        case 'net2':
        case 'net3':
        case 'net4':
        case 'net5':
        case 'net6':
        case 'net7':
            $i = substr($name, 3);
            return array(
                'ip' => $this->get('/VirtualBox/GuestInfo/Net/'.$i.'/V4/IP', '0.0.0.0'),
                'broadcast' => $this->get('/VirtualBox/GuestInfo/Net/'.$i.'/V4/Broadcast', '0.0.0.0'),
                'netmask' => $this->get('/VirtualBox/GuestInfo/Net/'.$i.'/V4/Netmask', '255.255.255.255'),
                'status' => $this->get('/VirtualBox/GuestInfo/Net/'.$i.'/Status', 'Down') == 'Up',
            );

            // vrde
        case 'vrdeaddress':
            return $this->get('vrde.TCP/Address', '127.0.0.1');
        case 'vrdeport':
            return $this->get('vrde.TCP/Ports', rand(1025, 65534));

            // usb filters
        case 'usbfilters':
            $filters = [];
            for ($i = 1; $i <= intval($this->get('usbfilters', '0')); $i++) {
                $filters[] = array(
                    'index' => $i,
                    'name' => $this->get('usbfiltername'.$i, ''),
                    'vendorid' => intval($this->get('usbfiltervendorid'.$i, '0'), 16),
                    'productid' => intval($this->get('usbfilterproductid'.$i, '0'), 16),
                    'manufacturer' => $this->get('usbfiltermanufacturer'.$i, ''),
                    'product' => $this->get('usbfilterproduct'.$i, ''),
                    'serialnumber' => $this->get('usbfilterserialnumber'.$i, '')
                );
            }
            return $filters;

            // system
        case 'ready':
            return ($this->get('/cem/ready', 'false') == 'true');
        case 'product':
            return $this->get('/VirtualBox/GuestInfo/OS/Product');
        case 'additions':
            return ($this->get('/VirtualBox/GuestAdd/Version').'-'.$this->get('/VirtualBox/GuestAdd/Revision'));
        case 'os':
            return Repository::getOs($this->get('ostype'));
        }
        return parent::__get($name);
    }


    public function isStandardStorage() {
        return (
            $this->storage0 && $this->storage0['name'] == 'FLOPPY' &&
            $this->storage1 && $this->storage1['name'] == 'IDE' &&
            $this->storage2 && $this->storage2['name'] == 'SATA'
        );
    }


    public function exists() {
        return (strlen($this->path) > 0 && file_exists($this->path));
    }

    public function export() {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'export', $this->id, '--output', BASE_PATH.'/exports/'.$this->id.'.ova')) == 0) {
            return TRUE;
        }
        return FALSE;
    }

    public function boot($mode = 'headless') {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'startvm', $this->id, '--type', $mode)) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function pause() {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'controlvm', $this->id, 'pause')) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function freeze() {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'controlvm', $this->id, 'savestate')) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function resume() {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'controlvm', $this->id, 'resume')) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function reset() {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'controlvm', $this->id, 'reset')) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function shutdown() {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'controlvm', $this->id, 'acpipowerbutton')) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function poweroff($acpi = FALSE) {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'controlvm', $this->id, $acpi ? 'acpipowerbutton' : 'poweroff')) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function enableVRDE($enabled) {
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'controlvm', $this->id, 'vrde', $enabled ? 'on' : 'off')) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }

    public function destroy() {
        $this->set(
            array(
                'ide0' => NULL,
                'ide1' => NULL,
                'ide2' => NULL,
                'ide3' => NULL,
                'sata0' => NULL,
                'sata1' => NULL,
                'sata2' => NULL,
                'sata3' => NULL,
                'sata4' => NULL,
                'sata5' => NULL,
                'sata6' => NULL,
                'sata7' => NULL,
                'sata8' => NULL,
                'sata9' => NULL,
                'sata10' => NULL,
                'sata11' => NULL,
                'sata12' => NULL,
                'sata13' => NULL,
                'sata14' => NULL,
                'sata15' => NULL,
                'sata16' => NULL,
                'sata17' => NULL,
                'sata18' => NULL,
                'sata19' => NULL,
                'sata20' => NULL,
                'sata21' => NULL,
                'sata22' => NULL,
                'sata23' => NULL,
                'sata24' => NULL,
                'sata25' => NULL,
                'sata26' => NULL,
                'sata27' => NULL,
                'sata28' => NULL,
                'sata29' => NULL
            )
        );
        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'unregistervm', $this->id, '--delete')) == 0) {
            $this->loaded = FALSE;
            return TRUE;
        }
        return FALSE;
    }


    protected function onChange($values) {
        $changed = FALSE;
        $modifyvm = array('-q', 'modifyvm', $this->id);
        foreach ($values as $name => $value) {
            switch ($name) {
                // booleans
            case 'bioslogofadein':
            case 'bioslogofadeout':
            case 'acpi':
            case 'ioapic':
            case 'pae':
            case 'hpet':
            case 'rtcuseutc':
            case 'hwvirtex':
//          case 'hwvirtexexcl':
            case 'nestedpaging':
            case 'largepages':
            case 'pagefusion':
            case 'vtxvpid':
            case 'synthcpu':
            case 'accelerate2dvideo':
            case 'accelerate3d':
            case 'usb':
            case 'usbehci':
            case 'usbxhci':
            case 'vrde':
                $value = $value ? 'on' : 'off';
                if ($this->get($name, 'off') != $value) {
                    $modifyvm[] = '--'.$name;
                    $modifyvm[] = $value;
                }
                break;

                // integers
            case 'memory':
            case 'vram':
            case 'cpus':
            case 'bioslogodisplaytime':
            case 'biossystemtimeoffset':
                if (intval($this->get($name, '0')) != intval($value)) {
                    $modifyvm[] = '--'.$name;
                    $modifyvm[] = intval($value);
                }
                break;

                // strings
            case 'name':
            case 'ostype':
            case 'boot1':
            case 'boot2':
            case 'boot3':
            case 'boot4':
            case 'firmware':
            case 'biosbootmenu':
            case 'clipboard':
            case 'audio':
            case 'audiocontroller':
            case 'vrdeaddress':
            case 'vrdeport':
            case 'vrdeauthtype':
                if ($this->get($name) != $value) {
                    $modifyvm[] = '--'.$name;
                    $modifyvm[] = $value;
                }
                break;

                // fdd controllers
            case 'fd0':
            case 'fd1':
                $port = 0;
                $device = 0;
                switch ($name) {
                case 'fd0':
                    $port = 0;
                    $device = 0;
                    break;
                case 'fd1':
                    $port = 0;
                    $device = 1;
                    break;
                }
                if (is_a($value, 'FDD')) {
                    if ($value->exists()) {
                        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'FLOPPY', '--port', $port, '--device', $device, '--type', 'fdd', '--medium', $value->path)) == 0) {
                            $changed = TRUE;
                        }
                    } else {
                        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'FLOPPY', '--port', $port, '--device', $device, '--type', 'fdd', '--medium', 'emptydrive')) == 0) {
                            $changed = TRUE;
                        }
                    }
                } else {
                    $current = $this->$name;
                    if ($current) {
                        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'FLOPPY', '--port', $port, '--device', $device, '--medium', 'none')) == 0) {
                            $changed = TRUE;
                        }
                    }
                }
                break;

                // ide controllers
            case 'ide0':
            case 'ide1':
            case 'ide2':
            case 'ide3':
                $port = 0;
                $device = 0;
                switch ($name) {
                case 'ide0':
                    $port = 0;
                    $device = 0;
                    break;
                case 'ide1':
                    $port = 0;
                    $device = 1;
                    break;
                case 'ide2':
                    $port = 1;
                    $device = 0;
                    break;
                case 'ide3':
                    $port = 1;
                    $device = 1;
                    break;
                }
                if (is_a($value, 'HDD')) {
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'IDE', '--port', $port, '--device', $device, '--type', 'hdd', '--medium', $value->path)) == 0) {
                        $changed = TRUE;
                    }
                } else if (is_a($value, 'DVD')) {
                    if ($value->exists()) {
                        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'IDE', '--port', $port, '--device', $device, '--type', 'dvddrive', '--medium', $value->path)) == 0) {
                            $changed = TRUE;
                        }
                    } else {
                        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'IDE', '--port', $port, '--device', $device, '--type', 'dvddrive', '--medium', 'emptydrive')) == 0) {
                            $changed = TRUE;
                        }
                    }
                } else {
                    $current = $this->$name;
                    if ($current) {
                        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'IDE', '--port', $port, '--device', $device, '--medium', 'none')) == 0) {
                            $changed = TRUE;
                        }
                    }
                }
                break;

                // sata controllers
            case 'sata0':
            case 'sata1':
            case 'sata2':
            case 'sata3':
            case 'sata4':
            case 'sata5':
            case 'sata6':
            case 'sata7':
            case 'sata8':
            case 'sata9':
            case 'sata10':
            case 'sata11':
            case 'sata12':
            case 'sata13':
            case 'sata14':
            case 'sata15':
            case 'sata16':
            case 'sata17':
            case 'sata18':
            case 'sata19':
            case 'sata20':
            case 'sata21':
            case 'sata22':
            case 'sata23':
            case 'sata24':
            case 'sata25':
            case 'sata26':
            case 'sata27':
            case 'sata28':
            case 'sata29':
                $i = substr($name, 4);
                if (is_a($value, 'HDD')) {
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'SATA', '--port', $i, '--device', '0', '--type', 'hdd', '--medium', $value->path)) == 0) {
                        $changed = TRUE;
                    }
                } else if (is_a($value, 'DVD')) {
                    if ($value->exists()) {
                        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'SATA', '--port', $i, '--device', '0', '--type', 'dvddrive', '--medium', $value->path)) == 0) {
                            $changed = TRUE;
                        }
                    } else {
                        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'SATA', '--port', $i, '--device', '0', '--type', 'dvddrive', '--medium', 'emptydrive')) == 0) {
                            $changed = TRUE;
                        }
                    }
                } else {
                    $current = $this->$name;
                    if ($current) {
                        if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'storageattach', $this->id, '--storagectl', 'SATA', '--port', $i, '--device', '0', '--medium', 'none')) == 0) {
                            $changed = TRUE;
                        }
                    }
                }
                break;

                // network adapters
            case 'nic0':
            case 'nic1':
            case 'nic2':
            case 'nic3':
            case 'nic4':
            case 'nic5':
            case 'nic6':
            case 'nic7':
                $i = substr($name, 3) + 1;
                if (!isset($value['type'])) {
                    $value['type'] = 'null';
                }
                if (!isset($value['driver'])) {
                    $value['driver'] = 'Am79C973';
                }
                if (!isset($value['mac'])) {
                    $value['mac'] = 'auto';
                }
                if (!isset($value['connected'])) {
                    $value['connected'] = 'off';
                }
                switch ($value['type']) {
                case 'nat':
                    if (!isset($value['net'])) {
                        $value['net'] = '';
                    }
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'modifyvm', $this->id, '--nic'.$i, $value['type'], '--nictype'.$i, $value['driver'], '--macaddress'.$i, $value['mac'], '--natnet'.$i, $value['net'], '--cableconnected'.$i, $value['connected'])) == 0) {
                        $changed = TRUE;
                    }
                    break;

                case 'bridged':
                    if (!isset($value['adapter'])) {
                        $value['adapter'] = DEFAULT_NET_ADAPTER;
                    }
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'modifyvm', $this->id, '--nic'.$i, $value['type'], '--nictype'.$i, $value['driver'], '--macaddress'.$i, $value['mac'], '--bridgeadapter'.$i, $value['adapter'], '--cableconnected'.$i, $value['connected'])) == 0) {
                        $changed = TRUE;
                    }
                    break;

                case 'intnet':
                    if (!isset($value['net'])) {
                        $value['net'] = 'intnet';
                    }
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'modifyvm', $this->id, '--nic'.$i, $value['type'], '--nictype'.$i, $value['driver'], '--macaddress'.$i, $value['mac'], '--intnet'.$i, $value['net'], '--cableconnected'.$i, $value['connected'])) == 0) {
                        $changed = TRUE;
                    }
                    break;

                case 'hostonly':
                    if (!isset($value['adapter'])) {
                        $value['adapter'] = 'vboxnet0';
                    }
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'modifyvm', $this->id, '--nic'.$i, $value['type'], '--nictype'.$i, $value['driver'], '--macaddress'.$i, $value['mac'], '--hostonlyadapter'.$i, $value['adapter'], '--cableconnected'.$i, $value['connected'])) == 0) {
                        $changed = TRUE;
                    }
                    break;

                default:
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'modifyvm', $this->id, '--nic'.$i, 'null')) == 0) {
                        $changed = TRUE;
                    }
                    break;
                }
                break;

                // usb filters
            case 'usbfilters':
                $total = intval($this->get('usbfilters', '0'));
                for ($i = 0; $i < sizeof($value); $i++) {
                    $filter = $value[$i];
                    $args = array('-q', 'usbfilter', $i < $total ? 'modify' : 'add', $i, '--target', $this->id, '--name');
                    if (isset($filter['vendorid']) && isset($filter['productid'])) {
                        $args[] = sprintf('Device %04X:%04X', $filter['vendorid'], $filter['productid']);
                    } else {
                        $args[] = 'Filter '.$i;
                    }
                    if (isset($filter['vendorid']) && $filter['vendorid'] > 0) {
                        $args[] = '--vendorid';
                        $args[] = sprintf('%04X', $filter['vendorid']);
                    }
                    if (isset($filter['productid']) && $filter['productid'] > 0) {
                        $args[] = '--productid';
                        $args[] = sprintf('%04X', $filter['productid']);
                    }
                    if (isset($filter['manufacturer']) && strlen($filter['manufacturer']) > 0) {
                        $args[] = '--manufacturer';
                        $args[] = $filter['manufacturer'];
                    }
                    if (isset($filter['product']) && strlen($filter['product']) > 0) {
                        $args[] = '--product';
                        $args[] = $filter['product'];
                    }
                    if (isset($filter['serialnumber']) && strlen($filter['serialnumber']) > 0) {
                        $args[] = '--serialnumber';
                        $args[] = $filter['serialnumber'];
                    }
                    if (voidExec(VIRTUALBOX_MGT_BIN, $args) == 0) {
                        $changed = TRUE;
                    }
                }
                for ($j = $total - 1; $j >= $i; $j--) {
                    if (voidExec(VIRTUALBOX_MGT_BIN, array('-q', 'usbfilter', 'remove', $j, '--target', $this->id)) == 0) {
                        $changed = TRUE;
                    }
                }
                break;
            }
        }
        if (sizeof($modifyvm) > 3) {
            if (voidExec(VIRTUALBOX_MGT_BIN, $modifyvm) == 0) {
                $changed = TRUE;
            }
        }
        return $changed;
    }

    protected function onRefresh() {
        return Repository::visitVariables(
            new SimpleXMLElement(
                captureExec(VIRTUALBOX_XML_BIN, array('--base', BASE_PATH, '--machine', $this->values['id']))
            )
        );
    }


    private static function formatMAC($mac) {
        $text = '';
        for ($i = 0; $i < 6; $i++) {
            if ($i > 0) {
                $text .= ':';
            }
            $text .= substr($mac, $i * 2, 2);
        }
        return $text;
    }
}

?>