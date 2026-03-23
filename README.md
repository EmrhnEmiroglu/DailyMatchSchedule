# Spor Yayın Takvimi (WordPress Eklentisi)

Sporekrani.com verisini çekip WordPress üzerinde günlük spor yayın akışını şık ve hızlı bir şekilde göstermeyi amaçlayan bir eklenti.

## İçerik

- [Özellikler](#özellikler)
- [Gereksinimler](#gereksinimler)
- [Kurulum](#kurulum)
- [Kullanım](#kullanım)
- [Filtre Mantığı ve Kategoriler](#filtre-mantığı-ve-kategoriler)
- [Önbellek](#önbellek)
- [Yönetim Paneli](#yönetim-paneli)
- [Veri Kaynağı](#veri-kaynağı)
- [Dizin Yapısı](#dizin-yapısı)
- [Sorun Giderme](#sorun-giderme)
- [Geliştirme](#geliştirme)
- [Lisans](#lisans)

## Özellikler

- Günlük yayın listesi, tek kısa kod ile sayfada görünür.
- Bugün ve Yarın arasında AJAX ile hızlı geçiş.
- Kategori filtreleri ve tema rengi `#ef7123` ile modern arayüz.
- Program içerikleri TV simgesiyle belirginleştirilir.
- Türkçe karakter dönüşümleri (ör. `Yıldız`, `Programı`) düzeltilir.
- Dış siteye yönlendirme yoktur; satıra tıklamak sayfadan çıkarmaz.
- Transient API ile önbellek ve performans optimizasyonu.

## Gereksinimler

- WordPress 5.8+ (önerilen)
- PHP 7.4+ (önerilen)
- `ext-dom` ve `ext-libxml` PHP eklentileri (HTML parse için)
- Sunucudan `https://www.sporekrani.com` adresine çıkış izni

## Kurulum

1. WordPress Yönetim Paneli > Eklentiler > Yeni Ekle > Eklenti Yükle yolunu izleyin.
1. `spor-yayin-takvimi-install.zip` dosyasını yükleyip etkinleştirin.
1. Klasör olarak kurmak isterseniz `spor-yayin-takvimi` dizinini `wp-content/plugins/` altına kopyalayın.

## Kullanım

Sayfa veya yazı içine şu kısa kodu ekleyin:

```text
[spor_yayin_takvimi]
```

İsteğe bağlı parametreler:

```text
[spor_yayin_takvimi date="2026-03-23" sport="Futbol"]
```

- `date`: `YYYY-MM-DD` formatında başlangıç tarihi
- `sport`: Varsayılan aktif filtre (örn. `Futbol`)

## Filtre Mantığı ve Kategoriler

Varsayılan kategoriler:

- Futbol ⚽
- Basketbol 🏀
- Voelybol 🏐
- Tenis 🎾

Program içerikleri otomatik olarak `Programlar` kategorisine düşer ve TV simgesiyle gösterilir.

Kategori filtrelerinin doğru çalışması için kategori adlarının veri kaynağındaki spor adlarıyla uyumlu olması önerilir.

## Önbellek

- Veri, WordPress Transient API ile saklanır.
- Varsayılan süre 3600 saniyedir.
- Önbellek süresi yönetim panelinden değiştirilebilir.
- “Tüm önbelleği temizle” butonu ile tek tıkla sıfırlanabilir.

## Yönetim Paneli

WordPress > Ayarlar > Spor Yayın Takvimi

Ayarlar:

- Önbellek süresi (saniye)
- Varsayılan spor filtresi
- Kategori listesi (her satıra bir kategori)
- Tüm önbelleği temizle

## Veri Kaynağı

Veri kaynağı:

- `https://www.sporekrani.com/home/day/YYYY-MM-DD`

Bu eklenti bağımsızdır ve sporekrani.com ile resmi bir bağlantısı yoktur.

## Dizin Yapısı

```text
spor-yayin-takvimi/
├── spor-yayin-takvimi.php
├── includes/
│   ├── scraper.php
│   ├── cache.php
│   ├── shortcode.php
│   └── admin.php
└── assets/
    ├── css/style.css
    └── js/script.js
```

## Sorun Giderme

- Eklenti listede görünmüyorsa zip’in kökünde `spor-yayin-takvimi/` klasörü olmalıdır.
- Türkçe karakterler bozuksa sunucu tarafında UTF-8 destekli PHP yapılandırması olduğundan emin olun.
- Veri gelmiyorsa sunucunun dış bağlantı iznini ve `wp_remote_get()` çağrılarını kontrol edin.
- Önbellek nedeniyle güncelleme gecikiyorsa yönetim panelinden önbelleği temizleyin.

## Geliştirme

Paket oluşturmak için klasör yapısının korunması gerekir. Örnek PowerShell komutu:

```powershell
python - <<'PY'
import os, zipfile
base = r"C:\path\to\spor-yayin-takvimi"
zip_path = r"C:\path\to\spor-yayin-takvimi-install.zip"
with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(base):
        for name in files:
            full = os.path.join(root, name)
            rel = os.path.relpath(full, os.path.dirname(base)).replace('\\', '/')
            zf.write(full, rel)
PY
```

## Lisans

GPL-2.0+
