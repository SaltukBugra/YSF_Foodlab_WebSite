#!/usr/bin/env python3
"""YSF Food Lab icerik kurulumu (REST API).

Kurulum sihirbazinin yaptigi isleri panelden tiklamaya gerek kalmadan
gerceklestirir: sayfa sablonlari, menu kategorileri, urun fotograflari,
menu urunleri, kampanyalar ve navigasyon menuleri.

Tekrar tekrar calistirilabilir; var olan kayitlari yeniden olusturmaz.

Kullanim:  python3 tools/bootstrap-content.py [--dry-run]
"""

import base64
import json
import re
import sys
import unicodedata
import urllib.error
import urllib.request
from pathlib import Path

SITE = "https://www.ysffoodlab.com.tr"
BASE = SITE + "/wp-json/wp/v2"
IMG_DIR = Path(__file__).resolve().parent.parent / "ysffoodlab" / "assets" / "images"
DRY = "--dry-run" in sys.argv

_cfg = Path("/tmp/ysf.cfg").read_text(encoding="utf-8")
AUTH = base64.b64encode(
    re.search(r'user\s*=\s*"([^"]+)"', _cfg).group(1).encode()
).decode()


# --------------------------------------------------------------------------
# HTTP yardimcilari
# --------------------------------------------------------------------------

def req(method, path, data=None, raw=None, headers=None):
    url = path if path.startswith("http") else BASE + path
    hdrs = {"Authorization": "Basic " + AUTH, "Accept": "application/json"}
    body = None

    if data is not None:
        body = json.dumps(data, ensure_ascii=False).encode("utf-8")
        hdrs["Content-Type"] = "application/json; charset=utf-8"
    elif raw is not None:
        body = raw

    if headers:
        hdrs.update(headers)

    request = urllib.request.Request(url, data=body, headers=hdrs, method=method)

    try:
        with urllib.request.urlopen(request, timeout=120) as resp:
            payload = resp.read().decode("utf-8")
            return json.loads(payload) if payload else {}
    except urllib.error.HTTPError as err:
        detail = err.read().decode("utf-8", "replace")[:400]
        raise RuntimeError("%s %s -> %s: %s" % (method, url, err.code, detail))


def slugify(text):
    text = unicodedata.normalize("NFKD", text)
    trmap = str.maketrans("çğıöşüÇĞİÖŞÜ", "cgiosuCGIOSU")
    text = text.translate(trmap)
    text = re.sub(r"[^a-zA-Z0-9]+", "-", text).strip("-").lower()
    return text


def log(msg):
    print(("[deneme] " if DRY else "") + msg, flush=True)


# --------------------------------------------------------------------------
# Veri
# --------------------------------------------------------------------------

PAGE_TEMPLATES = {
    "menu": "template-menu.php",
    "online-siparis": "template-order.php",
    "rezervasyon": "template-reservation.php",
    "iletisim": "template-contact.php",
}

PAGE_TITLES_EN = {
    "ana-sayfa": "Home",
    "menu": "Menu",
    "online-siparis": "Order Online",
    "rezervasyon": "Reservations",
    "hakkimizda": "About Us",
    "iletisim": "Contact",
    "blog": "Blog",
}

CATEGORIES = [
    ("kahvalti", "Kahvaltı", "Breakfast"),
    ("aperatif", "Aperatif & Başlangıç", "Appetisers & Starters"),
    ("hamur-isleri", "Hamur İşleri", "Turkish Pastries"),
    ("pizza", "Pizza", "Pizza"),
    ("makarna", "Makarna", "Pasta"),
    ("fast-food", "Burger & Fast Food", "Burgers & Fast Food"),
    ("tatlilar", "Tatlılar", "Desserts"),
    ("kahve-icecek", "Kahve & İçecek", "Coffee & Drinks"),
]

