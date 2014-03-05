<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Extend\Schema\ExtendOptionManager;
use Oro\Bundle\EntityExtendBundle\Extend\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryBuilder;

class ExtendMigrationQueryBuilder extends MigrationQueryBuilder
{
    /**
     * @var ExtendOptionManager
     */
    protected $extendOptionManager;

    /**
     * @var ExtendMigrationHelper
     */
    protected $extendMigrationHelper;

    /**
     * @param ExtendOptionManager $extendOptionManager
     */
    public function setExtendOptionManager(ExtendOptionManager $extendOptionManager)
    {
        $this->extendOptionManager   = $extendOptionManager;
    }

    /**
     * @param ExtendMigrationHelper $extendMigrationHelper
     */
    public function setExtendMigrationHelper(ExtendMigrationHelper $extendMigrationHelper)
    {
        $this->extendMigrationHelper = $extendMigrationHelper;
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

    /**
     * {@inheritdoc}
     */
    protected function setMigrationHelpers(Migration $migration)
    {
        parent::setMigrationHelpers($migration);

        if ($migration instanceof ExtendMigrationHelperAwareInterface) {
            $migration->setExtendSchemaHelper($this->extendMigrationHelper);
        }
    }
}
