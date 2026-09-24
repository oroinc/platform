<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

/**
 * Turns the entity audit of the menu items off and locks the setting: the changes of a menu item are
 * recorded by the menu audit, so the entity audit of the same rows would only duplicate them.
 */
class DisableAndLockMenuUpdateDataAudit implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            MenuUpdate::class,
            'dataaudit',
            'auditable',
            false
        ));
        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            MenuUpdate::class,
            'dataaudit',
            'immutable',
            true
        ));
    }
}
