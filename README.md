# Spor Yay?n Takvimi (WordPress Eklentisi)

Sporekrani.com verisini ?ekip WordPress ?zerinde g?nl?k spor yay?n ak???n? ??k ve h?zl? bir ?ekilde g?stermeyi ama?layan bir eklenti.

## ??erik

- [?zellikler](#?zellikler)
- [Gereksinimler](#gereksinimler)
- [Kurulum](#kurulum)
- [Kullan?m](#kullan?m)
- [Filtre Mant??? ve Kategoriler](#filtre-mant???-ve-kategoriler)
- [?nbellek](#?nbellek)
- [Y?netim Paneli](#y?netim-paneli)
- [Veri Kayna??](#veri-kayna??)
- [Dizin Yap?s?](#dizin-yap?s?)
- [Sorun Giderme](#sorun-giderme)
- [Geli?tirme](#geli?tirme)
- [Lisans](#lisans)

## ?zellikler

- G?nl?k yay?n listesi, tek k?sa kod ile sayfada g?r?n?r.
- Bug?n ve Yar?n aras?nda AJAX ile h?zl? ge?i?.
- Kategori filtreleri ve tema rengi `#ef7123` ile modern aray?z.
- Program i?erikleri TV simgesiyle belirginle?tirilir.
- T?rk?e karakter d?n???mleri (?r. `Y?ld?z`, `Program?`) d?zeltilir.
- D?? siteye y?nlendirme yoktur; sat?ra t?klamak sayfadan ??karmaz.
- Transient API ile ?nbellek ve performans optimizasyonu.

## Gereksinimler

- WordPress 5.8+ (?nerilen)
- PHP 7.4+ (?nerilen)
- `ext-dom` ve `ext-libxml` PHP eklentileri (HTML parse i?in)
- Sunucudan `https://www.sporekrani.com` adresine ??k?? izni

## Kurulum

1. WordPress Y?netim Paneli > Eklentiler > Yeni Ekle > Eklenti Y?kle yolunu izleyin.
1. `spor-yayin-takvimi-install.zip` dosyas?n? y?kleyip etkinle?tirin.
1. Klas?r olarak kurmak isterseniz `spor-yayin-takvimi` dizinini `wp-content/plugins/` alt?na kopyalay?n.

## Kullan?m

Sayfa veya yaz? i?ine ?u k?sa kodu ekleyin:

```text
[spor_yayin_takvimi]
```

?ste?e ba?l? parametreler:

```text
[spor_yayin_takvimi date="2026-03-23" sport="Futbol"]
```

- `date`: `YYYY-MM-DD` format?nda ba?lang?? tarihi
- `sport`: Varsay?lan aktif filtre (?rn. `Futbol`)

## Filtre Mant??? ve Kategoriler

Varsay?lan kategoriler:

- Futbol ?
- Basketbol ??
- Voelybol ??
- Tenis ??

Program i?erikleri otomatik olarak `Programlar` kategorisine d??er ve TV simgesiyle g?sterilir.

Kategori filtrelerinin do?ru ?al??mas? i?in kategori adlar?n?n veri kayna??ndaki spor adlar?yla uyumlu olmas? ?nerilir.

## ?nbellek

- Veri, WordPress Transient API ile saklan?r.
- Varsay?lan s?re 3600 saniyedir.
- ?nbellek s?resi y?netim panelinden de?i?tirilebilir.
- ?T?m ?nbelle?i temizle? butonu ile tek t?kla s?f?rlanabilir.

## Y?netim Paneli

WordPress > Ayarlar > Spor Yay?n Takvimi

Ayarlar:

- ?nbellek s?resi (saniye)
- Varsay?lan spor filtresi
- Kategori listesi (her sat?ra bir kategori)
- T?m ?nbelle?i temizle

## Veri Kayna??

Veri kayna??:

- `https://www.sporekrani.com/home/day/YYYY-MM-DD`

Bu eklenti ba??ms?zd?r ve sporekrani.com ile resmi bir ba?lant?s? yoktur.

## Dizin Yap?s?

```text
spor-yayin-takvimi/
??? spor-yayin-takvimi.php
??? includes/
?   ??? scraper.php
?   ??? cache.php
?   ??? shortcode.php
?   ??? admin.php
??? assets/
    ??? css/style.css
    ??? js/script.js
```

## Sorun Giderme

- Eklenti listede g?r?nm?yorsa zip?in k?k?nde `spor-yayin-takvimi/` klas?r? olmal?d?r.
- T?rk?e karakterler bozuksa sunucu taraf?nda UTF-8 destekli PHP yap?land?rmas? oldu?undan emin olun.
- Veri gelmiyorsa sunucunun d?? ba?lant? iznini ve `wp_remote_get()` ?a?r?lar?n? kontrol edin.
- ?nbellek nedeniyle g?ncelleme gecikiyorsa y?netim panelinden ?nbelle?i temizleyin.

## Geli?tirme

Paket olu?turmak i?in klas?r yap?s?n?n korunmas? gerekir. ?rnek PowerShell komutu:

```powershell
python - <<'PY'
import os, zipfile
base = r"C:\\path\\to\\spor-yayin-takvimi"
zip_path = r"C:\\path\\to\\spor-yayin-takvimi-install.zip"
with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(base):
        for name in files:
            full = os.path.join(root, name)
            rel = os.path.relpath(full, os.path.dirname(base)).replace('\\\\', '/')
            zf.write(full, rel)
PY
```

## Lisans

GPL-2.0+
