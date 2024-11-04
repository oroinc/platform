<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class LoadOptionSets implements
    Migration,
    OrderedMigrationInterface,
    DataStorageExtensionAwareInterface
{
    use DataStorageExtensionAwareTrait;

    #[\Override]
    public function getOrder()
    {
        return 0;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new LoadOptionSetsQuery($this->dataStorageExtension));
    }
}
