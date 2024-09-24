<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\MigrationExecutorWithNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\MigrationExtensionManager;

/**
 * Migrations query executor that aware about an extend options manager.
 */
class ExtendMigrationExecutor extends MigrationExecutorWithNameGenerator
{
    private ?ExtendOptionsManager $extendOptionsManager = null;

    public function setExtendOptionsManager(ExtendOptionsManager $extendOptionsManager): void
    {
        $this->extendOptionsManager = $extendOptionsManager;
        if ($this->extensionManager instanceof ExtendMigrationExtensionManager) {
            $this->extensionManager->setExtendOptionsManager($this->extendOptionsManager);
        }
    }

    #[\Override]
    public function setExtensionManager(MigrationExtensionManager $extensionManager): void
    {
        parent::setExtensionManager($extensionManager);
        if (null !== $this->extendOptionsManager
            && $this->extensionManager instanceof ExtendMigrationExtensionManager
        ) {
            $this->extensionManager->setExtendOptionsManager($this->extendOptionsManager);
        }
    }

    #[\Override]
    protected function createSchemaObject(
        array $tables = [],
        array $sequences = [],
        ?SchemaConfig $schemaConfig = null
    ): Schema {
        return new ExtendSchema(
            $this->extendOptionsManager,
            $this->nameGenerator,
            $tables,
            $sequences,
            $schemaConfig
        );
    }
}
