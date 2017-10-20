/** vboxxml.h */
#ifndef _VBOXXML_H_INCLUDE_
#define _VBOXXML_H_INCLUDE_


/** Standard libraries */
#include "vbox.h"


/** Libxml libraries */
#include <libxml/encoding.h>
#include <libxml/xmlwriter.h>


/** XML macros */
#define TOXMLCHAR(x)    ((const xmlChar*)((const char*)(x)))
#define TOXMLBOOL(x)    ((const xmlChar*)(x == PR_FALSE ? "off" : "on"))


#define WRITEXMLBOOL(name, value)                                               \
    if (value == PR_TRUE) {                                                     \
        xmlTextWriterStartElement(writer, TOXMLCHAR("variable"));               \
        xmlTextWriterWriteAttribute(writer, TOXMLCHAR("key"), TOXMLCHAR(name)); \
        xmlTextWriterWriteString(writer, TOXMLBOOL(value));                     \
        xmlTextWriterEndElement(writer);                                        \
    }

#define WRITEXMLINT16(name, value)                                              \
    xmlTextWriterStartElement(writer, TOXMLCHAR("variable"));                   \
    xmlTextWriterWriteAttribute(writer, TOXMLCHAR("key"), TOXMLCHAR(name));     \
    xmlTextWriterWriteFormatString(writer, "%d", (int)value);                   \
    xmlTextWriterEndElement(writer);

#define WRITEXMLINT32(name, value)                                              \
    xmlTextWriterStartElement(writer, TOXMLCHAR("variable"));                   \
    xmlTextWriterWriteAttribute(writer, TOXMLCHAR("key"), TOXMLCHAR(name));     \
    xmlTextWriterWriteFormatString(writer, "%ld", (long int)value);             \
    xmlTextWriterEndElement(writer);

#define WRITEXMLINT64(name, value)                                              \
    xmlTextWriterStartElement(writer, TOXMLCHAR("variable"));                   \
    xmlTextWriterWriteAttribute(writer, TOXMLCHAR("key"), TOXMLCHAR(name));     \
    xmlTextWriterWriteFormatString(writer, "%lld", (long long int)value);       \
    xmlTextWriterEndElement(writer);

#define WRITEXMLSTRING(name, value)                                             \
    if (value.length() > 0) {                                                   \
        xmlTextWriterStartElement(writer, TOXMLCHAR("variable"));               \
        xmlTextWriterWriteAttribute(writer, TOXMLCHAR("key"), TOXMLCHAR(name)); \
        xmlTextWriterWriteString(writer, TOXMLCHAR(value.c_str()));             \
        xmlTextWriterEndElement(writer);                                        \
    }

#define WRITEXMLENUM(name, value, converter)                                    \
    xmlTextWriterStartElement(writer, TOXMLCHAR("variable"));                   \
    xmlTextWriterWriteAttribute(writer, TOXMLCHAR("key"), TOXMLCHAR(name));     \
    xmlTextWriterWriteString(writer, TOXMLCHAR(converter(value)));              \
    xmlTextWriterEndElement(writer);

#define WRITEXMLTIMESTAMP(name, value)                                          \
    xmlTextWriterStartElement(writer, TOXMLCHAR("variable"));                   \
    xmlTextWriterWriteAttribute(writer, TOXMLCHAR("key"), TOXMLCHAR(name));     \
    {                                                                           \
        char buffer[256];                                                       \
        char buffer2[256];                                                      \
        time_t t = (time_t)(value / 1000);                                      \
        struct tm *tt = gmtime(&t);                                             \
                                                                                \
        strftime(                                                               \
            buffer,                                                             \
            sizeof(buffer) / sizeof(char),                                      \
            "%Y-%m-%dT%H:%M:%S",                                                \
            tt                                                                  \
        );                                                                      \
        sprintf(buffer2, "%s.%03ld", buffer, (value % 1000));                   \
        xmlTextWriterWriteString(writer, TOXMLCHAR(buffer2));                   \
    }                                                                           \
    xmlTextWriterEndElement(writer);


#define ADDXMLBOOL(getter, name) {                      \
        PRBool value;                                   \
        nsresult rc = getter(&value);                   \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLBOOL(name, value);                  \
        }                                               \
    }

#define ADDXMLINT16(getter, name) {                     \
        PRInt16 value;                                  \
        nsresult rc = getter(&value);                   \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLINT16(name, value);                 \
        }                                               \
    }

#define ADDXMLINT16U(getter, name) {                    \
        PRUint16 value;                                 \
        nsresult rc = getter(&value);                   \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLINT16(name, value);                 \
        }                                               \
    }

#define ADDXMLINT32(getter, name) {                     \
        PRInt32 value;                                  \
        nsresult rc = getter(&value);                   \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLINT32(name, value);                 \
        }                                               \
    }

#define ADDXMLINT32U(getter, name) {                    \
        PRUint32 value;                                 \
        nsresult rc = getter(&value);                   \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLINT32(name, value);                 \
        }                                               \
    }

#define ADDXMLINT64(getter, name) {                     \
        PRInt64 value;                                  \
        nsresult rc = getter(&value);                   \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLINT64(name, value);                 \
        }                                               \
    }

#define ADDXMLINT64U(getter, name) {                    \
        PRUint64 value;                                 \
        nsresult rc = getter(&value);                   \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLINT64(name, value);                 \
        }                                               \
    }

#define ADDXMLSTRING(getter, name) {                    \
        nsXPIDLString value;                            \
        nsresult rc = getter(getter_Copies(value));     \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLSTRING(name, convertString(value)); \
        }                                               \
    }

#define ADDXMLENUM(getter, name, converter) {           \
        PRUint32 value;                                 \
        nsresult rc = getter(&value);                   \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLENUM(name, value, converter);       \
        }                                               \
    }

#define ADDXMLTIMESTAMP(getter, name) {                 \
        PRInt64 value;                                  \
        nsresult rc = getter(&value);                   \
                                                        \
        if (NS_SUCCEEDED(rc)) {                         \
            WRITEXMLTIMESTAMP(name, value);             \
        }                                               \
    }


/** XML export routines */
void exportVirtualBoxSystem(IVirtualBox *virtualBox, xmlTextWriterPtr writer);
void exportVirtualBoxOSTypes(IVirtualBox *virtualBox, xmlTextWriterPtr writer);
void exportVirtualBoxDvds(IVirtualBox *virtualBox, xmlTextWriterPtr writer);
void exportVirtualBoxFdds(IVirtualBox *virtualBox, xmlTextWriterPtr writer);
void exportVirtualBoxHdds(IVirtualBox *virtualBox, xmlTextWriterPtr writer);
void exportVirtualBoxMedium(IMedium *medium, const char *type, xmlTextWriterPtr writer);
void exportVirtualBoxMachines(IVirtualBox *virtualBox, xmlTextWriterPtr writer);
void exportVirtualBoxMachine(IVirtualBox *virtualBox, IMachine *machine, xmlTextWriterPtr writer);


#endif //_VBOXXML_H_INCLUDE_
