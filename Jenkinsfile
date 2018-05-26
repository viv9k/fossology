pipeline {
  agent {
    label 'master'
  }
  environment {
    TEST_TARGETS = 'test-lib test-monk test-nomos'
  }
  stages {
    stage('Preparation') {
      steps{
        // Install dependencies
        sh '''
          sudo apt-get -qq update
          sudo apt-get install -qq -y lsb-release sudo php-curl libpq-dev libdbd-sqlite3-perl libspreadsheet-writeexcel-perl
          sudo apt-get install -qq -y postgresql postgresql-server-dev-all cppcheck php-pgsql sqlite3 php-sqlite3 libsqlite3-0 libsqlite3-dev
          sudo apt-get install -qq -y debhelper libglib2.0-dev libmagic-dev libxml2-dev
          sudo apt-get install -qq -y libtext-template-perl librpm-dev  rpm libpcre3-dev libssl-dev
          sudo apt-get install -qq -y apache2 libapache2-mod-php php-pgsql php-pear php-cli
          sudo apt-get install -qq -y binutils bzip2 cabextract cpio sleuthkit genisoimage poppler-utils
          sudo apt-get install -qq -y upx-ucl unrar-free unzip p7zip-full p7zip wget git-core subversion
          sudo apt-get install -qq -y libcunit1-dev libcppunit-dev libboost-regex-dev libboost-program-options-dev
          sudo apt-get install -qq -y liblocal-lib-perl libmxml-dev composer php-mbstring
          sudo utils/fo-installdeps -e -o -y
        '''
        // To resolve ninka build dependency
        dir(path: 'io-captureoutput') {
          git(url: 'git@code.siemens.com:mirror/io-captureoutput.git', branch: 'master')
          // tag: 'release-1.1104'
          sh '''
            perl Makefile.PL
            make
            make test
            sudo make install
          '''
          deleteDir()
        }
        // Create and patch FOSSology cache
        sh 'sudo mkdir -p /var/local/cache/fossology && sudo chown $(whoami) /var/local/cache/fossology'
        dir(path: 'ninkaClone') {
          git(url: 'git@code.siemens.com:mirror/ninka.git', branch: 'master')
          sh '''
            perl Makefile.PL
            make
            sudo make install
          '''
          deleteDir()
        }
        dir(path: 'src') {
          // Install composer dependencies
          sh 'composer install'
        }
        sh '''
          sudo /etc/init.d/postgresql start
          sudo /etc/init.d/postgresql status
          sudo -u postgres psql -c "CREATE USER fossy WITH PASSWORD 'fossy' CREATEDB" || true
          sudo -u postgres psql -c "CREATE DATABASE fossology OWNER fossy" || true
          sudo -u postgres psql -c "CREATE USER fossologytest WITH CREATEDB LOGIN PASSWORD 'fossologytest';" || true
          echo "localhost:*:*:fossy:fossy" >> ~/.pgpass && echo "localhost:*:*:fossologytest:fossologytest" >> ~/.pgpass
          chmod 0600 ~/.pgpass
        '''
      }
    }
    stage('Build') {
      steps {
        sh 'make'
      }
    }
    stage('Test'){
      parallel {
        stage('C-test') {
          steps {
            sh 'make $TEST_TARGETS'
            sh 'cppcheck -q -isrc/nomos/agent_tests/testdata/NomosTestfiles/ \
            -isrc/testing/dataFiles/ --suppress=*:src/copyright/agent/json.hpp src/'
          }
        }
        stage('PHPCS-test') {
          steps {
            sh 'src/vendor/bin/phpcs --standard=src/fossy-ruleset.xml \
            src/lib/php/*/ src/www/ui/page/ src/www/ui/async/ src/spdx2 src/monk'
          }
        }
        stage('PhpUnit-test') {
          steps {
            sh '''
              wget https://linux.siemens.de/pub/tools/FOSSologyNG/SPDXTools-v2.1.0.zip
              unzip -j "SPDXTools-v2.1.0.zip" "SPDXTools-v2.1.0/spdx-tools-2.1.0-jar-with-dependencies.jar" \
              -d "./src/spdx2/agent_tests/Functional/"
              rm -rf SPDXTools-v2.1.0.zip
              src/vendor/bin/phpunit -c src/phpunit.xml
            '''
          }
        }
      }
    }
    stage('Package') {
      parallel {
          stage('Trusty') {
              agent {
                label 'trustyImage'
              }
              steps {
                sh '''
                   sudo apt-get update -qq
                   sudo apt-get upgrade -qq -y
                   sudo apt-get install -qq -y lsb-release sudo php5-curl libpq-dev libdbd-sqlite3-perl libspreadsheet-writeexcel-perl debhelper
                   sudo apt-get install -qq -y postgresql postgresql-server-dev-all cppcheck php5-pgsql sqlite3 php5-sqlite libsqlite3-0 libsqlite3-dev
                   sudo apt-get install -qq -y debhelper libglib2.0-dev libmagic-dev libxml2-dev
                   sudo apt-get install -qq -y libtext-template-perl librpm-dev  rpm libpcre3-dev libssl-dev
                   sudo apt-get install -qq -y apache2 libapache2-mod-php5 php5-pgsql php-pear php5-cli
                   sudo apt-get install -qq -y binutils bzip2 cabextract cpio sleuthkit genisoimage poppler-utils
                   sudo apt-get install -qq -y rpm upx-ucl unrar-free unzip p7zip-full p7zip git-core subversion
                   sudo apt-get install -qq -y libpq-dev libcunit1-dev libcppunit-dev
                   sudo apt-get install -qq -y libboost-regex-dev libboost-program-options-dev
                   sudo apt-get install -y liblocal-lib-perl zip
                   sudo utils/fo-installdeps -y -b
                 '''
                 sh '''
                   # Install composer
                   wget -q https://getcomposer.org/composer.phar
                   sudo mv composer.phar /usr/local/bin/composer
                   sudo chmod +x /usr/local/bin/composer

                   rm -rf vendor
                   make clean phpvendors
                 '''
                 sh 'dpkg-buildpackage -I'
                 sh '''
                   mkdir packages
                   mv ../*.deb packages/
                   zip trustypackages.zip packages/*
                 '''

                // finally copying the artifacts
                archiveArtifacts(allowEmptyArchive: true, artifacts: 'trustypackages.zip', caseSensitive: false, fingerprint: true, onlyIfSuccessful: false)
              }
          }
          stage('Xenial') {
              agent {
                label 'xenialImage'
              }
              steps {
                sh '''
                   sudo apt-get update -qq
                   sudo apt-get upgrade -qq -y
                   sudo apt-get install -qq -y lsb-release sudo php-curl libpq-dev libdbd-sqlite3-perl libspreadsheet-writeexcel-perl debhelper
                   sudo apt-get install -qq -y postgresql postgresql-server-dev-all cppcheck php-pgsql sqlite3 php-sqlite3 libsqlite3-0 libsqlite3-dev
                   sudo apt-get install -qq -y debhelper libglib2.0-dev libmagic-dev libxml2-dev
                   sudo apt-get install -qq -y libtext-template-perl librpm-dev rpm libpcre3-dev libssl-dev
                   sudo apt-get install -qq -y apache2 libapache2-mod-php php-pgsql php-pear php-cli
                   sudo apt-get install -qq -y binutils bzip2 cabextract cpio sleuthkit genisoimage poppler-utils
                   sudo apt-get install -qq -y rpm upx-ucl unrar-free unzip p7zip-full p7zip wget git-core subversion
                   sudo apt-get install -qq -y libpq-dev libcunit1-dev libcppunit-dev php7.0-mbstring
                   sudo apt-get install -qq -y libboost-regex-dev libboost-program-options-dev
                   sudo apt-get install -qq -y liblocal-lib-perl composer zip
                   sudo utils/fo-installdeps -y -b
                 '''
                 sh '''
                   rm -rf vendor
                   make clean phpvendors
                 '''
                 sh 'dpkg-buildpackage -I'
                 sh '''
                   mkdir packages
                   mv ../*.deb packages/
                   zip xenialpackages.zip packages/*
                 '''

                // finally copying the artifacts
                archiveArtifacts(allowEmptyArchive: true, artifacts: 'xenialpackages.zip', caseSensitive: false, fingerprint: true, onlyIfSuccessful: false)
              }
          }
          stage('Jessie') {
              agent {
                label 'jessieImage'
              }
              steps {
                sh '''
                   sudo apt-get update -qq
                   sudo apt-get upgrade -qq -y
                   sudo apt-get install -qq -y lsb-release sudo php5-curl libpq-dev libdbd-sqlite3-perl libspreadsheet-writeexcel-perl debhelper
                   sudo apt-get install -qq -y postgresql postgresql-server-dev-all cppcheck php5-pgsql sqlite3 php5-sqlite libsqlite3-0 libsqlite3-dev
                   sudo apt-get install -qq -y debhelper libglib2.0-dev libmagic-dev libxml2-dev
                   sudo apt-get install -qq -y libtext-template-perl librpm-dev  rpm libpcre3-dev libssl-dev
                   sudo apt-get install -qq -y apache2 libapache2-mod-php5 php5-pgsql php-pear php5-cli
                   sudo apt-get install -qq -y binutils bzip2 cabextract cpio sleuthkit genisoimage poppler-utils
                   sudo apt-get install -qq -y rpm upx-ucl unrar-free unzip p7zip-full p7zip git-core subversion
                   sudo apt-get install -qq -y libpq-dev libcunit1-dev libcppunit-dev
                   sudo apt-get install -qq -y libboost-regex-dev libboost-program-options-dev
                   sudo apt-get install -qq -y liblocal-lib-perl zip
                   sudo utils/fo-installdeps -y -b
                 '''
                 sh '''
                   # Install composer
                   wget -q https://getcomposer.org/composer.phar
                   sudo mv composer.phar /usr/local/bin/composer
                   sudo chmod +x /usr/local/bin/composer

                   rm -rf vendor
                   make clean phpvendors
                 '''
                 sh 'dpkg-buildpackage -I'
                 sh '''
                   mkdir packages
                   mv ../*.deb packages/
                   zip jessiepackages.zip packages/*
                 '''

                // finally copying the artifacts
                archiveArtifacts(allowEmptyArchive: true, artifacts: 'jessiepackages.zip', caseSensitive: false, fingerprint: true, onlyIfSuccessful: false)
              }
          }
          stage('Stretch') {
              agent {
                label 'master'
              }
              steps {
                sh '''
                   sudo apt-get update -qq
                   sudo apt-get install -qq -y lsb-release sudo php-curl libpq-dev libdbd-sqlite3-perl libspreadsheet-writeexcel-perl debhelper
                   sudo apt-get install -qq -y postgresql postgresql-server-dev-all cppcheck php-pgsql sqlite3 php-sqlite3 libsqlite3-0 libsqlite3-dev
                   sudo apt-get install -qq -y debhelper libglib2.0-dev libmagic-dev libxml2-dev
                   sudo apt-get install -qq -y libtext-template-perl librpm-dev  rpm libpcre3-dev libssl-dev
                   sudo apt-get install -qq -y apache2 libapache2-mod-php php-pgsql php-pear php-cli
                   sudo apt-get install -qq -y binutils bzip2 cabextract cpio sleuthkit genisoimage poppler-utils
                   sudo apt-get install -qq -y rpm upx-ucl unrar-free unzip p7zip-full p7zip wget git-core subversion
                   sudo apt-get install -qq -y libpq-dev libcunit1-dev libcppunit-dev
                   sudo apt-get install -qq -y libboost-regex-dev libboost-program-options-dev
                   sudo apt-get install -qq -y liblocal-lib-perl libmxml-dev zip
                   sudo utils/fo-installdeps -y -b
                 '''
                 sh 'make clean phpvendors'
                 sh 'dpkg-buildpackage -I'
                 sh '''
                   find .. -type f -name "*-dbgsym*" -exec rm -rf {} \\;
                   mkdir packages
                   mv ../*.deb packages/
                   zip stretchpackages.zip packages/*
                 '''

                // finally copying the artifacts
                archiveArtifacts(allowEmptyArchive: true, artifacts: 'stretchpackages.zip', caseSensitive: false, fingerprint: true, onlyIfSuccessful: false)
              }
          }
      }
    }
    stage('Deploy') {
      parallel {
        stage('Docker image') {
          when {
            branch 'ng-master'
          }
          environment {
            DOCKER_CREDS = credentials('siemens_docker')
            DOCKER_REGISTRY = 'docker.siemens.com'
            CONTAINER_RELEASE_IMAGE = "${DOCKER_REGISTRY}/fossology/fossologyng:latest"
          }
          steps {
            sh 'docker login -u ${DOCKER_CREDS_USR} -p ${DOCKER_CREDS_PSW} ${DOCKER_REGISTRY}'
            sh 'docker build --pull -t ${CONTAINER_RELEASE_IMAGE} .'
            sh 'docker push ${CONTAINER_RELEASE_IMAGE}'
          }
        }
        stage('Tag image') {
          when { buildingTag() }
          environment {
            DOCKER_CREDS = credentials('siemens_docker')
            DOCKER_REGISTRY = 'docker.siemens.com'
            CONTAINER_TAG_IMAGE = "${DOCKER_REGISTRY}/fossology/fossologyng:${TAG_NAME}"
          }
          steps {
            sh 'docker login -u ${DOCKER_CREDS_USR} -p ${DOCKER_CREDS_PSW} ${DOCKER_REGISTRY}'
            sh 'docker build --pull -t ${CONTAINER_TAG_IMAGE} .'
            sh 'docker push ${CONTAINER_TAG_IMAGE}'
          }
        }
        stage('Siemens repo') {
          when {
           branch 'ng-master'
          }
          steps {
            echo 'Running deploy-dev'
            // sh 'wget http://linux.siemens.de/pub/tools/FOSSologyNG/fossologyng-$GIT_COMMIT.tar.gz'
            // adding host key to list of known hosts
            // sh 'mkdir -p ~/.ssh'
            // sh 'ssh-keyscan -H $DEVDEPLOY_SERVER_NAME > ~/.ssh/known_hosts'
            // super important: adding key to ssh agent, othewiese the private key file is not considered
            // sh 'echo $DEVDEPLOY_SERVER_KEY > key'
            // sh 'chmod 600 key'
            // sh 'eval $(ssh-agent -s)'
            // sh 'ssh-add  <(echo "$DEVDEPLOY_SERVER_KEY")'
            // sh 'ssh-add -L'
            // copying the artifacts
            // scp -i key ~/workspace/artifacts/fossologyng-$GIT_COMMIT.tar.gz ubuntu@$DEVDEPLOY_SERVER_NAME:/home/ubuntu
            // logging in and artifacts install
            // ssh -i key ubuntu@$DEVDEPLOY_SERVER_NAME "tar -x --overwrite -z -f fossologyng-$GIT_COMMIT.tar.gz"
            // ssh -i key ubuntu@$DEVDEPLOY_SERVER_NAME "rm -f packages/fossology-ninka*"
            // ssh -i key ubuntu@$DEVDEPLOY_SERVER_NAME "sudo dpkg -i packages/*.deb"
            // ssh -i key ubuntu@$DEVDEPLOY_SERVER_NAME "sudo apt-get -f install -y"
          }
        }
      }
    }
  }
}

