#!/usr/bin/env bash
set -euo pipefail
java -jar "$(dirname "$0")/signer.jar" "$@"