ITEMS = [
    dict(
        title="Serpme Kahvaltı (kişi başı)",
        title_en="Turkish Breakfast (per person)",
        excerpt="Köy peyniri, zeytin, bal-kaymak, ev reçelleri, sucuklu yumurta, sıcak simit ve sınırsız çay.",
        excerpt_en="Village cheeses, olives, honey with clotted cream, house jams, eggs with sucuk, warm simit and unlimited tea.",
        price=295, cat="kahvalti", image="dish-kahvalti.jpg",
        badge="Hafta sonu favorisi", badge_en="Weekend favourite", featured=True,
    ),
    dict(
        title="Simit Tabağı", title_en="Simit Plate",
        excerpt="Susamı bol taze simit, beyaz peynir, tereyağı ve mevsim reçeli.",
        excerpt_en="Fresh sesame simit with white cheese, butter and seasonal jam.",
        price=110, cat="kahvalti", image="dish-simit.jpg", vegetarian=True,
    ),
    dict(
        title="Aperatif Tabağı (2-3 kişilik)", title_en="Aperitif Board (serves 2-3)",
        excerpt="Haydari, ezme, muhammara, beyaz peynir, sucuk, sigara böreği, ceviz ve sıcak ekmek.",
        excerpt_en="Haydari, ezme, muhammara, white cheese, sucuk, cheese rolls, walnuts and warm bread.",
        price=385, cat="aperatif", image="dish-aperatif.jpg",
        badge="Paylaşmalık", badge_en="To share", featured=True,
    ),
    dict(
        title="Ev Yapımı Humus", title_en="House Hummus",
        excerpt="Nohut, tahin, limon ve zeytinyağı; yanında sıcak lavaş.",
        excerpt_en="Chickpeas, tahini, lemon and olive oil, served with warm flatbread.",
        price=155, cat="aperatif", image="dish-humus.jpg", vegan=True,
    ),
    dict(
        title="Kinoa & Avokado Salata", title_en="Quinoa & Avocado Salad",
        excerpt="Kinoa, avokado, nar, ceviz ve limonlu sos.",
        excerpt_en="Quinoa, avocado, pomegranate, walnuts and lemon dressing.",
        price=245, cat="aperatif", image="dish-salad.jpg", vegan=True, glutenfree=True,
    ),
    dict(
        title="Su Böreği (Peynirli)", title_en="Su Böreği with Cheese",
        excerpt="El açması yufka, bol beyaz peynir ve maydanoz; tepside günlük pişiyor.",
        excerpt_en="Hand-rolled layers filled with white cheese and parsley, baked fresh daily.",
        price=175, cat="hamur-isleri", image="dish-suborek-peynir.jpg",
        badge="Günlük açma", badge_en="Made this morning", vegetarian=True, featured=True,
    ),
    dict(
        title="Su Böreği (Kıymalı)", title_en="Su Böreği with Minced Beef",
        excerpt="Baharatlı dana kıyma ve soğan harcı, tereyağlı katmerli yufka.",
        excerpt_en="Spiced minced beef and onion between buttery layers of pastry.",
        price=195, cat="hamur-isleri", image="dish-suborek-kiyma.jpg",
    ),
    dict(
        title="Margherita Pizza", title_en="Margherita Pizza",
        excerpt="Taş fırında, ince hamur, San Marzano domates sosu, mozzarella ve taze fesleğen.",
        excerpt_en="Stone-baked thin crust with San Marzano tomato sauce, mozzarella and fresh basil.",
        price=265, cat="pizza", image="dish-pizza.jpg", vegetarian=True, featured=True,
    ),
    dict(
        title="Sucuklu Pizza", title_en="Pizza with Sucuk",
        excerpt="Mozzarella, acılı sucuk dilimleri, yeşil biber ve kekik.",
        excerpt_en="Mozzarella, spicy sucuk slices, green pepper and oregano.",
        price=295, cat="pizza", spicy=True,
    ),
    dict(
        title="Kremalı Fettuccine", title_en="Creamy Fettuccine",
        excerpt="Ev yapımı fettuccine, parmesan, karabiber ve tereyağı sos.",
        excerpt_en="House-made fettuccine with parmesan, black pepper and butter sauce.",
        price=275, cat="makarna", image="dish-pasta.jpg", vegetarian=True,
    ),
    dict(
        title="Domates Soslu Penne", title_en="Penne in Tomato Sauce",
        excerpt="Günlük hazırlanan domates sos, sarımsak, zeytinyağı ve fesleğen.",
        excerpt_en="Daily-made tomato sauce with garlic, olive oil and basil.",
        price=235, cat="makarna", vegan=True,
    ),
    dict(
        title="YSF Signature Burger", title_en="YSF Signature Burger",
        excerpt="180 gr dana köfte, cheddar, karamelize soğan, özel sos ve brioche ekmek.",
        excerpt_en="180 g beef patty, cheddar, caramelised onion, house sauce and brioche bun.",
        price=315, cat="fast-food", image="dish-burger.jpg",
        badge="En çok satan", badge_en="Best seller", featured=True,
    ),
    dict(
        title="Çıtır Tavuk Kanat & Patates", title_en="Crispy Wings & Fries",
        excerpt="Baharatlı çıtır kanat, kalın kesim patates ve ranch sos.",
        excerpt_en="Spiced crispy wings with thick-cut fries and ranch dip.",
        price=245, cat="fast-food", image="dish-kanat.jpg", spicy=True,
    ),
    dict(
        title="San Sebastian Cheesecake", title_en="San Sebastian Cheesecake",
        excerpt="Yanık yüzeyli, akışkan dokulu klasik.",
        excerpt_en="The classic burnt-top, molten-centre cheesecake.",
        price=165, cat="tatlilar", image="dish-cheesecake.jpg", featured=True,
    ),
    dict(
        title="Fıstık Rüyası", title_en="Pistachio Dream",
        excerpt="Antep fıstığı kreması, pandispanya ve bol çekilmiş fıstık.",
        excerpt_en="Pistachio cream, sponge cake and a generous pistachio topping.",
        price=175, cat="tatlilar", image="dish-fistik-ruyasi.jpg",
        allergens="gluten, süt, fıstık",
    ),
    dict(
        title="Donuk Pasta", title_en="Chilled Icebox Cake",
        excerpt="Bisküvi katları, soğuk vanilya-kakao kreması ve çikolata ganaj.",
        excerpt_en="Layers of biscuit, cold vanilla-cocoa cream and chocolate ganache.",
        price=145, cat="tatlilar", image="dish-donuk-pasta.jpg", allergens="gluten, süt",
    ),
    dict(
        title="Flat White", title_en="Flat White",
        excerpt="Çift shot espresso, kadifemsi süt.",
        excerpt_en="Double espresso with velvety milk.",
        price=105, cat="kahve-icecek", image="dish-coffee.jpg",
    ),
    dict(
        title="Filtre Kahve", title_en="Filter Coffee",
        excerpt="Haftanın tek origin çekirdeği, V60.",
        excerpt_en="Single origin of the week, brewed on V60.",
        price=95, cat="kahve-icecek",
    ),
    dict(
        title="Türk Kahvesi", title_en="Turkish Coffee",
        excerpt="Taze çekilmiş, bakır cezvede; yanında lokum.",
        excerpt_en="Freshly ground, brewed in copper, served with Turkish delight.",
        price=75, cat="kahve-icecek",
    ),
    dict(
        title="Ev Yapımı Limonata", title_en="Homemade Lemonade",
        excerpt="Limon, nane, az şeker.",
        excerpt_en="Lemon, mint and just a little sugar.",
        price=85, cat="kahve-icecek", vegan=True, glutenfree=True,
    ),
]

