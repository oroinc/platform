<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;

class UpdateExtendConfigMigration implements Migration
{
    /**
     * @var ExtendConfigProcessor
     */
    protected $configProcessor;

    /**
     * @param ExtendConfigProcessor $configProcessor
     */
    public function __construct(ExtendConfigProcessor $configProcessor)
    {
        $this->configProcessor = $configProcessor;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $queries->addQuery(
                new UpdateExtendConfigMigrationQuery(
                    $schema->getExtendOptions(),
                    $this->configProcessor
                )
            );
        }
    }
}
