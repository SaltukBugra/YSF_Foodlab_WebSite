=== YSF Food Lab ===

Contributors: ysffoodlab
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

YSF Food Lab icin gelistirilmis restoran temasi. Turkce / Ingilizce cift dilli,
online siparis ve rezervasyon sistemleri temanin icinde gelir; ek eklenti
gerekmez.

== Kurulum ==

1. WordPress panelinde Gorunum > Temalar > Yeni Ekle > Tema Yukle yolunu izleyin.
2. ysffoodlab.zip dosyasini secip yukleyin ve Etkinlestir'e basin.
3. Ust kisimda cikan bildirimden veya Gorunum > YSF Kurulum sayfasindan
   "Kurulumu Baslat" butonuna basin. Sayfalar, menuler ve ornek icerik olusur.
4. Gorunum > Ozelestir > YSF Food Lab Ayarlari bolumunden telefon, WhatsApp,
   adres, calisma saatleri ve sipariss ayarlarini girin.
5. Ayarlar > Kalici Baglantilar sayfasini bir kez kaydedin.

== Icerik yonetimi ==

* Menu Yonetimi: her urun icin fiyat, indirimli fiyat, Ingilizce ad ve
  aciklama, vegan / vejetaryen / aci / glutensiz etiketleri, alerjen listesi,
  kalori, hazirlanma suresi ve "online siparise acik" secenegi bulunur.
* Kampanya & Duyuru: baslangic ve bitis tarihi verilir; bitis tarihi gecen
  kayitlar sitede otomatik olarak gizlenir. "Ust duyuru seridinde goster"
  secenegi isaretli olanlar sayfanin en ustundeki seritte doner.
* Rezervasyonlar ve Siparisler: formdan gelen her talep panele kaydedilir,
  yoneticiye e-posta gider ve durumu Bekliyor / Onaylandi / Tamamlandi /
  Iptal olarak guncellenebilir.
* Masa servisi: /garson adresi garson telefonu icindir, /kasiyer kasa
  telefonu icindir, /mutfak-ekrani mutfak tableti icindir. Yonetici,
  Kullanicilar ekranindan personele Garson, Kasiyer veya Mutfak Sorumlusu
  rolunu verir. Kasiyer sekmesi yalnizca kasiyer ve yonetici hesaplarinda
  gorunur. Masa sayisi Ozellestir > Masa servisi bolumunden ayarlanir.

== Cift dil ==

Dil, ust bardaki TR / EN dugmesiyle degisir (?lang=en). Ingilizce alanlar bos
birakilirsa o icerikte Turkce metin gosterilir, yani site hicbir zaman bos
kalmaz. Ceviri alanlari: yazi/sayfa icin "Ingilizce Surum (EN)" kutusu, menu
urunleri ve kampanyalar icin kendi detay kutulari, menu kategorileri icin
kategori duzenleme ekranindaki "Ingilizce kategori adi" alani.

== Online siparis ==

Sepet tarayicida saklanir, siparis gonderildiginde tum fiyatlar sunucuda
veritabanindaki guncel degerlerle yeniden hesaplanir. Siparis panele kaydedilir,
e-posta gonderilir ve (ayardan kapatilabilir) musterinin WhatsApp uygulamasi
siparis ozeti ile acilir. Odeme alt yapisi icermez; onay telefonla yapilir.

== Performans ==

* jQuery kullanilmaz, tek CSS ve tek JS dosyasi yuklenir.
* LiteSpeed Cache ile uyumludur: dil secimi onbellek varyasyonu olarak
  bildirilir, form sayfalari onbellege alinmaz.
* Google Fonts kullanimi Ozelestir > Gelismis bolumunden kapatilabilir.

== Degisiklik gecmisi ==

= 1.4.39 =
* WordPress panelinde ve Hesabimda ayri Duyurular bolumu. Baslik, kisa aciklama, icerik, gorsel; yonetici silebilir.

= 1.4.38 =
* Saat araligi baslamadan once kampanya gizlenmez; "16:00'da basliyor" olarak gorunur.

= 1.4.37 =
* Kampanya urun listesi duzenli kart izgarasina alindi. Kampanya satirlarindaki yazi acildi. Kampanyaya gorsel eklenebilir.

= 1.4.36 =
* Adet esigi ucret esigine donustu. Secili urunlerin sepet tutari bu degeri gecince indirim uygulanir. Kampanya yazilarinin sikisik durusu acildi.

= 1.4.35 =
* Kampanyaya istege bagli saat araligi eklendi. Aralık bitince kampanya ertesi gune sarkiyorsa duyuruda "Yarin gene bekleriz" gorunur.

