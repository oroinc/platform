<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;
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
    public function up(Schema $schema)
    {
        if ($schema instanceof ExtendSchema) {
            return [
                new UpdateExtendConfigMigrationQuery(
                    $schema->getExtendOptions(),
                    $this->schemaGenerator,
                    $this->configDumper
                )
            ];
        }

        return [];
    }
}
