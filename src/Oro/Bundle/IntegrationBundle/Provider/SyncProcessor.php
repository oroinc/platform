<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
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

    /**
     * @param EntityManager     $em
     * @param ProcessorRegistry $processorRegistry
     * @param Executor          $jobExecutor
     * @param TypesRegistry     $registry
     */
    public function __construct(
        EntityManager $em,
        ProcessorRegistry $processorRegistry,
        Executor $jobExecutor,
        TypesRegistry $registry
    ) {
        $this->em                = $em;
        $this->processorRegistry = $processorRegistry;
        $this->jobExecutor       = $jobExecutor;
        $this->registry          = $registry;
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
            } catch (\Exception $e) {
                // log and continue
                /** @TODO log here */
                continue;
            }
            $mode    = $isValidationOnly ? ProcessorRegistry::TYPE_IMPORT_VALIDATION : ProcessorRegistry::TYPE_IMPORT;
            $jobName = $realConnector->getImportJobName($isValidationOnly);

            $processorAliases = $this->processorRegistry->getProcessorAliasesByEntity(
                ProcessorRegistry::TYPE_IMPORT,
                $realConnector->getImportEntityFQCN()
            );
            $realTransport    = $this->registry
                ->getTransportTypeBySettingEntity($channel->getTransport(), $channel->getType());
            /** @var ConnectorInterface $realConnector */
            $realConnector->configure($realTransport, $channel->getTransport());

            $configuration = [
                $mode => [
                    'processorAlias' => reset($processorAliases),
                    'entityName'     => $realConnector->getImportEntityFQCN(),
                    'channel'        => $channel,
                    'batchSize'      => self::DEFAULT_BATCH_SIZE,
                    'connector'      => $realConnector
                ],
            ];
            $this->processImport($mode, $jobName, $configuration);
            // save last sync datetime
            $this->saveLastSyncDate($mode, $channel->getTransport());
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
     * @param string $mode import or validation (dry run, readonly)
     * @param string $jobName
     * @param array  $configuration
     *
     * @return array
     */
    public function processImport($mode, $jobName, $configuration)
    {
        $jobResult = $this->jobExecutor->executeJob($mode, $jobName, $configuration);

        if ($jobResult->isSuccessful()) {
            $message = 'oro_importexport.import.import_success';
        } else {
            $message = 'oro_importexport.import.import_error';
        }

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
        /** @TODO log here */

        return [
            'success'    => $jobResult->isSuccessful() && empty($counts['errors']),
            'message'    => $message,
            'exceptions' => $jobResult->getFailureExceptions(),
            'counts'     => $counts,
            'errors'     => $errorsAndExceptions,
        ];
    }
}
