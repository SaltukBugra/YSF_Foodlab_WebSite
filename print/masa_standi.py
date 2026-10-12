#!/usr/bin/env python3
"""YSF Food Lab — lazer kazıma masa standı, 80 × 150 mm, çift yüz.

Üst kesim logo siluetini takip eder. YSF ve FoodLab yalnız dış kontur
kazınır; masa numarası da kesilmez, aynı şekilde çizgiyle kazınır.
Lazer dosyasında siyah = kazı, kırmızı saç çizgi = kesim, beyaz = ahşap kalsın.

Ön: kontur logo, menü QR’ı ve “QR Menü”, altta MASA ve çizgi numara.
Arka: kontur logo, “Afiyet diliyoruz / Yorumlarınızla desteklerinizi bekliyoruz”,
Google Haritalar, Instagram, Facebook; telefon bunların altında, aynı çizgi numara.

Çalıştır: python3 print/masa_standi.py
"""

from __future__ import annotations

from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont
from reportlab.graphics.barcode import qr
from scipy import ndimage

ROOT = Path(__file__).resolve().parent
FONTS = ROOT / "assets" / "fonts"
OUT = ROOT / "output" / "stand"
LOGO_PATH = ROOT.parent / "ysffoodlab" / "assets" / "brand" / "ysf-logo-resmi.png"

DPI = 600
TABLES = range(1, 11)  # içeride 1–6, dışarıda 7–10
INK = (0, 0, 0)
CUT = (220, 0, 0)  # kesim konturu
BURN = (46, 28, 16)  # önizlemede lazer yanığı
BOARD_W = 80
BOARD_H = 150
FRONT_LOGO_W = 62
FRONT_LOGO_Y = 8
CUT_PAD = 5.5
BACK_LOGO_W = 48
NUMBER_SIZE = 28
NUMBER_STROKE = 0.55

MENU = "https://www.ysffoodlab.com.tr/menu/?masa={n}"
MAPS = "https://maps.app.goo.gl/gEh8y7RFp7JDUetA8"
PHONE = "0552 392 44 19"
INSTAGRAM = "https://www.instagram.com/ysffoodlab/"
FACEBOOK = "https://www.facebook.com/profile.php?id=61594680628811"
YSF_STROKE = 0.35  # mm, YSF ve FoodLab çerçevesi; içi ahşap kalır

def mm(value: float) -> int:
    return int(round(value / 25.4 * DPI))


def font(name: str, size_mm: float, weight: int = 560) -> ImageFont.FreeTypeFont:
    face = ImageFont.truetype(str(FONTS / name), mm(size_mm))
    if hasattr(face, "set_variation_by_axes"):
        try:
            face.set_variation_by_axes([weight])
        except OSError:
            pass
    return face


def wood(w: int, h: int, seed: int = 11) -> Image.Image:
    """Sakin, ince damarlı açık meşe. Referanstaki düz plakaya yakın."""
    rng = np.random.default_rng(seed)
    xs = np.linspace(0, 1, w, dtype=np.float32)
    ys = np.linspace(0, 1, h, dtype=np.float32)
    bands = np.zeros(w, np.float32)
    for _ in range(22):
        cx = float(rng.random())
        width = float(rng.uniform(0.003, 0.022))
        amp = float(rng.uniform(0.03, 0.16))
        bands += amp * np.exp(-((xs - cx) / width) ** 2)
    fiber = 0.025 * np.sin(xs * np.pi * 520)
    drift = (0.015 * np.sin(ys * np.pi * 1.6))[:, None]
    noise = rng.normal(0, 0.012, (h, w)).astype(np.float32)
    field = 0.62 + bands[None, :] + fiber[None, :] + drift + noise
    t = np.clip(field, 0, 1)[..., None]
    light = np.array([228, 190, 136], np.float32)
    mid = np.array([210, 166, 112], np.float32)
    dark = np.array([184, 136, 84], np.float32)
    img = dark + (mid - dark) * t + (light - mid) * np.clip(t - 0.45, 0, 1)
    return Image.fromarray(np.clip(img, 0, 255).astype(np.uint8), "RGB")


