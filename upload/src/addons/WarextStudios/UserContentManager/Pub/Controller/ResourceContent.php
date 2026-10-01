<?php

namespace WarextStudios\UserContentManager\Pub\Controller;

use WarextStudios\UserContentManager\Permission;

use WarextStudios\UserContentManager\Content\AbstractHandler;
use WarextStudios\UserContentManager\Service\ResourceBulk;
use XF\Entity\User;
use XF\Mvc\Entity\Finder;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class ResourceContent extends AbstractController
{
    public const PER_PAGE = 25;
    public const IMMEDIATE_LIMIT = 500;

    public function actionIndex(ParameterBag $params)
    {
        $visitor = \XF::visitor();
        if (!Permission::check('view')) { return $this->noPermission(); }
        if (!class_exists('XFRM\\Entity\\ResourceItem')) { return $this->error('XenForo Resource Manager kurulu veya etkin değil.'); }
        $user = $this->assertRecordExists('XF:User', $params->user_id);
        if (!$user->canViewFullProfile($error)) { return $this->noPermission($error); }
        $page = $params->page ?: 1;
        $filters = $this->getFilters();
        [$categoryChoices, $visibleCategoryIds] = $this->getCategoryChoices();
        $handler = $this->repository('WarextStudios/UserContentManager:Content')->getHandler('resource');
        if (!$handler) { return $this->error('XenForo Resource Manager kurulu veya etkin değil.'); }
        $finder = $this->buildFinder($user, $handler, $filters, $visibleCategoryIds);
        $total = (clone $finder)->total();
        $filterParams = $this->getFilterParams($filters);
        $this->assertValidPage($page, self::PER_PAGE, $total, 'kullanici-kaynaklari', $user);
        $this->assertCanonicalUrl($this->buildLink('kullanici-kaynaklari', $user, array_merge($filterParams, ['page' => $page])));
        $resources = $finder->limitByPage($page, self::PER_PAGE)->fetch();
        foreach ($resources as $id => $resource) { if (!$handler->canView($resource, $visitor)) { unset($resources[$id]); } }
        return $this->view('WarextStudios\\UserContentManager:ResourceContent', 'wrxt_ucm_resource_content', [
            'user' => $user, 'resources' => $resources, 'filters' => $filters, 'filterParams' => $filterParams,
            'categoryChoices' => $categoryChoices, 'page' => $page, 'perPage' => self::PER_PAGE, 'total' => $total,
            'canBulk' => Permission::check('bulk'), 'canHardDelete' => Permission::check('hardDelete')
        ]);
    }

    public function actionBulk(ParameterBag $params)
    {
        $this->assertPostOnly();
        $visitor = \XF::visitor();
        if (!Permission::check('view') || !Permission::check('bulk')) { return $this->noPermission(); }
        if (!class_exists('XFRM\\Entity\\ResourceItem')) { return $this->error('XenForo Resource Manager kurulu veya etkin değil.'); }
        $user = $this->assertRecordExists('XF:User', $params->user_id);
        $handler = $this->repository('WarextStudios/UserContentManager:Content')->getHandler('resource');
        if (!$handler) { return $this->error('XenForo Resource Manager kurulu veya etkin değil.'); }
        $action = $this->filter('bulk_action', 'str');
        $scope = $this->filter('selection_scope', 'str');
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->filter('resource_ids', 'array')))));
        $page = max(1, $this->filter('source_page', 'uint'));
        $filters = $this->getFilters();
        [, $visibleCategoryIds] = $this->getCategoryChoices();
        $allowed = ['move', 'soft_delete', 'hard_delete', 'restore', 'approve', 'unapprove', 'prefix', 'title_prepend', 'title_append', 'title_replace'];
        if (!in_array($action, $allowed, true) || !in_array($scope, ['selected', 'page', 'filter'], true)) { return $this->error(\XF::phrase('warext_ucm_invalid_bulk_action')); }
        if ($action === 'hard_delete') {
            if (!Permission::check('hardDelete')) { return $this->noPermission(); }
            if (!$this->filter('confirm_hard_delete', 'bool')) { return $this->error(\XF::phrase('warext_ucm_hard_delete_confirmation_required')); }
        }
        $finder = $this->buildFinder($user, $handler, $filters, $visibleCategoryIds);
        if ($scope === 'selected') {
            if (!$ids) { return $this->error('En az bir kaynak seçmelisiniz.'); }
            if (count($ids) > self::IMMEDIATE_LIMIT) { return $this->error(\XF::phrase('warext_ucm_immediate_limit_x', ['limit' => self::IMMEDIATE_LIMIT])); }
            $finder->where('resource_id', $ids);
        } elseif ($scope === 'page') {
            $finder->limitByPage($page, self::PER_PAGE);
        } elseif ((clone $finder)->total() > self::IMMEDIATE_LIMIT) {
            return $this->error(\XF::phrase('warext_ucm_immediate_limit_x', ['limit' => self::IMMEDIATE_LIMIT]));
        }
        $resources = $finder->fetch();
        $targetCategory = null;
        if ($action === 'move') {
            $targetId = $this->filter('target_category_id', 'uint');
            if (!$targetId || !in_array($targetId, $visibleCategoryIds, true)) { return $this->error('Taşıma işlemi için erişebildiğiniz bir kaynak kategorisi seçmelisiniz.'); }
            $targetCategory = $this->assertRecordExists('XFRM:Category', $targetId);
        }
        $options = [
            'target_category' => $targetCategory, 'reason' => trim($this->filter('reason', 'str')),
            'prefix_id' => $this->filter('target_prefix_id', 'uint'), 'title_value' => trim($this->filter('title_value', 'str')),
            'title_search' => $this->filter('title_search', 'str'), 'title_replace' => $this->filter('title_replace', 'str')
        ];
        $service = new ResourceBulk();
        $permissionAction = $this->getPermissionAction($action);
        $processed = 0; $skipped = 0; $failed = 0;
        foreach ($resources as $resource) {
            $actionError = null;
            if (!$handler->canView($resource, $visitor) || !$handler->canPerformAction($permissionAction, $resource, $visitor, $actionError)) { $skipped++; continue; }
            try { $service->apply($resource, $action, $options); $processed++; }
            catch (\Throwable $e) { $failed++; \XF::logException($e, false, 'Warext UCM resource ' . $resource->resource_id . ': '); }
        }
        return $this->redirect($this->buildLink('kullanici-kaynaklari', $user, $this->getFilterParams($filters)), \XF::phrase('warext_ucm_bulk_result_x', compact('processed', 'skipped', 'failed')));
    }

    protected function buildFinder(User $user, AbstractHandler $handler, array $filters, array $visibleCategoryIds): Finder
    {
        $finder = $handler->getFinderForUser($user)->with('Category');
        if ($visibleCategoryIds) { $finder->where('resource_category_id', $visibleCategoryIds); } else { $finder->where('resource_id', 0); }
        if ($filters['category_id']) { $finder->where('resource_category_id', $filters['category_id']); }
        if ($filters['state']) { $finder->where('resource_state', $filters['state']); }
        if ($filters['title'] !== '') { $finder->where('title', 'LIKE', $finder->escapeLike($filters['title'], '%?%')); }
        return $finder->order('last_update', 'DESC')->order('resource_id', 'DESC');
    }

    protected function getCategoryChoices(): array
    {
        $choices = []; $ids = [];
        $categories = $this->finder('XFRM:Category')->order('lft')->fetch();
        foreach ($categories as $category) {
            if (!$category->canView()) { continue; }
            $choices[$category->resource_category_id] = str_repeat('— ', max(0, (int)$category->depth)) . $category->title;
            $ids[] = (int)$category->resource_category_id;
        }
        return [$choices, $ids];
    }

    protected function getFilters(): array
    {
        $state = $this->filter('state', 'str');
        if (!in_array($state, ['', 'visible', 'deleted', 'moderated'], true)) { $state = ''; }
        return ['category_id' => $this->filter('category_id', 'uint'), 'state' => $state, 'title' => trim($this->filter('title', 'str'))];
    }

    protected function getFilterParams(array $filters): array
    {
        return array_filter($filters, fn($value) => $value !== '' && $value !== 0 && $value !== null);
    }

    protected function getPermissionAction(string $action): string
    {
        return match ($action) {
            'move' => AbstractHandler::ACTION_MOVE, 'soft_delete' => AbstractHandler::ACTION_SOFT_DELETE,
            'hard_delete' => AbstractHandler::ACTION_HARD_DELETE, 'restore' => AbstractHandler::ACTION_RESTORE,
            'approve' => AbstractHandler::ACTION_APPROVE, 'unapprove' => AbstractHandler::ACTION_UNAPPROVE,
            'prefix' => AbstractHandler::ACTION_PREFIX, default => AbstractHandler::ACTION_EDIT
        };
    }
}
