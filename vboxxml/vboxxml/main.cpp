/** main.cpp */
#include "vboxxml.h"


class Parameters {
public:
    string basePath;
    string systemPath;
    string dvdPath;
    string fddPath;
    string hddPath;
    string machinePath;

    inline Parameters() {
    }
};


/**
 * Register a medium
 */
static void registerMedium(IVirtualBox *virtualBox, const string &path, PRUint32 type, PRUint32 access, nsCOMPtr<IMedium> &medium) {
    virtualBox->OpenMedium(NS_ConvertUTF8toUTF16(nsCString(path.c_str())).get(), type, access, PR_FALSE, getter_AddRefs(medium));
}

/**
 * Register a machine
 */
static void registerMachine(IVirtualBox *virtualBox, const string &path, nsCOMPtr<IMachine> &machine) {
    virtualBox->FindMachine(NS_ConvertUTF8toUTF16(nsCString(path.c_str())).get(), getter_AddRefs(machine));
    if (machine == nsnull) {
        virtualBox->OpenMachine(NS_ConvertUTF8toUTF16(nsCString(path.c_str())).get(), getter_AddRefs(machine));
        if (machine != nsnull) {
            virtualBox->RegisterMachine(machine);
        }
    }
}

/**
 * Register all medium in specified folder
 */
static void registerMediums(IVirtualBox *virtualBox, const string &path, const string &ext, PRUint32 type, PRUint32 access, PRUint32 targetType, bool recursive) {
    DIR *dp = opendir(path.c_str());
    struct dirent *d;

    if (dp == NULL) {
        return;
    }
    while ((d = readdir(dp)) != NULL) {
        if (d->d_type == DT_REG) {
            string name(d->d_name);

            transform(name.begin(), name.end(), name.begin(), (int(*)(int))std::tolower);
            if (name.rfind(ext) == (name.length() - ext.length())) {
                nsCOMPtr<IMedium> medium;

                registerMedium(virtualBox, path + "/" + string(d->d_name), type, access, medium);
                if (medium != nsnull) {
                    medium->SetType(targetType);
                }
            }
        } else if (recursive && d->d_type == DT_DIR && strcmp(d->d_name, ".") != 0 && strcmp(d->d_name, "..") != 0) {
            registerMediums(virtualBox, path + "/" + string(d->d_name), ext, type, access, targetType, recursive);
        }
    }
    closedir(dp);
}

/**
 * Register all machines in specified folder
 */
static void registerMachines(IVirtualBox *virtualBox, const string &path) {
    DIR *dp = opendir(path.c_str());
    struct dirent *d;

    if (dp == NULL) {
        return;
    }
    while ((d = readdir(dp)) != NULL) {
        if (d->d_type == DT_REG) {
            string name(d->d_name);

            transform(name.begin(), name.end(), name.begin(), (int(*)(int))std::tolower);
            if (name.rfind(".xml") == (name.length() - 4) ||
                name.rfind(".vbox") == (name.length() - 5)) {
                nsCOMPtr<IMachine> machine;

                registerMachine(virtualBox, path + "/" + string(d->d_name), machine);
            }
        } else if (d->d_type == DT_DIR && strcmp(d->d_name, ".") != 0 && strcmp(d->d_name, "..") != 0) {
            registerMachines(virtualBox, path + "/" + string(d->d_name));
        }
    }
    closedir(dp);
}

/**
 * Register resources
 */
