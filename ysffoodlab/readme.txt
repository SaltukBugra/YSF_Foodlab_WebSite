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
