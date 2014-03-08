<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryBuilderWithNameGenerator;

class ExtendMigrationQueryBuilder extends MigrationQueryBuilderWithNameGenerator
{
    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @param ExtendOptionsManager $extendOptionsManager
     */
    public function setExtendOptionsManager(ExtendOptionsManager $extendOptionsManager)
    {
        $this->extendOptionsManager = $extendOptionsManager;
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
    public function createSchemaObject($tables, $sequences, $schemaConfig)
    {
        return new ExtendSchema(
            $this->extendOptionsManager,
            $this->nameGenerator,
            $tables,
            $sequences,
            $schemaConfig
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