def make_qr(url: str, px: int, border: int = 2) -> Image.Image:
    """Siyah modül, şeffaf boşluk. Lazer yalnız siyaha değer; çevre ahşap kalır."""
    widget = qr.QrCodeWidget(url, barLevel="Q", barBorder=border)
    widget.qr.make()
    modules = widget.qr.modules
    count = widget.qr.getModuleCount()
    total = count + border * 2
    cell = 10
    side = total * cell
    arr = np.zeros((side, side, 4), np.uint8)
    for r, row in enumerate(modules):
        for c, bit in enumerate(row):
            if bit:
                y0 = (r + border) * cell
                x0 = (c + border) * cell
                arr[y0 : y0 + cell, x0 : x0 + cell] = (0, 0, 0, 255)
    im = Image.fromarray(arr, "RGBA")
    return im.resize((px, px), Image.NEAREST)


def tracked(draw: ImageDraw.ImageDraw, cx: int, cy: int, text: str, face: ImageFont.FreeTypeFont, fill, tracking: int) -> None:
    widths = []
    for ch in text:
        box = draw.textbbox((0, 0), ch, font=face)
        widths.append(box[2] - box[0])
    total = sum(widths) + tracking * max(0, len(text) - 1)
    x = cx - total / 2
    for ch, w in zip(text, widths):
        draw.text((x, cy), ch, font=face, fill=fill, anchor="lm")
        x += w + tracking


def rule(draw: ImageDraw.ImageDraw, cx: int, cy: int, half: int) -> None:
    gap = mm(2.4)
    stroke = max(2, mm(0.28))
    draw.line([(cx - half, cy), (cx - gap, cy)], fill=INK, width=stroke)
    draw.line([(cx + gap, cy), (cx + half, cy)], fill=INK, width=stroke)
    r = mm(0.85)
    draw.polygon([(cx, cy - r), (cx + r, cy), (cx, cy + r), (cx - r, cy)], fill=INK)


def load_logo() -> Image.Image:
    im = Image.open(LOGO_PATH).convert("RGBA")
    box = im.getbbox()
    if box:
        im = im.crop(box)
    return im


def _resize_bool(bits: np.ndarray, width: int) -> np.ndarray:
    im = Image.fromarray(np.where(bits, 255, 0).astype(np.uint8), "L")
    height = max(1, int(round(im.height * (width / max(1, im.width)))))
    im = im.resize((width, height), Image.Resampling.LANCZOS)
    return np.array(im) > 140


def _pad_to(bits: np.ndarray, height: int, width: int) -> np.ndarray:
    out = np.zeros((height, width), bool)
    out[: bits.shape[0], : bits.shape[1]] = bits
    return out


def _disk(radius: int) -> np.ndarray:
    radius = max(1, int(radius))
    y, x = np.ogrid[-radius : radius + 1, -radius : radius + 1]
    return (x * x + y * y) <= (radius * radius)


def _outer_ring(bits: np.ndarray, radius: int) -> np.ndarray:
    """Harfin dışına taşan halka. Deliklerin iç kenarı çizilmez."""
    filled = ndimage.binary_fill_holes(bits)
    grown = ndimage.binary_dilation(filled, structure=_disk(radius))
    return grown & ~filled


def _frame(bits: np.ndarray, radius: int) -> np.ndarray:
    """Dış kontur ve iç gözler. Gövde ahşap kalır."""
    grown = ndimage.binary_dilation(bits, structure=_disk(radius))
    return grown & ~bits


def logo_parts(src: Image.Image) -> tuple[np.ndarray, np.ndarray, np.ndarray, np.ndarray]:
    """Turuncu YSF, kırmızı FoodLab, şapka çizgisi ve şapkanın kapladığı yer."""
    arr = np.array(src.convert("RGBA"))
    r = arr[:, :, 0].astype(np.int16)
    g = arr[:, :, 1].astype(np.int16)
    b = arr[:, :, 2].astype(np.int16)
    opaque = arr[:, :, 3] > 80
    near_white = (r > 214) & (g > 214) & (b > 214)
    orange = opaque & (r > 150) & (g > 60) & (r > g + 30) & (b < 120)
    ink = opaque & ~near_white & ~orange
    rows = np.arange(opaque.shape[0])[:, None]
    food = ink & (rows >= 180)
    hat_ink = ink & (rows < 180)
    hat_region = opaque & (rows < 180) & (near_white | ink)
    return orange, food, hat_ink, hat_region


