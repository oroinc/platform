<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConnectorException;

class ReverseSyncProcessor extends AbstractSyncProcessor
{
    /** @var ManagerRegistry */
    protected $doctrineRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function setManagerRegistry(ManagerRegistry $managerRegistry)
    {
        $this->doctrineRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConnectorException
     */
    public function process(Integration $integration, $connector, array $parameters = [])
    {
        if (!$integration->isEnabled()) {
            return false;
        }

        $this->logger->info(sprintf('Start processing "%s" connector', $connector));
        $realConnector = $this->getRealConnector($integration, $connector);

        $processorAliases = $this->processorRegistry->getProcessorAliasesByEntity(
            ProcessorRegistry::TYPE_EXPORT,
            $realConnector->getImportEntityFQCN()
        );
        $configuration = [
            ProcessorRegistry::TYPE_EXPORT =>
                array_merge(
                    [
                        'entityName'     => $realConnector->getImportEntityFQCN(),
                        'processorAlias' => reset($processorAliases),
                        'channel'        => $integration->getId()
                    ],
                    $parameters
                ),
        ];
        // changes required to keep BC
        $configuration['connector']   = $realConnector;
        $configuration['integration'] = $integration;
        // changes required to keep BC

        return $this->processExport($realConnector->getExportJobName(), $configuration);
    }

    /**
     * @param string $jobName
     * @param array  $configuration
     *
     * @return bool
     *
     * @throws InvalidConnectorException
     */
    protected function processExport($jobName, array $configuration)
    {
        // changes required to keep BC
        $this->assertValidExportIntegrationConfiguration($configuration);
        $connector   = $configuration['connector'];
        $integration = $configuration['integration'];
        unset($configuration['connector'], $configuration['integration']);
        // changes required to keep BC

        $this->assertValidConnector($connector);
        /** @var TwoWaySyncConnectorInterface $connector */
        $exportJobName = $connector->getExportJobName();

        $syncBeforeEvent = $this->dispatchSyncEvent(SyncEvent::SYNC_BEFORE, $exportJobName, $configuration);

        $configuration = $syncBeforeEvent->getConfiguration();
        $jobResult = $this->jobExecutor->executeJob(ProcessorRegistry::TYPE_EXPORT, $exportJobName, $configuration);

        $this->dispatchSyncEvent(SyncEvent::SYNC_AFTER, $exportJobName, $configuration, $jobResult);

        $context = $jobResult->getContext();
        $connectorData = $errors = [];
        if ($context) {
            $connectorData = $context->getValue(ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY);
            $errors = $context->getErrors();
        }
        $exceptions = $jobResult->getFailureExceptions();
        $isSuccess = $jobResult->isSuccessful() && empty($exceptions);

        $status = $this->createConnectorStatus($connector);
        $status->setData((array)$connectorData);

        $message = $this->formatResultMessage($context);
        $this->logger->notice($message);

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

        return $isSuccess;
    }

    /**
     * Creates integration connector's status.
     *
     * @param ConnectorInterface $connector
     *
     * @return Status
     */
    protected function createConnectorStatus(ConnectorInterface $connector)
    {
        $status = new Status();
        $status->setConnector($connector->getType());

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatResultMessage(ContextInterface $context = null)
    {
        return sprintf(
            '[%s] %s',
            strtoupper(ProcessorRegistry::TYPE_EXPORT),
            parent::formatResultMessage($context)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertValidConnector(ConnectorInterface $connector)
    {
        if (!($connector instanceof TwoWaySyncConnectorInterface)) {
            throw new InvalidConnectorException('This connector does not support reverse sync.');
        }
    }

    /**
     * @param array $configuration
     *
     * @throws InvalidConfigurationException
     */
    protected function assertValidExportIntegrationConfiguration(array $configuration)
    {
        if (!isset($configuration['connector']) || !(isset($configuration['integration']))) {
            throw new InvalidConfigurationException(
                'Parameters "connector" and "integration" should be set.'
            );
        }
        if (!$configuration['connector'] instanceof ConnectorInterface) {
            throw new InvalidConnectorException(
                'Parameter "connector" has invalid type.'
            );
        }
        if (!$configuration['integration'] instanceof Integration) {
            throw new InvalidConfigurationException(
                'Parameter "integration" has invalid type.'
            );
        }
    }

    /**
     * Saves connector's status.
     *
     * @param Integration $integration
     * @param Status      $status
     *
     * @return void
     */
    protected function addConnectorStatusAndFlush(Integration $integration, Status $status)
    {
        $this->doctrineRegistry
            ->getRepository('OroIntegrationBundle:Channel')
            ->addStatusAndFlush($integration, $status);
    }
}
