<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendSchemaGenerator;

class UpdateExtendConfigMigration implements Migration
{
    /**
     * @var ExtendSchemaGenerator
     */
    protected $schemaGenerator;

    /**
     * @var ExtendConfigDumper
     */
    protected $configDumper;

    public function __construct(ExtendSchemaGenerator $schemaGenerator, ExtendConfigDumper $configDumper)
    {
        $this->schemaGenerator = $schemaGenerator;
        $this->configDumper    = $configDumper;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $queries->addSql(
                new UpdateExtendConfigMigrationQuery(
                    $schema->getExtendOptions(),
                    $this->schemaGenerator,
                    $this->configDumper
                )
            );
        }

        return [];
    }
}
