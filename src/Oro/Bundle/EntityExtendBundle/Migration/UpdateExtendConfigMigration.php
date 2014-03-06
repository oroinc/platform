<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class UpdateExtendConfigMigration implements Migration
{
    /**
     * @var ExtendConfigProcessor
     */
    protected $configProcessor;

    /**
     * @var ExtendConfigDumper
     */
    protected $configDumper;

    /**
     * @param ExtendConfigProcessor $configProcessor
     * @param ExtendConfigDumper    $configDumper
     */
    public function __construct(ExtendConfigProcessor $configProcessor, ExtendConfigDumper $configDumper)
    {
        $this->configProcessor = $configProcessor;
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
                    $schema->getExtendOptionsProvider(),
                    $this->configProcessor,
                    $this->configDumper
                )
            );
        }

        return [];
    }
}
