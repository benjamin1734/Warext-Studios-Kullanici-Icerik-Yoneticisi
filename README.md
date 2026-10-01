# Warext Studios | XenForo User Content Manager

## English

Warext Studios User Content Manager is a XenForo 2.3 moderation add-on for viewing, filtering, and managing user-created content by content type and category, including permission-controlled bulk actions.

## Direct XenForo installation

**Installation ZIP:** [WarextStudios-UserContentManager-1.0.5.zip](releases/WarextStudios-UserContentManager-1.0.5.zip?raw=1)

Upload the ZIP directly through XenForo Admin CP > Add-ons > Install/upgrade from archive.

## Usage

- Forum user profile > Moderator tools > Manage Content
- Admin CP > User Content Manager > User Content Manager
- Select a user from the dedicated UCM admin page, then open their content manager

The add-on has its own Admin CP category and dedicated admin permissions: `warextUcmView`, `warextUcmBulk`, and `warextUcmHardDelete`. Moderator/user-group permissions under `warextUcm` remain supported. Native XenForo content permissions are still revalidated for every action.

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

---

## Türkçe

XenForo 2.3 için kullanıcıların oluşturduğu içerikleri kategori ve içerik türüne göre görüntülemek, filtrelemek ve yetkiye bağlı olarak toplu yönetmek amacıyla geliştirilen moderasyon eklentisi.

## Doğrudan XenForo Kurulumu

**Kurulum ZIP'i:** [WarextStudios-UserContentManager-1.0.5.zip](releases/WarextStudios-UserContentManager-1.0.5.zip?raw=1)

ZIP doğrudan XenForo Admin CP > Add-ons > Install/upgrade from archive alanına yüklenir.

## Kullanım

- Forum kullanıcı profili > Moderator tools > İçerikleri Yönet
- Admin CP > Kullanıcı İçerik Yöneticisi > Kullanıcı İçerik Yöneticisi
- Ayrı UCM yönetim sayfasından kullanıcı seçip içerik yöneticisini açabilirsiniz

Eklentinin kendi Admin CP kategorisi ve ayrı yönetici izinleri vardır: `warextUcmView`, `warextUcmBulk` ve `warextUcmHardDelete`. `warextUcm` altındaki moderatör/kullanıcı grubu izinleri de desteklenmeye devam eder. Her işlemde XenForo'nun yerel içerik yetkileri ayrıca yeniden doğrulanır.

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

## Language support / Dil desteği

Version 1.1.1 includes the dedicated Admin CP category, granular UCM admin permissions, and Turkish/English language coverage for the new admin interface. See `LANGUAGE.md`.