def engrave_logo(src: Image.Image, width: int) -> Image.Image:
    """YSF ve FoodLab dış kontur. Şapka dolu kazınır; beyazı ahşap kalır."""
    orange, food, hat_ink, hat_region = logo_parts(src)
    orange = _resize_bool(orange, width)
    food = _resize_bool(food, width)
    hat_ink = _resize_bool(hat_ink, width)
    hat_region = _resize_bool(hat_region, width)
    height = max(orange.shape[0], food.shape[0], hat_ink.shape[0], hat_region.shape[0])
    width = max(orange.shape[1], food.shape[1], hat_ink.shape[1], hat_region.shape[1])
    orange = _pad_to(orange, height, width)
    food = _pad_to(food, height, width)
    hat_ink = _pad_to(hat_ink, height, width)
    hat_region = _pad_to(hat_region, height, width)
    radius = max(2, mm(YSF_STROKE))
    ring = _outer_ring(orange, radius) | _frame(food, radius)
    ring &= ~hat_region
    bits = np.where(ring | hat_ink, 255, 0).astype(np.uint8)
    return Image.fromarray(bits, "L")


def paste_mark(canvas: Image.Image, mark: Image.Image, xy: tuple[int, int]) -> None:
    black = Image.new("RGB", mark.size, INK)
    canvas.paste(black, xy, mark)


def blank() -> Image.Image:
    return Image.new("RGB", (mm(BOARD_W), mm(BOARD_H)), (255, 255, 255))


def _smooth(values: np.ndarray, width_px: int) -> np.ndarray:
    width_px = max(3, width_px | 1)
    sigma = width_px / 6
    kx = np.arange(width_px) - width_px // 2
    kernel = np.exp(-0.5 * (kx / sigma) ** 2)
    kernel /= kernel.sum()
    return np.convolve(values, kernel, mode="same")


def shape_mask(logo: Image.Image) -> Image.Image:
    """Üst kesim logonun gökyüzünü takip eder; şapka hizasındayükselir, daire yoktur."""
    w, h = mm(BOARD_W), mm(BOARD_H)
    mark = engrave_logo(logo, mm(FRONT_LOGO_W))
    ox = (w - mark.width) // 2
    oy = mm(FRONT_LOGO_Y)
    bits = np.array(mark) > 128
    col = np.full(w, 10**9, np.int32)
    ys, xs = np.where(bits)
    np.minimum.at(col, xs + ox, ys + oy)
    known = np.where(col < 10**8)[0]
    left, right = int(known.min()), int(known.max())
    filled = col.astype(np.float64)
    filled[col >= 10**8] = np.nan
    xp = np.arange(w)
    filled[~np.isfinite(filled)] = np.interp(xp[~np.isfinite(filled)], known, col[known])

    win = max(3, mm(10) | 1)
    pad = win // 2
    padded = np.pad(filled, pad, mode="edge")
    sky = np.lib.stride_tricks.sliding_window_view(padded, win).min(axis=1)
    sky = _smooth(sky - mm(CUT_PAD), mm(11))

    shoulder = float(np.median(sky[left : left + int((right - left) * 0.6)]))
    top = np.full(w, shoulder, np.float64)
    top[left : right + 1] = sky[left : right + 1]
    blend = mm(11)
    for i in range(blend):
        t = (i + 1) / blend
        li = left - blend + i
        ri = right + 1 + i
        if 0 <= li < w:
            top[li] = shoulder + (top[left] - shoulder) * t
        if 0 <= ri < w:
            top[ri] = top[right] + (shoulder - top[right]) * t
    top[: max(0, left - blend)] = shoulder
    top[min(w, right + 1 + blend) :] = shoulder

    rad = float(mm(6))
    for i in range(int(rad)):
        dy = rad - np.sqrt(max(0.0, rad * rad - (rad - i) ** 2))
        top[i] = max(top[i], shoulder + dy)
        top[w - 1 - i] = max(top[w - 1 - i], shoulder + dy)

    mask = Image.new("L", (w, h), 0)
    draw = ImageDraw.Draw(mask)
    step = 3
    pts = [(x, int(round(top[x]))) for x in range(0, w, step)]
    pts.append((w - 1, int(round(top[-1]))))
    pts.append((w - 1, h - 1))
    pts.append((0, h - 1))
    draw.polygon(pts, fill=255)
    foot = mm(5)
    draw.rectangle([0, h - foot, foot, h], fill=0)
    draw.rectangle([w - foot, h - foot, w, h], fill=0)
    draw.pieslice([0, h - 2 * foot, 2 * foot - 1, h - 1], 90, 180, fill=255)
    draw.pieslice([w - 2 * foot, h - 2 * foot, w - 1, h - 1], 0, 90, fill=255)
    return mask


