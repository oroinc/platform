<?php

namespace Oro\Bundle\PlatformBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\InstallerBundle\Migration\RenameActivityAssociations20;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameActivityAssociations implements
    Migration,
    RenameExtensionAwareInterface,
    ConnectionAwareInterface,
    NameGeneratorAwareInterface
{
    use RenameExtensionAwareTrait;
    use ConnectionAwareTrait;
    use ExtendNameGeneratorAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $helper = new RenameActivityAssociations20(
            $this->connection,
            $this->nameGenerator,
            $this->renameExtension
        );
        $helper->rename($schema, $queries);
    }
}
