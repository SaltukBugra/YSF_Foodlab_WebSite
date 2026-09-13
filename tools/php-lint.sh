#!/usr/bin/env bash
# Tema PHP dosyalarini gercek PHP derleyicisiyle denetler.
#
# Sunucuda PHP CLI kurulu olmadigi icin WordPress Playground'un WebAssembly
# PHP'si kullanilir (npm: @php-wasm/cli). Ilk calistirmada paketi indirir.
#
# Kullanim: tools/php-lint.sh [dizin]   (varsayilan: ysffoodlab)

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET="${1:-ysffoodlab}"
CACHE="${TMPDIR:-/tmp}/ysf-php-lint"
PHP="$CACHE/node_modules/.bin/php-wasm-cli"

if [ ! -x "$PHP" ]; then
	mkdir -p "$CACHE"
	( cd "$CACHE" && npm install --silent --no-audit --no-fund --no-save @php-wasm/cli )
fi

# WASM calistirmasi kendi dosya sistemine bagladigi icin kaynaklar kopyalanir.
WORK="$CACHE/work"
rm -rf "$WORK"
mkdir -p "$WORK"
cp -r "$ROOT/$TARGET" "$WORK/src"

failed=0
total=0

while IFS= read -r file; do
	total=$((total + 1))
	if ! output="$( cd "$CACHE" && "$PHP" -l "work/src/${file#"$WORK/src/"}" 2>&1 )" \
		|| [[ "$output" != *"No syntax errors"* ]]; then
		echo "HATA: ${file#"$WORK/src/"}"
		echo "$output" | head -3
		failed=$((failed + 1))
	fi
done < <(find "$WORK/src" -name '*.php' | sort)

echo "---"
echo "$total dosya denetlendi, $failed dosyada sozdizimi hatasi var."

[ "$failed" -eq 0 ]