static void registerResources(IVirtualBox *virtualBox, const Parameters &parameters) {
    // register mediums
    if (parameters.systemPath.length() > 0) {
        registerMediums(virtualBox, parameters.systemPath + "/additions", ".iso", DeviceType_DVD, AccessMode_ReadOnly, MediumType_Readonly, true);
    }
    registerMediums(virtualBox, parameters.basePath + "/dvd", ".dmg", DeviceType_DVD, AccessMode_ReadOnly, MediumType_Readonly, true);
    registerMediums(virtualBox, parameters.basePath + "/dvd", ".iso", DeviceType_DVD, AccessMode_ReadOnly, MediumType_Readonly, true);
    registerMediums(virtualBox, parameters.basePath + "/dvd", ".cdr", DeviceType_DVD, AccessMode_ReadOnly, MediumType_Readonly, true);
    registerMediums(virtualBox, parameters.basePath + "/dvd", ".cue", DeviceType_DVD, AccessMode_ReadOnly, MediumType_Readonly, true);
    registerMediums(virtualBox, parameters.basePath + "/fdd", ".ima", DeviceType_Floppy, AccessMode_ReadOnly, MediumType_Readonly, true);
    registerMediums(virtualBox, parameters.basePath + "/fdd", ".img", DeviceType_Floppy, AccessMode_ReadOnly, MediumType_Readonly, true);
    registerMediums(virtualBox, parameters.basePath + "/fdd", ".dsk", DeviceType_Floppy, AccessMode_ReadOnly, MediumType_Readonly, true);
    registerMediums(virtualBox, parameters.basePath + "/fdd", ".flp", DeviceType_Floppy, AccessMode_ReadOnly, MediumType_Readonly, true);
    registerMediums(virtualBox, parameters.basePath + "/fdd", ".vfd", DeviceType_Floppy, AccessMode_ReadOnly, MediumType_Readonly, true);
    registerMediums(virtualBox, parameters.basePath + "/hdd", ".vdi", DeviceType_HardDisk, AccessMode_ReadOnly, MediumType_MultiAttach, true);
    registerMediums(virtualBox, parameters.basePath + "/hdd", ".vhd", DeviceType_HardDisk, AccessMode_ReadOnly, MediumType_MultiAttach, true);
    registerMediums(virtualBox, parameters.basePath + "/hdd", ".vmdk", DeviceType_HardDisk, AccessMode_ReadOnly, MediumType_MultiAttach, true);

    // register machines (with hdds)
    registerMediums(virtualBox, parameters.basePath, ".vdi", DeviceType_HardDisk, AccessMode_ReadOnly, MediumType_Normal, false);
    registerMediums(virtualBox, parameters.basePath + "/machine", ".vdi", DeviceType_HardDisk, AccessMode_ReadWrite, MediumType_Normal, true);
    registerMachines(virtualBox, parameters.basePath + "/machine");
}


/**
 * Export VirtualBox state as XML document
 */
static int exportVirtualBox(IVirtualBox *virtualBox, const Parameters &parameters) {
    // create buffer wrapper
    xmlOutputBufferPtr buffer = xmlOutputBufferCreateFile(stdout, NULL);

    if (buffer != NULL) {
        // create xml writer
        xmlTextWriterPtr writer = xmlNewTextWriter(buffer);

        if (writer != NULL) {
            // writer document
            xmlTextWriterStartDocument(writer, "1.0", "UTF-8", NULL);

                if (parameters.dvdPath.length() > 0) {
                    // export dvd
                    nsCOMPtr<IMedium> medium;

                    registerMedium(virtualBox, parameters.dvdPath, DeviceType_DVD, AccessMode_ReadOnly, medium);
                    if (medium != nsnull) {
                        exportVirtualBoxMedium(medium, "dvd", writer);
                    } else {
                        // root element
                        xmlTextWriterStartElement(writer, TOXMLCHAR("dvd"));

                            WRITEXMLSTRING("path", parameters.dvdPath);

                        xmlTextWriterEndElement(writer);
                    }
                } else if (parameters.fddPath.length() > 0) {
                    // export fdd
                    nsCOMPtr<IMedium> medium;

                    registerMedium(virtualBox, parameters.fddPath, DeviceType_Floppy, AccessMode_ReadOnly, medium);
                    if (medium != nsnull) {
                        exportVirtualBoxMedium(medium, "fdd", writer);
                    } else {
                        // root element
                        xmlTextWriterStartElement(writer, TOXMLCHAR("fdd"));

                            WRITEXMLSTRING("path", parameters.fddPath);

                        xmlTextWriterEndElement(writer);
                    }
                } else if (parameters.hddPath.length() > 0) {
                    // export hdd
                    nsCOMPtr<IMedium> medium;

                    registerMedium(virtualBox, parameters.hddPath, DeviceType_HardDisk, AccessMode_ReadOnly, medium);
                    if (medium != nsnull) {
                        exportVirtualBoxMedium(medium, "hdd", writer);
                    } else {
                        // root element
                        xmlTextWriterStartElement(writer, TOXMLCHAR("hdd"));

                            WRITEXMLSTRING("path", parameters.hddPath);

                        xmlTextWriterEndElement(writer);
                    }
                } else if (parameters.machinePath.length() > 0) {
                    // export machine
                    nsCOMPtr<IMachine> machine;

                    registerMachine(virtualBox, parameters.machinePath, machine);
                    if (machine != nsnull) {
                        exportVirtualBoxMachine(virtualBox, machine, writer);
                    } else {
                        // root element
                        xmlTextWriterStartElement(writer, TOXMLCHAR("machine"));

                            WRITEXMLSTRING("path", parameters.machinePath);

                        xmlTextWriterEndElement(writer);
                    }
                } else {
                    // export everything
                    xmlTextWriterStartElement(writer, TOXMLCHAR("virtualbox"));

                        // export system informations
                        exportVirtualBoxSystem(virtualBox, writer);

                        // export os types
                        exportVirtualBoxOSTypes(virtualBox, writer);

                        // export dvd
                        exportVirtualBoxDvds(virtualBox, writer);

                        // export fdd
                        exportVirtualBoxFdds(virtualBox, writer);

                        // export hdd
                        exportVirtualBoxHdds(virtualBox, writer);

                        // export machine
                        exportVirtualBoxMachines(virtualBox, writer);

                    xmlTextWriterEndElement(writer);
                }

            xmlTextWriterEndDocument(writer);

            // flush document
            xmlTextWriterFlush(writer);

            // free xml writer
            xmlFreeTextWriter(writer);

            return 0;
        }
    }
    return 1;
}