def apply_shape(laser: Image.Image, mask: Image.Image) -> Image.Image:
    arr = np.array(laser.convert("RGB"))
    inside = np.array(mask) > 128
    arr[~inside] = (255, 255, 255)
    return Image.fromarray(arr, "RGB")


def add_cut_line(laser: Image.Image, mask: Image.Image) -> Image.Image:
    """Kırmızı saç çizgi, lazerde kesim katmanı."""
    inside = np.array(mask) > 128
    eroded = np.array(mask.filter(ImageFilter.MinFilter(5))) > 128
    edge = inside & ~eroded
    arr = np.array(laser.convert("RGB"))
    arr[edge] = CUT
    return Image.fromarray(arr, "RGB")


def to_laser(image: Image.Image) -> Image.Image:
    """Gri kenarları at. Lazer griyi nokta nokta yakmasın."""
    gray = np.array(image.convert("L"))
    bits = np.where(gray < 170, 0, 255).astype(np.uint8)
    return Image.fromarray(bits, "L").convert("RGB")


def burn_on_wood(laser: Image.Image, board: Image.Image, mask: Image.Image) -> Image.Image:
    wood_px = np.array(board)
    gray = np.array(laser.convert("L"))
    red = np.array(laser.convert("RGB"))
    # Kırmızı kesim çizgisi kazıma değildir.
    dark = (gray < 128) & (red[:, :, 0] < 180)
    wood_px[dark] = BURN
    out = np.zeros((wood_px.shape[0], wood_px.shape[1], 4), np.uint8)
    out[..., :3] = wood_px
    out[..., 3] = np.where(np.array(mask) > 128, 255, 0).astype(np.uint8)
    return Image.fromarray(out, "RGBA")


def outline_glyph(text: str, face: ImageFont.FreeTypeFont, stroke_mm: float) -> Image.Image:
    """Yazının dış çizgisi. Gözler de çerçevelenir, içi ahşap kalır."""
    scratch = ImageDraw.Draw(Image.new("L", (4, 4)))
    box = scratch.textbbox((0, 0), text, font=face)
    pad = mm(stroke_mm) + mm(0.5)
    width = max(1, box[2] - box[0] + pad * 2)
    height = max(1, box[3] - box[1] + pad * 2)
    plate = Image.new("L", (width, height), 0)
    ImageDraw.Draw(plate).text((pad - box[0], pad - box[1]), text, font=face, fill=255)
    bits = np.array(plate) > 128
    if not bits.any():
        return plate
    ring = _frame(bits, max(2, mm(stroke_mm)))
    return Image.fromarray(np.where(ring, 255, 0).astype(np.uint8), "L")


def place_number(img: Image.Image, draw: ImageDraw.ImageDraw, table: int) -> None:
    """Masa numarası kesilmez; YSF gibi yalnız kontur kazınır."""
    mark = outline_glyph(str(table), font("Cinzel-variable.ttf", NUMBER_SIZE, 700), NUMBER_STROKE)
    cx = img.width // 2
    bottom = mm(BOARD_H - 7)
    x = cx - mark.width // 2
    y = bottom - mark.height
    paste_mark(img, mark, (x, y))
    tracked(draw, cx, y - mm(2.8), "MASA", font("Cinzel-variable.ttf", 2.8, 600), INK, mm(0.8))


