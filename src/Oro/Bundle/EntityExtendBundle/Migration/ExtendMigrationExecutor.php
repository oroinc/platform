<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\MigrationExecutorWithNameGenerator;

class ExtendMigrationExecutor extends MigrationExecutorWithNameGenerator
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
    protected function createSchemaObject(array $tables = [], array $sequences = [], $schemaConfig = null)
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
