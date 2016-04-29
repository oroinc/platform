<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;

class SyncProcessor extends AbstractSyncProcessor
{
    /** @var ManagerRegistry */
    protected $doctrineRegistry;

    /**
     * @param ManagerRegistry          $doctrineRegistry
     * @param ProcessorRegistry        $processorRegistry
     * @param Executor                 $jobExecutor
     * @param TypesRegistry            $registry
     * @param LoggerStrategy           $logger
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ManagerRegistry $doctrineRegistry,
        ProcessorRegistry $processorRegistry,
        Executor $jobExecutor,
        TypesRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        LoggerStrategy $logger = null
    ) {
        $this->doctrineRegistry = $doctrineRegistry;

        parent::__construct($processorRegistry, $jobExecutor, $registry, $eventDispatcher, $logger);
    }

    /**
     * Process integration synchronization
     * By default, if $connector is empty, will process all connectors of given integration
     *
     * @param Integration $integration Integration object
     * @param string      $connector   Connector name
     * @param array       $parameters  Connector additional parameters
     *
     * @return boolean
     */
    public function process(Integration $integration, $connector = null, array $parameters = [])
    {
        if (!$integration->isEnabled()) {
            $this->logger->error(
                sprintf(
                    'Integration "%s" with type "%s" is not enabled. Cannot process synchronization.',
                    $integration->getName(),
                    $integration->getType()
                )
            );
            return false;
        }

        $callback = null;

        if ($connector) {
            $callback = function ($integrationConnector) use ($connector) {
                return $integrationConnector === $connector;
            };
        }

        return $this->processConnectors($integration, $parameters, $callback);
    }

    /**
     * Process integration synchronization
     * By default, if $connector is empty, will process all connectors of given integration
     *
     * @param Integration $integration Integration object
     * @param array       $parameters  Connector additional parameters
     * @param callable    $callback    Callback to filter connectors
     *
     * @return boolean
     */
    protected function processConnectors(Integration $integration, array $parameters = [], callable $callback = null)
    {
        $connectors = $this->getConnectorsToProcess($integration, $callback);

        $isSuccess = true;

        $processedConnectorStatuses = [];
        foreach ($connectors as $connector) {
            try {
                if (!$this->isConnectorAllowed($connector, $integration, $processedConnectorStatuses)) {
                    continue;
                }
                $status = $this->processIntegrationConnector($integration, $connector, $parameters);

                $isSuccess = $isSuccess && $this->isIntegrationConnectorProcessSuccess($status);
                $processedConnectorStatuses[$connector->getType()] = $status;
            } catch (\Exception $exception) {
                $isSuccess = false;
                $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
            }
        }

        return $isSuccess;
    }

    /**
     * @param Integration $integration
     * @param callable|null $callback
     * @return ConnectorInterface[]
     */
    protected function getConnectorsToProcess(Integration $integration, callable $callback = null)
    {
        $connectors = $this->loadConnectorsToProcess($integration, $callback);
        $orderedConnectors = $this->getSortedConnectors($connectors);

        return $orderedConnectors;
    }

    /**
     * @param Integration $integration
     * @param callable $callback
     * @return ConnectorInterface[]
     */
    protected function loadConnectorsToProcess($integration, callable $callback = null)
    {
        $connectorTypes = $this->getTypesOfConnectorsToProcess($integration, $callback);

        /** @var ConnectorInterface[] $realConnectors */
        $connectors = array_map(function ($connectorType) use ($integration) {
            return $this->getRealConnector($integration, $connectorType);
        }, $connectorTypes);

        return $connectors;
    }

    /**
     * @param Integration $integration
     * @param callable $callback
     *
     * @return string[]
     */
    protected function getTypesOfConnectorsToProcess(Integration $integration, callable $callback = null)
    {
        $connectors = $integration->getConnectors();

        if ($callback) {
            $connectors = array_filter($connectors, $callback);
        }

        return $connectors;
    }

    /**
     * @param ConnectorInterface[] $connectors
     * @return ConnectorInterface[]
     */
    protected function getSortedConnectors(array $connectors)
    {
        $sortCallback = function ($connector) {
            return $connector instanceof OrderedConnectorInterface
                ? $connector->getOrder()
                : OrderedConnectorInterface::DEFAULT_ORDER;
        };
        ArrayUtil::sortBy($connectors, false, $sortCallback);

        return $connectors;
    }