= 1.4.34 =
* Kampanyalar Hesabim sayfasinda da yonetilir. Sekme yalniz site yoneticisine aciktir.

= 1.4.33 =
* Kampanyalar sayfasi yonetici hesabina yeniden acildi.

= 1.4.32 =
* Yonetim panelinde Kampanyalar bolumu. Dort senaryo, tarih araligi, duyuru seridi ve ana sayfa. Suresi dolan kampanya "Suresi doldu" olarak kalir ve yalniz yonetici silebilir.

= 1.4.31 =
* Ebat girisi fiyat alaninin hemen altina alindi. Mutfak urun formunda da ebat eklenebilir.

= 1.4.30 =
* Urunlere ebat eklenebilir. Menude tek satir bilgi, online sipariste secim olarak gorunur.

= 1.4.29 =
* Online siparis ayni menu icerigini eski satir gorunumunde gosterir. S kivrimli duzen menude kalir.

= 1.4.28 =
* Menu kartlarindaki yan ve alt bosluk kalkti. Kategori basliklarina koyu zemin eklendi.

= 1.4.27 =
* Menu fotograflari ust uste ezilmeden, ortadaki S kivriminda ic ice gecmeli dizilir.

= 1.4.26 =
* Menu urunleri ortada S seklinde dalgalanan, ic ice binen tek serit halinde dizilir.

= 1.4.25 =
* Menu sayfasinda sepete ekle yok; urunler ic ice gommeli fotograflar. Fiyat, stok ve olumlu/olumsuz etiketler ayni.

= 1.4.24 =
* Menu urunleri genis fotografli kartlara donustu; gorseller alta dogru yumusakca solar. Fiyat, stok ve urun bilgisi ayni kalir.

= 1.4.23 =
* Authenticator karekodu yalnizca e-posta kodu dogrulandiktan sonra gosterilir. Yanlis kod veya sifre Hesabim girisine dondurur.

= 1.4.22 =
* Yonetici girisinde Authenticator karekodu ve elle anahtar her zaman gosterilir (hesapta 2FA kaydi olsa bile).

= 1.4.21 =
* Yonetici 2FA paneli giriste tekrar acilir; karekod ayri istekte gelir, elle anahtar JSON icinde kalir.

= 1.4.20 =
* Authenticator karekodu sitede uretilir; elle girilecek anahtar karekodun altinda buyuk yazilir.

= 1.4.19 =
* Yonetici girisinde Authenticator karekodu ve kod alani gorunmuyordu; panel zorla acilir, karekod yedek kaynaktan gelir.

= 1.4.18 =
* Kayitta telefon OTP/SMS dogrulamasi kaldirildi; uyelik e-posta kodu ile tamamlanir.

= 1.4.17 =
* Kayit OTP'si SMS yoksa e-postaya gider; kod ekranda gosterilmez.

= 1.4.16 =
* Yonetici sifresi dogruysa yalnizca Authenticator karekod ekrani acilir.
* Kayit, telefona SMS gitmeden tamamlanmaz; ekranda kod gosterilmez.

= 1.4.15 =
* Kayit OTP: 6 haneli kod telefona SMS (Netgsm) ve e-postaya gider; hesap kod dogrulanmadan acilmaz.

= 1.4.14 =
* Instagram ve Facebook ana sayfada ve yüzen WhatsApp butonunun üstünde.

= 1.4.13 =
* Instagram @ysffoodlab ve Facebook YSF Foodlab ikonları üst bar, iletişim ve alt bilgide.

= 1.4.12 =
* Yönetici girişi artık şifreyle tamamlanmaz: authenticator kodu zorunlu. Kurulu değilse girişte karekod çıkar; mevcut oturum kurulum bitene kadar kilitlenir.

= 1.4.11 =
* Yönetici hesabına Google Authenticator uyumlu iki adımlı doğrulama. Personel ve üyeler etkilenmez.

= 1.4.10 =
* Logo varken yanındaki YSF Foodlab yazısı kaldırıldı.

= 1.4.9 =
* SMTP kimliği info@ysffoodlab.com.tr kutusuna çekilir; kayıt mail gitmese de ekrandaki kodla tamamlanır.

= 1.4.8 =
* Veridyen SMTP: port 465/SSL varsayılan, kimlik doğrulama LOGIN, alternatif sunucular denenir.

= 1.4.7 =
* SMTP mail ayarları Özelleştir > YSF Food Lab Ayarları > İşletme Bilgileri içine alındı.

= 1.4.6 =
* Kayıt hatası artık gerçek nedeni gösterir; tarayıcı otomatik doldurma bot tuzağını tetiklemez.

