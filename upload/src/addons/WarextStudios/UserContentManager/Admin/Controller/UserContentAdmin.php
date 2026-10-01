<?php

namespace WarextStudios\UserContentManager\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class UserContentAdmin extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('warextUcmView');
    }

    public function actionIndex()
    {
        $recentUsers = $this->finder('XF:User')
            ->order('last_activity', 'DESC')
            ->order('user_id', 'DESC')
            ->limit(20)
            ->fetch();

        return $this->view(
            'WarextStudios\UserContentManager:UserContentAdmin',
            'wrxt_ucm_admin_index',
            ['recentUsers' => $recentUsers]
        );
    }

    public function actionSelect()
    {
        $this->assertPostOnly();

        $username = trim($this->filter('username', 'str'));
        if ($username === '')
        {
            return $this->error(\XF::phrase('warext_ucm_admin_username_required'));
        }

        $user = $this->finder('XF:User')
            ->where('username', $username)
            ->fetchOne();

        if (!$user)
        {
            return $this->error(\XF::phrase('warext_ucm_admin_user_not_found'));
        }

        return $this->redirect($this->buildLink('warext-ucm/manage', $user));
    }

    public function actionManage(ParameterBag $params)
    {
        $user = $this->assertRecordExists('XF:User', $params->user_id);
        $url = \XF::app()->router('public')->buildLink('canonical:kullanici-icerikleri', $user);

        return $this->redirect($url);
    }
}
