/** system.cpp */
#include "vboxxml.h"


void exportVirtualBoxOSTypes(IVirtualBox *virtualBox, xmlTextWriterPtr writer) {
	IGuestOSType **oses = NULL;
	PRUint32 osesCount = 0;
	nsresult rc = virtualBox->GetGuestOSTypes(&osesCount, &oses);

	if (NS_SUCCEEDED(rc)) {
		xmlTextWriterStartElement(writer, TOXMLCHAR("oses"));

			for (PRUint32 i = 0; i < osesCount; i++) {
				xmlTextWriterStartElement(writer, TOXMLCHAR("os"));

					// id
					ADDXMLSTRING(oses[i]->GetId, "id");

					// description
					ADDXMLSTRING(oses[i]->GetDescription, "description");

					// family
					ADDXMLSTRING(oses[i]->GetFamilyId, "family");

					// family description
					ADDXMLSTRING(oses[i]->GetFamilyDescription, "familydescription");

					// 64 bits
					ADDXMLBOOL(oses[i]->GetIs64Bit, "64bit");

					// recommended: ioapic
					ADDXMLBOOL(oses[i]->GetRecommendedIOAPIC, "ioapic");

					// recommended: virtex
					ADDXMLBOOL(oses[i]->GetRecommendedVirtEx, "virtex");

					// recommended: ram
					ADDXMLINT32U(oses[i]->GetRecommendedRAM, "memory");

					// recommended: vram
					ADDXMLINT32U(oses[i]->GetRecommendedVRAM, "vram");

					// recommended: hdd
					ADDXMLINT64(oses[i]->GetRecommendedHDD, "hdd");

					// recommended: pae
					ADDXMLBOOL(oses[i]->GetRecommendedPae, "pae");

					// recommended: hpet
					ADDXMLBOOL(oses[i]->GetRecommendedHpet, "hpet");

					// recommended: rtcuseutc
					ADDXMLBOOL(oses[i]->GetRecommendedRtcUseUtc, "rtcuseutc");

					// TODO: other recommended things
					
				xmlTextWriterEndElement(writer);
			}

		xmlTextWriterEndElement(writer);

		NS_FREE_XPCOM_ISUPPORTS_POINTER_ARRAY(osesCount, oses);
	}
}

void exportVirtualBoxSystem(IVirtualBox *virtualBox, xmlTextWriterPtr writer) {
	nsresult rc;

	xmlTextWriterStartElement(writer, TOXMLCHAR("system"));

		// version
		ADDXMLSTRING(virtualBox->GetVersion, "version");

		// revision
		ADDXMLINT32U(virtualBox->GetRevision, "revision");

		// home folder
		ADDXMLSTRING(virtualBox->GetHomeFolder, "home");

		// system properties
		{
			nsCOMPtr<ISystemProperties> systemProperties;
			nsresult rc = virtualBox->GetSystemProperties(getter_AddRefs(systemProperties));

			if (NS_SUCCEEDED(rc)) {
				// min guest ram
				ADDXMLINT32U(systemProperties->GetMinGuestRAM, "minguestmemory");

				// max guest ram
				ADDXMLINT32U(systemProperties->GetMaxGuestRAM, "maxguestmemory");

				// min guest vram
				ADDXMLINT32U(systemProperties->GetMinGuestVRAM, "minguestvram");

				// max guest vram
				ADDXMLINT32U(systemProperties->GetMaxGuestVRAM, "maxguestvram");

				// min guest cpu
				ADDXMLINT32U(systemProperties->GetMinGuestCPUCount, "minguestcpus");

				// max guest cpu
				ADDXMLINT32U(systemProperties->GetMaxGuestCPUCount, "maxguestcpus");

				// max guest monitors
				ADDXMLINT32U(systemProperties->GetMaxGuestMonitors, "maxguestmonitors");

				// network adapter count
				{
					PRUint32 networkAdaptersCount;

					rc = systemProperties->GetMaxNetworkAdapters(ChipsetType_PIIX3, &networkAdaptersCount);
					if (NS_SUCCEEDED(rc)) {
						WRITEXMLINT32("networkadapters_PIIX3", networkAdaptersCount);
					}
					rc = systemProperties->GetMaxNetworkAdapters(ChipsetType_ICH9, &networkAdaptersCount);
					if (NS_SUCCEEDED(rc)) {
						WRITEXMLINT32("networkadapters_ICH9", networkAdaptersCount);
					}
				}

				// serial port count
				ADDXMLINT32U(systemProperties->GetSerialPortCount, "serialports");

				// parallel port count
				ADDXMLINT32U(systemProperties->GetParallelPortCount, "parallelports");

				// max boot position
				ADDXMLINT32U(systemProperties->GetMaxBootPosition, "maxbootpositions");
			}
		}
		
	xmlTextWriterEndElement(writer);
}