def render_front(logo: Image.Image, menu_qr: Image.Image, table: int) -> Image.Image:
    img = blank()
    draw = ImageDraw.Draw(img)
    cx = img.width // 2

    mark = engrave_logo(logo, mm(FRONT_LOGO_W))
    paste_mark(img, mark, (cx - mark.width // 2, mm(FRONT_LOGO_Y)))

    qr_size = menu_qr.width
    qr_y = mm(46)
    img.paste(menu_qr, (cx - qr_size // 2, qr_y), menu_qr)
    draw.text(
        (cx, qr_y + qr_size + mm(2.0)),
        "QR Menü",
        font=font("Cinzel-variable.ttf", 3.2, 600),
        fill=INK,
        anchor="mt",
    )
    place_number(img, draw, table)
    return to_laser(img)


def render_back(logo: Image.Image, codes: dict[str, Image.Image], table: int) -> Image.Image:
    img = blank()
    draw = ImageDraw.Draw(img)
    cx = img.width // 2

    mark = engrave_logo(logo, mm(BACK_LOGO_W))
    ly = mm(8)
    paste_mark(img, mark, (cx - mark.width // 2, ly))

    title_y = ly + mark.height + mm(3.2)
    draw.text((cx, title_y), "Afiyet diliyoruz", font=font("PlayfairDisplay-Italic-variable.ttf", 4.6, 560), fill=INK, anchor="mt")
    draw.text(
        (cx, title_y + mm(6.2)),
        "Yorumlarınızla desteklerinizi bekliyoruz",
        font=font("PlayfairDisplay-variable.ttf", 2.55, 560),
        fill=INK,
        anchor="mt",
    )
    rule(draw, cx, title_y + mm(11.2), mm(12))

    order = ("Google Haritalar", "Instagram", "Facebook")
    qr_size = next(iter(codes.values())).width
    gap = mm(2.4)
    row_w = qr_size * len(order) + gap * (len(order) - 1)
    x0 = (img.width - row_w) // 2
    y0 = int(title_y + mm(14.5))
    label_face = font("Inter-variable.ttf", 1.85, 620)
    for index, name in enumerate(order):
        x = x0 + index * (qr_size + gap)
        img.paste(codes[name], (x, y0), codes[name])
        draw.text((x + qr_size // 2, y0 + qr_size + mm(1.2)), name, font=label_face, fill=INK, anchor="mt")

    phone_top = mm(100)
    draw.text((cx, phone_top), "Telefon", font=font("Inter-variable.ttf", 2.1, 620), fill=INK, anchor="mt")
    draw.text((cx, phone_top + mm(3.6)), PHONE, font=font("Inter-variable.ttf", 4.2, 700), fill=INK, anchor="mt")
    place_number(img, draw, table)
    return to_laser(img)


def finish(laser: Image.Image, outer: Image.Image) -> tuple[Image.Image, Image.Image]:
    """Dış form kesilir. Numara delik değildir, kazı çizgisi olarak kalır."""
    keep = np.array(outer) > 128
    arr = np.array(laser.convert("RGB"))
    arr[~keep] = (255, 255, 255)
    plate = Image.fromarray(np.where(keep, 255, 0).astype(np.uint8), "L")
    return add_cut_line(Image.fromarray(arr, "RGB"), plate), plate


def dark_wood(w: int, h: int) -> Image.Image:
    rng = np.random.default_rng(4)
    xs = np.linspace(0, 1, w, dtype=np.float32)
    bands = np.zeros(w, np.float32)
    for i, (cx, width, amp) in enumerate(((0.2, 0.08, 0.25), (0.55, 0.12, 0.18), (0.82, 0.06, 0.22))):
        bands += amp * np.exp(-((xs - cx) / width) ** 2)
    noise = rng.normal(0, 0.02, (h, w)).astype(np.float32)
    t = np.clip(0.35 + bands[None, :] + noise, 0, 1)[..., None]
    base = np.array([32, 20, 13], np.float32)
    light = np.array([58, 36, 24], np.float32)
    img = base + (light - base) * t
    img[0 : max(2, h // 8), :, :] = np.array([72, 48, 32], np.float32)
    return Image.fromarray(np.clip(img, 0, 255).astype(np.uint8), "RGB")


def mockup(front: Image.Image, back: Image.Image) -> Image.Image:
    """Ahşaba kazınmış hali, koyu kaide üzerinde."""
    canvas = Image.new("RGB", (1680, 1040), (255, 255, 255))
    shadow = Image.new("RGBA", canvas.size, (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow)

    def metrics(face: Image.Image, left: int) -> tuple[Image.Image, int, int, int, int, int, int]:
        card_h = 820
        card_w = int(face.width * card_h / face.height)
        card = face.resize((card_w, card_h), Image.LANCZOS)
        x, y = left, 70
        base_w, base_h = card_w + 90, 78
        bx = x - (base_w - card_w) // 2
        by = y + card_h - 28
        return card, x, y, base_w, base_h, bx, by

    card_w = int(front.width * 820 / front.height)
    gap = 110
    left0 = (canvas.width - (card_w * 2 + gap)) // 2
    slots = []
    for face, left in ((front, left0), (back, left0 + card_w + gap)):
        card, x, y, base_w, base_h, bx, by = metrics(face, left)
        slots.append((card, x, y, base_w, base_h, bx, by))
        sd.ellipse([bx + 16, by + 24, bx + base_w - 16, by + base_h + 28], fill=(0, 0, 0, 64))

    canvas = Image.alpha_composite(canvas.convert("RGBA"), shadow.filter(ImageFilter.GaussianBlur(16))).convert("RGB")
    for card, x, y, base_w, base_h, bx, by in slots:
        canvas.paste(card, (x, y), card)
        base = dark_wood(base_w, base_h)
        mask = Image.new("L", (base_w, base_h), 0)
        ImageDraw.Draw(mask).rounded_rectangle([0, 0, base_w - 1, base_h - 1], radius=10, fill=255)
        canvas.paste(base, (bx, by), mask)
    return canvas


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    board = wood(mm(BOARD_W), mm(BOARD_H))
    logo = load_logo()
    outer = shape_mask(logo)
    back_qr = mm(21)
    back_codes = {
        "Google Haritalar": make_qr(MAPS, back_qr),
        "Instagram": make_qr(INSTAGRAM, back_qr),
        "Facebook": make_qr(FACEBOOK, back_qr),
    }
    menu_qr_px = mm(40)

    pages: list[Image.Image] = []
    front_wood = None
    back_wood = None
    for n in TABLES:
        face, plate = finish(render_front(logo, make_qr(MENU.format(n=n), menu_qr_px), n), outer)
        back, _back_plate = finish(render_back(logo, back_codes, n), outer)
        pages.append(face)
        pages.append(back)
        if n == 1:
            front_wood = burn_on_wood(face, board, plate)
            back_wood = burn_on_wood(back, board, _back_plate)
            face.save(OUT / "lazer-01-on.png", "PNG", dpi=(DPI, DPI))
            back.save(OUT / "lazer-01-arka.png", "PNG", dpi=(DPI, DPI))
            front_wood.save(OUT / "masa-01-on.png", "PNG", dpi=(DPI, DPI))
            back_wood.save(OUT / "masa-01-arka.png", "PNG", dpi=(DPI, DPI))
        if n in (6, 10):
            preview_w = 320
            preview_h = int(round(preview_w * BOARD_H / BOARD_W))
            burn_on_wood(face, board, plate).resize((preview_w, preview_h), Image.LANCZOS).save(OUT / f"onizleme-{n:02d}.png")

    assert front_wood is not None
    shot = mockup(front_wood, back_wood)
    shot.save(OUT / "onizleme.png", "PNG")
    front_wood.resize((front_wood.width // 5, front_wood.height // 5), Image.LANCZOS).save(OUT / "onizleme-on.png", "PNG")
    back_wood.resize((back_wood.width // 5, back_wood.height // 5), Image.LANCZOS).save(OUT / "onizleme-arka.png", "PNG")

    pdf = OUT / "masa-standi-lazer.pdf"
    pages[0].save(pdf, "PDF", resolution=DPI, save_all=True, append_images=pages[1:])

    print(f"Lazer dosyası: {pdf}  ({len(TABLES)} masa, {len(pages)} sayfa)")
    print(f"Önizleme: {OUT / 'onizleme.png'}")


if __name__ == "__main__":
    main()
