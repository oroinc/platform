<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Oro\Bundle\InstallerBundle\Migrations\MigrationQueryBuilder as BaseMigrationQueryBuilder;

class MigrationQueryBuilder extends BaseMigrationQueryBuilder
{
    /**
     * @var ExtendOptionManager
     */
    protected $extendOptionManager;

    public function setExtendOptionManager(ExtendOptionManager $extendOptionManager)
    {
        $this->extendOptionManager = $extendOptionManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchema()
    {
        $sm        = $this->connection->getSchemaManager();
        $platform  = $this->connection->getDatabasePlatform();
        $sequences = array();
        if ($platform->supportsSequences()) {
            $sequences = $sm->listSequences();
        }
        $tables = $sm->listTables();

        return new ExtendSchema(
            $this->extendOptionManager,
            $tables,
            $sequences,
            $sm->createSchemaConfig()
        );
    }
}
