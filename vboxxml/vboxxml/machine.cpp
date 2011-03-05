/** machine.cpp */
#include "vboxxml.h"


/**
 * Enumeration converter
 */
static const char * firmwareTypeConverter(PRUint32 value) {
	switch (value) {
	case FirmwareType_BIOS:
		return "bios";
	case FirmwareType_EFI:
		return "efi";
	case FirmwareType_EFI32:
		return "efi32";
	case FirmwareType_EFI64:
		return "efi64";
	case FirmwareType_EFIDUAL:
		return "efidual";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * chipsetTypeConverter(PRUint32 value) {
	switch (value) {
	case ChipsetType_PIIX3:
		return "piix3";
	case ChipsetType_ICH9:
		return "ich9";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * pointingHidTypeConverter(PRUint32 value) {
	switch (value) {
	case PointingHidType_None:
		return "none";
	case PointingHidType_PS2Mouse:
		return "ps2";
	case PointingHidType_USBMouse:
		return "usb";
	case PointingHidType_USBTablet:
		return "usbtablet";
	case PointingHidType_ComboMouse:
		return "combo";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * keyboardHidTypeConverter(PRUint32 value) {
	switch (value) {
	case KeyboardHidType_None:
		return "none";
	case KeyboardHidType_PS2Keyboard:
		return "ps2";
	case KeyboardHidType_USBKeyboard:
		return "usb";
	case KeyboardHidType_ComboKeyboard:
		return "combo";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * deviceTypeConverter(PRUint32 value) {
	switch (value) {
	case DeviceType_Null:
		return "none";
	case DeviceType_Floppy:
		return "floppy";
	case DeviceType_DVD:
		return "dvd";
	case DeviceType_HardDisk:
		return "disk";
	case DeviceType_Network:
		return "net";
	case DeviceType_USB:
		return "usb";
	case DeviceType_SharedFolder:
		return "sharedfolder";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * stateConverter(PRUint32 value) {
	switch (value) {
	case MachineState_PoweredOff:
		return "poweroff";
	case MachineState_Starting:
		return "starting";
	case MachineState_Running:
		return "running";
	case MachineState_Stopping:
		return "stopping";
	case MachineState_Aborted:
		return "aborted";
	case MachineState_Paused:
		return "paused";
	case MachineState_Stuck:
		return "stuck";
	case MachineState_Saving:
		return "saving";
	case MachineState_Saved:
		return "saved";
	case MachineState_Restoring:
		return "restoring";
/*	case MachineState_Teleported:
	case MachineState_Teleporting:
	case MachineState_LiveSnapshotting:
	case MachineState_TeleportingPausedVM:
	case MachineState_TeleportingIn:
	case MachineState_DeletingSnapshotOnline:
	case MachineState_DeletingSnapshotPaused:
	case MachineState_RestoringSnapshot:
	case MachineState_DeletingSnapshot:
	case MachineState_SettingUp:
	case MachineState_FirstOnline:
	case MachineState_LastOnline:
	case MachineState_FirstTransient:
	case MachineState_LastTransient:*/
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * clipboardModeConverter(PRUint32 value) {
	switch (value) {
	case ClipboardMode_Disabled:
		return "disabled";
	case ClipboardMode_HostToGuest:
		return "host";
	case ClipboardMode_GuestToHost:
		return "guest";
	case ClipboardMode_Bidirectional:
		return "bidirectional";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * biosBootMenuModeConverter(PRUint32 value) {
	switch (value) {
	case BIOSBootMenuMode_Disabled:
		return "disabled";
	case BIOSBootMenuMode_MenuOnly:
		return "menuonly";
	case BIOSBootMenuMode_MessageAndMenu:
		return "messageandmenu";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * controllerTypeConverter(PRUint32 value) {
	switch (value) {
	case StorageControllerType_Null:
		return "none";
	case StorageControllerType_LsiLogic:
		return "LSILogic";
	case StorageControllerType_LsiLogicSas:
		return "LSILogicSAS";
	case StorageControllerType_BusLogic:
		return "BusLogic";
	case StorageControllerType_IntelAhci:
		return "IntelAHCI";
	case StorageControllerType_PIIX3:
		return "PIIX3";
	case StorageControllerType_PIIX4:
		return "PIIX4";
	case StorageControllerType_ICH6:
		return "ICH6";
	case StorageControllerType_I82078:
		return "I82078";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * networkAttachmentTypeConverter(PRUint32 value) {
	switch (value) {
	case NetworkAttachmentType_Null:
		return "none";
	case NetworkAttachmentType_NAT:
		return "nat";
	case NetworkAttachmentType_Bridged:
		return "bridged";
	case NetworkAttachmentType_Internal:
		return "intnet";
	case NetworkAttachmentType_HostOnly:
		return "hostonly";
	case NetworkAttachmentType_VDE:
		return "vde";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * networkAdapterTypeConverter(PRUint32 value) {
	switch (value) {
	case NetworkAdapterType_Null:
		return "none";
	case NetworkAdapterType_Am79C970A:
		return "Am79C970A";
	case NetworkAdapterType_Am79C973:
		return "Am79C973";
	case NetworkAdapterType_I82540EM:
		return "82540EM";
	case NetworkAdapterType_I82543GC:
		return "82543GC";
	case NetworkAdapterType_I82545EM:
		return "82545EM";
	case NetworkAdapterType_Virtio:
		return "virtio";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * audioDriverTypeConverter(PRUint32 value) {
	switch (value) {
	case AudioDriverType_Null:
		return "none";
	case AudioDriverType_WinMM:
		return "winmm";
	case AudioDriverType_OSS:
		return "oss";
	case AudioDriverType_ALSA:
		return "alsa";
	case AudioDriverType_DirectSound:
		return "directsound";
	case AudioDriverType_CoreAudio:
		return "coreaudio";
	case AudioDriverType_MMPM:
		return "mmpm";
	case AudioDriverType_Pulse:
		return "pulse";
	case AudioDriverType_SolAudio:
		return "solaudio";
	}
	return "unknown";
}

/**
 * Enumeration converter
 */
static const char * audioControllerTypeConverter(PRUint32 value) {
	switch (value) {
	case AudioControllerType_AC97:
		return "ac97";
	case AudioControllerType_SB16:
		return "sb16";
	}
	return "unknown";
}


/**
 * Export VirtualBox bios settings
 */
static void exportVirtualBoxBIOSSettings(IBIOSSettings *biosSettings, xmlTextWriterPtr writer) {
	// menu mode
	ADDXMLENUM(biosSettings->GetBootMenuMode, "biosbootmenu", biosBootMenuModeConverter);

	// logo fade in
	ADDXMLBOOL(biosSettings->GetLogoFadeIn, "bioslogofadein");

	// logo fade out
	ADDXMLBOOL(biosSettings->GetLogoFadeOut, "bioslogofadeout");

	// logo display time
	ADDXMLINT32U(biosSettings->GetLogoDisplayTime, "bioslogodisplaytime");

	// logo image path
	ADDXMLSTRING(biosSettings->GetLogoImagePath, "bioslogoimagepath");

	// time offset
	ADDXMLINT64(biosSettings->GetTimeOffset, "biossystemtimeoffset");

	// acpi
	ADDXMLBOOL(biosSettings->GetACPIEnabled, "acpi");

	// ioapic
	ADDXMLBOOL(biosSettings->GetIOAPICEnabled, "ioapic");
}


/**
 * Export VirtualBox storage controller
 */
static void exportVirtualBoxStorageController(IStorageController *storageController, PRUint32 index, xmlTextWriterPtr writer) {
	char name[256];

	// name
	sprintf(name, "storagecontrollername%d", index);
	ADDXMLSTRING(storageController->GetName, name);

	// type
	sprintf(name, "storagecontrollertype%d", index);
	ADDXMLENUM(storageController->GetControllerType, name, controllerTypeConverter);

	// instance
	sprintf(name, "storagecontrollerinstance%d", index);
	ADDXMLINT32U(storageController->GetInstance, name);

	// port count
	sprintf(name, "storagecontrollerportcount%d", index);
	ADDXMLINT32U(storageController->GetPortCount, name);

	// min port count
	sprintf(name, "storagecontrollerminportcount%d", index);
	ADDXMLINT32U(storageController->GetMinPortCount, name);

	// max port count
	sprintf(name, "storagecontrollermaxportcount%d", index);
	ADDXMLINT32U(storageController->GetMaxPortCount, name);

	// max device per port
	sprintf(name, "storagecontrollermaxdeviceperport%d", index);
	ADDXMLINT32U(storageController->GetMaxDevicesPerPortCount, name);
}


/**
 * Export VirtualBox medium attachment
 */
static void exportVirtualBoxMediumAttachment(IMediumAttachment *mediumAttachment, xmlTextWriterPtr writer) {
	// find info
	string controller;
	PRInt32 port = 0;
	PRInt32 device = 0;
	PRUint32 type = 0;
	nsCOMPtr<IMedium> medium;

	{
		nsXPIDLString value;
		nsresult rc = mediumAttachment->GetController(getter_Copies(value));

		if (NS_SUCCEEDED(rc)) {
			controller = convertString(value);
		}
	}
	mediumAttachment->GetPort(&port);
	mediumAttachment->GetDevice(&device);
	mediumAttachment->GetType(&type);
	mediumAttachment->GetMedium(getter_AddRefs(medium));

	// slot description
	char name[1024];
	string location = "none";

	if (type == DeviceType_Floppy || type == DeviceType_DVD) {
		location = "emptydrive";
	}
	if (medium != nsnull) {
		nsXPIDLString value;
		nsresult rc = medium->GetLocation(getter_Copies(value));

		if (NS_SUCCEEDED(rc)) {
			location = convertString(value);
		}
	}

	sprintf(name, "%s-%ld-%ld", controller.c_str(), (long int)port, (long int)device);
	WRITEXMLSTRING(name, location);

	if (medium != nsnull) {
		nsXPIDLString value;
		nsresult rc = medium->GetId(getter_Copies(value));
		string uuid;

		if (NS_SUCCEEDED(rc)) {
			uuid = convertString(value);
		}

		sprintf(name, "%s-ImageUUID-%ld-%ld", controller.c_str(), (long int)port, (long int)device);
		WRITEXMLSTRING(name, uuid);
	}
}

/**
 * Export VirtualBox network adapter
 */
static void exportVirtualBoxNetworkAdapter(INetworkAdapter *networkAdapter, PRUint32 index, xmlTextWriterPtr writer) {
	char name[256];

	// find info
	PRUint32 attachmentType = 0;

	networkAdapter->GetAttachmentType(&attachmentType);

	// attachment type
	sprintf(name, "nic%d", index);
	ADDXMLENUM(networkAdapter->GetAttachmentType, name, networkAttachmentTypeConverter);

	switch (attachmentType) {
	case NetworkAttachmentType_NAT:
	case NetworkAttachmentType_Bridged:
	case NetworkAttachmentType_Internal:
	case NetworkAttachmentType_HostOnly:
	case NetworkAttachmentType_VDE:
		// adapter type
		sprintf(name, "nictype%d", index);
		ADDXMLENUM(networkAdapter->GetAdapterType, name, networkAdapterTypeConverter);

		// mac address
		sprintf(name, "macaddress%d", index);
		ADDXMLSTRING(networkAdapter->GetMACAddress, name);

		// cable connected
		sprintf(name, "cableconnected%d", index);
		ADDXMLBOOL(networkAdapter->GetCableConnected, name);

		// speed
//		sprintf(name, "nicspeed%d", index);
//		ADDXMLINT32U(networkAdapter->GetLineSpeed, name);
		break;
	}

	switch (attachmentType) {
	case NetworkAttachmentType_NAT:
		// nat network
		sprintf(name, "natnet%d", index);
		ADDXMLSTRING(networkAdapter->GetNATNetwork, name);
		break;

	case NetworkAttachmentType_Bridged:
		// host adapter
		sprintf(name, "bridgeadapter%d", index);
		ADDXMLSTRING(networkAdapter->GetHostInterface, name);
		break;

	case NetworkAttachmentType_Internal:
		// internal network
		sprintf(name, "intnet%d", index);
		ADDXMLSTRING(networkAdapter->GetInternalNetwork, name);
		break;

	case NetworkAttachmentType_HostOnly:
		// host adapter
		sprintf(name, "hostonlyadapter%d", index);
		ADDXMLSTRING(networkAdapter->GetHostInterface, name);
		break;

	case NetworkAttachmentType_VDE:
		// vde network
		sprintf(name, "vde%d", index);
		ADDXMLSTRING(networkAdapter->GetVDENetwork, name);
		break;
	}
}

/**
 * Export VirtualBox audio adapter
 */
static void exportVirtualBoxAudioAdapter(IAudioAdapter *audioAdapter, xmlTextWriterPtr writer) {
	// find info
	PRUint32 audioDriverType = 0;

	audioAdapter->GetAudioDriver(&audioDriverType);

	// driver type
	ADDXMLENUM(audioAdapter->GetAudioDriver, "audio", audioDriverTypeConverter);

	switch (audioDriverType) {
	case AudioDriverType_WinMM:
	case AudioDriverType_OSS:
	case AudioDriverType_ALSA:
	case AudioDriverType_DirectSound:
	case AudioDriverType_CoreAudio:
	case AudioDriverType_MMPM:
	case AudioDriverType_Pulse:
	case AudioDriverType_SolAudio:
		// audio controller
		ADDXMLENUM(audioAdapter->GetAudioController, "audiocontroller", audioControllerTypeConverter);
		break;
	}
}


/**
 * Export VirtualBox vrdp server (obsolete)
 *
static void exportVirtualBoxVRDPServer(IVRDPServer *vrdpServer, xmlTextWriterPtr writer) {
	// find info
	PRBool enabled = PR_FALSE;

	vrdpServer->GetEnabled(&enabled);

	// vrdp enabled
	ADDXMLBOOL(vrdpServer->GetEnabled, "vrdp");

	if (enabled == PR_TRUE) {
		// vrdp address
		ADDXMLSTRING(vrdpServer->GetNetAddress, "vrdpaddress");

		// vrdp ports
		ADDXMLSTRING(vrdpServer->GetPorts, "vrdpport");

		// TODO: vrdp auth, ...
	}
}
*/


/**
 * Export VirtualBox vrde server
 */
static void exportVirtualBoxVRDEServer(IVRDEServer *vrdeServer, xmlTextWriterPtr writer) {
	nsresult rc;

	// find info
	PRBool enabled = PR_FALSE;

	vrdeServer->GetEnabled(&enabled);

	// vrde enabled
	ADDXMLBOOL(vrdeServer->GetEnabled, "vrde");

	if (enabled == PR_TRUE) {
		// vrde extpack
		ADDXMLSTRING(vrdeServer->GetVRDEExtPack, "vrde.ext");
		
		// vrde auth lib
		ADDXMLSTRING(vrdeServer->GetAuthLibrary, "vrde.authlib");

		// vrde properties		
		{
			PRUnichar **properties = nsnull;
			PRUint32 propertiesCount = 0;
			nsAutoString keyPrefix;
			
			rc = vrdeServer->GetVRDEProperties(&propertiesCount, &properties);
			if (NS_SUCCEEDED(rc)) {
				for (PRUint32 i = 0; i < propertiesCount; i++) {
					nsXPIDLString value;

					rc = vrdeServer->GetVRDEProperty(properties[i], getter_Copies(value));
					if (NS_SUCCEEDED(rc)) {
						nsCAutoString key("vrde.");

						key.AppendWithConversion(properties[i]);
						WRITEXMLSTRING(convertString(key).c_str(), convertString(value));
					}
				}

				NS_FREE_XPCOM_ALLOCATED_POINTER_ARRAY(propertiesCount, properties);
			}
		}
	}
}


/**
 * Export VirtualBox usb controller
 */
static void exportVirtualBoxUSBController(IUSBController *usbController, xmlTextWriterPtr writer) {
	// find info
	PRBool enabled = PR_FALSE;

	usbController->GetEnabled(&enabled);

	// usb enabled
	ADDXMLBOOL(usbController->GetEnabled, "usb");

	if (enabled == PR_TRUE) {
		// ehci
		ADDXMLBOOL(usbController->GetEnabledEhci, "usbehci");
	}
}


/**
 * Export VirtualBox machine
 */
void exportVirtualBoxMachine(IVirtualBox *virtualBox, IMachine *machine, xmlTextWriterPtr writer) {
	nsCOMPtr<ISystemProperties> systemProperties;
	nsresult rc = virtualBox->GetSystemProperties(getter_AddRefs(systemProperties));

	if (NS_SUCCEEDED(rc)) {
		xmlTextWriterStartElement(writer, TOXMLCHAR("machine"));

			// uuid
			ADDXMLSTRING(machine->GetId, "id");

			// name
			ADDXMLSTRING(machine->GetName, "name");

			// description
			ADDXMLSTRING(machine->GetDescription, "description");

			// os type
			ADDXMLSTRING(machine->GetOSTypeId, "ostype");

			// settings file
			ADDXMLSTRING(machine->GetSettingsFilePath, "path");

			// hardware uuid
			ADDXMLSTRING(machine->GetHardwareUUID, "hardwareuuid");

			// memory size
			ADDXMLINT32U(machine->GetMemorySize, "memory");

			// memory balloon size
			ADDXMLINT32U(machine->GetMemoryBalloonSize, "memoryballoon");

			// page fusion
			ADDXMLBOOL(machine->GetPageFusionEnabled, "pagefusion");

			// vram size
			ADDXMLINT32U(machine->GetVRAMSize, "vram");

			// hpet
			ADDXMLBOOL(machine->GetHpetEnabled, "hpet");

			// cpu count
			ADDXMLINT32U(machine->GetCPUCount, "cpus");

			// cpu execution cap
			ADDXMLINT32U(machine->GetCPUExecutionCap, "cpucap");

			// cpu hotplug
			ADDXMLBOOL(machine->GetCPUHotPlugEnabled, "cpuhotplug");

			// synthcpu
			{
				PRBool value;

				rc = machine->GetCPUProperty(CPUPropertyType_Synthetic, &value);
				if (NS_SUCCEEDED(rc)) {
					WRITEXMLBOOL("synthcpu", value);
				}
			}

			// firmware type
			ADDXMLENUM(machine->GetFirmwareType, "firmware", firmwareTypeConverter);

			// bios settings
			{
				nsCOMPtr<IBIOSSettings> value;

				rc = machine->GetBIOSSettings(getter_AddRefs(value));
				if (NS_SUCCEEDED(rc)) {
					exportVirtualBoxBIOSSettings(value, writer);
				}
			}

			// boot order
			{
				PRUint32 bootPositions;
	
				rc = systemProperties->GetMaxBootPosition(&bootPositions);
				if (NS_SUCCEEDED(rc)) {
					for (PRUint32 i = 1; i <= bootPositions; i++) {
						PRUint32 value;

						rc = machine->GetBootOrder(i, &value);
						if (NS_SUCCEEDED(rc)) {
							char name[256];

							sprintf(name, "boot%d", i);
							WRITEXMLENUM(name, value, deviceTypeConverter);
						}
					}
				}
			}

			// pae
			{
				PRBool value;

				rc = machine->GetCPUProperty(CPUPropertyType_PAE, &value);
				if (NS_SUCCEEDED(rc)) {
					WRITEXMLBOOL("pae", value);
				}
			}

			// rtc use utc
			ADDXMLBOOL(machine->GetRTCUseUTC, "rtcuseutc");

			// monitor count
			ADDXMLINT32U(machine->GetMonitorCount, "monitorcount");

			// accelerate 3D
			ADDXMLBOOL(machine->GetAccelerate3DEnabled, "accelerate3d");

			// accelerate 2D video
			ADDXMLBOOL(machine->GetAccelerate2DVideoEnabled, "accelerate2dvideo");

			// hwvirtex
			{
				PRBool value;

				rc = machine->GetCPUProperty(HWVirtExPropertyType_Enabled, &value);
				if (NS_SUCCEEDED(rc)) {
					WRITEXMLBOOL("hwvirtex", value);
				}
			}

			// hwvirtexexcl
			{
				PRBool value;

				rc = machine->GetHWVirtExProperty(HWVirtExPropertyType_Exclusive, &value);
				if (NS_SUCCEEDED(rc)) {
					WRITEXMLBOOL("hwvirtexexcl", value);
				}
			}

			// nestedpaging
			{
				PRBool value;

				rc = machine->GetHWVirtExProperty(HWVirtExPropertyType_NestedPaging, &value);
				if (NS_SUCCEEDED(rc)) {
					WRITEXMLBOOL("nestedpaging", value);
				}
			}

			// largepages
			{
				PRBool value;

				rc = machine->GetHWVirtExProperty(HWVirtExPropertyType_LargePages, &value);
				if (NS_SUCCEEDED(rc)) {
					WRITEXMLBOOL("largepages", value);
				}
			}

			// vtxvpid
			{
				PRBool value;

				rc = machine->GetHWVirtExProperty(HWVirtExPropertyType_VPID, &value);
				if (NS_SUCCEEDED(rc)) {
					WRITEXMLBOOL("vtxvpid", value);
				}
			}

			// io cache
			ADDXMLBOOL(machine->GetIoCacheEnabled, "iocache");

			// io cache size
			ADDXMLINT32U(machine->GetIoCacheSize, "iocachesize");

			// io cache size (obsolete)
//			ADDXMLINT32U(machine->GetIoBandwidthMax, "iobandwidth");

			// chipset type
			ADDXMLENUM(machine->GetChipsetType, "chipset", chipsetTypeConverter);

			// pointing hid type
			ADDXMLENUM(machine->GetPointingHidType, "mouse", pointingHidTypeConverter);

			// keyboard hid type
			ADDXMLENUM(machine->GetKeyboardHidType, "keyboard", keyboardHidTypeConverter);

			// clipboard mode
			ADDXMLENUM(machine->GetClipboardMode, "clipboard", clipboardModeConverter);

			// vm state
			ADDXMLENUM(machine->GetState, "state", stateConverter);

			// vm state change
			ADDXMLTIMESTAMP(machine->GetLastStateChange, "statechanged");

			// teleporterenabled, teleporterport, teleporteraddress, teleporterpassword

			// storage controller
			{
				IStorageController **storageControllers = nsnull;
				PRUint32 storageControllersCount = 0;

				rc = machine->GetStorageControllers(&storageControllersCount, &storageControllers);
				if (NS_SUCCEEDED(rc)) {
					for (PRUint32 i = 0; i < storageControllersCount; i++) {
						exportVirtualBoxStorageController(storageControllers[i], i, writer);
					}
				}

				NS_FREE_XPCOM_ISUPPORTS_POINTER_ARRAY(storageControllersCount, storageControllers);
			}

			// medium attachments
			{
				IMediumAttachment **mediumAttachments = nsnull;
				PRUint32 mediumAttachmentsCount = 0;

				rc = machine->GetMediumAttachments(&mediumAttachmentsCount, &mediumAttachments);
				if (NS_SUCCEEDED(rc)) {
					for (PRUint32 i = 0; i < mediumAttachmentsCount; i++) {
						exportVirtualBoxMediumAttachment(mediumAttachments[i], writer);
					}
				}

				NS_FREE_XPCOM_ISUPPORTS_POINTER_ARRAY(mediumAttachmentsCount, mediumAttachments);
			}

			// network adapters
			{
				PRUint32 networkAdaptersCount;
	
				rc = systemProperties->GetNetworkAdapterCount(&networkAdaptersCount);
				if (NS_SUCCEEDED(rc)) {
					for (PRUint32 i = 0; i < networkAdaptersCount; i++) {
						nsCOMPtr<INetworkAdapter> networkAdapter;

						rc = machine->GetNetworkAdapter(i, getter_AddRefs(networkAdapter));
						if (NS_SUCCEEDED(rc)) {
							exportVirtualBoxNetworkAdapter(networkAdapter, i + 1, writer);
						}
					}
				}
			}

			// uartX

			// audio adapter
			{
				nsCOMPtr<IAudioAdapter> value;

				rc = machine->GetAudioAdapter(getter_AddRefs(value));
				if (NS_SUCCEEDED(rc)) {
					exportVirtualBoxAudioAdapter(value, writer);
				}
			}

			// vrdp server (obsolete)
/*			{
				nsCOMPtr<IVRDPServer> value;

				rc = machine->GetVRDPServer(getter_AddRefs(value));
				if (NS_SUCCEEDED(rc)) {
					exportVirtualBoxVRDPServer(value, writer);
				}
			}*/

			// vrde server
			{
				nsCOMPtr<IVRDEServer> value;

				rc = machine->GetVRDEServer(getter_AddRefs(value));
				if (NS_SUCCEEDED(rc)) {
					exportVirtualBoxVRDEServer(value, writer);
				}
			}

			// usb controller
			{
				nsCOMPtr<IUSBController> value;

				rc = machine->GetUSBController(getter_AddRefs(value));
				if (NS_SUCCEEDED(rc)) {
					exportVirtualBoxUSBController(value, writer);
				}
			}

			// guest properties
			{
				PRUnichar **names = nsnull;
				PRUnichar **values = nsnull;
				PRInt64 *timestamps = nsnull;
				PRUnichar **flags = nsnull;
				PRUint32 namesCount = 0;
				PRUint32 valuesCount = 0;
				PRUint32 timestampsCount = 0;
				PRUint32 flagsCount = 0;

				rc = machine->EnumerateGuestProperties((const PRUnichar*)nsnull, &namesCount, &names, &valuesCount, &values, &timestampsCount, &timestamps, &flagsCount, &flags);
				if (NS_SUCCEEDED(rc)) {
					for (PRUint32 i = 0; i < namesCount; i++) {
						nsAutoString name(names[i]);
						nsAutoString value(values[i]);
						PRUint64 timestamp = timestamps[i];
						nsAutoString flag(flags[i]);

						WRITEXMLSTRING(convertString(name).c_str(), convertString(value));
					}
				}

				NS_FREE_XPCOM_ALLOCATED_POINTER_ARRAY(namesCount, names);
				NS_FREE_XPCOM_ALLOCATED_POINTER_ARRAY(valuesCount, values);
				nsMemory::Free(timestamps);
				NS_FREE_XPCOM_ALLOCATED_POINTER_ARRAY(flagsCount, flags);
			}

		xmlTextWriterEndElement(writer);
	}
}


/**
 * Export VirtualBox machines
 */
void exportVirtualBoxMachines(IVirtualBox *virtualBox, xmlTextWriterPtr writer) {
	IMachine **machines = NULL;
	PRUint32 machinesCount = 0;
	nsresult rc = virtualBox->GetMachines(&machinesCount, &machines);

	if (NS_SUCCEEDED(rc)) {
		xmlTextWriterStartElement(writer, TOXMLCHAR("machines"));

			for (PRUint32 i = 0; i < machinesCount; i++) {
				PRBool accessible = PR_FALSE;

				rc = machines[i]->GetAccessible(&accessible);
				if (NS_SUCCEEDED(rc) && accessible) {
					exportVirtualBoxMachine(virtualBox, machines[i], writer);
				}
			}

		xmlTextWriterEndElement(writer);

		NS_FREE_XPCOM_ISUPPORTS_POINTER_ARRAY(machinesCount, machines);
	}
}
