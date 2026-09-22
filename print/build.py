#!/usr/bin/env python3
"""YSF Food Lab masa broşürü — A5 yatay tent kart, A4’ten katlanır, 300 dpi."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent
ASSETS = ROOT / "assets"
FONTS = ASSETS / "fonts"
OUT = ROOT / "output"

DPI = 300
INK = (22, 22, 22)
CREAM = (251, 247, 241)
GOLD = (212, 180, 131)
GOLD_DIM = (168, 140, 98)
MUTED = (196, 184, 170)


def mm(value: float) -> int:
    return int(round(value / 25.4 * DPI))


def load_font(name: str, size_mm: float, weight: int | None = None) -> ImageFont.FreeTypeFont:
    font = ImageFont.truetype(str(FONTS / name), mm(size_mm))
    if weight is not None and hasattr(font, "set_variation_by_axes"):
        try:
            font.set_variation_by_axes([weight])
        except OSError:
            pass
    return font


def flatten_logo(path: Path) -> Image.Image:
    """Trim padding and dye near-black pixels to the card ink so no square halo remains."""
    im = Image.open(path).convert("RGBA")
    px = im.load()
    w, h = im.size
    minx, miny, maxx, maxy = w, h, 0, 0
    for y in range(h):
        for x in range(w):
            r, g, b, a = px[x, y]
            if a < 8:
                continue
            if r > 28 or g > 22 or b > 18:
                minx = min(minx, x)
                miny = min(miny, y)
                maxx = max(maxx, x)
                maxy = max(maxy, y)
    pad = 12
    crop = im.crop(
        (
            max(0, minx - pad),
            max(0, miny - pad),
            min(w, maxx + 1 + pad),
            min(h, maxy + 1 + pad),
        )
    )
    out = Image.new("RGBA", crop.size, INK + (255,))
    src = crop.load()
    dst = out.load()
    cw, ch = crop.size
    for y in range(ch):
        for x in range(cw):
            r, g, b, a = src[x, y]
            if a < 8 or (r < 48 and g < 48 and b < 48):
                dst[x, y] = INK + (255,)
            else:
                dst[x, y] = (r, g, b, 255)
    return out


def corner_marks(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int], length: int, width: int, color) -> None:
    x0, y0, x1, y1 = box
    segs = [
        [(x0, y0), (x0 + length, y0)],
        [(x0, y0), (x0, y0 + length)],
        [(x1, y0), (x1 - length, y0)],
        [(x1, y0), (x1, y0 + length)],
        [(x0, y1), (x0 + length, y1)],
        [(x0, y1), (x0, y1 - length)],
        [(x1, y1), (x1 - length, y1)],
        [(x1, y1), (x1, y1 - length)],
    ]
    for a, b in segs:
        draw.line([a, b], fill=color, width=width)


def diamond(draw: ImageDraw.ImageDraw, cx: int, cy: int, r: int, fill) -> None:
    draw.polygon([(cx, cy - r), (cx + r, cy), (cx, cy + r), (cx - r, cy)], fill=fill)


def draw_centered(draw: ImageDraw.ImageDraw, xy: tuple[int, int], text: str, font: ImageFont.FreeTypeFont, fill) -> None:
    draw.text(xy, text, font=font, fill=fill, anchor="mt")


def render_face(logo: Image.Image, qr: Image.Image) -> Image.Image:
    """A5 landscape: 210 × 148 mm. Masa üstünde alçak, geniş broşür."""
    W, H = mm(210), mm(148)
    img = Image.new("RGB", (W, H), INK)
    draw = ImageDraw.Draw(img)

    m1 = mm(5.5)
    m2 = mm(7.6)
    draw.rectangle([m1, m1, W - m1, H - m1], outline=GOLD, width=max(2, mm(0.32)))
    draw.rectangle([m2, m2, W - m2, H - m2], outline=GOLD_DIM, width=max(1, mm(0.16)))
    corner_marks(draw, (mm(9), mm(9), W - mm(9), H - mm(9)), mm(6), max(2, mm(0.28)), GOLD)

    cinzel_xs = load_font("Cinzel-variable.ttf", 2.7, 600)
    cinzel = load_font("Cinzel-variable.ttf", 3.2, 600)
    playfair = load_font("PlayfairDisplay-variable.ttf", 8.4, 700)
    playfair_it = load_font("PlayfairDisplay-Italic-variable.ttf", 3.8, 400)
    inter = load_font("Inter-variable.ttf", 3.1, 500)

    # Top eyebrow — logo already carries the name
    top_y = mm(11.5)
    draw_centered(draw, (W // 2, top_y), "KITCHEN  &  COFFEE", cinzel, GOLD)
    diamond(draw, mm(58), top_y + mm(1.6), mm(1.1), GOLD)
    diamond(draw, W - mm(58), top_y + mm(1.6), mm(1.1), GOLD)

    # Split columns
    col_y = mm(24)
    left_cx = mm(56)
    right_cx = mm(152)

    ty = col_y + mm(2)
    draw_centered(draw, (right_cx, ty), "MUTFAĞIMIZDAN  ·  FROM OUR KITCHEN", cinzel_xs, GOLD)
    ty += mm(7)
    draw_centered(draw, (right_cx, ty), "Dijital Menü", playfair, CREAM)
    ty += mm(11)

    plate = mm(64)
    px0 = right_cx - plate // 2
    py0 = ty
    plate_img = Image.new("RGB", (plate, plate), CREAM)
    pd = ImageDraw.Draw(plate_img)
    pd.rounded_rectangle([0, 0, plate - 1, plate - 1], radius=mm(3), outline=GOLD, width=max(2, mm(0.35)))
    qr_box = mm(54)
    qr_r = qr.resize((qr_box, qr_box), Image.NEAREST)
    plate_img.paste(qr_r.convert("RGB"), ((plate - qr_box) // 2, (plate - qr_box) // 2))
    img.paste(plate_img, (px0, py0))

    cap_y = py0 + plate + mm(4)
    draw_centered(draw, (right_cx, cap_y), "Kameranızla tarayın  ·  Scan to open", inter, MUTED)
    draw_centered(draw, (right_cx, cap_y + mm(5)), "www.ysffoodlab.com.tr", cinzel, GOLD)

    logo_w = mm(78)
    ratio = logo_w / logo.width
    logo_h = int(logo.height * ratio)
    logo_r = logo.resize((logo_w, logo_h), Image.LANCZOS)
    block_bottom = cap_y + mm(8)
    logo_y = col_y + max(0, (block_bottom - col_y - logo_h) // 2)
    img.paste(logo_r, (left_cx - logo_w // 2, logo_y), logo_r if logo_r.mode == "RGBA" else None)

    # Footer
    fy = H - mm(14)
    rule_w = mm(36)
    cx = W // 2
    draw.line([(cx - rule_w, fy - mm(5.5)), (cx - mm(3), fy - mm(5.5))], fill=GOLD_DIM, width=max(1, mm(0.18)))
    diamond(draw, cx, fy - mm(5.5), mm(1.15), GOLD_DIM)
    draw.line([(cx + mm(3), fy - mm(5.5)), (cx + rule_w, fy - mm(5.5))], fill=GOLD_DIM, width=max(1, mm(0.18)))
    draw_centered(draw, (cx, fy - mm(2.8)), "Menü  ·  Sipariş  ·  Rezervasyon", playfair_it, GOLD)

    # Keep frames crisp
    draw.rectangle([m1, m1, W - m1, H - m1], outline=GOLD, width=max(2, mm(0.32)))
    draw.rectangle([m2, m2, W - m2, H - m2], outline=GOLD_DIM, width=max(1, mm(0.16)))
    corner_marks(draw, (mm(9), mm(9), W - mm(9), H - mm(9)), mm(6), max(2, mm(0.28)), GOLD)
    return img


def render_a4_sheet(face: Image.Image) -> Image.Image:
    """A4 dikey: üst yarı 180° (çadırın arka yüzü), alt yarı düz. Ortadan katla."""
    W, H = mm(210), mm(297)
    sheet = Image.new("RGB", (W, H), (255, 255, 255))
    half = H // 2
    ox = (W - face.width) // 2
    oy = (half - face.height) // 2
    sheet.paste(face.rotate(180, expand=False), (ox, oy))
    sheet.paste(face, (ox, half + oy))

    draw = ImageDraw.Draw(sheet)
    y = half
    dash, gap = mm(3.5), mm(2.2)
    x = mm(8)
    while x < W - mm(8):
        x2 = min(W - mm(8), x + dash)
        draw.line([(x, y), (x2, y)], fill=(186, 176, 166), width=max(1, mm(0.16)))
        x = x2 + gap
    mark = load_font("Inter-variable.ttf", 2.5, 500)
    draw.text((mm(8), y), "KATLA", font=mark, fill=(130, 120, 110), anchor="lm")
    draw.text((W - mm(8), y), "KATLA", font=mark, fill=(130, 120, 110), anchor="rm")
    return sheet


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    logo = flatten_logo(ASSETS / "logo.png")
    logo.save(ASSETS / "logo-trim.png")
    qr = Image.open(ASSETS / "qr.png").convert("RGB")

    face = render_face(logo, qr)
    face.save(OUT / "masa-karti-yuz.png", "PNG", dpi=(DPI, DPI))
    face.save(OUT / "masa-karti-yuz.jpg", "JPEG", quality=95, dpi=(DPI, DPI))

    preview = face.resize((face.width // 2, face.height // 2), Image.LANCZOS)
    preview.save(OUT / "masa-karti-onizleme.png", "PNG")

    sheet = render_a4_sheet(face)
    sheet.save(OUT / "masa-karti-a4-yazdir.png", "PNG", dpi=(DPI, DPI))
    sheet.save(OUT / "masa-karti-a4-yazdir.jpg", "JPEG", quality=94, dpi=(DPI, DPI))
    sheet_prev = sheet.resize((sheet.width // 3, sheet.height // 3), Image.LANCZOS)
    sheet_prev.save(OUT / "masa-karti-a4-onizleme.png", "PNG")

    face.convert("RGB").save(OUT / "masa-karti-yuz.pdf", "PDF", resolution=DPI)
    sheet.convert("RGB").save(OUT / "masa-karti-a4-yazdir.pdf", "PDF", resolution=DPI)

    print("Wrote", OUT)
    for p in sorted(OUT.iterdir()):
        print(" ", p.name, p.stat().st_size)


if __name__ == "__main__":
    main()
