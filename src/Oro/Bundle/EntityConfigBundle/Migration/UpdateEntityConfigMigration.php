<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateEntityConfigMigration implements Migration
{
    /**
     * @var ConfigDumper
     */
    protected $configDumper;

    public function __construct(ConfigDumper $configDumper)
    {
        $this->configDumper = $configDumper;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateEntityConfigMigrationQuery($this->configDumper));
    }
}
