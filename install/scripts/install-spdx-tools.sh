#!/bin/bash

wget -nc https://linux.siemens.de/pub/tools/FOSSologyNG/SPDXTools-v2.1.0.zip
unzip -j "SPDXTools-v2.1.0.zip" "SPDXTools-v2.1.0/spdx-tools-2.1.0-jar-with-dependencies.jar" \
      -d "$(dirname $0)/../../src/spdx2/agent_tests/Functional/"
rm -rf SPDXTools-v2.1.0.zip
