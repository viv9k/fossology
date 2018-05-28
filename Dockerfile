# FOSSologyNG Dockerfile
# Copyright Siemens AG 2016, fabio.huser@siemens.com
#
# Copying and distribution of this file, with or without modification,
# are permitted in any medium without royalty provided the copyright
# notice and this notice are preserved.  This file is offered as-is,
# without any warranty.
#
# Description: Docker container image recipe

FROM debian:8.8

MAINTAINER Fossology <Fossology.Support.oss@internal.siemens.com>

WORKDIR /fossologyng
COPY . .

RUN echo "Acquire::http::Proxy \"false\";" >> /etc/apt/apt.conf.d/95proxy
RUN echo "deb http://linux.siemens.de/pub/debian jessie main\n\
deb http://linux.siemens.de/pub/debian jessie-updates main\n\
deb http://linux.siemens.de/pub/debian-security jessie/updates main\n" > /etc/apt/sources.list

RUN apt-get update && \
    apt-get install -y lsb-release sudo postgresql php5-curl libpq-dev libdbd-sqlite3-perl libspreadsheet-writeexcel-perl && \
    /fossology/utils/fo-installdeps -e -y && \
    rm -rf /var/lib/apt/lists/*

# Install composer dependencies
RUN cd src && \
    wget https://linux.siemens.de/pub/tools/FOSSologyNG/php-vendor.tar && \
    tar xvf php-vendor.tar && rm -rf php-vendor.tar && cd ..

RUN /fossologyng/install/scripts/install-spdx-tools.sh

RUN /fossologyng/install/scripts/install-ninka.sh

RUN make install

RUN cp /fossology/install/src-install-apache-example.conf /etc/apache2/conf-available/fossology.conf && \
    ln -s /etc/apache2/conf-available/fossology.conf /etc/apache2/conf-enabled/fossology.conf

RUN /fossology/install/scripts/php-conf-fix.sh --overwrite

EXPOSE 8081

RUN chmod +x /fossologyng/docker-entrypoint.sh
ENTRYPOINT ["/fossologyng/docker-entrypoint.sh"]
