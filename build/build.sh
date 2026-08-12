#!/usr/bin/env bash
# Builds the installable extension zip into dist/.
set -euo pipefail
cd "$(dirname "$0")/.."

version=$(grep -oPm1 '(?<=<version>)[^<]+' mod_toc.xml)
out="dist/mod_toc-${version}.zip"

mkdir -p dist
rm -f "$out"

zip -r "$out" mod_toc.xml services src tmpl language media LICENSE -x '*.DS_Store'

echo "Built $out"
sha256sum "$out"
