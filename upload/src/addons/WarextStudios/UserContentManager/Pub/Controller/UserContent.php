<?php

namespace WarextStudios\UserContentManager\Pub\Controller;

use WarextStudios\UserContentManager\Permission;

use WarextStudios\UserContentManager\Content\AbstractHandler;
use WarextStudios\UserContentManager\Filter\ThreadFilter;
use WarextStudios\UserContentManager\Service\ThreadBulk;
use XF\Entity\User;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Finder;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class UserContent extends AbstractController
{
    public const PER_PAGE = 25;
    public const IMMEDIATE_LIMIT = 500;

    public function actionIndex(ParameterBag $params)
    {
        $visitor = \XF::visitor();

        if (!Permission::check('view'))
        {
            return $this->noPermission();
        }

        $user = $this->assertRecordExists('XF:User', $params->user_id);

        if (!$user->canViewFullProfile($error))
        {
            return $this->noPermission($error);
        }

        $page = $params->page ?: 1;
        $filters = $this->getFilters();
        $filterParams = $this->getFilterParams($filters);
        $contentRepo = $this->repository('WarextStudios/UserContentManager:Content');
        $threadHandler = $contentRepo->getHandler('thread');

        if (!$threadHandler)
        {
            return $this->error(\XF::phrase('warext_ucm_thread_handler_unavailable'));
        }

        [$nodes, $categoryChoices, $forumChoices] = $this->getNodeChoices();
        $categoryNodeIds = $this->getCategoryForumIds($nodes, $filters['category_id']);
        $visibleForumIds = array_map('intval', array_keys($forumChoices));
        $threadFinder = $this->buildThreadFinder($user, $threadHandler, $filters, $categoryNodeIds, $visibleForumIds);
        $total = (clone $threadFinder)->total();

        $this->assertValidPage($page, self::PER_PAGE, $total, 'kullanici-icerikleri', $user);
        $this->assertCanonicalUrl($this->buildLink('kullanici-icerikleri', $user, array_merge($filterParams, ['page' => $page])));

        $threads = $threadFinder
            ->limitByPage($page, self::PER_PAGE)
            ->fetch();

        foreach ($threads as $threadId => $thread)
        {
            if (!$threadHandler->canView($thread, $visitor))
            {
                unset($threads[$threadId]);
            }
        }

        $groups = $this->groupThreads($threads);
        $stats = $this->getThreadStats($user->user_id, $visibleForumIds);
        $prefixes = $this->finder('XF:ThreadPrefix')
            ->order('display_order')
            ->order('prefix_id')
            ->fetch();

        return $this->view(
            'WarextStudios\UserContentManager:UserContent',
            'wrxt_ucm_user_content',
            [
                'user' => $user,
                'threads' => $threads,
                'groups' => $groups,
                'stats' => $stats,
                'filters' => $filters,
                'filterParams' => $filterParams,
                'categoryChoices' => $categoryChoices,
                'forumChoices' => $forumChoices,
                'prefixes' => $prefixes,
                'page' => $page,
                'perPage' => self::PER_PAGE,
                'total' => $total,
                'canBulk' => Permission::check('bulk'),
                'canHardDelete' => Permission::check('hardDelete')
            ]
        );
    }

    public function actionBulk(ParameterBag $params)
    {
        $this->assertPostOnly();

        $visitor = \XF::visitor();

        if (!Permission::check('view') || !Permission::check('bulk'))
        {
            return $this->noPermission();
        }

        $user = $this->assertRecordExists('XF:User', $params->user_id);

        if (!$user->canViewFullProfile($error))
        {
            return $this->noPermission($error);
        }

        $bulkAction = $this->filter('bulk_action', 'str');
        $selectionScope = $this->filter('selection_scope', 'str');
        $threadIds = array_values(array_unique(array_filter(array_map('intval', $this->filter('thread_ids', 'array')))));
        $page = max(1, $this->filter('source_page', 'uint'));
        $filters = $this->getFilters();
        $filterParams = $this->getFilterParams($filters);
        $allowedActions = [
            'move', 'soft_delete', 'hard_delete', 'restore', 'lock', 'unlock',
            'stick', 'unstick', 'approve', 'unapprove', 'prefix',
            'title_prepend', 'title_append', 'title_replace'
        ];

        if (!in_array($bulkAction, $allowedActions, true))
        {
            return $this->error(\XF::phrase('warext_ucm_invalid_bulk_action'));
        }

        if (!in_array($selectionScope, ['selected', 'page', 'category', 'filter'], true))
        {
            return $this->error(\XF::phrase('warext_ucm_invalid_selection_scope'));
        }

        if ($bulkAction === 'hard_delete')
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

        $contentRepo = $this->repository('WarextStudios/UserContentManager:Content');
        $threadHandler = $contentRepo->getHandler('thread');

        if (!$threadHandler)
        {
            return $this->error(\XF::phrase('warext_ucm_thread_handler_unavailable'));
        }

        [$nodes, , $forumChoices] = $this->getNodeChoices();
        $visibleForumIds = array_map('intval', array_keys($forumChoices));
        $selectionFilters = $filters;

        if ($selectionScope === 'category')
        {
            if (!$filters['category_id'] && !$filters['node_id'])
            {
                return $this->error(\XF::phrase('warext_ucm_category_selection_requires_filter'));
            }

            $selectionFilters = $this->getEmptyFilters();
            $selectionFilters['category_id'] = $filters['category_id'];
            $selectionFilters['node_id'] = $filters['node_id'];
        }

        $categoryNodeIds = $this->getCategoryForumIds($nodes, $selectionFilters['category_id']);
        $threadFinder = $this->buildThreadFinder($user, $threadHandler, $selectionFilters, $categoryNodeIds, $visibleForumIds);

        if ($selectionScope === 'selected')
        {
            if (!$threadIds)
            {
                return $this->error(\XF::phrase('warext_ucm_select_at_least_one'));
            }

            if (count($threadIds) > self::IMMEDIATE_LIMIT)
            {
                return $this->error(\XF::phrase('warext_ucm_immediate_limit_x', ['limit' => self::IMMEDIATE_LIMIT]));
            }

            $threadFinder->where('thread_id', $threadIds);
        }
        elseif ($selectionScope === 'page')
        {
            $threadFinder->limitByPage($page, self::PER_PAGE);
        }
        else
        {
            $matchCount = (clone $threadFinder)->total();

            if ($matchCount > self::IMMEDIATE_LIMIT)
            {
                return $this->error(\XF::phrase('warext_ucm_immediate_limit_x', ['limit' => self::IMMEDIATE_LIMIT]));
            }
        }

        $threads = $threadFinder->fetch();

        if (!$threads->count())
        {
            return $this->error(\XF::phrase('warext_ucm_no_bulk_matches'));
        }

        $targetForum = null;
        $targetNodeId = $this->filter('target_node_id', 'uint');

        if ($bulkAction === 'move')
        {
            if (!$targetNodeId || !in_array($targetNodeId, $visibleForumIds, true))
            {
                return $this->error(\XF::phrase('warext_ucm_target_forum_required'));
            }

            $targetForum = $this->assertRecordExists('XF:Forum', $targetNodeId);
        }

        $permissionAction = $this->getPermissionAction($bulkAction);
        $bulkService = new ThreadBulk();
        $options = [
            'target_forum' => $targetForum,
            'reason' => trim($this->filter('reason', 'str')),
            'prefix_id' => $this->filter('target_prefix_id', 'uint'),
            'title_value' => trim($this->filter('title_value', 'str')),
            'title_search' => $this->filter('title_search', 'str'),
            'title_replace' => $this->filter('title_replace', 'str')
        ];
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($threads as $thread)
        {
            $actionError = null;

            if (!$threadHandler->canView($thread, $visitor))
            {
                $skipped++;
                continue;
            }

            if (!$threadHandler->canPerformAction($permissionAction, $thread, $visitor, $actionError))
            {
                $skipped++;
                continue;
            }

            try
            {
                $bulkService->apply($thread, $bulkAction, $options);
                $processed++;
            }
            catch (\Throwable $e)
            {
                $failed++;
                \XF::logException($e, false, 'Warext UCM bulk thread ' . $thread->thread_id . ': ');
            }
        }

        $redirectParams = $filterParams;

        if ($page > 1)
        {
            $redirectParams['page'] = $page;
        }

        return $this->redirect(
            $this->buildLink('kullanici-icerikleri', $user, $redirectParams),
            \XF::phrase('warext_ucm_bulk_result_x', [
                'processed' => $processed,
                'skipped' => $skipped,
                'failed' => $failed
            ])
        );
    }

    protected function buildThreadFinder(User $user, AbstractHandler $threadHandler, array $filters, array $categoryNodeIds, array $visibleForumIds): Finder
    {
        $finder = $threadHandler->getFinderForUser($user)
            ->with('Forum')
            ->where('discussion_type', '<>', 'redirect');

        if ($visibleForumIds)
        {
            $finder->where('node_id', $visibleForumIds);
        }
        else
        {
            $finder->where('thread_id', 0);
        }

        $threadFilter = new ThreadFilter();
        $threadFilter->apply($finder, $filters, $categoryNodeIds);

        return $finder;
    }

    protected function getPermissionAction(string $bulkAction): string
    {
        return match ($bulkAction)
        {
            'title_prepend', 'title_append', 'title_replace' => AbstractHandler::ACTION_EDIT,
            'move' => AbstractHandler::ACTION_MOVE,
            'soft_delete' => AbstractHandler::ACTION_SOFT_DELETE,
            'hard_delete' => AbstractHandler::ACTION_HARD_DELETE,
            'restore' => AbstractHandler::ACTION_RESTORE,
            'lock' => AbstractHandler::ACTION_LOCK,
            'unlock' => AbstractHandler::ACTION_UNLOCK,
            'stick' => AbstractHandler::ACTION_STICK,
            'unstick' => AbstractHandler::ACTION_UNSTICK,
            'approve' => AbstractHandler::ACTION_APPROVE,
            'unapprove' => AbstractHandler::ACTION_UNAPPROVE,
            'prefix' => AbstractHandler::ACTION_PREFIX,
            default => AbstractHandler::ACTION_EDIT
        };
    }

    protected function getFilters(): array
    {
        $state = $this->filter('state', 'str');
        $locked = $this->filter('locked', 'str');
        $sort = $this->filter('sort', 'str');
        $direction = $this->filter('direction', 'str');
        $dateFrom = $this->filter('date_from', 'str');
        $dateTo = $this->filter('date_to', 'str');

        if (!in_array($state, ['', 'visible', 'deleted', 'moderated'], true))
        {
            $state = '';
        }

        if (!in_array($locked, ['', 'open', 'locked'], true))
        {
            $locked = '';
        }

        if (!in_array($sort, ['last_post', 'created', 'title', 'replies', 'views'], true))
        {
            $sort = 'last_post';
        }

        if (!in_array($direction, ['asc', 'desc'], true))
        {
            $direction = 'desc';
        }

        return [
            'category_id' => $this->filter('category_id', 'uint'),
            'node_id' => $this->filter('node_id', 'uint'),
            'state' => $state,
            'locked' => $locked,
            'prefix_id' => $this->filter('prefix_id', 'int'),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_from_ts' => $this->parseDate($dateFrom, false),
            'date_to_ts' => $this->parseDate($dateTo, true),
            'title' => trim($this->filter('title', 'str')),
            'min_replies' => $this->nullableUnsignedInt('min_replies'),
            'max_replies' => $this->nullableUnsignedInt('max_replies'),
            'min_views' => $this->nullableUnsignedInt('min_views'),
            'max_views' => $this->nullableUnsignedInt('max_views'),
            'sort' => $sort,
            'direction' => $direction
        ];
    }

    protected function getEmptyFilters(): array
    {
        return [
            'category_id' => 0,
            'node_id' => 0,
            'state' => '',
            'locked' => '',
            'prefix_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'date_from_ts' => null,
            'date_to_ts' => null,
            'title' => '',
            'min_replies' => null,
            'max_replies' => null,
            'min_views' => null,
            'max_views' => null,
            'sort' => 'last_post',
            'direction' => 'desc'
        ];
    }

    protected function getFilterParams(array $filters): array
    {
        $params = [];

        foreach ($filters as $key => $value)
        {
            if (str_ends_with($key, '_ts') || $value === null || $value === '' || $value === 0)
            {
                continue;
            }

            if ($key === 'sort' && $value === 'last_post')
            {
                continue;
            }

            if ($key === 'direction' && $value === 'desc')
            {
                continue;
            }

            $params[$key] = $value;
        }

        return $params;
    }

    protected function getNodeChoices(): array
    {
        $nodes = $this->repository('XF:Node')->getNodeList();
        $categories = [];
        $forums = [];

        foreach ($nodes as $node)
        {
            if (!$node->canView())
            {
                continue;
            }

            $label = str_repeat('— ', max(0, (int)$node->depth - 1)) . $node->title;

            if ($node->node_type_id === 'Category')
            {
                $categories[$node->node_id] = $label;
            }
            elseif ($node->node_type_id === 'Forum')
            {
                $forums[$node->node_id] = $label;
            }
        }

        return [$nodes, $categories, $forums];
    }

    protected function getCategoryForumIds(ArrayCollection $nodes, int $categoryId): array
    {
        if (!$categoryId || !isset($nodes[$categoryId]))
        {
            return [];
        }

        $category = $nodes[$categoryId];

        if (!$category->canView())
        {
            return [];
        }

        if ($category->node_type_id === 'Forum')
        {
            return [$category->node_id];
        }

        if ($category->node_type_id !== 'Category')
        {
            return [];
        }

        $forumIds = [];

        foreach ($nodes as $node)
        {
            if ($node->node_type_id === 'Forum' && $node->canView() && $node->lft > $category->lft && $node->rgt < $category->rgt)
            {
                $forumIds[] = $node->node_id;
            }
        }

        return $forumIds;
    }

    protected function groupThreads(ArrayCollection $threads): array
    {
        $groups = [];

        foreach ($threads as $thread)
        {
            $nodeId = (int)$thread->node_id;

            if (!isset($groups[$nodeId]))
            {
                $groups[$nodeId] = [
                    'forum' => $thread->Forum,
                    'threads' => []
                ];
            }

            $groups[$nodeId]['threads'][] = $thread;
        }

        return $groups;
    }

    protected function nullableUnsignedInt(string $key): ?int
    {
        $raw = $this->filter($key, 'str');

        if ($raw === '')
        {
            return null;
        }

        return max(0, (int)$raw);
    }

    protected function parseDate(string $date, bool $endOfDay): ?int
    {
        if ($date === '')
        {
            return null;
        }

        $timezone = new \DateTimeZone(\XF::visitor()->timezone ?: 'UTC');
        $value = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timezone);

        if (!$value || $value->format('Y-m-d') !== $date)
        {
            return null;
        }

        if ($endOfDay)
        {
            $value = $value->setTime(23, 59, 59);
        }

        return $value->getTimestamp();
    }

    protected function getThreadStats(int $userId, array $visibleForumIds): array
    {
        $base = function () use ($userId, $visibleForumIds)
        {
            $finder = $this->finder('XF:Thread')
                ->where('user_id', $userId)
                ->where('discussion_type', '<>', 'redirect');

            if ($visibleForumIds)
            {
                $finder->where('node_id', $visibleForumIds);
            }
            else
            {
                $finder->where('thread_id', 0);
            }

            return $finder;
        };

        return [
            'total' => $base()->total(),
            'visible' => $base()->where('discussion_state', 'visible')->total(),
            'deleted' => $base()->where('discussion_state', 'deleted')->total(),
            'moderated' => $base()->where('discussion_state', 'moderated')->total(),
            'locked' => $base()->where('discussion_open', 0)->total()
        ];
    }
}
