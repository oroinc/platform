<?php

namespace Oro\Bundle\PlatformBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\InstallerBundle\Migration\MigrateNoteAssociations20;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateNoteAssociations implements
    Migration,
    ConnectionAwareInterface,
    NameGeneratorAwareInterface,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface
{
    use ConnectionAwareTrait;
    use ExtendNameGeneratorAwareTrait;
    use ActivityExtensionAwareTrait;
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $helper = new MigrateNoteAssociations20(
            $this->connection,
            $this->nameGenerator,
            $this->activityExtension,
            $this->extendExtension
        );
        $helper->migrate($schema, $queries);
    }
}
