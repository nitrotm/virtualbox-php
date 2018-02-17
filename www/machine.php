<?

require_once('include/virtualbox.inc.php');

// find machine
$machine = machineParam('machine');
if (!$machine || !$machine->exists()) {
    header('Location: index.php');
    exit;
}

// perform actions
switch (stringParam('op')) {
case 'boot':
    if ($machine->boot('headless')) {
        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;

case 'pause':
    if ($machine->pause()) {
        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;

case 'freeze':
    if ($machine->freeze()) {
        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;

case 'resume':
    if ($machine->resume()) {
        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;

case 'reboot':
    if ($machine->reset()) {
        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;

case 'acpipowerbutton':
    if ($machine->poweroff(TRUE)) {
        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;

case 'poweroff':
    if ($machine->poweroff(FALSE)) {
        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;

case 'destroy':
    for ($i = 0; $i < 2; $i++) {
        $slot = 'fd'.$i;
        $fdd = $machine->$slot;
        $machine->$slot = NULL;
    }
    for ($i = 0; $i < 4; $i++) {
        $slot = 'ide'.$i;
        $ide = $machine->$slot;
        $machine->$slot = NULL;
        if (is_a($ide, 'HDD')) {
            if ($ide->type != 'multiattach') {
                $ide->destroy();
            }
        }
    }
    for ($i = 0; $i < 30; $i++) {
        $slot = 'sata'.$i;
        $sata = $machine->$slot;
        $machine->$slot = NULL;
        if (is_a($sata, 'HDD')) {
            if ($sata->type != 'multiattach') {
                $sata->destroy();
            }
        }
    }
    if ($machine->destroy()) {
        header('Location: index.php');
        exit;
    }

case 'set':
    // mount
    foreach (arrayParam('slot') as $slot) {
        switch (stringParam($slot)) {
        case 'dvd':
            $machine->$slot = dvdParam('dvd_'.$slot);
            break;
        case 'fdd':
            $machine->$slot = fddParam('fdd_'.$slot);
            break;
        case 'hdd':
            $machine->$slot = hddParam('hdd_'.$slot);
            break;
        default:
            $machine->$slot = NULL;
            break;
        }
    }

    // usb filters
    $usbFilters = [];
    foreach (arrayParam('usbfilter') as $filter) {
        $ids = explode(':', $filter);
        $usbFilters[] = array(
            'vendorid' => intval($ids[0], 16),
            'productid' => intval($ids[1], 16)
        );
    }
    $machine->usbfilters = $usbFilters;

    if ($machine->state == 'poweroff') {
        // network
        foreach (arrayParam('net') as $net) {
            $machine->$net = array(
                'type' => stringParam($net.'_type', 'null'),
                'driver' => stringParam($net.'_driver', '82543GC'),
                'mac' => str_replace(':', '', stringParam($net.'_mac', 'auto')),
                'adapter' => stringParam($net.'_adapter', FALSE),
                'connected' => 'on'
            );
        }

        // options
        $values = array(
            'name' => stringParam('name', 'vbox-'.time()),
            'ostype' => stringParam('ostype'),
            'cpus' => intParam('cpus', 1),
            'memory' => intParam('memory', 512),
            'audio' => stringParam('audio'),
            'audiocontroller' => stringParam('audiocontroller'),
            'usb' => boolParam('usb'),
            'usbehci' => boolParam('usbehci'),
            'usbxhci' => boolParam('usbxhci'),
            'vram' => intParam('vram', 12),
            'vrde' => boolParam('vrde'),
            'vrdeaddress' => stringParam('vrdeaddress', '0.0.0.0'),
            'vrdeport' => intParam('vrdeport', 3389)
        );
        foreach (Machine::$vm as $name) {
            $values[$name] = boolParam($name);
        }
        $machine->set($values);
    } else {
        if ($machine->enableVRDE(boolParam('vrde'))) {
            header('Location: machine.php?machine='.$machine->id);
            exit;
        }
    }
    break;

case 'export':
    if ($machine->export()) {
        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;

case 'extract':
    $slot = stringParam('slot');
    $hdd = $machine->$slot;
    if (is_a($hdd, 'HDD')) {
        $hdd2 = $hdd->duplicate();
        if ($hdd2) {
            $machine->$slot = $hdd2;
            if ($machine->$slot->id == $hdd2->id) {
                $hdd->destroy();
            }
        }
    }
    break;

case 'compact':
    $slot = stringParam('slot');
    $hdd = $machine->$slot;
    if (is_a($hdd, 'HDD')) {
        $hdd->compact();
    }
    break;

case 'adddisk':
    $slot = NULL;
    if (stringParam('disktype') == 'sata') {
        for ($i = 0; $i < 30; $i++) {
            $key = 'sata'.$i;
            if (!$machine->$key) {
                $slot = $key;
                break;
            }
        }
    } else {
        for ($i = 0; $i < 4; $i++) {
            $key = 'ide'.$i;
            if (!$machine->$key) {
                $slot = $key;
                break;
            }
        }
    }
    if ($slot != NULL) {
        switch (stringParam('disksource')) {
        case 'new':
            $machine->$slot = Repository::createHdd(intParam('disk', 8192));
            break;

        case 'clone':
            $hdd = hddParam('hdd');
            if ($hdd && $hdd->exists()) {
                $machine->$slot = $hdd->duplicate();
            }
            break;

        case 'differencial':
            $hdd = hddParam('hdd');
            if ($hdd && $hdd->exists()) {
                if ($hdd->type == 'multiattach') {
                    $machine->$slot = $hdd;
                    $machine->$slot->autoreset = FALSE;
                } else {
                    $machine->$slot = $hdd->duplicate();
                }
            }
            break;

        case 'volatile':
            $hdd = hddParam('hdd');
            if ($hdd && $hdd->exists()) {
                if ($hdd->type == 'multiattach') {
                    $machine->$slot = $hdd;
                    $machine->$slot->autoreset = TRUE;
                } else {
                    $machine->$slot = $hdd->duplicate();
                }
            }
            break;
        }
    }
    break;

case 'resizedisk':
    $slot = stringParam('slot');
    $hdd = $machine->$slot;
    if (is_a($hdd, 'HDD')) {
        $hdd->resize(intParam('disk', 8192));
    }
    break;

case 'removedisk':
    $slot = stringParam('slot');
    $machine->$slot = NULL;
    break;
}

include('include/header.inc.php');

?>
        <!-- Machine details -->
        <div class="content">
            <div class="title">MACHINE: <?=$machine->name?></div>
            <form method="POST">
                <input type="hidden" name="machine" value="<?=$machine->id?>"/>
                <input type="hidden" name="op" value="set"/>
                <table cellspacing="0">
                    <tr>
                        <th>State</th>
                        <td>[<?=$machine->state?>]
<?
switch ($machine->state) {
case 'poweroff':
case 'aborted':
case 'saved':
?>
                            <a href="machine.php?machine=<?=$machine->id?>&op=boot">boot</a>
                            <a href="machine.php?machine=<?=$machine->id?>&op=export">export</a>
                            <a href="machine.php?machine=<?=$machine->id?>&op=destroy" onclick="return confirm(&quot;Are you sure you want to destroy this machine?&quot;);">destroy</a>
<?
    break;

case 'starting':
?>
                            <a href="machine.php?machine=<?=$machine->id?>&op=poweroff">poweroff</a>
<?
    break;

case 'running':
    if ($machine->vrdp['enabled']) {
        if (stringParam('op') != 'console') {
?>
                            <a href="machine.php?machine=<?=$machine->id?>&op=console">vrd console</a>
<?
        }
    }
?>
                            <a href="machine.php?machine=<?=$machine->id?>&op=pause">pause</a>
                            <a href="machine.php?machine=<?=$machine->id?>&op=freeze">freeze</a>
                            <a href="machine.php?machine=<?=$machine->id?>&op=reboot">reboot</a>
                            <a href="machine.php?machine=<?=$machine->id?>&op=acpipowerbutton">poweroff(acpi)</a>
                            <a href="machine.php?machine=<?=$machine->id?>&op=poweroff">poweroff</a>
<?
    break;

case 'paused':
?>
                            <a href="machine.php?machine=<?=$machine->id?>&op=resume">resume</a>
                            <a href="machine.php?machine=<?=$machine->id?>&op=reboot">reboot</a>
                            <a href="machine.php?machine=<?=$machine->id?>&op=poweroff">poweroff</a>
<?
    break;
}
?>
                        </td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td>
<?
if ($machine->state == 'poweroff') {
?>
                            <input type="text" name="name" value="<?=$machine->name?>"/>
<?
} else {
?>
                            <?=$machine->name?>
<?
}
?>
                        </td>
                    </tr>
                    <tr>
                        <th>O.S.</th>
                        <td>
<?
if ($machine->state == 'poweroff') {
?>
                            <select name="ostype">
<?
    foreach (Repository::listOses() as $os) {
?>
                                <option value="<?=$os->id?>" <?=$machine->ostype == $os->id ? 'selected=""' : ''?>><?=$os->name?></option>
<?
    }
?>
                            </select>
<?
} else {
?>
                            <?=$machine->os->name?>
<?
}
?>
                        </td>
                    </tr>
                    <tr>
                        <th>CHIPSET</th>
                        <td><?=$machine->chipset?></td>
                    </tr>
                    <tr>
                        <th>CPU(S)</th>
                        <td>
<?
if ($machine->state == 'poweroff') {
?>
                            <input type="text" name="cpus" value="<?=$machine->cpus?>" size="2"/>
<?
} else {
?>
                            <?=$machine->cpus?>
<?
}
?>
                        </td>
                    </tr>
                    <tr>
                        <th>RAM</th>
                        <td>
<?
if ($machine->state == 'poweroff') {
?>
                            <input type="text" name="memory" value="<?=$machine->memory?>" size="4"/> [mb]
<?
} else {
?>
                            <?=$machine->memory?> [mb]
<?
}
?>
                        </td>
                    </tr>
                    <tr>
                        <th>VRAM</th>
                        <td>
<?
if ($machine->state == 'poweroff') {
?>
                            <input type="text" name="vram" value="<?=$machine->vram?>" size="4"/> [mb]
<?
} else {
?>
                            <?=$machine->vram?> [mb]
<?
}
?>
                        </td>
                    </tr>
                    <tr>
                        <th>VRD</th>
                        <td>
<?
if ($machine->state == 'poweroff') {
?>
                            <input type="checkbox" name="vrde" value="on" <?=$machine->vrde ? 'checked=""' : ''?>/>
                            <input type="text" name="vrdeaddress" value="<?=$machine->vrde ? $machine->vrdeaddress : $_SERVER['SERVER_ADDR']?>" size="15"/> : <input type="text" name="vrdeport" value="<?=$machine->vrde ? $machine->vrdeport : rand(1025, 65534)?>" size="5"/>
<?
} else {
?>
                            <input type="checkbox" name="vrde" value="on" <?=$machine->vrde ? 'checked=""' : ''?>/>
                            <?=$machine->vrde ? '('.$machine->vrdeaddress.':'.$machine->vrdeport.')' : ''?>
<?
}
?>
                        </td>
                    </tr>
<?
for ($i = 0; $i < 8; $i++) {
    $slot = 'storage'.$i;
    $storage = $machine->$slot;
    if ($storage) {
?>
                    <tr>
                        <th>CTRL-<?=$i?></th>
                        <td>
                            name: <?=$storage['name']?><br/>
                            type: <?=$storage['type']?><br/>
                            instance: <?=$storage['instance']?><br/>
                            ports: <?=$storage['ports']['count']?> x <?=$storage['ports']['maxdevices']?> (min:<?=$storage['ports']['min']?>, max:<?=$storage['ports']['max']?>)<br/>
<?
        foreach ($storage['devices'] as $port => $devices) {
            foreach ($devices as $index => $device) {
                if (strlen($device->path) == 0) {
                    continue;
                }
?>
                        device[<?=$port?>][<?=$index?>]: <?=$device->path?><br/>
<?
            }
        }
?>
                        </td>
                    </tr>
<?
    }
}
for ($i = 0; $i < 2; $i++) {
    $slot = 'fd'.$i;
    $fd = $machine->$slot;
    if (is_a($fd, 'FDD')) {
?>
                    <tr>
                        <th>FD-<?=$i?> (FDD)</th>
                        <td>
                            <input type="hidden" name="slot[]" value="<?=$slot?>"/>
                            <input type="hidden" name="<?=$slot?>" value="fdd"/>
                            <select name="fdd_<?=$slot?>">
                                <option value="" <?=$fd->id == '' ? 'selected=""' : ''?>>(none)</option>
<?
        foreach (Repository::listFdds() as $fdd) {
?>
                                <option value="<?=$fdd->path?>" <?=$fd->path == $fdd->path ? 'selected=""' : ''?>><?=$fdd->name?></option>
<?
        }
?>
                            </select>
                        </td>
                    </tr>
<?
    }
}
for ($i = 0; $i < 4; $i++) {
    $slot = 'ide'.$i;
    $ide = $machine->$slot;
    if (is_a($ide, 'HDD')) {
?>
                    <tr>
                        <th>
                            IDE-<?=$i?> (HDD)
<?
        if ($machine->state == 'poweroff') {
            if ($ide->parent) {
                ?> [<a href="machine.php?machine=<?=$machine->id?>&op=extract&slot=<?=$slot?>">extract</a>]<?
            } else {
                ?> [<a href="machine.php?machine=<?=$machine->id?>&op=compact&slot=<?=$slot?>">compact</a>]<?
            }
            ?> [<a href="machine.php?machine=<?=$machine->id?>&op=removedisk&slot=<?=$slot?>">remove</a>]<?
        }
?>
                        </th>
                        <td>
                            <?if ($ide->parent) {?>parent: <?=$ide->parent->path?><br/><?}?>
                            path: <?=$ide->path?><br/>
                            size: <?=$ide->usedsize?> [mb]<br/>
                            total: <?=$ide->size?> [mb]<br/>
                            type: <?=$ide->type?><br/>
                            volatile: <?=$ide->autoreset?>
                        </td>
                    </tr>
<?
    } else if (is_a($ide, 'DVD')) {
?>
                    <tr>
                        <th>IDE-<?=$i?> (DVD)</th>
                        <td>
                            <input type="hidden" name="slot[]" value="<?=$slot?>"/>
                            <input type="hidden" name="<?=$slot?>" value="dvd"/>
                            <select name="dvd_<?=$slot?>">
                                <option value="" <?=$ide->path == '' ? 'selected=""' : ''?>>(none)</option>
<?
        foreach (Repository::listDvds() as $dvd) {
?>
                                <option value="<?=$dvd->path?>" <?=$ide->path == $dvd->path ? 'selected=""' : ''?>><?=$dvd->name?></option>
<?
        }
?>
                            </select>
                        </td>
                    </tr>
<?
    }
}
for ($i = 0; $i < 30; $i++) {
    $slot = 'sata'.$i;
    $sata = $machine->$slot;
    if (is_a($sata, 'HDD')) {
?>
                    <tr>
                        <th>
                            SATA-<?=$i?> (HDD)
<?
        if ($machine->state == 'poweroff') {
            if ($sata->parent) {
                ?> [<a href="machine.php?machine=<?=$machine->id?>&op=extract&slot=<?=$slot?>">extract</a>]<?
            } else {
                ?> [<a href="machine.php?machine=<?=$machine->id?>&op=compact&slot=<?=$slot?>">compact</a>]<?
            }
            if ($slot != 'sata0') {
                ?> [<a href="machine.php?machine=<?=$machine->id?>&op=removedisk&slot=<?=$slot?>">remove</a>]<?
            }
        }
?>
                        </th>
                        <td>
                            <?if ($sata->parent) {?>parent: <?=$sata->parent->path?><br/><?}?>
                            path: <?=$sata->path?><br/>
                            size: <?=$sata->usedsize?> [mb]<br/>
                            total: <?=$sata->size?> [mb]<br/>
                            type: <?=$sata->type?><br/>
                            volatile: <?=$sata->autoreset?>
                        </td>
                    </tr>
<?
    }
}
for ($i = 0; $i < 8; $i++) {
    $slot = 'nic'.$i;
    $nic = $machine->$slot;
    if ($nic['type'] == 'null') {
        continue;
    }
?>
                    <tr>
                        <th>NET-<?=$i?></th>
                        <td>
                            <input type="hidden" name="net[]" value="<?=$slot?>"/>
<?php
    if ($machine->state != 'poweroff') {
        if ($machine->ready) {
            $slot2 = 'net'.$i;
            $net = $machine->$slot2;
?>
                            mac: <?=$nic['mactext']?><br/>
                            address: <?=$net['ip']?> / <?=$net['netmask']?><br/>
                            broadcast: <?=$net['broadcast']?><br/>
<?php
        } else {
?>
                            mac: <?=$nic['mactext']?><br/>
<?php
        }
    } else {
?>
                            type: <select name="<?=$slot?>_type">
<?php
        foreach (array('bridged' => 'Bridged', 'hostonly' => 'Host-only', 'null' => 'None') as $key => $value) {
            if ($key == $nic['type']) {
                ?><option value="<?=$key?>" selected=""><?=$value?></option><?php
            } else {
                ?><option value="<?=$key?>"><?=$value?></option><?php
            }
        }
?>
                            </select><br/>
                            driver: <select name="<?=$slot?>_driver">
<?php
        foreach (array('Am79C970A', 'Am79C973', '82540EM', '82543GC', '82545EM', 'virtio') as $value) {
            if ($value == $nic['driver']) {
                ?><option value="<?=$value?>" selected=""><?=$value?></option><?php
            } else {
                ?><option value="<?=$value?>"><?=$value?></option><?php
            }
        }
?>
                            </select><br/><br/>
                            mac: <input type="text" name="<?=$slot?>_mac" value="<?=$nic['mactext']?>"/><br/>
                            adapter: <input type="text" name="<?=$slot?>_adapter" value="<?=$nic['adapter']?>"/><br/>
<?php
    }
?>
                        </td>
                    </tr>
<?php
}
?>
                    <tr>
                        <th>VM/Hardware</th>
                        <td>
<?
foreach (Machine::$vm as $name) {
    switch ($name) {
    case 'hwvirtex':
        $label = 'VT-x/AMD-v';
        break;
    case 'vtxvpid':
        $label = 'VPID (VT-x only)';
        break;
    case 'largepages':
        $label = 'Large pages (VT-x only)';
        break;
    case 'nestedpaging':
        $label = 'Nested paging';
        break;
    case 'ioapic':
        $label = 'I/O APIC';
        break;
    case 'rtcuseutc':
        $label = 'RTC uses UTC';
        break;
    case 'synthcpu':
        $label = 'Synthetic CPU';
        break;
    case 'pagefusion':
        $label = 'Page fusion (only Win64)';
        break;
    case 'accelerate2dvideo':
        $label = '2D video acceleration';
        break;
    case 'accelerate3d':
        $label = '3D acceleration';
        break;
    default:
        $label = strtoupper($name);
        break;
    }
    $value = $machine->$name;
    if ($value || $machine->state == 'poweroff') {
?>
                            <input type="checkbox" name="<?=$name?>" value="on" <?=$value ? 'checked=""' : ''?> <?=$machine->state != 'poweroff' ? 'disabled=""' : ''?>/><?=$label?><br/>
<?
    }
}
?>
                        </td>
                    </tr>
                    <tr>
                        <th>Guest Additions</th>
                        <td>
                            <?=$machine->additions?>
                        </td>
                    </tr>
                    <tr>
                        <th>Audio Controller</th>
                        <td>
<?
if ($machine->state == 'poweroff') {
?>
                            host: <select name="audio">
<?php
        foreach (array('none', 'null', 'oss', 'alsa', 'pulse') as $value) {
            if ($value == $machine->audio) {
                ?><option value="<?=$value?>" selected=""><?=$value?></option><?php
            } else {
                ?><option value="<?=$value?>"><?=$value?></option><?php
            }
        }
?>
                            </select><br/>
                            controller: <select name="audiocontroller">
<?php
        foreach (array('hda', 'ac97', 'sb16') as $value) {
            if ($value == $machine->audiocontroller) {
                ?><option value="<?=$value?>" selected=""><?=$value?></option><?php
            } else {
                ?><option value="<?=$value?>"><?=$value?></option><?php
            }
        }
?>
                            </select><br/>
                            codec: N/A
<?
} else {
?>
                            host: <?=$machine->audio?><br/>
                            controller: <?=$machine->audiocontroller?><br/>
                            codec: N/A
<?
}
?>
                        </td>
                    </tr>
                    <tr>
                        <th>USB Controller</th>
                        <td>
                            <input type="checkbox" name="usb" value="on" <?=$machine->usb ? 'checked=""' : ''?> <?=$machine->state != 'poweroff' ? 'disabled=""' : ''?>/> OHCI<br/>
                            <input type="checkbox" name="usbehci" value="on" <?=$machine->usbehci ? 'checked=""' : ''?> <?=$machine->state != 'poweroff' ? 'disabled=""' : ''?>/> EHCI<br/>
                            <input type="checkbox" name="usbxhci" value="on" <?=$machine->usbxhci ? 'checked=""' : ''?> <?=$machine->state != 'poweroff' ? 'disabled=""' : ''?>/> XHCI
                        </td>
                    </tr>
                    <tr>
                        <th>USB Devices</th>
                        <td>
<?
$usbDevices = readUSBInfo();
foreach ($machine->usbfilters as $filter) {
    $usbDevice = array(
        'bus' => 0,
        'device' => 0,
        'vendor' => $filter['vendorid'],
        'product' => $filter['productid'],
        'name' => $filter['name']
    );
    foreach ($usbDevices as $other) {
        if ($filter['vendorid'] == $other['vendor'] && $filter['productid'] == $other['product']) {
            $usbDevice = $other;
            break;
        }
    }
?>
                            <input type="checkbox" name="usbfilter[]" value="<?=sprintf('%04X:%04X', $filter['vendorid'], $filter['productid'])?>" checked="">
                            <?=sprintf("%03d.%03d %04X:%04X %s", $usbDevice['bus'], $usbDevice['device'], $usbDevice['vendor'], $usbDevice['product'], $usbDevice['name'])?><br/>
<?
}
foreach ($usbDevices as $usbDevice) {
    $active = FALSE;
    foreach ($machine->usbfilters as $filter) {
        if ($filter['vendorid'] == $usbDevice['vendor'] && $filter['productid'] == $usbDevice['product']) {
            $active = TRUE;
            break;
        }
    }
    if ($active) {
        continue;
    }
?>
                            <input type="checkbox" name="usbfilter[]" value="<?=sprintf('%04X:%04X', $usbDevice['vendor'], $usbDevice['product'])?>">
                            <?=sprintf("%03d.%03d %04X:%04X %s", $usbDevice['bus'], $usbDevice['device'], $usbDevice['vendor'], $usbDevice['product'], $usbDevice['name'])?><br/>
<?
}
?>
                        </td>
                    </tr>
                    <!--tr>
                        <th valign="top">Debug</th>
                        <td>
<?
    foreach ($machine->listValues() as $k => $v) {
?>
                            <?=$k?>: <?=$v?><br/>
<?
    }
?>
                        </td>
                    </tr-->
                    <tr class="action">
                        <td colspan="2"><input type="submit" value="set"/></td>
                    </tr>
                </table>
            </form>
        </div>
<?
if ($machine->state == 'poweroff' && $machine->isStandardStorage()) {
?>
        <!-- Machine disks -->
        <div class="content">
            <div class="title">ADD DISK:</div>
            <form method="POST">
                <input type="hidden" name="machine" value="<?=$machine->id?>"/>
                <input type="hidden" name="op" value="adddisk"/>
                <table cellspacing="0">
                    <tr>
                        <th>HDD</th>
                        <td>
                            <input type="radio" name="disktype" value="ide" <?=stringParam('disktype') == 'ide' ? 'checked=""' : ''?>/>IDE<br/>
                            <input type="radio" name="disktype" value="sata" <?=stringParam('disktype') == '' || stringParam('disktype') == 'sata' ? 'checked=""' : ''?>/>SATA
                        </td>
                    </tr>
                    <tr>
                        <th>HDD Source</th>
                        <td>
                            <input type="radio" name="disksource" value="new" <?=stringParam('disksource') == 'new' ? 'checked=""' : ''?>/>new
                            <input type="text" name="disk" value="<?=intParam('disk', 8192)?>"/> [mb]<br/><br/>

                            <input type="radio" name="disksource" value="clone" <?=stringParam('disksource') == 'clone' ? 'checked=""' : ''?>/>clone
                            <input type="radio" name="disksource" value="differencial" <?=stringParam('disksource') == '' || stringParam('disksource') == 'differencial' ? 'checked=""' : ''?>/>differencial
                            <input type="radio" name="disksource" value="volatile" <?=stringParam('disksource') == 'volatile' ? 'checked=""' : ''?>/>volatile
                            <select name="hdd">
<?
$lastHdd = NULL;
foreach (Repository::listHdds() as $hdd) {
    if ($hdd->type == 'multiattach' && ($lastHdd == NULL || $lastHdd->time <= $hdd->time)) {
        $lastHdd = $hdd;
    }
}
foreach (Repository::listHdds() as $hdd) {
    if ($hdd->type == 'multiattach') {
        $selected = stringParam('hdd') == $hdd->path;
        if (strlen(stringParam('hdd')) == 0) {
            $selected = $hdd->path == $lastHdd->path;
        }
?>
                                <option value="<?=$hdd->path?>" <?=$selected ? 'selected=""' : ''?>><?=$hdd->name?></option>
<?
    }
}
?>
                            </select>
                        </td>
                    </tr>
                    <tr class="action">
                        <td colspan="2"><input type="submit" value="add"/></td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="content">
            <div class="title">RESIZE DISK:</div>
            <form method="POST">
                <input type="hidden" name="machine" value="<?=$machine->id?>"/>
                <input type="hidden" name="op" value="resizedisk"/>
                <table cellspacing="0">
                    <tr>
                        <th>HDD</th>
                        <td>
                            <select name="slot">
<?
for ($i = 0; $i < 4; $i++) {
    $slot = 'ide'.$i;
    $ide = $machine->$slot;
    if (is_a($ide, 'HDD')) {
?>
                                <option value="<?=$slot?>">IDE-<?=$i?></option>
<?
    }
}
for ($i = 0; $i < 30; $i++) {
    $slot = 'sata'.$i;
    $sata = $machine->$slot;
    if (is_a($sata, 'HDD')) {
?>
                                <option value="<?=$slot?>">SATA-<?=$i?></option>
<?
    }
}
?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>New size</th>
                        <td>
                            <input type="text" name="disk" value="<?=intParam('disk', 8192)?>"/> [mb]
                        </td>
                    </tr>
                    <tr class="action">
                        <td colspan="2"><input type="submit" value="resize"/></td>
                    </tr>
                </table>
            </form>
        </div>
<?
} else if ($machine->state == 'running' && $machine->vrde) {
?>
        <!-- Machine console -->
        <div class="content">
            <div class="title">VRD CONSOLE</div>
            <div class="console">
                <a href="#" onclick="return RDPWebSCAN('rdpwidget', '1d 2e ae 9d');">Ctrl.+C</a>
                <a href="#" onclick="return RDPWebSCAN('rdpwidget', '1d 2d ad 9d');">Ctrl.+X</a>
                <a href="#" onclick="return RDPWebCTRLALTDEL('rdpwidget');">Ctrl.+Alt.+Del.</a>
                <div id="rdpwidgetContainer"><div id="rdpwidget"></div></div>
            </div>
        </div>
        <script type="text/javascript" src="rdp/swfobject.js"></script>
        <script type="text/javascript" src="rdp/webclient.js"></script>
        <script type="text/javascript">

            function RDPWebEventLoaded(rdpId) {
                var rdp = RDPWebClient.getFlashById('rdpwidget');

                if (rdp) {
                    rdp.setProperty('serverAddress', '<?=$machine->vrdeaddress?>');
                    rdp.setProperty('serverPort', '<?=$machine->vrdeport?>');
                    rdp.setProperty('logonUsername', '');
                    rdp.setProperty('logonPassword', '');
                    rdp.setProperty('displayWidth', '1152');
                    rdp.setProperty('displayHeight', '864');
                    rdp.setProperty('keyboardLayout', 'en');
                    rdp.connect();
                }
            }

            function RDPWebEventConnected(rdpId) {
            }

            function RDPWebEventServerRedirect(rdpId) {
            }

            function RDPWebEventDisconnected(rdpId) {
            }

            function RDPWebCTRLALTDEL(rdpId) {
                var rdp = RDPWebClient.getFlashById('rdpwidget');

                if (rdp) {
                    rdp.keyboardSendCAD();
                }
                return false;
            }

                        function RDPWebSCAN(rdpId, codes) {
                                var rdp = RDPWebClient.getFlashById('rdpwidget');

                                if (rdp) {
                                        rdp.keyboardSendScancodes(codes);
                                }
                                return false;
                        }

            RDPWebClient.embedSWF ('rdp/RDPClientUI.swf', 'rdpwidget');

        </script>
<?
}

include('include/footer.inc.php');

?>
