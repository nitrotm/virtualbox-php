<?

require_once('include/virtualbox.inc.php');

// perform actions
if (stringParam('op') == 'create') {
	$machine = Repository::createMachine(stringParam('name'), stringParam('ostype', 'Other'));
	if ($machine && $machine->exists()) {
		$machine->set(
			array(
				'cpus' => intParam('cpus', 1),
				'memory' => intParam('memory', 512),
				'vram' => intParam('vram', 12),
				'boot1' => 'floppy',
				'boot2' => 'dvd',
				'boot3' => 'disk',
				'boot4' => 'none',
				'vrde' => boolParam('vrde'),
				'vrdeaddress' => stringParam('vrdeaddress', '0.0.0.0'),
				'vrdeport' => intParam('vrdeport', 3389)
			)
		);

		// set hdd
		switch (stringParam('disksource')) {
		case 'new':
			if (stringParam('disktype') == 'sata') {
				$machine->sata0 = Repository::createHdd(intParam('disk', 8192));
			} else {
				$machine->ide0 = Repository::createHdd(intParam('disk', 8192));
			}
			break;

		case 'clone':
			$hdd = hddParam('hdd');
			if ($hdd && $hdd->exists()) {
				if (stringParam('disktype') == 'sata') {
					$machine->sata0 = $hdd->duplicate();
				} else {
					$machine->ide0 = $hdd->duplicate();
				}
			}
			break;

		case 'differencial':
			$hdd = hddParam('hdd');
			if ($hdd && $hdd->exists()) {
				if (stringParam('disktype') == 'sata') {
					if ($hdd->type == 'multiattach') {
						$machine->sata0 = $hdd;
						$machine->sata0->autoreset = FALSE;
					} else {
						$machine->sata0 = $hdd->duplicate();
					}
				} else {
					if ($hdd->type == 'multiattach') {
						$machine->ide0 = $hdd;
						$machine->ide0->autoreset = FALSE;
					} else {
						$machine->ide0 = $hdd->duplicate();
					}
				}
			}
			break;

		case 'volatile':
			$hdd = hddParam('hdd');
			if ($hdd && $hdd->exists()) {
				if (stringParam('disktype') == 'sata') {
					if ($hdd->type == 'multiattach') {
						$machine->sata0 = $hdd;
						$machine->sata0->autoreset = TRUE;
					} else {
						$machine->sata0 = $hdd->duplicate();
					}
				} else {
					if ($hdd->type == 'multiattach') {
						$machine->ide0 = $hdd;
						$machine->ide0->autoreset = TRUE;
					} else {
						$machine->ide0 = $hdd->duplicate();
					}
				}
			}
			break;
		}

		// set dvd
		if (boolParam('dvdenabled')) {
			if (stringParam('disktype') == 'sata') {
				$machine->ide0 = dvdParam('dvd');
			} else {
				$machine->ide1 = dvdParam('dvd');
			}
		}

		// set fdd
		if (boolParam('fddenabled')) {
			$machine->fd0 = fddParam('fdd');
		}

		// set nic
		$machine->nic0 = array(
			'type' => 'bridged',
			'driver' => '82543GC',
			'adapter' => 'eth0',
			'connected' => 'on'
		);

		header('Location: machine.php?machine='.$machine->id);
		exit;
	}
}

// list machines
$running = array();
$poweroff = array();
foreach (Repository::listMachines() as $machine) {
	switch ($machine->state) {
	case 'starting':
	case 'running':
	case 'paused':
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
						<? if ($machine->ready && stripos($machine->product, 'linux') !== FALSE) { ?>[<a href="http://<?=$machine->net0['ip']?>/system/" target="_blank">admin console</a>]<? } ?>
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
						<td><input type="text" name="memory" value="<?=intParam('memory', 512)?>"/> [mb]</td>
					</tr>
					<tr>
						<th>VRAM</th>
						<td><input type="text" name="vram" value="<?=intParam('vram', 12)?>"/> [mb]</td>
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
<?

include('include/footer.inc.php');

?>
