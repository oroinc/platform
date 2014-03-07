<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateExtendConfigMigrationQuery implements MigrationQuery
{
    /**
     * @var ExtendOptionsProviderInterface
     */
    protected $optionsProvider;

    /**
     * @var ExtendConfigProcessor
     */
    protected $configProcessor;

    /**
     * @var ExtendConfigDumper
     */
    protected $configDumper;

    /**
     * @param ExtendOptionsProviderInterface $optionsProvider
     * @param ExtendConfigProcessor          $configProcessor
     * @param ExtendConfigDumper             $configDumper
     */
    public function __construct(
        ExtendOptionsProviderInterface $optionsProvider,
        ExtendConfigProcessor $configProcessor,
        ExtendConfigDumper $configDumper
    ) {
        $this->optionsProvider = $optionsProvider;
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
            $this->optionsProvider->getOptions(),
            $logger,
            true
        );

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->configProcessor->processConfigs($this->optionsProvider->getOptions());
        $this->configDumper->updateConfig();
        $this->configDumper->dump();
    }
}
