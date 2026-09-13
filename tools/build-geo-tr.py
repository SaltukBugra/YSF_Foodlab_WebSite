#!/usr/bin/env python3
"""Turkiye il/ilce veri setini tema icin JSON'a donusturur.

Kaynak veri (kamuya acik idari bolunus listesi):
    https://github.com/isubas/iller_ve_ilceler

Kullanim:
    curl -sSL -o /tmp/il.json \
        https://raw.githubusercontent.com/isubas/iller_ve_ilceler/master/iller_ve_ilceler.json
    python3 tools/build-geo-tr.py /tmp/il.json

Cikti: ysffoodlab/assets/data/tr-il-ilce.json
    { "Adana": ["Aladag", ...], ... }  (il adina gore Turkce alfabetik)
"""

import json
import sys
from pathlib import Path

OUT = Path(__file__).resolve().parent.parent / "ysffoodlab" / "assets" / "data" / "tr-il-ilce.json"

# Turkce buyuk -> kucuk harf esleme. Python'un lower() metodu I -> i cevirdigi
# icin kullanilamaz; Turkce'de I'nin kucugu ı, İ'nin kucugu i'dir.
UP2LOW = str.maketrans(
    "ABCÇDEFGĞHIİJKLMNOÖPQRSŞTUÜVWXYZ",
    "abcçdefgğhıijklmnoöpqrsştuüvwxyz",
)

WORD_BREAKS = " -'./"

# Turkce alfabetik siralama anahtari.
TR_ORDER = "0123456789 -'.abcçdefgğhıijklmnoöpqrsştuüvwxyz"


def tr_title(value: str) -> str:
    """ADANA -> Adana, 19 MAYIS -> 19 Mayıs, İMAMOĞLU -> İmamoğlu."""
    out = []
    new_word = True

    for ch in value.strip():
        if ch in WORD_BREAKS:
            out.append(ch)
            new_word = True
            continue

        if new_word:
            out.append(ch)
            new_word = False
        else:
            out.append(ch.translate(UP2LOW))

    return "".join(out)


def tr_key(value: str):
    """Turkce alfabeye gore siralama anahtari."""
    lowered = value.translate(UP2LOW)

    return [TR_ORDER.index(ch) if ch in TR_ORDER else len(TR_ORDER) for ch in lowered]


def main() -> int:
    if len(sys.argv) < 2:
        print(__doc__)
        return 1

    raw = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))

    provinces = {}

    for entry in raw.values():
        name = tr_title(entry["ad"])
        districts = sorted(
            {tr_title(d["ad"]) for d in entry.get("ilceler", [])},
            key=tr_key,
        )
        provinces[name] = districts

    ordered = {name: provinces[name] for name in sorted(provinces, key=tr_key)}

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(
        json.dumps(ordered, ensure_ascii=False, separators=(",", ":")) + "\n",
        encoding="utf-8",
    )

    total = sum(len(v) for v in ordered.values())
    print(f"{OUT.relative_to(OUT.parents[3])}: {len(ordered)} il, {total} ilce")
    print("kontrol Konya:", len(ordered.get("Konya", [])), "ilce")
    print("kontrol Istanbul:", len(ordered.get("İstanbul", [])), "ilce")

    return 0


if __name__ == "__main__":
    sys.exit(main())
