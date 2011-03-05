/** medium.cpp */
#include "vboxxml.h"


/**
 * Enumeration converter
 */
static const char * mediumTypeConverter(PRUint32 value) {
	switch (value) {
	case MediumType_Normal:
		return "normal";
	case MediumType_Immutable:
		return "immutable";
	case MediumType_Writethrough:
		return "writethrough";
	case MediumType_Shareable:
		return "shareable";
	case MediumType_Readonly:
		return "readonly";
	case MediumType_MultiAttach:
		return "multiattach";
	}
	return "unknown";
}


/**
 * Export VirtualBox medium
 */
void exportVirtualBoxMedium(IMedium *medium, const char *type, xmlTextWriterPtr writer) {
	PRUint32 state;
	nsresult rc = medium->RefreshState(&state);

	if (NS_SUCCEEDED(rc)) {
		xmlTextWriterStartElement(writer, TOXMLCHAR(type));

			// uuid
			ADDXMLSTRING(medium->GetId, "id");

			// location
			ADDXMLSTRING(medium->GetLocation, "path");

			// name
			ADDXMLSTRING(medium->GetName, "name");

			// description
			ADDXMLSTRING(medium->GetDescription, "description");

			// format
			ADDXMLSTRING(medium->GetFormat, "format");

			// logical size
			ADDXMLINT64(medium->GetLogicalSize, "size");

			// used size
			{
				PRInt64 value;

				rc = medium->GetSize(&value);
				if (NS_SUCCEEDED(rc)) {
					WRITEXMLINT64("usedsize", value / 1024 / 1024);
				}
			}

			// disk type
			ADDXMLENUM(medium->GetType, "type", mediumTypeConverter);

			// auto reset
			ADDXMLBOOL(medium->GetAutoReset, "autoreset");

			// read only
			ADDXMLBOOL(medium->GetReadOnly, "readonly");

			// base
			{
				nsCOMPtr<IMedium> base;

				rc = medium->GetBase(getter_AddRefs(base));
				if (NS_SUCCEEDED(rc) && base != nsnull && base != medium) {
					ADDXMLSTRING(base->GetId, "base");
				}
			}

			// parent
			{
				nsCOMPtr<IMedium> parent;

				rc = medium->GetParent(getter_AddRefs(parent));
				if (NS_SUCCEEDED(rc) && parent != nsnull) {
					ADDXMLSTRING(parent->GetId, "parent");
				}
			}

			// children
			{
				IMedium **children = nsnull;
				PRUint32 childrenCount = 0;

				rc = medium->GetChildren(&childrenCount, &children);
				if (NS_SUCCEEDED(rc)) {
					for (PRUint32 i = 0; i < childrenCount; i++) {
						char name[256];

						sprintf(name, "child%ld", (long int)i);
						ADDXMLSTRING(children[i]->GetId, name);
					}

					NS_FREE_XPCOM_ISUPPORTS_POINTER_ARRAY(childrenCount, children);
				}
			}

			// machines
			{
				PRUnichar **machines;
				PRUint32 machinesCount;

				rc = medium->GetMachineIds(&machinesCount, &machines);
				if (NS_SUCCEEDED(rc)) {
					for (PRUint32 i = 0; i < machinesCount; i++) {
						nsAutoString machine(machines[i]);
						char name[256];

						sprintf(name, "machine%ld", (long int)i);
						WRITEXMLSTRING(name, convertString(machine));
					}
				}

				NS_FREE_XPCOM_ALLOCATED_POINTER_ARRAY(machinesCount, machines);
			}

			// properties
			{
				PRUnichar **names;
				PRUnichar **values;
				PRUint32 namesCount;
				PRUint32 valuesCount;

				rc = medium->GetProperties(nsnull, &namesCount, &names, &valuesCount, &values);
				if (NS_SUCCEEDED(rc)) {
					for (PRUint32 i = 0; i < namesCount; i++) {
						nsAutoString name(names[i]);
						nsAutoString value(values[i]);

						WRITEXMLSTRING(convertString(name).c_str(), convertString(value));
					}
				}

				NS_FREE_XPCOM_ALLOCATED_POINTER_ARRAY(namesCount, names);
				NS_FREE_XPCOM_ALLOCATED_POINTER_ARRAY(valuesCount, values);
			}

		xmlTextWriterEndElement(writer);

		// export also children
		{
			IMedium **children = nsnull;
			PRUint32 childrenCount = 0;

			rc = medium->GetChildren(&childrenCount, &children);
			if (NS_SUCCEEDED(rc)) {
				for (PRUint32 i = 0; i < childrenCount; i++) {
					exportVirtualBoxMedium(children[i], type, writer);
				}
	
				NS_FREE_XPCOM_ISUPPORTS_POINTER_ARRAY(childrenCount, children);
			}
		}
	}
}


/**
 * Export VirtualBox dvds
 */
void exportVirtualBoxDvds(IVirtualBox *virtualBox, xmlTextWriterPtr writer) {
	IMedium **images = NULL;
	PRUint32 imagesCount = 0;
	nsresult rc = virtualBox->GetDVDImages(&imagesCount, &images);

	if (NS_SUCCEEDED(rc)) {
		xmlTextWriterStartElement(writer, TOXMLCHAR("dvds"));

			for (PRUint32 i = 0; i < imagesCount; i++) {
				exportVirtualBoxMedium(images[i], "dvd", writer);
			}

		xmlTextWriterEndElement(writer);

		NS_FREE_XPCOM_ISUPPORTS_POINTER_ARRAY(imagesCount, images);
	}
}

/**
 * Export VirtualBox floppies
 */
void exportVirtualBoxFdds(IVirtualBox *virtualBox, xmlTextWriterPtr writer) {
	IMedium **images = NULL;
	PRUint32 imagesCount = 0;
	nsresult rc = virtualBox->GetFloppyImages(&imagesCount, &images);

	if (NS_SUCCEEDED(rc)) {
		xmlTextWriterStartElement(writer, TOXMLCHAR("fdds"));

			for (PRUint32 i = 0; i < imagesCount; i++) {
				exportVirtualBoxMedium(images[i], "fdd", writer);
			}

		xmlTextWriterEndElement(writer);

		NS_FREE_XPCOM_ISUPPORTS_POINTER_ARRAY(imagesCount, images);
	}
}

/**
 * Export VirtualBox hdds
 */
void exportVirtualBoxHdds(IVirtualBox *virtualBox, xmlTextWriterPtr writer) {
	// export registered disks
	IMedium **images = NULL;
	PRUint32 imagesCount = 0;
	nsresult rc = virtualBox->GetHardDisks(&imagesCount, &images);

	if (NS_SUCCEEDED(rc)) {
		xmlTextWriterStartElement(writer, TOXMLCHAR("hdds"));

			for (PRUint32 i = 0; i < imagesCount; i++) {
				exportVirtualBoxMedium(images[i], "hdd", writer);
			}

		xmlTextWriterEndElement(writer);

		NS_FREE_XPCOM_ISUPPORTS_POINTER_ARRAY(imagesCount, images);
	}
}
