<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList\JsonApi;

use Oro\Bundle\ApiBundle\Processor\UpdateList\ProcessSynchronousOperation;
use Oro\Bundle\ApiBundle\Processor\UpdateList\UpdateListContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the synchronous Batch API operation result document for JSON:API requests.
 */
class SetSynchronousOperationResultDocument implements ProcessorInterface
{
    private const DATA_ID_META = 'dataId';
    private const INCLUDE_ID_META = 'includeId';

    private ValueNormalizer $valueNormalizer;

    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if (null !== $context->getResponseDocumentBuilder()) {
            // the result document will be set via the response document builder
            return;
        }

        $resultData = $context->getResult();
        if (!\is_array($resultData) || !\array_key_exists(ProcessSynchronousOperation::PRIMARY_DATA, $resultData)) {
            // the result document is already set
            return;
        }

        $responseData = $resultData[ProcessSynchronousOperation::PRIMARY_DATA];
        $primaryRequestIdMap = $this->getPrimaryRequestIdMap(
            $resultData[ProcessSynchronousOperation::PRIMARY_ENTITIES]
        );
        foreach ($responseData[JsonApiDoc::DATA] as $key => $entity) {
            $requestId = $primaryRequestIdMap[$entity[JsonApiDoc::ID]] ?? null;
            if (null !== $requestId) {
                $responseData[JsonApiDoc::DATA][$key][JsonApiDoc::META][self::DATA_ID_META] = $requestId;
            }
        }
        $this->sortPrimaryEntities($responseData, $resultData[ProcessSynchronousOperation::PRIMARY_ENTITIES]);

        if (\array_key_exists(ProcessSynchronousOperation::INCLUDED_DATA, $resultData)) {
            $includedData = $this->getIncludedData($resultData[ProcessSynchronousOperation::INCLUDED_DATA]);
            $includedRequestIdMap = $this->getIncludedRequestIdMap(
                $resultData[ProcessSynchronousOperation::INCLUDED_ENTITIES],
                $context->getRequestType()
            );
            foreach ($includedData as $key => $entity) {
                $requestId = $includedRequestIdMap[$entity[JsonApiDoc::TYPE]][$entity[JsonApiDoc::ID]] ?? null;
                if (null !== $requestId) {
                    $includedData[$key][JsonApiDoc::META][self::INCLUDE_ID_META] = $requestId;
                }
            }
            $responseData[JsonApiDoc::INCLUDED] = $includedData;
            $this->sortIncludedEntities(
                $responseData,
                $resultData[ProcessSynchronousOperation::INCLUDED_ENTITIES],
                $context->getRequestType()
            );
        }
        $context->setResult($responseData);
    }

    private function sortPrimaryEntities(array &$responseData, array $primaryEntities): void
    {
        $dataMap = [];
        foreach ($responseData[JsonApiDoc::DATA] as $i => $item) {
            $dataMap[$item[JsonApiDoc::ID]] = $i;
        }
        $data = [];
        foreach ($primaryEntities as [$entityId]) {
            $i = $dataMap[$entityId] ?? null;
            if (null !== $i) {
                $data[] = $responseData[JsonApiDoc::DATA][$i];
                unset($dataMap[$entityId]);
            }
        }
        foreach ($dataMap as $i) {
            $data[] = $responseData[JsonApiDoc::DATA][$i];
        }
        $responseData[JsonApiDoc::DATA] = $data;
    }

    private function sortIncludedEntities(array &$responseData, array $includedEntities, RequestType $requestType): void
    {
        $dataMap = [];
        foreach ($responseData[JsonApiDoc::INCLUDED] as $i => $item) {
            $dataMap[$item[JsonApiDoc::TYPE] . '::' . $item[JsonApiDoc::ID]] = $i;
        }
        $data = [];
        foreach ($includedEntities as [$entityClass, $entityId]) {
            $key = $this->convertToEntityType($entityClass, $requestType) . '::' . $entityId;
            $i = $dataMap[$key] ?? null;
            if (null !== $i) {
                $data[] = $responseData[JsonApiDoc::INCLUDED][$i];
                unset($dataMap[$key]);
            }
        }
        foreach ($dataMap as $i) {
            $data[] = $responseData[JsonApiDoc::INCLUDED][$i];
        }
        $responseData[JsonApiDoc::INCLUDED] = $data;
    }

    private function getPrimaryRequestIdMap(array $primaryEntities): array
    {
        $result = [];
        foreach ($primaryEntities as [$entityId, $requestId]) {
            $result[(string)$entityId] = $requestId;
        }

        return $result;
    }

    private function getIncludedData(array $includedData): array
    {
        $result = [];
        foreach ($includedData as $item) {
            $result[] = $item[JsonApiDoc::DATA];
        }

        return array_merge(...$result);
    }

    private function getIncludedRequestIdMap(array $includedEntities, RequestType $requestType): array
    {
        $result = [];
        foreach ($includedEntities as [$entityClass, $entityId, $requestId]) {
            $result[$this->convertToEntityType($entityClass, $requestType)][(string)$entityId] = $requestId;
        }

        return $result;
    }

    private function convertToEntityType(string $entityClass, RequestType $requestType): string
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType
        );
    }
}
