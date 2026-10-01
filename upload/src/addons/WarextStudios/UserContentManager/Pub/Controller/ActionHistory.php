<?php

namespace WarextStudios\UserContentManager\Pub\Controller;

use WarextStudios\UserContentManager\Permission;

use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class ActionHistory extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        if (!Permission::check('view')) { return $this->noPermission(); }
        $user = $this->assertRecordExists('XF:User', $params->user_id);
        if (!$user->canViewFullProfile($error)) { return $this->noPermission($error); }
        $logs = $this->finder('WarextStudios\\UserContentManager:ActionLog')->where('target_user_id', $user->user_id)->with('Actor')->order('log_date', 'DESC')->limit(200)->fetch();
        return $this->view('WarextStudios\\UserContentManager:ActionHistory', 'wrxt_ucm_action_history', ['user' => $user, 'logs' => $logs]);
    }
}
