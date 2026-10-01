<?php

namespace WarextStudios\UserContentManager;

final class Permission
{
    private const ADMIN_PERMISSION_MAP = [
        'view' => 'warextUcmView',
        'bulk' => 'warextUcmBulk',
        'hardDelete' => 'warextUcmHardDelete'
    ];

    public static function check(string $permission): bool
    {
        $visitor = \XF::visitor();

        if ($visitor->hasPermission('warextUcm', $permission))
        {
            return true;
        }

        if (!$visitor->is_admin)
        {
            return false;
        }

        $adminPermission = self::ADMIN_PERMISSION_MAP[$permission] ?? null;
        if (!$adminPermission)
        {
            return false;
        }

        return $visitor->hasAdminPermission($adminPermission);
    }
}
