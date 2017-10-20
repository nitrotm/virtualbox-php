<?

require_once('include/virtualbox.inc.php');

// perform actions
switch (stringParam('op')) {
case 'create':
    $dstpath = BASE_PATH."/hdd/".stringParam('name').'.vdi';
    if (!file_exists($dstpath)) {
        $src = hddParam('hdd');
        if ($src->exists()) {
            $dst = $src->duplicate($dstpath);
            if ($dst->exists()) {
                $dst->type = 'multiattach';

                header('Location: disks.php');
                exit;
            }
        }
    }
    break;

case 'destroy':
    $hdd = hddParam('hdd');
    if ($hdd->exists() && $hdd->destroy()) {
        header('Location: disks.php');
        exit;
    }
    break;
}

include('include/header.inc.php');

$hdds = array();
$usedHdds = array();
foreach (Repository::listMachines() as $machine) {
    for ($i = 0; $i < 8; $i++) {
        $slot = 'storage'.$i;
        $storage = $machine->$slot;
        if ($storage) {
            foreach ($storage['devices'] as $port => $devices) {
                foreach ($devices as $index => $device) {
                    if (is_a($device, 'HDD')) {
                        $hdd = $device;
                        if ($machine->state == 'poweroff') {
                            $hdds[$hdd->path] = array(
                                'machine' => $machine,
                                'hdd' => $hdd,
                                'slot' => $storage['name'].'-'.$port.'-'.$index
                            );
                        }
                        while ($hdd && !in_array($hdd->path, $usedHdds)) {
                            $usedHdds[] = $hdd->path;
                            $hdd = $hdd->parent;
                        }
                    }
                }
            }
        }
    }
}
if (sizeof($hdds) > 0) {
?>
        <!-- Create new disk -->
        <div class="content">
            <div class="title">ADD DISK TEMPLATE</div>
            <form method="POST">
                <input type="hidden" name="op" value="create"/>
                <table cellspacing="0" width="100%">
                    <tr>
                        <th>Name</th>
                        <td><input type="text" name="name" value="<?=stringParam('name', 'vdisk-'.time())?>"/></td>
                    </tr>
                    <tr>
                        <th>Source</th>
                        <td>
                            <select name="hdd">
<?
foreach ($hdds as $path => $hdd) {
?>
                                <option value="<?=$path?>" <?stringParam('hdd') == $path ? 'selected=""' : ''?>><?=$hdd['machine']->name?> (<?=$hdd['slot']?>)</option>
<?
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
<?
}

$hdds = array();
foreach (Repository::listHdds() as $hdd) {
    if ($hdd->type == 'multiattach' || !in_array($hdd->path, $usedHdds)) {
        $hdds[] = $hdd;
    }
}
?>
        <!-- List of disks -->
        <div class="content">
            <div class="title">EXISTING DISK TEMPLATES</div>
<?
if (sizeof($hdds) > 0) {
?>
            <table cellspacing="0">
                <tr class="title">
                    <th>Name</th>
                    <th width="200">Type</th>
                    <th width="200">Size [mb]</th>
                    <th width="200">Used [mb]</th>
                </tr>
<?
    foreach ($hdds as $hdd) {
?>
                <tr>
                    <td><?=$hdd->shortname?>
<?
        if (!$hdd->parent && !in_array($hdd->path, $usedHdds)) {
            ?> [<a href="?op=destroy&hdd=<?=$hdd->path?>" onclick="return confirm(&quot;Are you sure you want to destroy this disk?&quot;);">destroy</a>]<?
        }
?>
                    </td>
                    <td width="200"><?=$hdd->type?></td>
                    <td width="200"><?=$hdd->size?></td>
                    <td width="200"><?=$hdd->usedsize?></td>
                </tr>
<?
    }
?>
            </table>
<?
} else {
?>
            No disk template available.
<?
}
?>
        </div>
<?

include('include/footer.inc.php');

?>