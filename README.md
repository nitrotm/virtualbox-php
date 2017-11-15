# Virtualbox Web Frontend

This project provides a simple virtualbox frontend in php.

The goal is to be able to control virtual machines for doing
some quick deployments.

The main logic is to have a set of machine templates that can be
quickly deployed using differential disk images, so it takes
only a few seconds to setup a new machine and have it running.

This is not meant for production or large scale deployment, but
more as a headless controller for a shared VirtualBox host in a
dev environment.


## Requirements

- Linux host
- VirtualBox >= 4.3
- libxml2
- A webserver with php 5 or better


## VirtualBox Compatibility

Tags are available to match VirtualBox version.


## Installation

- On Debian to minimum dependencies are:

  ```
  sudo apt-get install build-essentials libxml2-dev virtualbox
  ```

- Download and unpack the "VirtualBox x.y.z Software Developer Kit (SDK)" matching the version installed:

  https://www.virtualbox.org/wiki/Downloads

  Or here for older builds:

  https://www.virtualbox.org/wiki/Download_Old_Builds

- Compile the vboxxml native tool:

  ```
  cd vboxxml
  make VBOX_PATH=/usr/lib/virtualbox XPCOM_PATH=/home/myuser/Downloads/sdk/bindings/xpcom
  sudo make VBOX_PATH=/usr/lib/virtualbox install
  ```

  This will install VboxXML into Virtualbox's directory.

- Deploy the web frontend located in www into a webserver and set options in include/config.inc.php


## License

The MIT License
