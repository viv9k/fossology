######################################################################
# Copyright (C) 2011-2012 Hewlett-Packard Development Company, L.P.
# Copyright (C) 2015 Siemens AG
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License version 2.1 as published by the Free Software Foundation.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with this library; if not, write to the Free Software Foundation, Inc.0
# 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
######################################################################

TOP = ../../..
VARS = $(TOP)/Makefile.conf
include $(VARS)

FILES = `find . -type f | grep -v svn |grep -v tests | grep -E "(php)$$"`

DIR = `pwd` 
THRIFTDIR = /tmp/thrift/lib/php/lib/Thrift
all:
	@echo "nothing to do"

install: all
	@if [ -d $(THRIFTDIR) ]; then \
		cp -ar $(THRIFTDIR)/ $(DIR); \
	else \
		cd /tmp && git clone https://github.com/apache/thrift.git ;\
		cp -ar $(THRIFTDIR)/ $(DIR); \
	fi
	mkdir -p $(DESTDIR)$(MODDIR)/lib/Thrift
	for file in $(FILES); do \
		$(INSTALL_DATA) $$file $(DESTDIR)$(MODDIR)/lib/Thrift/$$file; \
	done

uninstall:
	@echo "Removing thrift libs"
	rm -rf $(DESTDIR)$(MODDIR)/lib/Thrift

clean:
	@echo "Removing Thrift lib"
	rm -rf Thrift
        
test:
	@echo "nothing to do"

coverage:
	@echo "nothing to do"

.PHONY: all install uninstall clean test
