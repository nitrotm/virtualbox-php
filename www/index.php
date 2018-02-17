<?

require_once('include/virtualbox.inc.php');

// perform actions
switch (stringParam('op')) {
case 'create':
    $machine = Repository::createMachine(stringParam('name'), stringParam('ostype', 'Other'));
    if ($machine && $machine->exists()) {
        $machine->set(
            array(
                'cpus' => intParam('cpus', 1),
                'memory' => intParam('memory', 1024),
                'vram' => intParam('vram', 128),
                'boot1' => 'floppy',
                'boot2' => 'dvd',
                'boot3' => 'disk',
                'boot4' => 'none',
                'vrde' => boolParam('vrde'),
                'vrdeaddress' => stringParam('vrdeaddress', '0.0.0.0'),
                'vrdeport' => intParam('vrdeport', 3389),
                'accelerate2dvideo' => TRUE
            )
        );

        // set hdd
        if (stringParam('disktype') == 'sata') {
            $slot = 'sata0';
        } else {
            $slot = 'ide0';
        }
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

        // set dvd
        if (boolParam('dvdenabled')) {
            if ($slot != 'ide0') {
                $slot = 'ide0';
            } else {
                $slot = 'ide1';
            }
            $machine->$slot = dvdParam('dvd');
        }

        // set fdd
        if (boolParam('fddenabled')) {
            $machine->fd0 = fddParam('fdd');
        }

        // set nic
        $nicDriver = '82545EM';
        switch ($machine->ostype) {
        case 'Windows31':
        case 'Windows95':
        case 'Windows98':
        case 'WindowsMe':
        case 'WindowsNT4':
        case 'WindowsXP':
        case 'WindowsXP_64':
            $nicDriver = 'Am79C973';
            break;
        }
        $machine->nic0 = array(
            'type' => 'bridged',
            'driver' => $nicDriver,
            'adapter' => DEFAULT_NET_ADAPTER,
            'connected' => 'on'
        );

        // set audio
        $audioController = 'hda';
        switch ($machine->ostype) {
        case 'Windows31':
        case 'Windows95':
        case 'Windows98':
        case 'WindowsMe':
        case 'WindowsNT4':
        case 'WindowsXP':
        case 'WindowsXP_64':
            $audioController = 'ac97';
            break;
        }
        // $machine->audio = 'pulse';
        $machine->audiocontroller = $audioController;

        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;

case 'import':
    $machine = Repository::importMachine(stringParam('file'));
    if ($machine && $machine->exists()) {
        header('Location: machine.php?machine='.$machine->id);
        exit;
    }
    break;
}

// list machines
$running = array();
$poweroff = array();
foreach (Repository::listMachines() as $machine) {
    switch ($machine->state) {
    case 'starting':
    case 'running':
    case 'paused':
    case 'saved':
        $running[] = $machine;
        break;

    default:
        $poweroff[] = $machine;
        break;
    }
}

include('include/header.inc.php');

if (sizeof($running) > 0 || sizeof($poweroff) > 0) {
?>
        <!-- List of machines -->
        <div class="content">
            <div class="title">MACHINES</div>
<?
    if (sizeof($running) > 0) {
?>
            <div class="subtitle">ACTIVE</div>
            <table cellspacing="0">
                <tr class="title">
                    <th>Name</th>
                    <th width="200">OS</th>
                    <th width="200">State</th>
                </tr>
<?
        foreach ($running as $machine) {
?>
                <tr>
                    <td>
                        <a href="machine.php?machine=<?=$machine->id?>"><?=$machine->name?></a>
                        <? if ($machine->ready) { ?>(<?=$machine->net0['ip']?>)<? } ?>
                    </td>
                    <td width="200"><?=$machine->os->name?></td>
                    <td width="200"><?=$machine->state?></td>
                </tr>
<?
        }
?>
            </table>
<?
    }
    if (sizeof($poweroff) > 0) {
        if (sizeof($running) > 0) {
?>
            <div class="spacer"></div>
            <div class="spacer"></div>
<?
        }
?>
            <div class="subtitle">INACTIVE</div>
            <table cellspacing="0">
                <tr class="title">
                    <th>Name</th>
                    <th width="200">OS</th>
                    <th width="200">State</th>
                </tr>
<?
        foreach ($poweroff as $machine) {
?>
                <tr>
                    <td><a href="machine.php?machine=<?=$machine->id?>"><?=$machine->name?></a></td>
                    <td width="200"><?=$machine->os->name?></td>
                    <td width="200"><?=$machine->state?></td>
                </tr>
<?
        }
?>
            </table>
<?
    }
?>
        </div>
<?
}
?>

        <!-- Create new machine -->
        <div class="content">
            <div class="title">ADD MACHINE</div>
            <form method="POST">
                <input type="hidden" name="op" value="create"/>
                <table cellspacing="0">
                    <tr>
                        <th>Name</th>
                        <td><input type="text" name="name" value="<?=stringParam('name', 'vbox-'.time())?>"/></td>
                    </tr>
                    <tr>
                        <th>O.S.</th>
                        <td>
                            <select name="ostype">
<?
foreach (Repository::listOses() as $os) {
?>
                                <option value="<?=$os->id?>" <?stringParam('ostype') == $os->id ? 'selected=""' : ''?>><?=$os->name?></option>
<?
}
?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>CPU(S)</th>
                        <td><input type="text" name="cpus" value="<?=intParam('cpus', 1)?>"/></td>
                    </tr>
                    <tr>
                        <th>RAM</th>
                        <td><input type="text" name="memory" value="<?=intParam('memory', 1024)?>"/> [mb]</td>
                    </tr>
                    <tr>
                        <th>VRAM</th>
                        <td><input type="text" name="vram" value="<?=intParam('vram', 128)?>"/> [mb]</td>
                    </tr>
                    <tr>
                        <th>VRD</th>
                        <td>
                            <input type="checkbox" name="vrde" value="on"/>
                            <input type="text" name="vrdeaddress" value="<?=stringParam('vrdeaddress', $_SERVER['SERVER_ADDR'])?>" size="15"/> : <input type="text" name="vrdeport" value="<?=intParam('vrdeport', rand(1025, 65534))?>" size="5"/>
                        </td>
                    </tr>
                    <tr>
                        <th>FDD</th>
                        <td>
                            <input type="checkbox" name="fddenabled" value="on"/>
                            <select name="fdd">
                                <option value="">(none)</option>
<?
foreach (Repository::listFdds() as $fdd) {
?>
                                <option value="<?=$fdd->path?>" <?stringParam('fdd') == $fdd->path ? 'selected=""' : ''?>><?=$fdd->name?></option>
<?
}
?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>DVD</th>
                        <td>
                            <input type="checkbox" name="dvdenabled" value="on" checked=""/>
                            <select name="dvd">
                                <option value="">(none)</option>
<?
foreach (Repository::listDvds() as $dvd) {
?>
                                <option value="<?=$dvd->path?>" <?stringParam('dvd') == $dvd->path ? 'selected=""' : ''?>><?=$dvd->name?></option>
<?
}
?>
                            </select>
                        </td>
                    </tr>
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

        <!-- Import machine -->
        <div class="content">
            <div class="title">IMPORT MACHINE</div>
<?
if (sizeof(Repository::listOVAs()) > 0) {
?>
            <table cellspacing="0">
                <tr class="title">
                    <th>Name</th>
                </tr>
<?
    foreach (Repository::listOVAs() as $ova) {
?>
                <tr>
                    <td><?=$ova?> [<a href="?op=import&file=<?=$ova?>">import</a>]</td>
                </tr>
<?
    }
?>
            </table>
<?
} else {
?>
            No machine available.
<?
}
?>
        </div>
<?

include('include/footer.inc.php');

?>
