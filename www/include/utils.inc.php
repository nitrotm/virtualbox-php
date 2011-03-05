<?php

function readCpuInfo($delta = 100000) {
	$o1 = file_get_contents('/proc/stat');
	$t = microtime(TRUE);
	usleep($delta);
	$o2 = file_get_contents('/proc/stat');
	$t = microtime(TRUE) - $t;

	$cpu = array();
	foreach (explode("\n", $o1) as $line) {
		if (preg_match("/^cpu\s+(\d+) (\d+) (\d+) (\d+)/", $line, $matches)) {
			$cpu = array(
				'user' => intval($matches[1]),
				'nice' => intval($matches[2]),
				'system' => intval($matches[3]),
				'idle' => intval($matches[4])
			);
			break;
		}
	}
	foreach (explode("\n", $o2) as $line) {
		if (preg_match("/^cpu\s+(\d+) (\d+) (\d+) (\d+)/", $line, $matches)) {
			$cpu['user'] = intval($matches[1]) - $cpu['user'];
			$cpu['nice'] = intval($matches[2]) - $cpu['nice'];
			$cpu['system'] = intval($matches[3]) - $cpu['system'];
			$cpu['idle'] = intval($matches[4]) - $cpu['idle'];
			break;
		}
	}
	return $cpu;
}

function readMemoryInfo() {
	$mem = array();
	foreach (explode("\n", file_get_contents('/proc/meminfo')) as $line) {
		if (preg_match("/^(\w+):\s+(\d+) kB$/", $line, $matches)) {
			$mem[$matches[1]] = floatval($matches[2]) / 1024.0;
		}
	}
	return $mem;
}

function readHddInfo() {
	$hdd = array();
	foreach (simpleExec('/bin/df', array('-lk', '/')) as $line) {
		$matches = preg_split("/\s+/", $line);
		if (sizeof($matches) == 6) {
			$hdd['size'] = intval($matches[1]) / 1024.0;
			$hdd['used'] = intval($matches[2]) / 1024.0;
		}
	}
	return $hdd;
}

function captureExec($binary, $params) {
	$code = safeExec($binary, $params, $out, $err);
	if ($code != 0) {
		echo("<pre>Command $binary failed ($code)!\n");
		if (strlen($out) > 0) {
			echo("$out\n");
		}
		if (strlen($err) > 0) {
			echo("$err\n");
		}
		echo("</pre>\n");
		return "";
	}
	return $out;
}

function simpleExec($binary, $params) {
	$code = safeExec($binary, $params, $out, $err);
	if ($code != 0) {
		echo("<pre>Command $binary failed!\n");
		if (strlen($out) > 0) {
			echo("$out\n");
		}
		if (strlen($err) > 0) {
			echo("$err\n");
		}
		echo("</pre>\n");
		return array();
	}
	return explode("\n", $out);
}

function voidExec($binary, $params) {
	$code = safeExec($binary, $params, $out, $err);
	return $code;
}

function safeExec($binary, $params, &$stdout, &$stderr) {
	// build command-line
	$cmdline = $binary;
	foreach ($params as $param) {
		if (is_array($param)) {
			if (!isset($param['nospace'])) {
				$cmdline .= " ";
			}
			if (isset($param['escape'])) {
				$cmdline .= escapeshellarg($param['text']);
			} else {
				$cmdline .= $param['text'];
			}
		} else if ($param !== NULL) {
			$cmdline .= " ";
			if (is_numeric($param) || preg_match('/^[a-zA-Z0-9_-]+$/', $param)) {
				$cmdline .= $param;
			} else {
				$cmdline .= escapeshellarg($param);
			}
		}
	}

	// build descriptors
	$descriptors = array(
		0 => array('pipe', 'r'),
		1 => array('pipe', 'w'),
		2 => array('pipe', 'w')
	);

	// build env
	$env = $_ENV;
	$env['HOME'] = BASE_PATH;
	$env['VBOX_XPCOM_HOME'] = VIRTUALBOX_PATH;

	// fork process
	$code = -1;
	$pid = proc_open($cmdline, $descriptors, $pipes, BASE_PATH, $env);
	if (is_resource($pid)) {
		// close input stream
		fclose($pipes[0]);

		// wait process terminate
		$status = FALSE;
		do {
			$status = proc_get_status($pid);
			usleep(10000);

			// fetch output stream
			$stdout .= stream_get_contents($pipes[1]);

			// fetch error stream
			$stderr .= stream_get_contents($pipes[2]);
		} while ($status && $status['running']);

		// close streams
		fclose($pipes[1]);
		fclose($pipes[2]);

		// close process
		proc_close($pid);
		$code = $status['exitcode'];
	}
	if ($code != 0) {
		echo("<pre>Command $cmdline failed!\n");
		if (strlen($stdout) > 0) {
			echo("$stdout\n");
		}
		if (strlen($stderr) > 0) {
			echo("$stderr\n");
		}
		echo("</pre>\n");
	} else {
//		echo("<pre>$cmdline</pre>\n");
	}
	return $code;
}

?>