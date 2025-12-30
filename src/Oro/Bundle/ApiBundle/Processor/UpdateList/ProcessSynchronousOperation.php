<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Async\Topic\DeleteAsyncOperationTopic;
use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\SyncProcessingLimitProvider;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Processes synchronous Batch API operation.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProcessSynchronousOperation implements ProcessorInterface
{
    public const PRIMARY_ENTITIES = 'primary_entities';
    public const INCLUDED_ENTITIES = 'included_entities';
    public const PRIMARY_DATA = 'primary_data';
    public const INCLUDED_DATA = 'included_data';

    private const CHUNK_LIMIT_EXCEEDED_ERROR_MESSAGE = 'The limit for the maximum number of chunks exceeded';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly int $waitTimeout,
        private readonly ErrorManager $errorManager,
        private readonly FileManager $fileManager,
        private readonly SyncProcessingLimitProvider $syncProcessingLimitProvider,
        private readonly MessageProducerInterface $producer,
        private readonly ActionProcessorBagInterface $processorBag,
        private readonly FilterNamesRegistry $filterNamesRegistry
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        $operationId = $context->getOperationId();
        if (null === $operationId) {
            return;
        }

        $status = $this->waitForAsyncOperationFinished($operationId);
        if (null === $status) {
            // wait timeout exceed
            return;
        }

        try {
            if (AsyncOperation::STATUS_SUCCESS === $status) {
                $context->setResult($this->getResultData($context, $operationId));
                $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
            } else {
                $this->addErrorsToContext($context, $operationId);
            }
        } finally {
            if ($context->isProcessByMessageQueue()) {
                $this->producer->send(DeleteAsyncOperationTopic::getName(), ['operationId' => $operationId]);
                if ($this->producer instanceof BufferedMessageProducer && $this->producer->isBufferingEnabled()) {
                    $this->producer->flushBuffer();
                }
            }
        }
    }

    private function waitForAsyncOperationFinished(int $operationId): ?string
    {
        $query = $this->doctrineHelper->createQueryBuilder(AsyncOperation::class, 'e')
            ->select('e.status')
            ->where('e.id = :id')
            ->setParameter('id', $operationId)
            ->getQuery();

        $startTime = microtime(true);
        while ((int)round(microtime(true) - $startTime) <= $this->waitTimeout) {
            $status = $this->getAsyncOperationStatus($query);
            if (null === $status) {
                throw new RuntimeException(\sprintf(
                    'An operation for synchronous processing was not found. The operation ID: %d.',
                    $operationId
                ));
            }
            if (AsyncOperation::STATUS_CANCELLED === $status) {
                throw new RuntimeException(\sprintf(
                    'An operation for synchronous processing was cancelled. The operation ID: %d.',
                    $operationId
                ));
            }
            if (AsyncOperation::STATUS_FAILED === $status || AsyncOperation::STATUS_SUCCESS === $status) {
                return $status;
            }
            usleep(300);
        }

        return null;
    }

    private function getAsyncOperationStatus(Query $query): ?string
    {
        $rows = $query->getArrayResult();
        if (!$rows) {
            return null;
        }

        return $rows[0]['status'];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getResultData(UpdateListContext $context, int $operationId): array
    {
        $affectedEntities = $this->getAffectedEntities($operationId);
        if (!$affectedEntities) {
            return [];
        }

        $primaryEntities = $affectedEntities['primary'] ?? [];
        if (!$primaryEntities) {
            return [];
        }

        $primaryEntityIds = [];
        foreach ($primaryEntities as [$entityId]) {
            $primaryEntityIds[] = $entityId;
        }
        $this->processNormalizationResult(
            $this->normalizeEntities($context, $context->getClassName(), $primaryEntityIds),
            $context
        );
        if ($context->hasErrors()) {
            return [];
        }

        $result = [
            self::PRIMARY_ENTITIES => $primaryEntities,
            self::PRIMARY_DATA => $context->getResult()
        ];

        $includedEntities = $affectedEntities['included'] ?? [];
        if ($includedEntities) {
            $includedDataPerEntityType = [];
            $includedEntityMap = [];
            foreach ($includedEntities as [$entityClass, $entityId]) {
                $includedEntityMap[$entityClass][] = $entityId;
            }
            foreach ($includedEntityMap as $entityClass => $entityIds) {
                $includedTargetContext = $this->normalizeEntities($context, $entityClass, $entityIds);
                if ($includedTargetContext->hasErrors()) {
                    $this->processNormalizationErrors($includedTargetContext, $context);
                } else {
                    $includedDataPerEntityType[] = $includedTargetContext->getResult();
                }
            }
            if ($includedDataPerEntityType) {
                $result[self::INCLUDED_ENTITIES] = $includedEntities;
                $result[self::INCLUDED_DATA] = $includedDataPerEntityType;
            }
        }

        return $result;
    }

    private function getAffectedEntities(int $operationId): ?array
    {
        $rows = $this->doctrineHelper->createQueryBuilder(AsyncOperation::class, 'e')
            ->select('e.affectedEntities')
            ->where('e.id = :id')
            ->setParameter('id', $operationId)
            ->getQuery()
            ->getArrayResult();
        if (!$rows) {
            return null;
        }

        return $rows[0]['affectedEntities'];
    }

    private function normalizeEntities(
        UpdateListContext $context,
        string $entityClass,
        array $entityIds
    ): GetListContext {
        $targetProcessor = $this->processorBag->getProcessor(ApiAction::GET_LIST);
        /** @var GetListContext $targetContext */
        $targetContext = $targetProcessor->createContext();
        $targetContext->setVersion($context->getVersion());
        $targetContext->getRequestType()->set($context->getRequestType());
        $targetContext->setRequestHeaders($context->getRequestHeaders());
        $targetContext->setSharedData($context->getSharedData());
        $targetContext->setHateoas($context->isHateoasEnabled());
        $targetContext->setClassName($entityClass);
        $targetContext->skipGroup(ApiActionGroup::SECURITY_CHECK);
        $targetContext->skipGroup(ApiActionGroup::DATA_SECURITY_CHECK);
        $targetContext->setSoftErrorsHandling(true);

        $targetContext->setInitializeCriteriaCallback(function (Criteria $criteria) use ($entityIds, $context) {
            self::applyEntityIdRestriction($criteria, $entityIds, $context->getMetadata());
        });

        $pageSizeFilterName = $this->filterNamesRegistry->getFilterNames($context->getRequestType())
            ->getPageSizeFilterName();
        $targetContext->getFilterValues()->set($pageSizeFilterName, new FilterValue($pageSizeFilterName, -1));

        $targetProcessor->process($targetContext);

        return $targetContext;
    }

    private static function applyEntityIdRestriction(
        Criteria $criteria,
        array $entityIds,
        EntityIdMetadataInterface $metadata
    ): void {
        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (\count($idFieldNames) === 1) {
            // single identifier
            $criteria->andWhere(Criteria::expr()->in($metadata->getPropertyPath(reset($idFieldNames)), $entityIds));
        } else {
            // composite identifier
            // will be fixed in BAP-15595
            throw new RuntimeException(\sprintf(
                'A composite identifiers is not implemented yet. Entity: %s.',
                $metadata->getClassName()
            ));
        }
    }

    private function processNormalizationResult(GetListContext $targetContext, UpdateListContext $context): void
    {
        if ($targetContext->hasErrors()) {
            $this->processNormalizationErrors($targetContext, $context);
        } else {
            $context->setConfigExtras($targetContext->getConfigExtras());
            $targetConfig = $targetContext->getConfig();
            if (null !== $targetConfig) {
                $context->setConfig($targetConfig);
            }
            $targetConfigSections = $targetContext->getConfigSections();
            foreach ($targetConfigSections as $configSection) {
                if ($targetContext->hasConfigOf($configSection)) {
                    $context->setConfigOf($configSection, $targetContext->getConfigOf($configSection));
                }
            }

            $targetMetadata = $targetContext->getMetadata();
            if (null !== $targetMetadata) {
                $context->setMetadata($targetMetadata);
            }

            $responseHeaders = $context->getResponseHeaders();
            $targetResponseHeaders = $targetContext->getResponseHeaders();
            foreach ($targetResponseHeaders as $key => $value) {
                $responseHeaders->set($key, $value);
            }

            $context->setInfoRecords($targetContext->getInfoRecords());
            $context->setResult($targetContext->getResult());
        }
    }

    private function processNormalizationErrors(GetListContext $targetContext, UpdateListContext $context): void
    {
        $errors = $targetContext->getErrors();
        foreach ($errors as $error) {
            $context->addError($error);
        }
    }

    private function addErrorsToContext(UpdateListContext $context, int $operationId): void
    {
        $batchErrors = $this->errorManager->readErrors(
            $this->fileManager,
            $operationId,
            0,
            $this->errorManager->getTotalErrorCount($this->fileManager, $operationId)
        );
        $entityClass = $context->getClassName();
        foreach ($batchErrors as $batchError) {
            $context->addError($this->createError($batchError, $entityClass));
        }
    }

    private function createError(BatchError $batchError, string $entityClass): Error
    {
        $error = Error::create(
            $this->getErrorTitle($batchError),
            $this->getErrorDetail($batchError, $entityClass)
        );
        if (null !== $batchError->getCode()) {
            $error->setCode($batchError->getCode());
        }
        if (null !== $batchError->getStatusCode()) {
            $error->setStatusCode($batchError->getStatusCode());
        }
        if (null !== $batchError->getInnerException()) {
            $error->setInnerException($batchError->getInnerException());
        }
        if (null !== $batchError->getSource()) {
            $error->setSource($batchError->getSource());
        }

        return $error;
    }

    private function getErrorTitle(BatchError $batchError): string
    {
        $title = $batchError->getTitle();
        $detail = $batchError->getDetail();
        if ($detail && str_starts_with($detail, self::CHUNK_LIMIT_EXCEEDED_ERROR_MESSAGE)) {
            $title = Constraint::REQUEST_DATA;
        } elseif ('async operation exception' === $title) {
            $title = 'operation exception';
        }

        return $title;
    }

    private function getErrorDetail(BatchError $batchError, string $entityClass): ?string
    {
        $detail = $batchError->getDetail();
        if ($detail && str_starts_with($detail, self::CHUNK_LIMIT_EXCEEDED_ERROR_MESSAGE)) {
            $prefix = substr($detail, \strlen(self::CHUNK_LIMIT_EXCEEDED_ERROR_MESSAGE));
            $detail = 'The data limit for the synchronous operation exceeded' . $prefix;
            if ('.' === $prefix) {
                $detail .= \sprintf(
                    ' The maximum number of records that can be processed by the synchronous operation is %d.',
                    $this->syncProcessingLimitProvider->getLimit($entityClass)
                );
            } elseif (' for the section "included".' === $prefix) {
                $detail .= \sprintf(
                    ' The maximum number of included records that can be processed by the synchronous operation is %d.',
                    $this->syncProcessingLimitProvider->getIncludedDataLimit($entityClass)
                );
            }
        }

        return $detail;
    }
}
