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
	$machine->boot('headless');
	header('Location: machine.php?machine='.$machine->id);
	exit;

case 'pause':
	$machine->pause();
	header('Location: machine.php?machine='.$machine->id);
	exit;

case 'resume':
	$machine->resume();
	header('Location: machine.php?machine='.$machine->id);
	exit;

case 'reboot':
	$machine->reset();
	header('Location: machine.php?machine='.$machine->id);
	exit;

case 'poweroff':
	$machine->poweroff();
	header('Location: machine.php?machine='.$machine->id);
	exit;

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
	$machine->destroy();
	header('Location: index.php');
	exit;

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

	if ($machine->state == 'poweroff') {
		// options
		$values = array(
			'name' => stringParam('name', 'vbox-'.time()),
			'ostype' => stringParam('ostype'),
			'cpus' => intParam('cpus', 1),
			'memory' => intParam('memory', 512),
			'vram' => intParam('vram', 12),
			'vrde' => boolParam('vrde'),
			'vrdeaddress' => stringParam('vrdeaddress', '0.0.0.0'),
			'vrdeport' => intParam('vrdeport', 3389)
		);
		foreach (Machine::$vm as $name) {
			$values[$name] = boolParam($name);
		}
		$machine->set($values);
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
?>
							<a href="machine.php?machine=<?=$machine->id?>&op=boot">boot</a>
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
							<a href="machine.php?machine=<?=$machine->id?>&op=reboot">reboot</a>
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
							<?=$machine->vrde ? 'on ('.$machine->vrdeaddress.':'.$machine->vrdeport.')' : 'off'?>
<?
}
?>
						</td>
					</tr>
<?
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
if ($machine->state != 'poweroff' && $machine->ready) {
	$nic = $machine->nic0;
	$net = $machine->net0;
?>
					<tr>
						<th>NET-0</th>
						<td>
							address: <?=$net['ip']?> / <?=$net['netmask']?><br/>
							broadcast: <?=$net['broadcast']?><br/>
							mac: <?=$nic['mactext']?>
						</td>
					</tr>
<?
} else {
	$nic = $machine->nic0;
?>
					<tr>
						<th>NET-0</th>
						<td>
							mac: <?=$nic['mactext']?>
						</td>
					</tr>
<?
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
					<!--tr>
						<th>Path</th>
						<td><?=$machine->path?></td>
					</tr-->
					<!--tr>
						<th>Audio</th>
						<td><?=$machine->audio?> <?=$machine->audio != 'none' ? '('.$machine->audiocontroller.')' : ''?>
						</td>
					</tr>
					<tr>
						<th>USB</th>
						<td><?=$machine->usb ? 'on' : 'off'?> <?=$machine->usbehci ? '(EHCI)' : ''?></td>
					</tr-->
					<!--tr>
						<th>UUID</th>
						<td><?=$machine->id?></td>
					</tr-->
					<!--tr>
						<th valign="top">Debug</th>
						<td><pre><? print_r($machine); ?></pre></td>
					</tr-->
					<tr class="action">
						<td colspan="2"><input type="submit" value="set"/></td>
					</tr>
				</table>
			</form>
		</div>
<?
if ($machine->state == 'running' && $machine->vrde) {
?>
		<!-- Machine console -->
		<div class="content">
			<div class="title">VRD CONSOLE</div>
			<div class="console">
				<a href="#" onclick="return RDPWebCTRLALTDEL('rdpwidget');">Send Ctrl.+Alt.+Del.</a>
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

			RDPWebClient.embedSWF ('rdp/RDPClientUI.swf', 'rdpwidget');

		</script>
<?
}

include('include/footer.inc.php');

?>
