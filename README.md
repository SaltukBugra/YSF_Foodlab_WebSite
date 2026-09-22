# YSF Food Lab — Web Sitesi Projesi

**Canlı site:** [ysffoodlab.com.tr](https://www.ysffoodlab.com.tr)  
**GitHub:** [github.com/SaltukBugra/YSF_Foodlab_WebSite](https://github.com/SaltukBugra/YSF_Foodlab_WebSite)  
**Güncel tema sürümü:** `1.4.39`  
**WordPress teması:** `ysffoodlab/`

YSF Food Lab restoranının resmi web sitesi, online sipariş, rezervasyon, masa servisi (garson / kasiyer / mutfak), üye hesapları, kampanya ve duyuru yönetimi ile birlikte tek bir WordPress temasında toplanmıştır. Ek eklenti gerektirmez; Türkçe ve İngilizce çift dilli çalışır.

---

## İçindekiler

1. [Proje yapısı](#proje-yapısı)
2. [Kurulum ve dağıtım](#kurulum-ve-dağıtım)
3. [Tema özellikleri](#tema-özellikleri)
4. [Üye hesapları ve güvenlik](#üye-hesapları-ve-güvenlik)
5. [Menü ve sipariş](#menü-ve-sipariş)
6. [Masa servisi ve personel ekranları](#masa-servisi-ve-personel-ekranları)
7. [Kampanya yönetimi](#kampanya-yönetimi)
8. [Duyuru yönetimi](#duyuru-yönetimi)
9. [Android personel uygulamaları](#android-personel-uygulamaları)
10. [Masa broşürü baskı araçları](#masa-broşürü-baskı-araçları)
11. [Önemli URL’ler](#önemli-urller)
12. [Geliştirme notları](#geliştirme-notları)
13. [Sürüm geçmişi (özet)](#sürüm-geçmişi-özet)

---

## Proje yapısı

```
ysffoodlab-web/
├── ysffoodlab/              # WordPress teması (ana uygulama)
│   ├── inc/                 # PHP modülleri (kampanya, duyuru, sipariş, hesap…)
│   ├── template-parts/      # Parça şablonlar (menü, hesap, mutfak…)
│   ├── assets/js/           # main.js (müşteri), staff.js (personel)
│   └── readme.txt           # WordPress.org formatında tema readme
├── android-apps/            # Android WebView personel uygulaması kaynak kodu
├── print/                   # Masa kartı / broşür PDF ve görsel üretim araçları
├── ysffoodlab.zip           # WordPress’e yüklenecek paketlenmiş tema (git’e dahil değil)
├── ysf-sosyal-medya-duyuru-banner.png   # Hazır duyuru görseli
└── README.md                # Bu dosya
```

| Klasör / dosya | Açıklama |
|----------------|----------|
| `ysffoodlab/` | Tüm site mantığı burada; WordPress teması olarak yüklenir |
| `android-apps/` | Garson, kasiyer ve mutfak için Android uygulama iskeleti |
| `print/` | QR kodlu masa kartı broşürü oluşturan Python betiği ve şablonlar |
| `apk/` | Derlenmiş APK dosyaları (git’e eklenmez) |
| `ysffoodlab.zip` | Canlı siteye yüklenen paket; `.gitignore` ile hariç tutulur |

---

## Kurulum ve dağıtım

### WordPress temasını yükleme

1. `ysffoodlab.zip` dosyasını oluşturun (proje kökünde):
   ```bash
   zip -r ysffoodlab.zip ysffoodlab -x 'ysffoodlab/_preview-menu.html'
   ```
2. WordPress panelinde **Görünüm → Temalar → Yeni Ekle → Tema Yükle** yolunu izleyin.
3. Zip’i seçip **Etkinleştir**’e basın.
4. Üstte çıkan bildirimden veya **Görünüm → YSF Kurulum** sayfasından **Kurulumu Başlat**’a tıklayın.
5. **Görünüm → Özelleştir → YSF Food Lab Ayarları** bölümünden telefon, WhatsApp, adres, çalışma saatleri ve sipariş ayarlarını girin.
6. **Ayarlar → Kalıcı Bağlantılar** sayfasını bir kez kaydedin.
7. LiteSpeed Cache kullanıyorsanız tema güncellemesinden sonra önbelleği temizleyin (Ctrl+F5 veya **LiteSpeed Cache → Purge All**).

### Git ve GitHub

```bash
git remote -v
# origin  git@github.com:SaltukBugra/YSF_Foodlab_WebSite.git

git push -u origin cursor/register-otp-verification   # özellik dalı
```

Ana dal `master`’dır. Büyük güncellemeler özellik dalında geliştirilip pull request ile birleştirilir.

---

## Tema özellikleri

### Genel

- **Çift dil (TR / EN):** Üst bardaki TR / EN düğmesiyle dil değişir (`?lang=en`). İngilizce alan boş bırakılırsa Türkçe metin gösterilir; site boş kalmaz.
- **Kapak slaytı:** Ana sayfada yumuşak geçişli hero slaytı; Özelleştirici’den en fazla 4 görsel seçilebilir.
- **Sayfa geçişleri:** View Transitions ile üst bar sabit kalırken içerik kayarak değişir.
- **Performans:** jQuery kullanılmaz; tek CSS ve tek JS dosyası yüklenir. LiteSpeed Cache ile uyumludur.
- **SEO:** Restaurant schema desteği.
- **Sosyal medya:** Instagram (@ysffoodlab) ve Facebook (YSF Foodlab) ikonları üst bar, iletişim ve ana sayfada; WhatsApp yüzen butonu.

### İçerik tipleri

| Tip | Açıklama |
|-----|----------|
| Menü ürünleri | Fiyat, indirim, ebat, etiketler, alerjen, kalori, stok durumu |
| Kampanyalar | İndirim kuralları, tarih/saat aralığı, görsel |
| Duyurular | Bilgilendirme metni, görsel; fiyat değiştirmez |
| Rezervasyonlar | Form kayıtları, e-posta bildirimi, durum takibi |
| Siparişler | Online sipariş kayıtları, WhatsApp entegrasyonu |

---

## Üye hesapları ve güvenlik

### Müşteri üyeliği (Hesabım)

- **Kayıt:** Ad soyad, e-posta, telefon ve şifre ile üyelik açılır.
- **E-posta doğrulama:** Kayıt, e-postaya giden 6 haneli kod doğrulanmadan tamamlanmaz.
- **Giriş:** Kullanıcı adı, e-posta veya telefon numarası ile yapılabilir.
- **Adres defteri:** Ev ve iş olmak üzere iki adres; il / ilçe listesi temayla gelir (81 il, 972 ilçe).
- **Telefon formatı:** `+90XXXXXXXXXX` biçimine normalize edilir; `0552…`, `+90 552…` gibi yazımlar kabul edilir.
- **Sipariş entegrasyonu:** Giriş yapmış üyenin ad, telefon, e-posta ve kayıtlı adresi sipariş formuna otomatik dolar.
- **Gizlilik:** Müşteri hesapları wp-admin paneline giremez; yönetim çubuğu gösterilmez.

### Yönetici güvenliği (2FA)

- Site yöneticisi (`manage_options`) girişinde **Google Authenticator** uyumlu iki adımlı doğrulama zorunludur.
- İlk kurulumda QR kodu ve elle girilecek anahtar gösterilir (`inc/lib-qrcode.php` ile sitede üretilir).
- Personel ve normal üye hesapları 2FA’dan etkilenmez.

### E-posta (SMTP)

- Giden adres: `info@ysffoodlab.com.tr`
- SMTP ayarları: **Özelleştir → YSF Food Lab Ayarları → İşletme Bilgileri**
- Veridyen SMTP: port 465/SSL, LOGIN kimlik doğrulama

---

## Menü ve sipariş

### Menü sayfası (`/menu`)

- Ürünler ortada S şeklinde dalgalanan, iç içe geçen fotoğraf serisi olarak dizilir.
- Kategori başlıkları koyu zemin üzerinde gösterilir.
- Menü sayfasında **sepete ekle yok**; yalnızca listeleme ve bilgi.

### Online sipariş (`/siparis`)

- Aynı menü içeriği satır görünümünde; ebat seçimi ve sepete ekleme burada yapılır.
- Sepet tarayıcıda saklanır; gönderimde fiyatlar sunucuda güncel değerlerle yeniden hesaplanır.
- Sipariş panele kaydedilir, e-posta gider ve (ayardan kapatılabilir) WhatsApp özeti açılır.
- Ödeme altyapısı yoktur; onay telefonla yapılır.

### Ürün özellikleri

- Fiyat, indirimli fiyat, **ebat** (fiyat alanının altında)
- Etiketler: bilgi (koyu), olumlu (yeşil), olumsuz (kırmızı), kampanya (sarı)
- Vegan, vejetaryen, acı, glutensiz, alerjen listesi, kalori
- Mutfak panelinden stokta yok işareti, yeni ürün ekleme, menüden kaldırma

---

## Masa servisi ve personel ekranları

| URL | Rol | Açıklama |
|-----|-----|----------|
| `/garson` | Garson | Masadan sipariş alma, mutfağa gönderme |
| `/kasiyer` | Kasiyer | Açık masa hesapları, nakit/kart tahsilat, günlük kasa |
| `/mutfak-ekrani` | Mutfak | Canlı sipariş kuyruğu (KDS) |

### Hesabım sekmeleri (role göre)

| Sekme | Kim görür? |
|-------|------------|
| Mutfak | Mutfak sorumlusu, yönetici |
| Kampanyalar | Yalnız site yöneticisi |
| Duyurular | Yalnız site yöneticisi |
| Garson | Garson rolü |
| Kasiyer | Kasiyer ve yönetici |
| Profil | Tüm giriş yapmış kullanıcılar |

Masa sayısı: **Özelleştir → Masa servisi** bölümünden ayarlanır.

---

## Kampanya yönetimi

Kampanyalar indirim kurallarını tanımlar; duyuru şeridinde ve ana sayfadaki **“Bu haftanın fırsatları”** bölümünde görünür.

### Yönetim yerleri

1. **WordPress paneli:** Sol menü → **Kampanyalar** (`wp-admin/admin.php?page=ysf-campaigns`)
2. **Hesabım:** YSF Admin ile giriş → **Kampanyalar** sekmesi (`/hesabim/#kampanya`)

Her iki yerden de ekleme, düzenleme ve **kalıcı silme** (yalnız yönetici) yapılabilir.

### Dört kampanya senaryosu

| Senaryo | Ne yapar? |
|---------|-----------|
| **Doğrudan indirim** | Seçili ürünlerin fiyatını hemen düşürür |
| **Ücret eşiği** | Seçili ürünlerin sepetteki toplam tutarı belirlenen TL değerini geçince indirim uygulanır (eski “adet eşiği” yerine geçti) |
| **Birlikte toplam** | Seçilen ürünlerin hepsi sepetteyken birer adedinin toplamı girilen tutara çekilir |
| **Genel kampanya** | Fiyat değiştirmez; yalnızca duyuru olarak görünür |

### Kampanya alanları

- Başlık, kısa duyuru metni (TR / EN)
- Senaryo, ürün seçimi (kategorili grid + arama + küçük resim)
- İndirim türü (yüzde / tutar), indirim değeri
- Ücret eşiği (TL), birlikte toplam tutar
- Başlangıç / bitiş **tarihi** (zorunlu)
- İsteğe bağlı **saat aralığı** (ör. 16:00–00:00); boş bırakılırsa gün boyu geçerli
- **Kampanya görseli** (öne çıkan görsel / featured image)

### Görünürlük kuralları

| Durum | Sitede ne görünür? |
|-------|-------------------|
| Tarih aralığında, saat içinde | Normal kampanya + indirim aktif |
| Tarih aralığında, saat henüz başlamadı | **“16:00'da başlıyor”** (1.4.38+) |
| Tarih aralığında, saat bitti ama kampanya ertesi güne sarkıyor | **“Yarın gene bekleriz”** — indirim uygulanmaz |
| Bitiş tarihi geçti | **“Süresi doldu”** — kayıt silinmez, yalnız yönetici silebilir |

### Sepet entegrasyonu

- İndirimler hem `main.js` (online sipariş) hem `staff.js` (garson POS) tarafında uygulanır.
- Ücret eşiği: seçili ürünlerin `taban fiyat × adet` toplamı eşiği geçince indirim devreye girer.
- Kampanya kuralları sunucudan JSON olarak JS’e aktarılır; saat farkı için duyuru metni 30 saniyede bir güncellenir.

### Örnek: Pizza kampanyası

- **Senaryo:** Ücret eşiği
- **Ücret eşiği:** 1000 TL
- **İndirim:** %20
- **Saat:** 16:00 – 23:59
- **Ürünler:** Tüm pizza çeşitleri
- **Görsel:** Kampanya banner’ı (WordPress medyasına yüklenir)

---

## Duyuru yönetimi

Kampanyalardan **ayrı** bir modüldür. Fiyat veya indirim uygulamaz; bilgilendirme amaçlıdır.

### Yönetim yerleri

1. **WordPress paneli:** Sol menü → **Duyurular** (`wp-admin/admin.php?page=ysf-announcements`)
2. **Hesabım:** YSF Admin → **Duyurular** sekmesi (`/hesabim/#duyuru`)

### Duyuru alanları

| Alan | Zorunlu | Açıklama |
|------|---------|----------|
| Başlık | Evet | Kart ve şerit başlığı |
| Kısa açıklama | Evet | Üst şerit ve ana sayfa kartında görünür |
| İçerik | Hayır | Ayrıntılı metin |
| Görsel | Hayır | Öne çıkan görsel |
| Başlangıç / bitiş tarihi | Evet | Yayın aralığı |
| Üst şeritte göster | Hayır | İşaretliyse duyuru şeridinde döner |

Yönetici duyuruları **düzenleyebilir** ve **kalıcı olarak silebilir**.

### Hazır duyuru görseli

Projede sosyal medya takibi için hazır banner bulunur:

```
ysf-sosyal-medya-duyuru-banner.png
```

**Önerilen duyuru metni:**

- **Başlık:** Bizi takip edin
- **Kısa açıklama:** Instagram ve Facebook hesaplarımızı takip etmeyi unutmayın. Kampanyalardan ve paylaşılacak indirim kodlarından habersiz kalmayın.

---

## Android personel uygulamaları

`android-apps/` klasöründe WebView tabanlı personel uygulaması kaynak kodu bulunur. Site URL’sini açarak garson, kasiyer ve mutfak ekranlarına hızlı erişim sağlar.

Derleme çıktıları `apk/` klasöründe tutulabilir (git’e eklenmez):

- `YSF-Garson.apk`
- `YSF-Kasiyer.apk`
- `YSF-Mutfak.apk`

---

## Masa broşürü baskı araçları

`print/` klasörü QR kodlu masa kartı broşürü üretir.

```bash
cd print
python3 build.py
```

Çıktılar `print/output/` altında PDF, PNG ve JPG olarak oluşur. Fontlar ve logo `print/assets/` içindedir.

---

## Önemli URL’ler

| Sayfa | URL |
|-------|-----|
| Ana sayfa | `/` |
| Menü | `/menu` |
| Online sipariş | `/siparis` |
| Rezervasyon | `/rezervasyon` |
| Hesabım | `/hesabim` |
| Garson | `/garson` |
| Kasiyer | `/kasiyer` |
| Mutfak ekranı | `/mutfak-ekrani` |
| wp-admin kampanyalar | `/wp-admin/admin.php?page=ysf-campaigns` |
| wp-admin duyurular | `/wp-admin/admin.php?page=ysf-announcements` |

---

## Geliştirme notları

### Ana PHP modülleri

| Dosya | Görev |
|-------|-------|
| `inc/campaigns.php` | Kampanya CRUD, indirim mantığı, wp-admin + AJAX |
| `inc/announcements.php` | Duyuru CRUD, wp-admin + AJAX |
| `inc/accounts.php` | Üyelik, giriş, adres, OTP |
| `inc/two-factor.php` | Yönetici 2FA |
| `inc/orders.php` | Sipariş işleme |
| `inc/kitchen.php` | Mutfak / KDS |
| `inc/floor.php` | Masa yönetimi |
| `inc/template-tags.php` | `ysf_get_campaigns()` vb. yardımcılar |

### Önemli şablon parçaları

| Dosya | Görev |
|-------|-------|
| `template-parts/account-campaigns.php` | Hesabım kampanya paneli |
| `template-parts/account-announcements.php` | Hesabım duyuru paneli |
| `template-parts/kitchen-desk.php` | Mutfak ürün yönetimi |

### Bilinen davranışlar

- Kampanya listesi önbellekten eski görünebilir; sayfa yenileme veya LiteSpeed purge gerekir.
- Tema zip’i (`ysffoodlab.zip`) her güncellemede yeniden oluşturulup WordPress’e manuel yüklenir.
- Canlı sitede kampanya saat aralığı dışında kart **gizlenmez** (1.4.38+); “X'te başlıyor” mesajı gösterilir.

### Tema paketleme

```bash
cd ysffoodlab-web
rm -f ysffoodlab.zip
zip -r ysffoodlab.zip ysffoodlab -x 'ysffoodlab/_preview-menu.html'
```

---

## Sürüm geçmişi (özet)

Tam liste `ysffoodlab/readme.txt` içindedir. Son sürümler:

| Sürüm | Öne çıkanlar |
|-------|--------------|
| **1.4.39** | Ayrı Duyurular modülü (wp-admin + Hesabım); başlık, açıklama, içerik, görsel |
| **1.4.38** | Saat aralığı başlamadan kampanya gizlenmez; “16:00'da başlıyor” |
| **1.4.37** | Kampanya ürün grid’i, görsel yükleme, satır düzeni |
| **1.4.36** | Adet eşiği → **ücret eşiği** (TL) |
| **1.4.35** | Kampanyaya isteğe bağlı saat aralığı; “Yarın gene bekleriz” |
| **1.4.34** | Kampanyalar Hesabım sekmesinde yönetilir |
| **1.4.33** | wp-admin Kampanyalar sayfası yönetici erişimi düzeltildi |
| **1.4.32** | Kampanya yönetim paneli; dört senaryo |
| **1.4.15–1.4.23** | Kayıt OTP, yönetici 2FA / Authenticator |
| **1.4.14** | Instagram ve Facebook ikonları |
| **1.4.0** | Giriş e-posta/telefon; e-posta doğrulamalı kayıt |
| **1.3.x** | Garson, kasiyer, mutfak ekranı, Android kasiyer |
| **1.2.x** | Üye hesapları, adres defteri, mutfak rolü, etiketler |
| **1.1.x** | Kapak slaytı, çift dil menü, mobil düzeltmeler |
| **1.0.0** | İlk sürüm |

---

## Lisans

WordPress teması **GPLv2 or later** lisansı altındadır (`ysffoodlab/readme.txt`).

---

*Son güncelleme: Eylül 2026 — tema sürümü 1.4.39*
