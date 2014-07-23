<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ReverseSyncProcessor
{
    /** @var ProcessorRegistry */
    protected $processorRegistry;

    /** @var Executor */
    protected $jobExecutor;

    /** @var TypesRegistry */
    protected $registry;

    /** @var LoggerStrategy */
    protected $logger;

    /**
     * @param ProcessorRegistry $processorRegistry
     * @param Executor          $jobExecutor
     * @param TypesRegistry     $registry
     * @param LoggerStrategy    $logger
     */
    public function __construct(
        ProcessorRegistry $processorRegistry,
        Executor $jobExecutor,
        TypesRegistry $registry,
        LoggerStrategy $logger
    ) {
        $this->processorRegistry = $processorRegistry;
        $this->jobExecutor       = $jobExecutor;
        $this->registry          = $registry;
        $this->logger            = $logger;
    }

    /**
     * Process channel synchronization
     *
     * @param Integration $integration Integration object
     * @param string      $connector   Connector name
     * @param array       $parameters  Connector additional parameters
     *
     * @return $this
     */
    public function process(Integration $integration, $connector, array $parameters)
    {
        if (!$integration->getEnabled()) {
            return $this;
        }

        try {
            $this->logger->info(sprintf('Start processing "%s" connector', $connector));

            $realConnector = $this->getRealConnector($integration, $connector);

            if (!($realConnector instanceof TwoWaySyncConnectorInterface)) {
                throw new LogicException('This connector doesn`t support two-way sync.');
            }

        } catch (\Exception $e) {
            return $this->logger->error($e->getMessage());
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

        $this->processExport($realConnector->getExportJobName(), $configuration);

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
     * @param $jobName
     * @param $configuration
     *
     * @return $this
     */
    protected function processExport($jobName, $configuration)
    {
        $jobResult = $this->jobExecutor->executeJob(ProcessorRegistry::TYPE_EXPORT, $jobName, $configuration);

        $context = $jobResult->getContext();

        $counts = [];
        if ($context) {
            $counts['process'] = $counts['warnings'] = 0;
            $counts['read']    = $context->getReadCount();
            $counts['process'] += $counts['add'] = $context->getAddCount();
            $counts['process'] += $counts['update'] = $context->getUpdateCount();
            $counts['process'] += $counts['delete'] = $context->getDeleteCount();
        }

        $exceptions = $jobResult->getFailureExceptions();
        $isSuccess  = $jobResult->isSuccessful() && empty($exceptions);

        if (!$isSuccess) {
            $this->logger->error('Errors were occurred:');
            $exceptions = implode(PHP_EOL, $exceptions);
            $this->logger->error(
                $exceptions,
                ['exceptions' => $jobResult->getFailureExceptions()]
            );
        } else {
            if ($context->getErrors()) {
                $this->logger->warning('Some entities were skipped due to warnings:');
                foreach ($context->getErrors() as $error) {
                    $this->logger->warning($error);
                }
            }

            $message = sprintf(
                "Stats: read [%d], process [%d], updated [%d], added [%d], delete [%d], invalid entities: [%d]",
                $counts['read'],
                $counts['process'],
                $counts['update'],
                $counts['add'],
                $counts['delete'],
                $context->getErrorEntriesCount()
            );
            $this->logger->info($message);
        }

        return $this;
    }

    /**
     * Clone object here because it will be modified and changes should not be shared between
     *
     * @param Integration $integration
     * @param string      $connector
     *
     * @return TwoWaySyncConnectorInterface
     */
    protected function getRealConnector(Integration $integration, $connector)
    {
        return clone $this->registry->getConnectorType($integration->getType(), $connector);
    }
}
