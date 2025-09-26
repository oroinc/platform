<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * General processor that expands data for virtual file field.
 *
 * Use this processor to make the virtual field that references a file entity be properly expanded
 * in the "included" section of a response.
 */
class ExpandVirtualFileField implements ProcessorInterface
{
    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ObjectNormalizer $objectNormalizer
     * @param string $virtualFieldName The name of the virtual field to be expanded.
     */
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly ObjectNormalizer $objectNormalizer,
        private readonly string $virtualFieldName
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }

        $fileFieldName = $context->getResultFieldName($this->virtualFieldName);
        $fileField = $config->getField($fileFieldName);
        if (null === $fileField || $fileField->isCollapsed()) {
            return;
        }

        $data = $context->getData();
        $fileIds = $this->getFileIds($data, $fileFieldName);
        if (!$fileIds) {
            return;
        }

        $normalizedFiles = $this->getNormalizedFiles(
            $fileIds,
            $fileField->getTargetEntity(),
            $context->getNormalizationContext()
        );
        foreach ($data as $key => $item) {
            if (isset($item[$fileFieldName])) {
                $fileId = $item[$fileFieldName];
                if ($fileId && isset($normalizedFiles[$fileId])) {
                    $data[$key][$fileFieldName] = $normalizedFiles[$fileId];
                }
            }
        }
        $context->setData($data);
    }

    private function getFileIds(array $data, string $typeFieldName): array
    {
        $types = [];
        foreach ($data as $item) {
            if (isset($item[$typeFieldName])) {
                $type = $item[$typeFieldName];
                if ($type && !isset($types[$type])) {
                    $types[$type] = true;
                }
            }
        }

        return array_keys($types);
    }

    private function getNormalizedFiles(
        array $fileIds,
        EntityDefinitionConfig $config,
        array $context
    ): array {
        $toNormalize = [];
        foreach ($fileIds as $fileId) {
            $toNormalize[$fileId] = $this->doctrineHelper->getEntityManager(File::class)->find(File::class, $fileId);
        }

        return $this->objectNormalizer->normalizeObjects($toNormalize, $config, $context);
    }
}
