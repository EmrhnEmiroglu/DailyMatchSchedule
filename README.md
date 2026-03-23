# ⚽ Sporkulis Yayın Takvimi

> WordPress için günlük spor yayın takvimi eklentisi.  

## Ekran Görüntüleri

<p align="center">
  <img src="https://github.com/user-attachments/assets/d811bc1d-6e8e-483a-b908-87505913cfc7" alt="Admin Paneli" width="48%" />
  <img src="https://github.com/user-attachments/assets/f35e0305-ce62-4fa4-badf-1a93f1da1bc5" alt="Tablo Görünümü" width="48%" />
</p>

---

## İçindekiler

- [Özellikler](#özellikler)
- [Gereksinimler](#gereksinimler)
- [Kurulum](#kurulum)
- [Kullanım](#kullanım)
- [Filtreler ve Kategoriler](#filtreler-ve-kategoriler)
- [Önbellek](#önbellek)
- [Yönetim Paneli](#yönetim-paneli)
- [Dizin Yapısı](#dizin-yapısı)
- [Sorun Giderme](#sorun-giderme)
- [Geliştirme](#geliştirme)
- [Lisans](#lisans)

---

## Özellikler

**Tarih ve Navigasyon**
- 📅 **Bugün / Yarın butonu** — iki buton arasında AJAX ile sayfa yenilemesiz geçiş
- 🟠 **Aktif buton vurgusu** — seçili tarih `#ef7123` turuncu ile belirtilir

**Kategori Filtreleri**
- 🔘 **Tek tıkla filtreleme** — Tümü, Futbol, Basketbol, Voleybol, Tenis, Programlar
- ✅ **Aktif filtre vurgusu** — seçili kategori turuncu dolgu ile öne çıkar
- 📺 **Otomatik Program kategorisi** — TV programları ayrı kategoriye alınır, 📺 simgesiyle gösterilir
- ⚽🏀🏐🎾 **Spor simgeleri** — her kategori butonunda ilgili emoji

**Maç Listesi**
- 🕐 **Saat sütunu** — turuncu saat ikonu ve altında `HH:MM` formatında zaman
- 🏷️ **Spor simgesi** — her satırda maçın spor dalına ait ikon
- 📋 **Maç adı + lig** — büyük maç adı, altında küçük lig/turnuva bilgisi
- 📡 **Kanal badge'leri** — yayınlayan kanal(lar) soluk gri etiket olarak listelenir
- ▶ **Satır oku** — her satırın sağında yönlendirme ikonu; dış siteye **çıkmaz**
- 🎨 **Açık turuncu arka plan** — saat sütunu hafif renkli bantla ayrışır

**Teknik**
- 🗄️ **Transient önbellek** — gereksiz ağ istekleri engellenir, sayfa hızı korunur
- 🔤 **Türkçe karakter düzeltmesi** — `Yıldız`, `Programı` gibi bozuk karakterler otomatik düzeltilir

---

## Gereksinimler

| Bileşen | Minimum Sürüm |
|---|---|
| WordPress | 5.8+ |
| PHP | 7.4+ |
| PHP eklentisi | `ext-dom`, `ext-libxml` |
| Sunucu | Dış kaynaklara `wp_remote_get()` ile erişim izni |

---

## Kurulum

**ZIP ile (önerilen):**

1. **Eklentiler → Yeni Ekle → Eklenti Yükle** yolunu izleyin
2. `spor-yayin-takvimi-install.zip` dosyasını yükleyin
3. **Etkinleştir** butonuna tıklayın

**Manuel:**

```bash
# Repoyu klonlayın veya ZIP'i açın
cp -r spor-yayin-takvimi /wp-content/plugins/
```

Ardından WordPress yönetim panelinden eklentiyi etkinleştirin.

---

## Kullanım

Herhangi bir sayfa veya yazıya aşağıdaki shortcode'u ekleyin:

```
[spor_yayin_takvimi]
```

**Parametreler:**

```
[spor_yayin_takvimi date="2026-03-23" sport="Futbol"]
```

| Parametre | Varsayılan | Açıklama |
|---|---|---|
| `date` | Bugünün tarihi | `YYYY-MM-DD` formatında başlangıç tarihi |
| `sport` | *(boş — tümü)* | Sayfa açıldığında varsayılan aktif filtre |

---

## Filtreler ve Kategoriler

Arayüzde varsayılan olarak görünen kategoriler:

| Simge | Kategori |
|---|---|
| ⚽ | Futbol |
| 🏀 | Basketbol |
| 🏐 | Voleybol |
| 🎾 | Tenis |
| 📺 | Programlar *(otomatik)* |

> **Not:** Kategori adları veri kaynağındaki spor adlarıyla birebir eşleşmelidir. Yönetim panelinden kategori listesi düzenlenebilir.

---

## Önbellek

Eklenti, WordPress **Transient API** üzerinden önbellek yönetimi yapar.

- Varsayılan süre: **3600 saniye (1 saat)**
- Yönetim panelinden süre değiştirilebilir
- **"Tüm önbelleği temizle"** butonu ile anlık sıfırlama yapılabilir

---

## Yönetim Paneli

**WordPress → Ayarlar → Sporkulis Yayın Takvimi**

| Ayar | Açıklama |
|---|---|
| Önbellek süresi | Saniye cinsinden saklanma süresi |
| Varsayılan filtre | Sayfa ilk açıldığında aktif spor dalı |
| Kategori listesi | Her satıra bir kategori; filtrelerde gösterilir |
| Önbelleği temizle | Tüm tarihler için önbelleği sıfırlar |

---

## Dizin Yapısı

```
spor-yayin-takvimi/
├── spor-yayin-takvimi.php   ← Ana dosya: header, hook'lar, AJAX
├── includes/
│   ├── scraper.php          ← HTML çekme ve parse etme (DOMDocument)
│   ├── cache.php            ← Transient okuma / yazma / silme
│   ├── shortcode.php        ← [spor_yayin_takvimi] render
│   └── admin.php            ← Yönetim paneli ve ayarlar
└── assets/
    ├── css/style.css        ← Arayüz stilleri
    └── js/script.js         ← AJAX tarih geçişi, spor filtresi
```

---

## Sorun Giderme

**Eklenti listede görünmüyor:**  
ZIP içinde en dışta `spor-yayin-takvimi/` klasörü olmalıdır. Dosyalar doğrudan ZIP kökünde olmamalıdır.

**Türkçe karakterler bozuk görünüyor:**  
Sunucunuzun PHP yapılandırmasında `default_charset = UTF-8` ayarlı olduğundan emin olun.

**Veri gelmiyor / boş tablo:**  
Sunucunun dış bağlantıya izin verdiğini kontrol edin. Bazı hosting sağlayıcılar `wp_remote_get()` ile yapılan dış istekleri engeller.

**Güncelleme gecikiyor:**  
Yönetim panelinden **"Tüm önbelleği temizle"** butonuna tıklayın; sonraki sayfa yüklemesinde veri yeniden çekilecektir.

---

## Geliştirme

Kurulum ZIP'ini oluşturmak için proje kökünde aşağıdaki Python betiğini çalıştırabilirsiniz:

```python
import os, zipfile

base     = r"C:\path\to\spor-yayin-takvimi"
zip_path = r"C:\path\to\spor-yayin-takvimi-install.zip"

with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(base):
        for name in files:
            full = os.path.join(root, name)
            rel  = os.path.relpath(full, os.path.dirname(base)).replace('\\', '/')
            zf.write(full, rel)

print("ZIP oluşturuldu:", zip_path)
```

---

## Lisans

[GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html)
