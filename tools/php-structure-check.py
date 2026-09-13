#!/usr/bin/env python3
"""Kaba PHP yapı denetleyicisi.

Gercek bir PHP yorumlayicisi olmadigi icin sozdizimini tam dogrulamaz; ancak
dengesiz parantez/kume, eksik endif/endforeach ve kapanmamis dize gibi en sik
gorulen hatalari yakalar.
"""

import re
import sys
from pathlib import Path

PAIRS = {")": "(", "]": "[", "}": "{"}
OPENERS = set(PAIRS.values())

ALT_BLOCKS = {
    "if": "endif",
    "foreach": "endforeach",
    "for": "endfor",
    "while": "endwhile",
    "switch": "endswitch",
}


def strip_php(source: str):
    """PHP dizelerini, yorumlarini ve HTML bloklarini temizler."""
    out = []
    i = 0
    n = len(source)
    in_php = False
    errors = []

    while i < n:
        ch = source[i]

        if not in_php:
            start = source.find("<?php", i)
            short = source.find("<?=", i)
            if short != -1 and (start == -1 or short < start):
                start = short
            if start == -1:
                break
            i = start + (5 if source.startswith("<?php", start) else 3)
            in_php = True
            continue

        # PHP kapanisi
        if source.startswith("?>", i):
            in_php = False
            out.append(";")
            i += 2
            continue

        # Tek satir yorumlar
        if source.startswith("//", i) or ch == "#":
            end = source.find("\n", i)
            i = n if end == -1 else end
            continue

        # Blok yorum
        if source.startswith("/*", i):
            end = source.find("*/", i + 2)
            if end == -1:
                errors.append("kapanmamis /* yorum blogu")
                break
            i = end + 2
            continue

        # Heredoc / nowdoc
        heredoc = re.match(r"<<<\s*(['\"]?)([A-Za-z_][A-Za-z0-9_]*)\1\r?\n", source[i:])
        if heredoc:
            label = heredoc.group(2)
            closing = re.search(r"^\s*" + label + r"\s*[;,)]?", source[i + heredoc.end():], re.M)
            if not closing:
                errors.append(f"kapanmamis heredoc: {label}")
                break
            i = i + heredoc.end() + closing.end()
            out.append('""')
            continue

        # Dizeler
        if ch in "'\"":
            quote = ch
            j = i + 1
            while j < n:
                if source[j] == "\\":
                    j += 2
                    continue
                if source[j] == quote:
                    break
                j += 1
            if j >= n:
                errors.append(f"kapanmamis dize ({quote}) — satir {source.count(chr(10), 0, i) + 1}")
                break
            out.append('""')
            i = j + 1
            continue

        out.append(ch)
        i += 1

    return "".join(out), errors


def check(path: Path):
    source = path.read_text(encoding="utf-8")
    code, errors = strip_php(source)

    # Denge kontrolu
    stack = []
    for index, ch in enumerate(code):
        if ch in OPENERS:
            stack.append((ch, index))
        elif ch in PAIRS:
            if not stack:
                errors.append(f"fazladan kapanis: {ch}")
                break
            opener, _ = stack.pop()
            if opener != PAIRS[ch]:
                errors.append(f"eslesmeyen kapanis: {opener} ... {ch}")
                break

    if stack:
        errors.append(f"kapanmamis {len(stack)} adet: " + ", ".join(sorted({s[0] for s in stack})))

    # Alternatif sozdizimi dengesi
    for keyword, closer in ALT_BLOCKS.items():
        # `for` basligi noktali virgul icerir, digerleri icermez.
        inner = r"[^{]*?" if keyword == "for" else r"[^;{]*"
        opens = len(re.findall(r"\b" + keyword + r"\s*\(" + inner + r"\)\s*:", code))
        closes = len(re.findall(r"\b" + closer + r"\b", code))
        if opens != closes:
            errors.append(f"{keyword}: {opens} acilis / {closer}: {closes} kapanis")

    # else/elseif alternatif kullanimi endif sayisini etkilemez, ek kontrol yok.
    return errors


def main():
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".")
    files = sorted(root.rglob("*.php"))
    failed = 0

    for path in files:
        errors = check(path)
        if errors:
            failed += 1
            print(f"HATA {path}")
            for error in errors:
                print(f"      - {error}")

    print(f"\n{len(files)} dosya denetlendi, {failed} dosyada sorun bulundu.")
    return 1 if failed else 0


if __name__ == "__main__":
    sys.exit(main())
