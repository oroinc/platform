<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ReverseSyncProcessor
{
    /** @var EntityManager */
    protected $em;

    /** @var ProcessorRegistry */
    protected $processorRegistry;

    /** @var Executor */
    protected $jobExecutor;

    /** @var TypesRegistry */
    protected $registry;

    /** @var LoggerStrategy */
    protected $logger;

    /**
     * @param EntityManager     $em
     * @param ProcessorRegistry $processorRegistry
     * @param Executor          $jobExecutor
     * @param TypesRegistry     $registry
     * @param LoggerStrategy    $logger
     */
    public function __construct(
        EntityManager $em,
        ProcessorRegistry $processorRegistry,
        Executor $jobExecutor,
        TypesRegistry $registry,
        LoggerStrategy $logger
    ) {
        $this->em                = $em;
        $this->processorRegistry = $processorRegistry;
        $this->jobExecutor       = $jobExecutor;
        $this->registry          = $registry;
        $this->logger            = $logger;
    }

    /**
     * Process channel synchronization
     *
     * @param Channel $channel    Channel object
     * @param string  $connector  Connector name
     * @param array   $parameters Connector additional parameters
     *
     * @return $this
     */
    public function process(Channel $channel, $connector, array $parameters)
    {
        try {
            $this->logger->info(sprintf('Start processing "%s" connector', $connector));

            $realConnector = $this->getRealConnector($channel, $connector);

            if (!($realConnector instanceof TwoWaySyncConnectorInterface)) {
                throw new \Exception('This connector doesn`t support two-way sync.');
            }

        } catch (\Exception $e) {
            return $this->logger->error($e->getMessage());
        }

        $configuration = [
            ProcessorRegistry::TYPE_EXPORT =>
                array_merge(
                    [
                        'entityName' => $realConnector->getImportEntityFQCN(),
                        'channel'    => $channel->getId()
                    ],
                    $parameters
                ),
        ];

        $this->processExport($realConnector->getExportJobName(), $configuration);

        return $this;
    }

    /**
     * Get logger strategy
     *
     * @return LoggerStrategy
     */
    protected function getLoggerStrategy()
    {
        return $this->logger;
    }

    /**
     * @param $jobName
     * @param $configuration
     *
     * @return $this
     */
    protected function processExport($jobName, $configuration)
    {
        $this->jobExecutor->executeJob(ProcessorRegistry::TYPE_EXPORT, $jobName, $configuration);

        return $this;
    }

    /**
     * Clone object here because it will be modified and changes should not be shared between
     *
     * @param Channel $channel
     * @param string $connector
     *
     * @return TwoWaySyncConnectorInterface
     */
    protected function getRealConnector(Channel $channel, $connector)
    {
        return clone $this->registry->getConnectorType($channel->getType(), $connector);
    }
}
