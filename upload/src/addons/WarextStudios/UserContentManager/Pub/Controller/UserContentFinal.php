<?php

namespace WarextStudios\UserContentManager\Pub\Controller;

use WarextStudios\UserContentManager\Permission;

use WarextStudios\UserContentManager\Service\BulkOperationQueue;
use XF\Mvc\ParameterBag;

class UserContentFinal extends UserContent
{
    public function actionBulk(ParameterBag $params)
    {
        $this->assertPostOnly();
        $visitor = \XF::visitor();

        if (!Permission::check('view') || !Permission::check('bulk'))
        {
            return $this->noPermission();
        }

        $scope = $this->filter('selection_scope', 'str');

        if (!in_array($scope, ['category', 'filter'], true))
        {
            return parent::actionBulk($params);
        }

        $user = $this->assertRecordExists('XF:User', $params->user_id);

        if (!$user->canViewFullProfile($error))
        {
            return $this->noPermission($error);
        }

        $action = $this->filter('bulk_action', 'str');
        $allowed = [
            'move', 'soft_delete', 'hard_delete', 'restore', 'lock', 'unlock',
            'stick', 'unstick', 'approve', 'unapprove', 'prefix',
            'title_prepend', 'title_append', 'title_replace'
        ];

        if (!in_array($action, $allowed, true))
        {
            return $this->error(\XF::phrase('warext_ucm_invalid_bulk_action'));
        }

        if ($action === 'hard_delete')
        {
            if (!Permission::check('hardDelete'))
            {
                return $this->noPermission();
            }

            if (!$this->filter('confirm_hard_delete', 'bool'))
            {
                return $this->error(\XF::phrase('warext_ucm_hard_delete_confirmation_required'));
            }
        }

        $filters = $this->getFilters();
        $selectionFilters = $filters;

        if ($scope === 'category')
        {
            if (!$filters['category_id'] && !$filters['node_id'])
            {
                return $this->error(\XF::phrase('warext_ucm_category_selection_requires_filter'));
            }

            $selectionFilters = $this->getEmptyFilters();
            $selectionFilters['category_id'] = $filters['category_id'];
            $selectionFilters['node_id'] = $filters['node_id'];
        }

        $handler = $this->repository('WarextStudios/UserContentManager:Content')->getHandler('thread');

        if (!$handler)
        {
            return $this->error(\XF::phrase('warext_ucm_thread_handler_unavailable'));
        }

        [$nodes, , $forumChoices] = $this->getNodeChoices();
        $visibleForumIds = array_map('intval', array_keys($forumChoices));
        $categoryNodeIds = $this->getCategoryForumIds($nodes, $selectionFilters['category_id']);
        $finder = $this->buildThreadFinder($user, $handler, $selectionFilters, $categoryNodeIds, $visibleForumIds);
        $matchCount = (clone $finder)->total();

        if ($matchCount <= self::IMMEDIATE_LIMIT)
        {
            return parent::actionBulk($params);
        }

        $targetNodeId = $this->filter('target_node_id', 'uint');

        if ($action === 'move' && (!$targetNodeId || !in_array($targetNodeId, $visibleForumIds, true)))
        {
            return $this->error(\XF::phrase('warext_ucm_target_forum_required'));
        }

        $options = [
            'target_node_id' => $targetNodeId,
            'reason' => trim($this->filter('reason', 'str')),
            'prefix_id' => $this->filter('target_prefix_id', 'uint'),
            'title_value' => trim($this->filter('title_value', 'str')),
            'title_search' => $this->filter('title_search', 'str'),
            'title_replace' => $this->filter('title_replace', 'str')
        ];

        $operation = (new BulkOperationQueue())->enqueue(
            'thread',
            $visitor->user_id,
            $user->user_id,
            $action,
            $selectionFilters,
            $options,
            $matchCount
        );

        return $this->redirect(
            $this->buildLink('kullanici-icerikleri', $user, $this->getFilterParams($filters)),
            'Toplu işlem #' . $operation->operation_id . ' iş kuyruğuna alındı. ' . $matchCount . ' konu arka planda güvenli batch işlemleriyle işlenecek.'
        );
    }
}
