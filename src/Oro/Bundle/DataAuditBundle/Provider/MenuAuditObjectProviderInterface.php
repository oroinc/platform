<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\DataAuditBundle\Model\MenuAuditObject;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Maps a menu item to its Data Audit record for change history tracking,
 * registered using the oro_dataaudit.menu_audit_object tag.
 */
interface MenuAuditObjectProviderInterface
{
    public function getAuditObject(MenuUpdateInterface $menuUpdate): ?MenuAuditObject;
}