= 1.4.5 =
* Form hataları “çok fazla deneme” kilidini doldurmaz; kilit 15 dakikada açılır.

= 1.4.4 =
* Üye ol formunda adres otomatik doldurma “zorunlu alan” hatasını tetiklemez; eksik alan adı gösterilir.

= 1.4.3 =
* Kayıtta e-posta kullanıcı adı alanına yazılsa da kabul edilir; @ öncesi giriş adı olur.

= 1.4.2 =
* Giden e-posta adresi info@ysffoodlab.com.tr (gerçek MX kaydı). SMTP ayarları eklendi.

= 1.4.1 =
* Giden e-postalar info@ysffoodlab.com adresinden gönderilir.

= 1.4.0 =
* Giriş kullanıcı adı, e-posta veya telefonla yapılabilir.
* Üyelik e-posta doğrulama kodu ile tamamlanır.
* Şifremi unuttum bağlantısı Hesabım sayfasında yeni şifre belirletir.

= 1.3.9 =
* Kasiyer sekmesi yalnızca kasiyer ve yönetici hesaplarında görünür.
* Kasiyer penceresi (/kasiyer): açık masa hesapları, nakit/kart tahsilat, günlük kasa.
* Android YSF Kasiyer uygulaması eklendi.

= 1.3.2 =
* Personel ekranlarında sekme yok. Mutfak siparişleri sırayla kuyruğa düşer.
* Garson masaları dolu/boş listeler; dolu masada önceki siparişler, Ekle ile yeni sipariş.

= 1.3.1 =
* Hesabım ve personel ekranlarında Mutfak / Garson sekmeleri.
* Garson sayfası (/garson) ve mutfak ekranı (/mutfak-ekrani) tema açılınca oluşur.

= 1.3.0 =
* Garson uygulaması (mobil): masadan sipariş alma, mutfağa gönderme, masayı kapatma.
* Mutfak ekranı: masa (ve isteğe bağlı online) biletlerini canlı kolonlarda gösterir.
* Her iki uygulama da tarayıcıdan ana ekrana eklenebilir. Garson rolü Kullanıcılar’dan atanır.
* Masa sayısı Görünüm > Özelleştir > Masa servisi bölümünden ayarlanır.

= 1.2.11 =
* Sepete ekleme yalnizca Online Siparis sayfasinda; Menü sayfasi sadece listeler.

= 1.2.10 =
* Header yazisi Great Vibes yerine Cinzel (Roman, iddiali) fonta gecildi.

= 1.2.9 =
* Header yazisi el yazisi font ve logo ile ayni siyah zeminde,
  markanin devamı gibi gorunur.

= 1.2.8 =
* Header logosunun yanina buyuk harflerle YSF Foodlab yazisi eklendi.

= 1.2.7 =
* Header logosu buyutuldu; ozelestirici kirpma alani kare (200x200)
  oldu, boylece kare logo yatay serite sigmayip kesilmiyor.

= 1.2.6 =
* Urun adi yanindaki etiketler siniflandi: bilgi (koyu), olumlu (yesil),
  olumsuz (kirmizi), kampanya (sari). Sef ve yonetici birden fazla etiket ekler.

= 1.2.5 =
* Giris, kayit ve profil sifre alanlarinda Goster / Gizle dugmesi.

= 1.2.4 =
* Mutfak Sorumlusu rolu eklendi. Yetkili, siparis ekranina degil Hesabim
  sayfasindaki menu kontrolune girer: stokta yok isareti, yeni urun ekleme
  ve urunu menuden kaldirma. Furkan Sef hesabi tema yuklenince olusur.

= 1.2.3 =
* Giris yapmis uyede ust seritte Hesabim yerine uyenin adi yazilir.

= 1.2.2 =
* Hesabim kisi simgesi masaustu menude header'i dolduran dev bir siluete
  donusuyordu. Simge artik kucuk kalir; masaustunde yalnizca ust seritte,
  mobilde acilir menunun altinda gorunur.

= 1.2.1 =
* Kayit islemi uye olusturulduktan sonra e-posta/eklenti hatasinda 500 donuyordu.
  Uyelik ve oturum yine aciliyordu ama sayfa hata gosteriyordu. Bildirim
  e-postalari artik yaniti bozmuyor; kayit sonrasi yonlendirme calisiyor.

= 1.2.0 =
* Uye hesaplari eklendi. Hesabim sayfasindan giris, kayit, sifre sifirlama,
  profil guncelleme ve adres defteri yonetilir. Kayitta ad soyad, e-posta,
  telefon ve sifre alinir; e-posta ile telefon numarasi birden fazla uyede
  kullanilamaz. Girise e-posta ya da telefon numarasiyla izin verilir.
