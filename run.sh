#!/bin/sh

# use goagent as the proxy server
# export http_proxy='localhost:8087'
# export https_proxy='localhost:8087'

# use the company proxy
export http_proxy='10.27.7.110:8080'
export https_proxy='10.27.7.110:8080'

mkdir -p temp
./runner.php
