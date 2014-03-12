<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

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
     * @param array                 $options
     * @param ExtendConfigProcessor $configProcessor
     */
    public function __construct(
        array $options,
        ExtendConfigProcessor $configProcessor
    ) {
        $this->options         = $options;
        $this->configProcessor = $configProcessor;
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
    public function execute(Connection $connection, LoggerInterface $logger)
    {
        $this->configProcessor->processConfigs($this->options, $logger);
    }
}
