<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Oro\Bundle\MigrationBundle\Exception\InvalidNameException;
use Oro\Bundle\MigrationBundle\Migration\Schema\SchemaWithNameGenerator;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

/**
 * Migrations query executor that aware about a database identifier name generator.
 */
class MigrationExecutorWithNameGenerator extends MigrationExecutor
{
    protected ?DbIdentifierNameGenerator $nameGenerator = null;

    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
        if (null !== $this->extensionManager) {
            $this->extensionManager->setNameGenerator($this->nameGenerator);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setExtensionManager(MigrationExtensionManager $extensionManager): void
    {
        parent::setExtensionManager($extensionManager);
        if (null !== $this->nameGenerator) {
            $this->extensionManager->setNameGenerator($this->nameGenerator);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function createSchemaObject(
        array $tables = [],
        array $sequences = [],
        ?SchemaConfig $schemaConfig = null
    ): Schema {
        if ($schemaConfig && null !== $this->nameGenerator) {
            $schemaConfig->setMaxIdentifierLength($this->nameGenerator->getMaxIdentifierSize());
        }

        return new SchemaWithNameGenerator(
            $this->nameGenerator,
            $tables,
            $sequences,
            $schemaConfig
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function checkTableName(string $tableName, Migration $migration): void
    {
        parent::checkTableName($tableName, $migration);
        if (\strlen($tableName) > $this->nameGenerator->getMaxIdentifierSize()) {
            throw new InvalidNameException(
                sprintf(
                    'Max table name length is %s. Please correct "%s" table in "%s" migration',
                    $this->nameGenerator->getMaxIdentifierSize(),
                    $tableName,
                    \get_class($migration)
                )
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function checkColumnName(string $tableName, string $columnName, Migration $migration): void
    {
        parent::checkColumnName($tableName, $columnName, $migration);
        if (\strlen($columnName) > $this->nameGenerator->getMaxIdentifierSize()) {
            throw new InvalidNameException(
                sprintf(
                    'Max column name length is %s. Please correct "%s:%s" column in "%s" migration',
                    $this->nameGenerator->getMaxIdentifierSize(),
                    $tableName,
                    $columnName,
                    \get_class($migration)
                )
            );
        }
    }
}
