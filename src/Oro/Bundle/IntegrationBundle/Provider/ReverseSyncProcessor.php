<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\Debug;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

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
        $this->processChannelConnector($channel, $connector, $parameters, false);

        return $this;
    }

    /**
     * Get logger strategy
     *
     * @return LoggerStrategy
     */
    public function getLoggerStrategy()
    {
        return $this->logger;
    }

    /**
     * Process channel connector
     *
     * @param Channel $channel    Channel object
     * @param string  $connector  Connector name
     * @param array   $parameters Connector additional parameters
     * @param boolean $saveStatus Do we need to save new status to bd
     */
    protected function processChannelConnector(Channel $channel, $connector, array $parameters, $saveStatus = true)
    {
        try {
            $this->logger->info(sprintf('Start processing "%s" connector', $connector));

            $realConnector = $this->getRealConnector($channel, $connector);

            if (!($realConnector instanceof TwoWaySyncConnectorInterface)) {
                throw new \Exception('This connector doesn`t support two-way sync.');
            }

        } catch (\Exception $e) {
            // log and continue
            $this->logger->error($e->getMessage());
            $status = new Status();
            $status->setCode(Status::STATUS_FAILED)
                ->setMessage($e->getMessage())
                ->setConnector($connector);

            $this->em->getRepository('OroIntegrationBundle:Channel')
                ->addStatus($channel, $status);
            return;
        }

        $jobName = $realConnector->getTwoWayJobName();

        $configuration = [
            ProcessorRegistry::TYPE_EXPORT =>
                array_merge(
                    [
                        'entityName'     => $realConnector->getImportEntityFQCN(),
                        'channel'        => $channel->getId()
                    ],
                    $parameters
                ),
        ];

        $this->processExport($connector, $jobName, $configuration, $channel, $saveStatus);
    }

    /**
     * @param string  $connector
     * @param string  $jobName
     * @param array   $configuration
     * @param Channel $channel
     * @param boolean $saveStatus
     */
    protected function processExport($connector, $jobName, $configuration, Channel $channel, $saveStatus)
    {
        $this->jobExecutor->executeJob(ProcessorRegistry::TYPE_EXPORT, $jobName, $configuration);
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
