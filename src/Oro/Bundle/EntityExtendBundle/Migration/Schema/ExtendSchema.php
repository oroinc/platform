<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Schema;

use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Schema\SchemaWithNameGenerator;

/**
 * Extended database schema for managing custom entity fields and relations.
 *
 * This schema class extends the base schema with support for managing extend options
 * that control custom field behavior, relations, and configurations. It creates ExtendTable
 * instances instead of regular tables, allowing tables to store and manage extend-specific
 * metadata alongside standard Doctrine schema information.
 */
class ExtendSchema extends SchemaWithNameGenerator
{
    public const TABLE_CLASS = 'Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendTable';

    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @param ExtendOptionsManager            $extendOptionsManager
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param Table[]                         $tables
     * @param Sequence[]                      $sequences
     * @param SchemaConfig|null $schemaConfig
     */
    public function __construct(
        ExtendOptionsManager $extendOptionsManager,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        array $tables = [],
        array $sequences = [],
        ?SchemaConfig $schemaConfig = null
    ) {
        $this->extendOptionsManager = $extendOptionsManager;

        parent::__construct(
            $nameGenerator,
            $tables,
            $sequences,
            $schemaConfig
        );
    }

    #[\Override]
    protected function createTableObject(array $args)
    {
        $args['extendOptionsManager'] = $this->extendOptionsManager;

        return parent::createTableObject($args);
    }

    /**
     * @return array
     */
    public function getExtendOptions()
    {
        return $this->extendOptionsManager->getExtendOptions();
    }
}
