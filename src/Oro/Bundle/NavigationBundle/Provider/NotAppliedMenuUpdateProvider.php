<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides the not applied menu updates.
 */
class NotAppliedMenuUpdateProvider implements MenuUpdateProviderInterface, ResetInterface
{
    private array $notAppliedMenuUpdates = [];

    public function getMenuUpdatesForMenuItem(ItemInterface $menuItem, array $options = [])
    {
        $menuName = $menuItem->getName();

        return $this->notAppliedMenuUpdates[$menuName] ?? [];
    }

    public function onMenuUpdatesApplyAfter(MenuUpdatesApplyAfterEvent $event): void
    {
        $result = $event->getApplyResult();
        $menuName = $result->getMenu()->getName();
        $this->notAppliedMenuUpdates[$menuName] = $result->getNotAppliedMenuUpdates() + $result->getOrphanMenuUpdates();
    }

    public function reset(): void
    {
        $this->notAppliedMenuUpdates = [];
    }
}
