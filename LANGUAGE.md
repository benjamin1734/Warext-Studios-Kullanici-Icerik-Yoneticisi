# Language support / Dil desteği

The add-on now uses XenForo's native phrase/language system for user-facing interface text.

- `languages/Turkish.xml`
- `languages/English.xml`

Import the matching XML into an existing XenForo language (or a child language based on it). Users then switch language through XenForo's normal language selector; the add-on follows the selected forum language automatically.

User-generated thread/resource content is not translated automatically.

Yeni kullanıcıya görünen metinler PHP/template/JS içine sabit yazılmamalı; XenForo phrase sistemi kullanılmalıdır.
