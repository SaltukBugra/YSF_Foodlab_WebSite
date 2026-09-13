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
