<?php

namespace WarextStudios\UserContentManager\Pub\Controller;

use WarextStudios\UserContentManager\Permission;

use WarextStudios\UserContentManager\Service\ActionLogger;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class UserWarning extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $visitor = \XF::visitor();
        if (!Permission::check('view') || !Permission::check('bulk')) { return $this->noPermission(); }
        $user = $this->assertRecordExists('XF:User', $params->user_id);
        if (!$user->canWarn($error)) { return $this->noPermission($error); }
        $definitions = $this->finder('XF:WarningDefinition')->order('warning_definition_id')->fetch();
        $threads = $this->finder('XF:Thread')->where('user_id', $user->user_id)->order('last_post_date', 'DESC')->limit(100)->fetch();
        foreach ($threads as $id => $thread) { if (!$thread->canView()) { unset($threads[$id]); } }
        return $this->view('WarextStudios\\UserContentManager:UserWarning', 'wrxt_ucm_user_warning', ['user' => $user, 'definitions' => $definitions, 'threads' => $threads]);
    }

    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $visitor = \XF::visitor();
        if (!Permission::check('view') || !Permission::check('bulk')) { return $this->noPermission(); }
        $user = $this->assertRecordExists('XF:User', $params->user_id);
        if (!$user->canWarn($error)) { return $this->noPermission($error); }
        $definitionId = $this->filter('warning_definition_id', 'uint');
        $definition = $this->assertRecordExists('XF:WarningDefinition', $definitionId);
        $threadIds = array_values(array_unique(array_filter(array_map('intval', $this->filter('thread_ids', 'array')))));
        if (!$threadIds) { return $this->error('Uyarı vermek için en az bir ilgili konu seçmelisiniz.'); }
        $threads = $this->finder('XF:Thread')->where('user_id', $user->user_id)->where('thread_id', $threadIds)->fetch();
        $validIds = [];
        foreach ($threads as $thread) { if ($thread->canView()) { $validIds[] = (int)$thread->thread_id; } }
        if (!$validIds) { return $this->error('Uyarı vermek için en az bir ilgili konu seçmelisiniz.'); }
        $warnService = $this->service('XF:User\Warn', $user, 'user', $user, $visitor);
        $warnService->setFromDefinition($definition);
        $warning = $warnService->save();
        $db = $this->app()->db();
        foreach ($validIds as $threadId) { $db->insert('xf_warext_ucm_warning_content', ['warning_id' => $warning->warning_id, 'content_type' => 'thread', 'content_id' => $threadId, 'link_date' => \XF::$time]); }
        (new ActionLogger())->log('user', $user, $user->user_id, 'warning', '', ['warning_id' => (int)$warning->warning_id, 'warning_definition_id' => (int)$definitionId, 'thread_ids' => $validIds]);
        return $this->redirect($this->buildLink('kullanici-icerikleri', $user), 'Uyarı oluşturuldu ve ' . count($validIds) . ' içerik uyarıyla ilişkilendirildi.');
    }
}
