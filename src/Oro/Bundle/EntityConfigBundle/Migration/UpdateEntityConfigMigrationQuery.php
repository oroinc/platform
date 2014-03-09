<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;

class UpdateEntityConfigMigrationQuery implements MigrationQuery
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
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'UPDATE ENTITY CONFIG';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Connection $connection)
    {
        $this->configDumper->updateConfigs();
    }
}
