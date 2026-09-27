<?php

namespace Oro\Bundle\DataAuditBundle\Placeholder;

use Oro\Bundle\DataAuditBundle\Provider\MenuAuditObjectProviderInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Placeholder filter that determines whether the page shows a menu item the audit keeps a history.
 */
class MenuAuditFilter
{
    public function __construct(
        private readonly MenuAuditObjectProviderInterface $auditObjectProvider
    ) {
    }

    public function isMenuItemAuditable(mixed $entity): bool
    {
        return $entity instanceof MenuUpdateInterface
            && null !== $this->auditObjectProvider->getAuditObject($entity);
    }
}
