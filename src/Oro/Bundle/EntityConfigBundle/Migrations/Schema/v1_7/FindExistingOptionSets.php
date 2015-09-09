<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;

class FindExistingOptionSets implements Migration, OrderedMigrationInterface, DataStorageExtensionAwareInterface
{
    /** @var DataStorageExtension */
    protected $storage;

    public function setDataStorageExtension(DataStorageExtension $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new StoreOptionSetsQuery($this->storage));
        $queries->addQuery(new StoreOptionSetsValuesQuery($this->storage));
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }
}
