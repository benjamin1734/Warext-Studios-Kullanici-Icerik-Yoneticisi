# Warext Studios | XenForo User Content Manager

## Türkçe

XenForo 2.3 için kullanıcıların oluşturduğu içerikleri kategori ve içerik türüne göre görüntülemek, filtrelemek ve yetkiye bağlı olarak toplu yönetmek amacıyla geliştirilen moderasyon eklentisi.

## Sürüm

`1.0.5`

## Doğrudan XenForo Kurulumu

**Kurulum ZIP'i:** [WarextStudios-UserContentManager-1.0.5.zip](releases/WarextStudios-UserContentManager-1.0.5.zip?raw=1)

ZIP doğrudan XenForo Admin CP > Add-ons > Install/upgrade from archive alanına yüklenir.

## Kullanım

- Forum kullanıcı profili > Moderator tools > İçerikleri Yönet
- Admin CP > Users > Kullanıcı İçerik Yöneticisi
- Admin CP > Users > kullanıcıyı düzenle > Actions > İçerikleri Yönet

Super admin hesapları Warext UCM izinlerine otomatik olarak sahiptir. Diğer moderatör ve yöneticiler için ilgili `warextUcm` izinleri ayrıca tanımlanmalıdır.

## Gereksinimler

- XenForo 2.3.0+
- PHP 8.1+
- PHP 8.4 uyumluluğu
- XenForo Resource Manager isteğe bağlıdır

## Özellikler

- Kullanıcı içerik yönetim merkezi
- XenForo default arayüz bileşenleri
- Konu kategori gruplaması ve gelişmiş filtreleme
- Tekli, sayfa, kategori ve tüm filtre sonucu seçimi
- Toplu taşıma, silme, kalıcı silme, geri getirme, kilit ve sabitleme
- Toplu onay, önek ve başlık düzenleme
- Kalıcı silme için ayrı yetki ve açık onay
- İçerik bazında XenForo native permission yeniden doğrulaması
- İşlem geçmişi ve XenForo moderator log entegrasyonu
- Birden fazla konuya dayalı tek kullanıcı uyarısı
- XenForo Resource Manager opsiyonel entegrasyonu
- 500 üzeri işlemlerde XenForo Job sistemi
- Cursor tabanlı batch işleme
- Operation bazlı concurrency kilidi ve idempotent ilerleme

## Eklenti kimliği

`WarextStudios/UserContentManager`

## Destek

Sorularınız, hata bildirimleriniz, kurulum desteği ve Warext Studios XenForo eklentileriyle ilgili yardım için destek Discord sunucumuza katılabilirsiniz:

**Discord:** https://discord.gg/tgsV5XMcFS

---

## English

Warext Studios User Content Manager is a XenForo 2.3 moderation add-on for viewing, filtering, and managing user-created content by content type and category, including permission-controlled bulk actions.

## Version

`1.0.5`

## Direct XenForo installation

**Installation ZIP:** [WarextStudios-UserContentManager-1.0.5.zip](releases/WarextStudios-UserContentManager-1.0.5.zip?raw=1)

Upload the ZIP directly through XenForo Admin CP > Add-ons > Install/upgrade from archive.

## Usage

- Forum user profile > Moderator tools > Manage Content
- Admin CP > Users > User Content Manager
- Admin CP > Users > edit user > Actions > Manage Content

Super admin accounts automatically have Warext UCM permissions. Other moderators and administrators must be granted the relevant `warextUcm` permissions separately.

## Requirements

- XenForo 2.3.0+
- PHP 8.1+
- PHP 8.4 compatible
- XenForo Resource Manager is optional

## Features

- Central user-content management interface
- XenForo-native/default UI components
- Thread category grouping and advanced filtering
- Selection of individual items, the current page, a category, or the entire filtered result set
- Bulk move, soft delete, permanent delete, restore, lock, and sticky operations
- Bulk approval, prefix editing, and title editing
- Separate permission and explicit confirmation for permanent deletion
- XenForo native permission revalidation per content item
- Operation history and XenForo moderator-log integration
- One user warning based on multiple selected threads
- Optional XenForo Resource Manager integration
- XenForo Job system for operations above 500 items
- Cursor-based batch processing
- Operation-based concurrency locking and idempotent progress

## Add-on ID

`WarextStudios/UserContentManager`

## Support

For questions, bug reports, installation support, and help with Warext Studios XenForo add-ons, you can join our support Discord server:

**Discord:** https://discord.gg/tgsV5XMcFS
