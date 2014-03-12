<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class RefreshExtendCacheMigration implements Migration
{
    /**
     * @var ExtendConfigDumper
     */
    protected $configDumper;

    /**
     * @param ExtendConfigDumper $configDumper
     */
    public function __construct(ExtendConfigDumper $configDumper)
    {
        $this->configDumper = $configDumper;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $queries->addQuery(
                new RefreshExtendCacheMigrationQuery($this->configDumper)
            );
        }
    }
}
