<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
        $isSuccess = true;

        $connectors = $integration->getConnectors();

        if ($callback) {
            $connectors = array_filter($connectors, $callback);
        }

        foreach ((array)$connectors as $connector) {
            try {
                $result = $this->processIntegrationConnector(
                    $integration,
                    $connector,
                    $parameters
                );

                $isSuccess = $isSuccess && $result;
            } catch (\Exception $e) {
                $isSuccess = false;

                $this->logger->critical($e->getMessage());
            }
        }

        return $isSuccess;
    }

    /**
     * Process integration connector
     *
     * @param Integration $integration Integration object
     * @param string      $connector   Connector name
     * @param array       $parameters  Connector additional parameters
     *
     * @return boolean
     */
    protected function processIntegrationConnector(
        Integration $integration,
        $connector,
        array $parameters = []
    ) {
        if (!$integration->isEnabled()) {
            return false;
        }

        try {
            $this->logger->info(sprintf('Start processing "%s" connector', $connector));
            // Clone object here because it will be modified and changes should not be shared between
            $realConnector = clone $this->registry->getConnectorType($integration->getType(), $connector);

            $jobName          = $realConnector->getImportJobName();
            $processorAliases = $this->processorRegistry->getProcessorAliasesByEntity(
                ProcessorRegistry::TYPE_IMPORT,
                $realConnector->getImportEntityFQCN()
            );
        } catch (\Exception $e) {
            // log and continue
            $this->logger->error($e->getMessage());
            $status = new Status();
            $status
                ->setCode(Status::STATUS_FAILED)
                ->setMessage($e->getMessage())
                ->setConnector($connector);

            $this->doctrineRegistry
                ->getRepository('OroIntegrationBundle:Channel')
                ->addStatus($integration, $status);

            return false;
        }

        $configuration = [
            ProcessorRegistry::TYPE_IMPORT =>
                array_merge(
                    [
                        'processorAlias' => reset($processorAliases),
                        'entityName'     => $realConnector->getImportEntityFQCN(),
                        'channel'        => $integration->getId(),
                        'channelType'    => $integration->getType(),
                    ],
                    $parameters
                ),
        ];

        return $this->processImport($connector, $jobName, $configuration, $integration);
    }

    /**
     * @param string      $connector
     * @param string      $jobName
     * @param array       $configuration
     * @param Integration $integration
     *
     * @return boolean
     */
    protected function processImport($connector, $jobName, $configuration, Integration $integration)
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

        $status = new Status();
        $status->setConnector($connector);
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

        $this->doctrineRegistry
            ->getRepository('OroIntegrationBundle:Channel')
            ->addStatus($integration, $status);

        if ($integration->getEditMode() < Integration::EDIT_MODE_RESTRICTED) {
            $integration->setEditMode(Integration::EDIT_MODE_RESTRICTED);
        }

        return $isSuccess;
    }
}