CAMPAIGNS = [
    dict(
        title="Hafta içi öğle menüsü 295 ₺", title_en="Weekday lunch menu for 295 ₺",
        excerpt="Pazartesi–Cuma 12:00–16:00 arası pizza veya makarna + salata + filtre kahve.",
        excerpt_en="Monday to Friday, 12:00–16:00: pizza or pasta, a salad and filter coffee.",
        badge="Öğle fırsatı", badge_en="Lunch deal", type="kampanya",
        image="dish-pasta.jpg", bar=True, days=45,
    ),
    dict(
        title="Hafta sonu serpme kahvaltı", title_en="Weekend Turkish breakfast",
        excerpt="Cumartesi–Pazar 09:00–13:00 arası serpme kahvaltı, sınırsız çay dahil.",
        excerpt_en="Saturday and Sunday, 09:00–13:00: full Turkish breakfast with unlimited tea.",
        badge="Hafta sonu", badge_en="Weekend", type="kampanya",
        image="dish-kahvalti.jpg", bar=True, days=60,
    ),
    dict(
        title="İki kişilik akşam menüsü", title_en="Dinner menu for two",
        excerpt="Aperatif tabağı, iki ana yemek ve tatlı; özel günler için ideal.",
        excerpt_en="An aperitif board, two mains and dessert — ideal for special occasions.",
        badge="Çift menü", badge_en="For two", type="kampanya",
        image="hero-2.jpg", days=60,
    ),
    dict(
        title="Doğum günü masası hazırlıyoruz", title_en="We set the table for birthdays",
        excerpt="Rezervasyon formundan “doğum günü” seçin; pasta ve süsleme bizden.",
        excerpt_en="Pick “birthday” in the reservation form and we will handle cake and decoration.",
        badge="Duyuru", badge_en="Notice", type="duyuru",
        image="dish-donuk-pasta.jpg", bar=True, days=120,
    ),
]

