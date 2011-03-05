#!/bin/sh

#VBOX_XPCOM_HOME=/opt/VirtualBox/ ./VBoxXML | xmllint --format -

sudo -u http HOME="/var/lib/virtualbox/" VBOX_XPCOM_HOME=/usr/lib/virtualbox/ ./VBoxXML --base /var/lib/virtualbox | xmllint --format -

#sudo -u http HOME="/var/lib/virtualbox/" VBOX_XPCOM_HOME=/usr/lib/virtualbox/ /usr/lib/virtualbox/VBoxXML | xmllint --format -
