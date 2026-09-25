<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\DataAuditBundle\Model\MenuAuditObject;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * All application menus are managed through a single MenuAuditObjectProviderInterface,
 * allowing menu items to be queried without knowing their source package.
 */
class ChainMenuAuditObjectProvider implements MenuAuditObjectProviderInterface
{
    /**
     * @param iterable<MenuAuditObjectProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers
    ) {
    }

    #[\Override]
    public function getAuditObject(MenuUpdateInterface $menuUpdate): ?MenuAuditObject
    {
        foreach ($this->providers as $provider) {
            $auditObject = $provider->getAuditObject($menuUpdate);
            if (null !== $auditObject) {
                return $auditObject;
            }
        }

        return null;
    }
}
