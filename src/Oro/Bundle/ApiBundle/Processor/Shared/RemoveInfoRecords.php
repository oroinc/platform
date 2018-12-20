<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The base class for processors that remove records with key "_".
 * Such records contain an additional information about a collection, e.g. "has_more" flag
 * in such record indicates whether a collection has more records than it was requested.
 * All removed records are stored in the context for further usage.
 */
abstract class RemoveInfoRecords implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $data = $context->getResult();
        if (!\is_array($data) || empty($data)) {
            // empty or not supported result data
            return;
        }

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            // only API resources with metadata are supported
            return;
        }

        $infoRecords = $this->processData($data, $metadata);
        $context->setResult($data);
        if (!empty($infoRecords)) {
            $context->setInfoRecords($infoRecords);
        }
    }

    /**
     * @param array          $data
     * @param EntityMetadata $metadata
     *
     * @return array [property path => info record, ...]
     */
    abstract protected function processData(array &$data, EntityMetadata $metadata): array;

    /**
     * @param array          $data
     * @param EntityMetadata $metadata
     * @param string         $propertyPath
     *
     * @return array [property path => info record, ...]
     */
    protected function processEntity(array &$data, EntityMetadata $metadata, string $propertyPath): array
    {
        $result = [];
        foreach ($data as $fieldName => &$value) {
            $association = $metadata->getAssociation($fieldName);
            if (null === $association) {
                continue;
            }

            $infoRecords = null;
            if ($association->isCollection()) {
                if (!empty($value)) {
                    $infoRecords = $this->processEntities(
                        $value,
                        $association->getTargetMetadata(),
                        $this->buildPath($propertyPath, $fieldName)
                    );
                }
            } elseif (\is_array($value)) {
                $infoRecords = $this->processEntity(
                    $value,
                    $association->getTargetMetadata(),
                    $this->buildPath($propertyPath, $fieldName)
                );
            }
            if (!empty($infoRecords)) {
                foreach ($infoRecords as $path => $record) {
                    $result[$path] = $record;
                }
            }
        }

        return $result;
    }

    /**
     * @param array          $data
     * @param EntityMetadata $metadata
     * @param string         $propertyPath
     *
     * @return array [property path => info record, ...]
     */
    protected function processEntities(array &$data, EntityMetadata $metadata, string $propertyPath): array
    {
        $result = [];
        if (isset($data[ConfigUtil::INFO_RECORD_KEY])) {
            $result[$propertyPath] = $data[ConfigUtil::INFO_RECORD_KEY];
            unset($data[ConfigUtil::INFO_RECORD_KEY]);
        }
        foreach ($data as $key => &$value) {
            if (!\is_array($value)) {
                continue;
            }

            $infoRecords = $this->processEntity($value, $metadata, $this->buildPath($propertyPath, (string)$key));
            if (!empty($infoRecords)) {
                foreach ($infoRecords as $path => $record) {
                    $result[$path] = $record;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $propertyPath
     * @param string $nextKey
     *
     * @return string
     */
    private function buildPath(string $propertyPath, string $nextKey): string
    {
        return '' !== $propertyPath
            ? $propertyPath . '.' . $nextKey
            : $nextKey;
    }
}
