<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

		<title>VirtualBox manager</title>
		<link rel="stylesheet"    type="text/css"     href="page.css" />
		<link rel="icon"          type="image/x-icon" href="favicon.ico" />
		<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
	</head>
	<body>
		<div class="header">
			<ul>
				<li><a href="index.php">Machines</a></li>
				<li><a href="disks.php">Disks</a></li>
			</ul>
			<div class="stats">
				<span class="label">memory:</span><?
					$mem = readMemoryInfo();
					printf("%.02f[mo] / %.02f[mo]", ($mem['MemTotal'] - $mem['MemFree'] - $mem['Cached'] + $mem['Buffers']), $mem['MemTotal']);
				?><br/>
				<span class="label">swap:</span><?
					printf("%.02f[mo] / %.02f[mo]", ($mem['SwapTotal'] - $mem['SwapFree'] - $mem['SwapCached']), $mem['SwapTotal']);
				?><br/>
			</div>
			<div class="stats">
				<span class="label">cpu:</span><?
					$cpu = readCpuInfo();
					$total = $cpu['user'] + $cpu['nice'] + $cpu['system'] + $cpu['idle'];
					if ($total <= 0) {
						$total = 1;
					}
					printf(
						"%.01f%% (%.01f%% user)",
						100.0 * ($cpu['user'] + $cpu['nice'] + $cpu['system']) / $total,
						100.0 * $cpu['user'] / $total
					);
				?><br/>
				<span class="label">hdd:</span><?
					$hdd = readHddInfo();
					printf("%.02f[go] / %.02f[go]", $hdd['used'] / 1024.0, $hdd['size'] / 1024.0);
				?>
			</div>
			<div style="clear: both;"></div>
		</div>
