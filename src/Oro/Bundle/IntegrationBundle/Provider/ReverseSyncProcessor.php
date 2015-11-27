<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Event\SyncEvent;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ReverseSyncProcessor extends AbstractSyncProcessor
{
    /**
     * Process channel synchronization
     *
     * @param Integration $integration Integration object
     * @param string      $connector   Connector name
     * @param array       $parameters  Connector additional parameters
     *
     * @return bool
     */
    public function process(Integration $integration, $connector, array $parameters)
    {
        if (!$integration->isEnabled()) {
            return false;
        }

        try {
            $this->logger->info(sprintf('Start processing "%s" connector', $connector));

            $realConnector = $this->getRealConnector($integration, $connector);

            if (!($realConnector instanceof TwoWaySyncConnectorInterface)) {
                throw new LogicException('This connector does not support reverse sync.');
            }

        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            return false;
        }

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

        return $this->processExport($realConnector->getExportJobName(), $configuration);
    }

    /**
     * @param string $jobName
     * @param array  $configuration
     *
     * @return bool
     */
    protected function processExport($jobName, array $configuration)
    {
        $event = new SyncEvent($jobName, $configuration);
        $this->eventDispatcher->dispatch(SyncEvent::SYNC_BEFORE, $event);
        $configuration = $event->getConfiguration();

        $jobResult = $this->jobExecutor->executeJob(ProcessorRegistry::TYPE_EXPORT, $jobName, $configuration);

        $this->eventDispatcher->dispatch(SyncEvent::SYNC_AFTER, new SyncEvent($jobName, $configuration, $jobResult));

        /** @var ContextInterface $contexts */
        $context = $jobResult->getContext();
        $errors  = [];
        if ($context) {
            $errors = $context->getErrors();
        }

        $exceptions = $jobResult->getFailureExceptions();
        $isSuccess  = $jobResult->isSuccessful() && empty($exceptions);

        $message = $this->formatResultMessage($context);
        $this->logger->info($message);

        if ($isSuccess) {
            if ($errors) {
                $warningsText = 'Some entities were skipped due to warnings:' . PHP_EOL;
                $warningsText .= implode($errors, PHP_EOL);
                $this->logger->warning($warningsText);
            }
        } else {
            $this->logger->error('Errors were occurred:');
            $exceptions = implode(PHP_EOL, $exceptions);

            $this->logger->error($exceptions);
        }

        return $isSuccess;
    }
}
