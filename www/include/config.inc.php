<?php

/**
 * Install:
 *
 * - Add base folder:
 *      /var/lib/virtualbox
 *      /var/lib/virtualbox/dvd
 *      /var/lib/virtualbox/fdd
 *      /var/lib/virtualbox/hdd
 *      /var/lib/virtualbox/machine
 *      /var/lib/virtualbox/ovf
 *
 * - Change ownership to web server user/group
 *
 * - Install ISO cd/dvd images in dvd/ sub-folder
 * - Install IMG floppy images in fdd/ sub-folder
 * - Install VDi hdd images in hdd/ sub-folder
 * - Install machines in machine/ sub-folder
 * - Install OVF machines in ovf/ sub-folder
 */

define('VIRTUALBOX_PATH',			'/usr/lib/virtualbox');
define('VIRTUALBOX_MGT_BIN',		VIRTUALBOX_PATH.'/VBoxManage');
define('VIRTUALBOX_OPENMEDIUM_BIN',	VIRTUALBOX_PATH.'/VBoxOpenMedium');
define('VIRTUALBOX_XML_BIN',		VIRTUALBOX_PATH.'/VBoxXML');

define('BASE_PATH', '/var/lib/virtualbox');

?>