NAV_MENUS = [
    ("Ana Menü", "primary",
     ["ana-sayfa", "menu", "online-siparis", "rezervasyon", "hakkimizda", "iletisim"]),
    ("Alt Bilgi Menüsü", "secondary",
     ["menu", "rezervasyon", "blog", "iletisim"]),
]


# --------------------------------------------------------------------------
# Adimlar
# --------------------------------------------------------------------------

def step_pages():
    """Sayfa sablonlarini ve Ingilizce basliklarini tamamlar."""
    pages = req("GET", "/pages?per_page=50&status=publish,draft&_fields=id,slug,template,meta")
    ids = {}

    for page in pages:
        slug = page["slug"]
        ids[slug] = page["id"]
        payload = {}

        want_template = PAGE_TEMPLATES.get(slug)
        if want_template and page.get("template") != want_template:
            payload["template"] = want_template

        want_en = PAGE_TITLES_EN.get(slug)
        if want_en and not (page.get("meta") or {}).get("_ysf_title_en"):
            payload["meta"] = {"_ysf_title_en": want_en}

        if payload:
            if not DRY:
                req("POST", "/pages/%d" % page["id"], payload)
            log("sayfa guncellendi: %s (%s)" % (slug, ", ".join(payload.keys())))

    return ids


def step_categories():
    """Menu kategorilerini olusturur."""
    existing = {
        t["slug"]: t["id"]
        for t in req("GET", "/ysf-menu-categories?per_page=100&_fields=id,slug")
    }
    ids = {}

    for slug, name_tr, name_en in CATEGORIES:
        if slug in existing:
            ids[slug] = existing[slug]
            continue

        if DRY:
            ids[slug] = 0
            log("kategori olusturulacak: %s" % name_tr)
            continue

        term = req("POST", "/ysf-menu-categories", {
            "name": name_tr,
            "slug": slug,
            "meta": {"_ysf_name_en": name_en},
        })
        ids[slug] = term["id"]
        log("kategori olusturuldu: %s" % name_tr)

    return ids


class Media:
    """Gorselleri medya kutuphanesine bir kez yukler."""

    def __init__(self):
        self.cache = {}

        for item in req("GET", "/media?per_page=100&_fields=id,slug"):
            self.cache[item["slug"]] = item["id"]

    def get(self, filename, title):
        if not filename:
            return 0

        path = IMG_DIR / filename
        if not path.exists():
            log("!! gorsel bulunamadi: %s" % filename)
            return 0

        slug = "ysf-" + slugify(filename.rsplit(".", 1)[0])

        if slug in self.cache:
            return self.cache[slug]

        if DRY:
            log("gorsel yuklenecek: %s" % filename)
            return 0

        created = req(
            "POST", "/media",
            raw=path.read_bytes(),
            headers={
                "Content-Type": "image/jpeg",
                "Content-Disposition": 'attachment; filename="%s.jpg"' % slug,
            },
        )
        req("POST", "/media/%d" % created["id"], {"title": title, "alt_text": title})

        self.cache[slug] = created["id"]
        log("gorsel yuklendi: %s -> #%d" % (filename, created["id"]))

        return created["id"]


