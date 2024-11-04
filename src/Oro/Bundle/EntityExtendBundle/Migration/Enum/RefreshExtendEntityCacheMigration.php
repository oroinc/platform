<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Enum;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendCacheMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Psr\Container\ContainerInterface;

/**
 * Refresh extended entity and config manager cache.
 */
class RefreshExtendEntityCacheMigration implements Migration
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->container->get('oro_entity_config.config_manager')->clear();

        $queries->addQuery(
            new RefreshExtendCacheMigrationQuery(
                $this->container->get('oro_entity_config.tools.command_executor')
            )
        );
    }
}