* Ev ve is olmak uzere iki adres kaydedilebilir. Adres girmek zorunlu degildir,
  fakat doldurulursa il, ilce, mahalle, cadde/sokak ve bina no zorunlu olur.
  Il ve ilce, temayla gelen 81 il / 972 ilce listesinden secilir; ilce listesi
  secilen ile gore kurulur ve sunucu tarafinda da dogrulanir. Posta kodu
  istege bagli, girildiginde 5 hane olmak zorundadir.
* Telefon numaralari +90XXXXXXXXXX bicimine cevrilerek saklanir; 0552...,
  +90 552..., 90552... ve 0090... gibi yazimlarin hepsi kabul edilir.
* Siparis ve rezervasyon formlari uyenin ad, telefon ve e-postasiyla otomatik
  dolar; teslimat adresi kayitli adreslerden tek dokunusla secilebilir. Uye
  olmayan ziyaretciler eskisi gibi form doldurmaya devam eder.
* Uyeler kendi siparis ve rezervasyon kayitlarini Hesabim sayfasinda gorur.
* Musteri hesaplari yonetim paneline giremez, yonetim cubugu gosterilmez;
  panele yonelen istekler Hesabim sayfasina dondurulur.
* Yeni uye kaydi Ozelestir > Gelismis bolumunden kapatilabilir.

= 1.1.3 =
* Cerez uyarisinin her sayfada yeniden cikmasi giderildi. Cookie Admin Pro
  eklentisi onayi yalnizca admin-ajax kaydi basarili donerse tarayiciya
  yaziyor; sunucudaki kayit "Error saving consent data." hatasi verdigi icin
  onay hic saklanmiyordu. Tema, eklentinin kaydetme fonksiyonunu sarmalayip
  onayi cereze de yazdiriyor. Kalici cozum icin Cookie Admin Pro eklentisi
  devre disi birakilabilir.
* Kapak slaytindaki nokta butonlarinda aria-current yalnizca aktif slaytta
  birakiliyor.

= 1.1.2 =
* Mobil menu duzeltildi. Baslikta kullanilan backdrop-filter, icindeki
  position:fixed menuye kapsayici blok olusturuyordu; bu nedenle acilan menu
  ekran yuksekligi yerine basligin yuksekligine sikisiyor, baglantilar yarim
  gorunuyor ve menunun icinde elle kaydirma gerekiyordu. Dar ekranda
  backdrop-filter kapatildi, menuye ekran yuksekligi (100dvh) verildi ve ust
  bosluk baslik yuksekligine gore ayarlandi.

= 1.1.1 =
* Ust menu artik Ingilizce'ye ceviriliyor. Menu ogesinin etiketi, bagli
  sayfanin Turkce basligiyla ayniysa sayfanin _ysf_title_en degeri kullanilir;
  menude elle yazilmis ozel etiketlere dokunulmaz.
* Menuden gezinirken dil secimi korunuyor (site ici baglantilara lang=en
  eklenir, dis baglantilara eklenmez).
* Mobilde ust seritteki duyuru metni kirpilmiyor: dar ekranda telefon
  numarasi ve "Duyurular" etiketi gizlenip metne tam genislik veriliyor.
  Telefon zaten alt eylem cubugunda ve yuzen butonda mevcut.
* Menu sayfasindaki kategori dugmeleri mobilde yana kaydirma yerine alt
  satira geciyor; tum kategoriler tek bakista gorunur.
* Mobil menu acikken kapatma (X) dugmesi cekmecenin altinda kalmiyor.

= 1.1.0 =
* Ana sayfaya kapak slayti eklendi: gorseller yumusak gecisle degisir ve
  yavasca yakinlasir. Ozelestir bolumunden 4 gorsele kadar secilebilir.
* Sayfalar arasi gecis eklendi (View Transitions): ust bar, baslik ve alt
  bilgi yerinde kalirken icerik kayarak degisir.
* Bolumler ve kartlar sayfa kaydirildikca sirayla belirir.
* Menu kategorileri arasinda gecis yumusatildi; urunler sirayla gorunur.
* Temayla birlikte 20 hazir yemek fotografi gelir; kurulum sihirbazi bu
  fotograflari medya kutuphanesine aktarip ornek urunlere baglar.
* Fotografi olmayan urunler icin yedek gorsel eklendi.
* Ornek menu kahvalti, aperatif, hamur isleri, pizza, makarna, fast food,
  tatli ve kahve kategorileriyle yeniden duzenlendi.
* Tum hareketler "azaltilmis hareket" tercihine saygi duyar.

= 1.0.0 =
* Ilk surum.
