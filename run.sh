#!/bin/sh

# use goagent as the proxy server
export http_proxy='localhost:8087'
export https_proxy='localhost:8087'
./runner.php
