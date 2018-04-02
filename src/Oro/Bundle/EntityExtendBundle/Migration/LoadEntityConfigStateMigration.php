<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class LoadEntityConfigStateMigration implements Migration, DataStorageExtensionAwareInterface
{
    /** @var DataStorageExtension */
    protected $dataStorageExtension;

    /**
     * {@inheritdoc}
     */
    public function setDataStorageExtension(DataStorageExtension $dataStorageExtension)
    {
        $this->dataStorageExtension = $dataStorageExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $queries->addQuery(
                new LoadEntityConfigStateMigrationQuery($this->dataStorageExtension)
            );
        }
    }
}
