<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class UpdateExtendConfigMigrationQuery implements MigrationQuery
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var ExtendConfigProcessor
     */
    protected $configProcessor;

    /**
     * @var ExtendConfigDumper
     */
    protected $configDumper;

    /**
     * @param array                 $options
     * @param ExtendConfigProcessor $configProcessor
     * @param ExtendConfigDumper    $configDumper
     */
    public function __construct(
        array $options,
        ExtendConfigProcessor $configProcessor,
        ExtendConfigDumper $configDumper
    ) {
        $this->options         = $options;
        $this->configProcessor = $configProcessor;
        $this->configDumper    = $configDumper;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new UpdateExtendConfigMigrationArrayLogger();
        $this->configProcessor->processConfigs(
            $this->options,
            $logger,
            true
        );

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Connection $connection)
    {
        $this->configProcessor->processConfigs($this->options);
        $this->configDumper->updateConfig();
        $this->configDumper->dump();
    }
}
