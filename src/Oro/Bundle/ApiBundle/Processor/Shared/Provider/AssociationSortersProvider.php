<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Provides a method to help validation and normalization association related sorters.
 */
class AssociationSortersProvider
{
    private DoctrineHelper $doctrineHelper;
    private ConfigProvider $configProvider;

    public function __construct(DoctrineHelper $doctrineHelper, ConfigProvider $configProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * @param string[]      $path
     * @param Context       $context
     * @param ClassMetadata $metadata
     *
     * @return array [sorters config, associations]
     * @psalm-return array{0: SortersConfig|null, 1: string|null}
     */
    public function getAssociationSorters(array $path, Context $context, ClassMetadata $metadata): array
    {
        $targetConfigExtras = [
            new EntityDefinitionConfigExtra($context->getAction()),
            new SortersConfigExtra()
        ];

        $config = $context->getConfig();
        $sorters = null;
        $associations = [];

        foreach ($path as $fieldName) {
            if (!$config->hasField($fieldName)) {
                return [null, null];
            }

            $associationName = $config->getField($fieldName)->getPropertyPath($fieldName);
            if (!$metadata->hasAssociation($associationName)) {
                return [null, null];
            }

            $targetClass = $metadata->getAssociationTargetClass($associationName);
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass, false);
            if (!$metadata) {
                return [null, null];
            }

            $targetConfig = $this->configProvider->getConfig(
                $targetClass,
                $context->getVersion(),
                $context->getRequestType(),
                $targetConfigExtras
            );
            if (!$targetConfig->hasDefinition()) {
                return [null, null];
            }

            $config = $targetConfig->getDefinition();
            $sorters = $targetConfig->getSorters();
            $associations[] = $associationName;
        }

        return [$sorters, implode('.', $associations)];
    }
}
