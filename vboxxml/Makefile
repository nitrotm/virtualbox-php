DESTDIR:=

CXX=g++
VBOX_PATH=/usr/lib/virtualbox
XPCOM_PATH=$(VBOX_PATH)/sdk/bindings/xpcom

VBOXXML_SRC:=$(wildcard vboxxml/*.cpp)
VBOXXML_OBJ:=$(VBOXXML_SRC:.cpp=.o)
VBOXXML_OUTPUT:=VBoxXML

CXXFLAGS:=-g -fshort-wchar -DRT_OS_LINUX -DVBOX_WITH_XPCOM_NAMESPACE_CLEANUP
LDFLAGS:=-Wl,-rpath $(VBOX_PATH)/ -lxml2 -ldl -lpthread $(VBOX_PATH)/VBoxDDU.so $(VBOX_PATH)/VBoxXPCOM.so
INCLUDES:=-I/usr/include \
	-I/usr/include/libxml2 \
	-I$(XPCOM_PATH)/include \
	-I$(XPCOM_PATH)/include/nsprpub \
	-I$(XPCOM_PATH)/include/string \
	-I$(XPCOM_PATH)/include/xpcom \
	-I$(XPCOM_PATH)/include/ipcd \
	-Iinclude


all: $(VBOXXML_OUTPUT)

install: $(VBOXXML_OUTPUT)
	@echo "installing binary..."
	@echo " $(VBOX_PATH)/$(VBOXXML_OUTPUT)"
	@cp $(VBOXXML_OUTPUT) $(DESTDIR)$(VBOX_PATH)/$(VBOXXML_OUTPUT)
	@chmod 755 $(DESTDIR)$(VBOX_PATH)/$(VBOXXML_OUTPUT)

distclean: clean
	@echo "cleaning binary..."
	@echo " $(VBOXXML_OUTPUT)"
	@rm -f $(VBOXXML_OUTPUT)
 
clean:
	@echo "cleaning..."
	@echo " *.o"
	rm -f $(VBOXXML_OBJ)

$(VBOXXML_OUTPUT): compile $(VBOXXML_OBJ)
	@echo "linking..."
	@$(CXX) $(CXXFLAGS) $(LDFLAGS) -o $@ $(VBOXXML_OBJ)

compile:
	@echo "compiling..."

%.o: %.cpp
	@echo " $<"
	@$(CXX) -c $(CXXFLAGS) $(INCLUDES) -o $@ $<


.PHONY: all compile clean distclean
