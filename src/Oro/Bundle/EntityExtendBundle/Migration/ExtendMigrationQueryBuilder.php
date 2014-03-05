<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryBuilder;

class ExtendMigrationQueryBuilder extends MigrationQueryBuilder
{
    /**
     * @var ExtendOptionManager
     */
    protected $extendOptionManager;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @param ExtendOptionManager $extendOptionManager
     */
    public function setExtendOptionManager(ExtendOptionManager $extendOptionManager)
    {
        $this->extendOptionManager   = $extendOptionManager;
    }

    /**
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
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
    protected function setExtensions(Migration $migration)
    {
        parent::setExtensions($migration);

        if ($migration instanceof ExtendExtensionAwareInterface) {
            $migration->setExtendExtension($this->extendExtension);
        }
    }
}
