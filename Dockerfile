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
# Using Siemens CCP V5, based on Debian 8

FROM docker.siemens.com/ccp/vm:v5

LABEL maintainer="Fossology <Fossology.Support.oss@internal.siemens.com>"

WORKDIR fossologyng

RUN sudo chown $(whoami):$(whoami) -R .

# Remove proxy (if any)
RUN echo "Acquire::http::Proxy \"false\";" | sudo tee -a /etc/apt/apt.conf.d/95proxy

RUN DEBIAN_FRONTEND=noninteractive sudo apt-get update \
 && DEBIAN_FRONTEND=noninteractive sudo apt-get install -y --no-install-recommends \
      git \
      lsb-release \
      php5-cli \
      sudo \
 && sudo rm -rf /var/lib/apt/lists/*

COPY ./utils/fo-installdeps ./utils/fo-installdeps
COPY ./utils/utils.sh ./utils/utils.sh
COPY ./src/delagent/mod_deps ./src/delagent/
COPY ./src/mimetype/mod_deps ./src/mimetype/
COPY ./src/pkgagent/mod_deps ./src/pkgagent/
COPY ./src/scheduler/mod_deps ./src/scheduler/
COPY ./src/ununpack/mod_deps ./src/ununpack/
COPY ./src/wget_agent/mod_deps ./src/wget_agent/

RUN mkdir -p ~/fossologyng/dependencies-for-runtime \
 && cp -R ~/fossologyng/src ~/fossologyng/utils ~/fossologyng/dependencies-for-runtime/

RUN DEBIAN_FRONTEND=noninteractive sudo apt-get update \
 && DEBIAN_FRONTEND=noninteractive sudo ~/fossologyng/utils/fo-installdeps --build -y \
 && sudo rm -rf /var/lib/apt/lists/* 

COPY . .

RUN echo "check_certificate=off" | sudo tee -a /etc/wgetrc
RUN echo "--insecure" | tee -a ~/.curlrc

# Install composer dependencies
RUN cd src && \
    wget https://linux.siemens.de/pub/tools/FOSSologyNG/php-vendor.tar && \
    tar xvf php-vendor.tar && rm -rf php-vendor.tar && cd ..

RUN ~/fossologyng/install/scripts/install-spdx-tools.sh
RUN ~/fossologyng/install/scripts/install-ninka.sh

RUN sudo make install_offline clean


FROM docker.siemens.com/ccp/vm:v5

LABEL maintainer="Fossology <Fossology.Support.oss@internal.siemens.com>"

WORKDIR fossologyng

### install dependencies
COPY --from=builder ~/fossologyng/dependencies-for-runtime ~/fossologyng

RUN DEBIAN_FRONTEND=noninteractive sudo apt-get update \
 && DEBIAN_FRONTEND=noninteractive sudo apt-get install -y --no-install-recommends \
      curl \
      lsb-release \
      sudo \
 && DEBIAN_FRONTEND=noninteractive sudo ~/fossologyng/utils/fo-installdeps --offline --runtime -y \
 && DEBIAN_FRONTEND=noninteractive sudo apt-get purge -y lsb-release \
 && DEBIAN_FRONTEND=noninteractive sudo apt-get autoremove -y \
 && sudo rm -rf /var/lib/apt/lists/*

# configure php
COPY ./install/scripts/php-conf-fix.sh ./install/scripts/php-conf-fix.sh
RUN sudo ~/fossologyng/install/scripts/php-conf-fix.sh --overwrite

# configure apache
COPY ./install/src-install-apache-example.conf /etc/apache2/conf-available/fossology.conf
RUN sudo a2enconf fossology.conf \
 && sudo mkdir -p /var/log/apache2/ \
 && sudo ln -sf /proc/self/fd/1 /var/log/apache2/access.log \
 && sudo ln -sf /proc/self/fd/1 /var/log/apache2/error.log

EXPOSE 80

COPY ./docker-entrypoint.sh ~/fossology/docker-entrypoint.sh
RUN chmod +x ~/fossologyng/docker-entrypoint.sh
ENTRYPOINT ["~/fossologyng/docker-entrypoint.sh"]

COPY --from=builder /etc/cron.d/fossology /etc/cron.d/fossology
COPY --from=builder /etc/init.d/fossology /etc/init.d/fossology
COPY --from=builder /usr/local/ /usr/local/

# the database is filled in the entrypoint
RUN sudo /usr/local/lib/fossology/fo-postinstall --agent --common --scheduler-only --web-only --no-running-database
