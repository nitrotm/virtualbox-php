/** vbox.h */
#ifndef _VBOX_H_INCLUDE_
#define _VBOX_H_INCLUDE_


/** Standard libraries */
#include <stdio.h>
#include <stdlib.h>
#include <iconv.h>
#include <errno.h>
#include <dirent.h>
#include <time.h>
#include <string>
#include <vector>
#include <cctype>
#include <algorithm>

using namespace std;


/** XPCOM / VirtualBox libraries */
#if defined(XPCOM_GLUE)
#include <nsXPCOMGlue.h>
#endif

#include <nsMemory.h>
#include <nsString.h>
#include <nsIServiceManager.h>
#include <nsEventQueueUtils.h>
#include <nsIExceptionService.h>

#include "VirtualBox_XPCOM.h"


/**
 * Convert XPCOM nsString to std::string
 */
inline string convertString(const nsString &str) {
	char *ptr = ToNewUTF8String(str);
	string value(ptr);

	free(ptr);
	return value;
}
inline string convertString(const nsCString &str) {
	char *ptr = ToNewCString(str);
	string value(ptr);

	free(ptr);
	return value;
}


#endif //_VBOX_H_INCLUDE_