    /**
     * Checks whether connector is allowed to process. Logs information if connector is not allowed.
     *
     * @param ConnectorInterface $connector
     * @param Integration $integration
     * @param Status[] $processedConnectorStatuses
     * @return bool
     */
    protected function isConnectorAllowed(
        ConnectorInterface $connector,
        Integration $integration,
        array $processedConnectorStatuses
    ) {
        if ($connector instanceof AllowedConnectorInterface
            && !$connector->isAllowed($integration, $processedConnectorStatuses)
        ) {
            $this->logger->info(
                sprintf(
                    'Connector with type "%s" is not allowed and it\'s processing is skipped.',
                    $connector->getType()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Process integration connector
     *
     * @param Integration        $integration Integration object
     * @param ConnectorInterface $connector   Connector object
     * @param array              $parameters  Connector additional parameters
     *
     * @return Status
     */
    protected function processIntegrationConnector(
        Integration $integration,
        ConnectorInterface $connector,
        array $parameters = []
    ) {
        try {
            $this->logger->info(sprintf('Start processing "%s" connector', $connector->getType()));

            $jobName          = $connector->getImportJobName();
            $processorAliases = $this->processorRegistry->getProcessorAliasesByEntity(
                ProcessorRegistry::TYPE_IMPORT,
                $connector->getImportEntityFQCN()
            );
        } catch (\Exception $exception) {
            // log and continue
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            $status = $this->createConnectorStatus($connector)
                ->setCode(Status::STATUS_FAILED)
                ->setMessage($exception->getMessage());
            $this->addConnectorStatusAndFlush($integration, $status);
            return $status;
        }

        $configuration = [
            ProcessorRegistry::TYPE_IMPORT =>
                array_merge(
                    [
                        'processorAlias' => reset($processorAliases),
                        'entityName'     => $connector->getImportEntityFQCN(),
                        'channel'        => $integration->getId(),
                        'channelType'    => $integration->getType(),
                    ],
                    $parameters
                ),
        ];

        return $this->processImport($connector, $jobName, $configuration, $integration);
    }

    /**
     * @param ConnectorInterface $connector
     * @param string             $jobName
     * @param array              $configuration
     * @param Integration        $integration
     *
     * @return Status
     */
    protected function processImport(ConnectorInterface $connector, $jobName, $configuration, Integration $integration)
    {
        $event = new SyncEvent($jobName, $configuration);
        $this->eventDispatcher->dispatch(SyncEvent::SYNC_BEFORE, $event);
        $configuration = $event->getConfiguration();

        $jobResult = $this->jobExecutor->executeJob(ProcessorRegistry::TYPE_IMPORT, $jobName, $configuration);

        $this->eventDispatcher->dispatch(SyncEvent::SYNC_AFTER, new SyncEvent($jobName, $configuration, $jobResult));

        /** @var ContextInterface $contexts */
        $context = $jobResult->getContext();

        $connectorData = $errors = [];
        if ($context) {
            $connectorData = $context->getValue(ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY);
            $errors        = $context->getErrors();
        }
        $exceptions = $jobResult->getFailureExceptions();
        $isSuccess  = $jobResult->isSuccessful() && empty($exceptions);

        $status = $this->createConnectorStatus($connector);
        $status->setData(is_array($connectorData) ? $connectorData : []);

        $message = $this->formatResultMessage($context);
        $this->logger->info($message);

        if ($isSuccess) {
            if ($errors) {
                $warningsText = 'Some entities were skipped due to warnings:' . PHP_EOL;
                $warningsText .= implode($errors, PHP_EOL);
                $this->logger->warning($warningsText);

                $message .= PHP_EOL . $warningsText;
            }

            $status->setCode(Status::STATUS_COMPLETED)->setMessage($message);
        } else {
            $this->logger->error('Errors were occurred:');
            $exceptions = implode(PHP_EOL, $exceptions);

            $this->logger->error($exceptions);
            $status->setCode(Status::STATUS_FAILED)->setMessage($exceptions);
        }

        $this->addConnectorStatusAndFlush($integration, $status);

        return $status;
    }

    /**
     * Creates status of connector of integration.
     *
     * @param ConnectorInterface $connector
     * @return Status
     */
    protected function createConnectorStatus(ConnectorInterface $connector)
    {
        $status = new Status();
        $status->setConnector($connector->getType());

        return $status;
    }

    /**
     * Adds status of connector to integration and flush changes.
     *
     * @param Integration $integration
     * @param Status $status
     */
    protected function addConnectorStatusAndFlush(Integration $integration, Status $status)
    {
        $this->doctrineRegistry
            ->getRepository('OroIntegrationBundle:Channel')
            ->addStatusAndFlush($integration, $status);
    }
}
