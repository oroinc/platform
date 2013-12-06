<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;

class SyncProcessor implements SyncProcessorInterface
{
    const DEFAULT_BATCH_SIZE = 15;

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
     * {@inheritdoc}
     */
    public function process(Channel $channel, $isValidationOnly = false)
    {
        /** @var Channel $channel */
        $connectors = $channel->getConnectors();

        foreach ($connectors as $connector) {
            try {
                $realConnector = $this->registry->getConnectorType($channel->getType(), $connector);
                $realTransport = $this->registry
                    ->getTransportTypeBySettingEntity($channel->getTransport(), $channel->getType());
            } catch (\Exception $e) {
                // log and continue
                $this->logger->error($e->getMessage());
                continue;
            }
            $mode    = $isValidationOnly ? ProcessorRegistry::TYPE_IMPORT_VALIDATION : ProcessorRegistry::TYPE_IMPORT;
            $jobName = $realConnector->getImportJobName($isValidationOnly);

            $processorAliases = $this->processorRegistry->getProcessorAliasesByEntity(
                ProcessorRegistry::TYPE_IMPORT,
                $realConnector->getImportEntityFQCN()
            );
            $configuration    = [
                $mode => [
                    'processorAlias' => reset($processorAliases),
                    'entityName'     => $realConnector->getImportEntityFQCN(),
                    'channel'        => $channel,
                    'transport'      => $realTransport,
                    'batchSize'      => self::DEFAULT_BATCH_SIZE
                ],
            ];
            $this->processImport($mode, $jobName, $configuration, $channel);
        }
    }

    /**
     * @param string    $mode
     * @param Transport $transport
     */
    protected function saveLastSyncDate($mode, Transport $transport)
    {
        if ($mode != ProcessorRegistry::TYPE_IMPORT) {
            return;
        }

        // merge to uow due to object has changed hash after serialization/deserialization in job context
        // {@link} http://doctrine-orm.readthedocs.org/en/2.0.x/reference/working-with-objects.html#merging-entities
        if ($this->em->isOpen()) {
            $transport = $this->em->merge($transport);
            $transport->setLastSyncDate(new \DateTime('now', new \DateTimeZone('UTC')));
            $this->em->persist($transport);
            $this->em->flush();
        }
    }

    /**
     * @param string  $mode import or validation (dry run, readonly)
     * @param string  $jobName
     * @param array   $configuration
     * @param Channel $channel
     *
     * @return array
     */
    public function processImport($mode, $jobName, $configuration, Channel $channel)
    {
        $jobResult = $this->jobExecutor->executeJob($mode, $jobName, $configuration);

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
        if (!$isSuccess) {
            $this->logger->error('Errors were occurred:');
            $this->logger->error(
                implode(PHP_EOL, $errorsAndExceptions),
                ['exceptions' => $jobResult->getFailureExceptions()]
            );
        } else {
            /** @TODO FIXME save date for each connector */
            // save last sync datetime
            $this->saveLastSyncDate($mode, $channel->getTransport());
            $this->logger->info(
                sprintf(
                    "Stats: read [%d], process [%d], updated [%d], added [%d], delete [%d]",
                    $counts['read'],
                    $counts['process'],
                    $counts['update'],
                    $counts['add'],
                    $counts['delete']
                )
            );
        }
    }
}
