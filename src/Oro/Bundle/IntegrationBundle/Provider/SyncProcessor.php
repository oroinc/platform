<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;

class SyncProcessor
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
     * By default, if $connector is empty, will process all connectors of given channel
     *
     * @param Channel $channel    Channel object
     * @param string  $connector  Connector name
     * @param array   $parameters Connector additional parameters
     */
    public function process(Channel $channel, $connector = '', array $parameters = [])
    {
        if ($connector) {
            $this->processChannelConnector($channel, $connector, $parameters, false);
        } else {
            $connectors = $channel->getConnectors();
            foreach ((array)$connectors as $connector) {
                $this->processChannelConnector($channel, $connector, $parameters);
            }
        }
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
    protected function processChannelConnector(Channel $channel, $connector, array $parameters = [], $saveStatus = true)
    {
        try {
            $this->logger->info(sprintf('Start processing "%s" connector', $connector));
            /**
             * Clone object here because it will be modified and changes should not be shared between
             */
            $realConnector = clone $this->registry->getConnectorType($channel->getType(), $connector);
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
        $jobName = $realConnector->getImportJobName();

        $processorAliases = $this->processorRegistry->getProcessorAliasesByEntity(
            ProcessorRegistry::TYPE_IMPORT,
            $realConnector->getImportEntityFQCN()
        );
        $configuration    = [
            ProcessorRegistry::TYPE_IMPORT =>
                array_merge(
                    [
                        'processorAlias' => reset($processorAliases),
                        'entityName'     => $realConnector->getImportEntityFQCN(),
                        'channel'        => $channel->getId()
                    ],
                    $parameters
                ),
        ];
        $this->processImport($connector, $jobName, $configuration, $channel, $saveStatus);
    }

    /**
     * @param string  $connector
     * @param string  $jobName
     * @param array   $configuration
     * @param Channel $channel
     * @param boolean $saveStatus
     */
    protected function processImport($connector, $jobName, $configuration, Channel $channel, $saveStatus)
    {
        $jobResult = $this->jobExecutor->executeJob(ProcessorRegistry::TYPE_IMPORT, $jobName, $configuration);

        /** @var ContextInterface $contexts */
        $context = $jobResult->getContext();

        $counts           = [];
        $counts['errors'] = count($jobResult->getFailureExceptions());
        if ($context) {
            $counts['process'] = 0;
            $counts['read']    = $context->getReadCount();
            $counts['process'] += $counts['add'] = $context->getAddCount();
            $counts['process'] += $counts['replace'] = $context->getReplaceCount();
            $counts['process'] += $counts['update'] = $context->getUpdateCount();
            $counts['process'] += $counts['delete'] = $context->getDeleteCount();
            $counts['process'] -= $counts['error_entries'] = $context->getErrorEntriesCount();
            $counts['errors'] += count($context->getErrors());
        }

        $errorsAndExceptions = [];
        if (!empty($counts['errors'])) {
            $errorsAndExceptions = array_slice(
                array_merge(
                    $jobResult->getFailureExceptions(),
                    $context ? $context->getErrors() : []
                ),
                0,
                100
            );
        }
        $isSuccess = $jobResult->isSuccessful() && empty($counts['errors']);

        $status = new Status();
        $status->setConnector($connector);
        if (!$isSuccess) {
            $this->logger->error('Errors were occurred:');
            $exceptions = implode(PHP_EOL, $errorsAndExceptions);
            $this->logger->error(
                $exceptions,
                ['exceptions' => $jobResult->getFailureExceptions()]
            );
            $status->setCode(Status::STATUS_FAILED)->setMessage($exceptions);
        } else {
            $message = sprintf(
                "Stats: read [%d], process [%d], updated [%d], added [%d], delete [%d]",
                $counts['read'],
                $counts['process'],
                $counts['update'],
                $counts['add'],
                $counts['delete']
            );
            $this->logger->info($message);

            $status->setCode(Status::STATUS_COMPLETED)->setMessage($message);
        }
        if ($saveStatus) {
            $this->em->getRepository('OroIntegrationBundle:Channel')
                ->addStatus($channel, $status);
        }
    }
}
