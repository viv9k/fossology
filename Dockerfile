# FOSSologyNG Dockerfile
# Copyright Siemens AG 2016, fabio.huser@siemens.com
# Copyright TNG Technology Consulting GmbH 2016-2017, maximilian.huber@tngtech.com
#
# Copying and distribution of this file, with or without modification,
# are permitted in any medium without royalty provided the copyright
# notice and this notice are preserved.  This file is offered as-is,
# without any warranty.
#
# Description: Docker container image recipe

FROM debian:jessie as builder

LABEL maintainer="Fossology <Fossology.Support.oss@internal.siemens.com>"

WORKDIR /fossologyng

RUN echo "check_certificate=off" >> /etc/wgetrc
RUN echo "--insecure" >> ~/.curlrc

RUN echo "Acquire::http::Proxy \"false\";" >> /etc/apt/apt.conf.d/95proxy
RUN echo "deb http://linux.siemens.de/pub/debian jessie main\n\
deb http://linux.siemens.de/pub/debian jessie-updates main\n\
deb http://linux.siemens.de/pub/debian-security jessie/updates main\n" > /etc/apt/sources.list

RUN DEBIAN_FRONTEND=noninteractive apt-get update \
 && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
      git \
      lsb-release \
      php5-cli \
      sudo \
 && rm -rf /var/lib/apt/lists/*

COPY ./utils/fo-installdeps ./utils/fo-installdeps
COPY ./utils/utils.sh ./utils/utils.sh
COPY ./src/delagent/mod_deps ./src/delagent/
COPY ./src/mimetype/mod_deps ./src/mimetype/
COPY ./src/pkgagent/mod_deps ./src/pkgagent/
COPY ./src/scheduler/mod_deps ./src/scheduler/
COPY ./src/ununpack/mod_deps ./src/ununpack/
COPY ./src/wget_agent/mod_deps ./src/wget_agent/

RUN mkdir -p /fossology/dependencies-for-runtime \
 && cp -R /fossology/src /fossology/utils /fossology/dependencies-for-runtime/

RUN DEBIAN_FRONTEND=noninteractive apt-get update \
 && DEBIAN_FRONTEND=noninteractive /fossologyng/utils/fo-installdeps --build -y \
 && rm -rf /var/lib/apt/lists/* 

COPY . .

# Install composer dependencies
RUN cd src && \
    wget https://linux.siemens.de/pub/tools/FOSSologyNG/php-vendor.tar && \
    tar xvf php-vendor.tar && rm -rf php-vendor.tar && cd ..

RUN /fossologyng/utils/install_composer.sh

RUN /fossologyng/install/scripts/install-spdx-tools.sh

RUN /fossologyng/install/scripts/install-ninka.sh

RUN make install clean


FROM debian:jessie

LABEL maintainer="Fossology <fossology@fossology.org>"

### install dependencies
COPY --from=builder /fossologyng/dependencies-for-runtime /fossologyng

WORKDIR /fossologyng

RUN DEBIAN_FRONTEND=noninteractive apt-get update \
 && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
      curl \
      lsb-release \
      sudo \
 && DEBIAN_FRONTEND=noninteractive /fossologyng/utils/fo-installdeps --offline --runtime -y \
 && DEBIAN_FRONTEND=noninteractive apt-get purge -y lsb-release \
 && DEBIAN_FRONTEND=noninteractive apt-get autoremove -y \
 && rm -rf /var/lib/apt/lists/*

# configure php
COPY ./install/scripts/php-conf-fix.sh ./install/scripts/php-conf-fix.sh
RUN /fossologyng/install/scripts/php-conf-fix.sh --overwrite

# configure apache
COPY ./install/src-install-apache-example.conf /etc/apache2/conf-available/fossology.conf
RUN a2enconf fossology.conf \
 && mkdir -p /var/log/apache2/ \
 && ln -sf /proc/self/fd/1 /var/log/apache2/access.log \
 && ln -sf /proc/self/fd/1 /var/log/apache2/error.log

EXPOSE 80

COPY ./docker-entrypoint.sh /fossology/docker-entrypoint.sh
RUN chmod +x /fossologyng/docker-entrypoint.sh
ENTRYPOINT ["/fossologyng/docker-entrypoint.sh"]

COPY --from=builder /etc/cron.d/fossology /etc/cron.d/fossology
COPY --from=builder /etc/init.d/fossology /etc/init.d/fossology
COPY --from=builder /usr/local/ /usr/local/

# the database is filled in the entrypoint
RUN /usr/local/lib/fossology/fo-postinstall --agent --common --scheduler-only --web-only --no-running-database
