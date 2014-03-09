<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryLoaderWithNameGenerator;

class ExtendMigrationQueryLoader extends MigrationQueryLoaderWithNameGenerator
{
    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @param ExtendOptionsManager $extendOptionsManager
     */
    public function setExtendOptionsManager(ExtendOptionsManager $extendOptionsManager)
    {
        $this->extendOptionsManager = $extendOptionsManager;
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
}
