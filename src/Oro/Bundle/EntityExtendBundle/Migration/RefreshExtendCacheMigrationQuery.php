<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class RefreshExtendCacheMigrationQuery implements MigrationQuery
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
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Refresh extend entity cache';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Connection $connection, LoggerInterface $logger)
    {
        $logger->notice('Prepare extend entity configs');
        $this->configDumper->updateConfig();
        $logger->notice('Generate a cache');
        $this->configDumper->dump();
    }
}
