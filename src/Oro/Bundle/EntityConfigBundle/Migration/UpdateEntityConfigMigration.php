<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;

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
    public function up(Schema $schema)
    {
        return [new UpdateEntityConfigMigrationQuery($this->configDumper)];
    }
}