def step_items(cat_ids, media):
    """Menu urunlerini olusturur."""
    existing = {
        p["slug"]
        for p in req("GET", "/ysf-menu-items?per_page=100&status=any&_fields=id,slug")
    }
    created = 0

    for order, item in enumerate(ITEMS, start=1):
        slug = slugify(item["title"])

        if slug in existing:
            continue

        meta = {
            "_ysf_price": item["price"],
            "_ysf_title_en": item["title_en"],
            "_ysf_excerpt_en": item["excerpt_en"],
            "_ysf_orderable": True,
            "_ysf_featured": bool(item.get("featured")),
            "_ysf_vegan": bool(item.get("vegan")),
            "_ysf_vegetarian": bool(item.get("vegetarian")),
            "_ysf_glutenfree": bool(item.get("glutenfree")),
            "_ysf_spicy": bool(item.get("spicy")),
            "_ysf_badge": item.get("badge", ""),
            "_ysf_badge_en": item.get("badge_en", ""),
            "_ysf_allergens": item.get("allergens", ""),
        }

        payload = {
            "status": "publish",
            "title": item["title"],
            "slug": slug,
            "excerpt": item["excerpt"],
            "menu_order": order,
            "meta": meta,
            "ysf-menu-categories": [cat_ids[item["cat"]]] if cat_ids.get(item["cat"]) else [],
        }

        media_id = media.get(item.get("image"), item["title"])
        if media_id:
            payload["featured_media"] = media_id

        if DRY:
            log("urun olusturulacak: %s" % item["title"])
            continue

        req("POST", "/ysf-menu-items", payload)
        created += 1
        log("urun eklendi: %-32s %s ₺" % (item["title"], item["price"]))

    return created


def step_campaigns(media, order_page_id):
    """Kampanya ve duyurulari olusturur."""
    import datetime

    existing = {
        p["slug"]
        for p in req("GET", "/ysf-campaigns?per_page=100&status=any&_fields=id,slug")
    }
    today = datetime.date.today()
    created = 0

    for campaign in CAMPAIGNS:
        slug = slugify(campaign["title"])

        if slug in existing:
            continue

        meta = {
            "_ysf_title_en": campaign["title_en"],
            "_ysf_excerpt_en": campaign["excerpt_en"],
            "_ysf_badge": campaign["badge"],
            "_ysf_badge_en": campaign["badge_en"],
            "_ysf_type": campaign["type"],
            "_ysf_start": today.isoformat(),
            "_ysf_end": (today + datetime.timedelta(days=campaign["days"])).isoformat(),
            "_ysf_show_in_bar": bool(campaign.get("bar")),
        }

        if order_page_id:
            meta["_ysf_link"] = "%s/?page_id=%d" % (SITE, order_page_id)

        payload = {
            "status": "publish",
            "title": campaign["title"],
            "slug": slug,
            "excerpt": campaign["excerpt"],
            "meta": meta,
        }

        media_id = media.get(campaign.get("image"), campaign["title"])
        if media_id:
            payload["featured_media"] = media_id

        if DRY:
            log("kampanya olusturulacak: %s" % campaign["title"])
            continue

        req("POST", "/ysf-campaigns", payload)
        created += 1
        log("kampanya eklendi: %s" % campaign["title"])

    return created


def step_navigation(page_ids):
    """Ust ve alt bilgi menulerini kurar."""
    existing = {m["name"]: m for m in req("GET", "/menus?per_page=100")}

    for name, location, slugs in NAV_MENUS:
        if name in existing:
            menu = existing[name]
            menu_id = menu["id"]

            if location not in (menu.get("locations") or []):
                if not DRY:
                    req("POST", "/menus/%d" % menu_id, {"locations": [location]})
                log("menu konumu atandi: %s -> %s" % (name, location))

            continue

        if DRY:
            log("menu olusturulacak: %s (%s)" % (name, location))
            continue

        menu = req("POST", "/menus", {"name": name, "locations": [location]})
        menu_id = menu["id"]

        for order, slug in enumerate(slugs, start=1):
            if slug not in page_ids:
                continue

            req("POST", "/menu-items", {
                "menus": menu_id,
                "type": "post_type",
                "object": "page",
                "object_id": page_ids[slug],
                "status": "publish",
                "menu_order": order,
            })

        log("menu kuruldu: %s (%d bağlantı)" % (name, len(slugs)))


def main():
    log("--- sayfalar ---")
    page_ids = step_pages()

    log("--- kategoriler ---")
    cat_ids = step_categories()

    log("--- gorseller ve urunler ---")
    media = Media()
    items = step_items(cat_ids, media)

    log("--- kampanyalar ---")
    campaigns = step_campaigns(media, page_ids.get("online-siparis"))

    log("--- navigasyon ---")
    step_navigation(page_ids)

    print()
    log("bitti: %d urun, %d kampanya, %d gorsel" % (items, campaigns, len(media.cache)))


if __name__ == "__main__":
    main()
