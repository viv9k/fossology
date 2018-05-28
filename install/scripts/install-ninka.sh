#!/bin/bash

set -e

git clone --branch release-1.1104 https://code.siemens.com/mirror/io-captureoutput.git
cd io-captureoutput
perl Makefile.PL
make
sudo make install
cd ..
rm -rf io-captureoutput

git clone https://code.siemens.com/mirror/ninka.git
cd ninka
git reset --hard 81f185261c8863c5b84344ee31192870be939faf
perl Makefile.PL
make
sudo make install
cd ..
rm -rf ninka