/**
 * Print usage
 */
static void usage() {
    printf("usage: VBoxXML --base path ( --system path ) ( [ --dvd path ] | [ --fdd path ] | [ --hdd path ] | [ --machine path ] )\n");
}


/**
 * Program entry-point
 */
int main(int argc, char *argv[]) {
    // parse command line
    Parameters parameters;

    for (int i = 1; i < argc; i++) {
        if (strcmp(argv[i], "--base") == 0 && (i + 1) < argc) {
            parameters.basePath = argv[++i];
        } else if (strcmp(argv[i], "--system") == 0 && (i + 1) < argc) {
            parameters.systemPath = argv[++i];
        } else if (strcmp(argv[i], "--dvd") == 0 && (i + 1) < argc) {
            parameters.dvdPath = argv[++i];
        } else if (strcmp(argv[i], "--fdd") == 0 && (i + 1) < argc) {
            parameters.fddPath = argv[++i];
        } else if (strcmp(argv[i], "--hdd") == 0 && (i + 1) < argc) {
            parameters.hddPath = argv[++i];
        } else if (strcmp(argv[i], "--machine") == 0 && (i + 1) < argc) {
            parameters.machinePath = argv[++i];
        } else {
            usage();
            return 2;
        }
    }
    if (parameters.basePath.length() == 0) {
        usage();
        return 2;
    }

    // initialize xpcom
    #if defined(XPCOM_GLUE)

        XPCOMGlueStartup(nsnull);

    #endif

    // acquire xpcom session
    nsCOMPtr<nsIServiceManager> serviceManager;
    nsresult rc;
    int retcode = 1;

    rc = NS_InitXPCOM2(getter_AddRefs(serviceManager), nsnull, nsnull);
    if (NS_SUCCEEDED(rc)) {
        nsCOMPtr<nsIEventQueue> eventQueue;

        rc = NS_GetMainEventQ(getter_AddRefs(eventQueue));
        if (NS_SUCCEEDED(rc)) {
            nsCOMPtr<nsIComponentManager> componentManager;

            // process event queue
            eventQueue->ProcessPendingEvents();

            rc = NS_GetComponentManager(getter_AddRefs(componentManager));
            if (NS_SUCCEEDED(rc)) {
                nsCOMPtr<IVirtualBox> virtualBox;

                // acquire virtualbox session
                rc = componentManager->CreateInstanceByContractID(NS_VIRTUALBOX_CONTRACTID, nsnull, NS_GET_IID(IVirtualBox), getter_AddRefs(virtualBox));
                if (NS_SUCCEEDED(rc)) {
                    registerResources(virtualBox, parameters);

                    retcode = exportVirtualBox(virtualBox, parameters);
                } else {
                    printf("Error: could not get virtualbox manager! rc=0x%x\n", rc);
                }
            } else {
                printf("Error: could not get main event queue! rc=%08X\n", rc);
            }

            // process event queue
            eventQueue->ProcessPendingEvents();
        } else {
            printf("Error: could not get component manager! rc=0x%x\n", rc);
        }
    } else {
        printf("Error: XPCOM could not be initialized! rc=0x%x\n", rc);
    }

    // free xpcom
    NS_ShutdownXPCOM(nsnull);

    #if defined(XPCOM_GLUE)

        XPCOMGlueShutdown();

    #endif
    return retcode;
}
