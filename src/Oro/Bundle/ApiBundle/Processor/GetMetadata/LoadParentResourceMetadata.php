<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads metadata for an entity if it is based on another API resource.
 */
class LoadParentResourceMetadata implements ProcessorInterface
{
    private MetadataProvider $metadataProvider;
    private DoctrineHelper $doctrineHelper;

    public function __construct(MetadataProvider $metadataProvider, DoctrineHelper $doctrineHelper)
    {
        $this->metadataProvider = $metadataProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var MetadataContext $context */

        if ($context->hasResult()) {
            // metadata is already loaded
            return;
        }

        $config = $context->getConfig();
        $parentResourceClass = $config->getParentResourceClass();
        if ($parentResourceClass) {
            $entityClass = $context->getClassName();
            $this->assertRelationship($entityClass, $parentResourceClass);
            $entityMetadata = $this->loadParentResourceMetadata(
                $parentResourceClass,
                $config,
                $context->getVersion(),
                $context->getRequestType(),
                $context->getExtras()
            );
            if (null !== $entityMetadata) {
                $entityMetadata->setClassName($entityClass);
                $context->setResult($entityMetadata);
            }
        }
    }

    private function assertRelationship(string $entityClass, string $parentResourceClass): void
    {
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            throw new \LogicException(
                sprintf(
                    'The class "%s" must not be a manageable entity because it is based on another API resource.'
                    . ' Parent resource is "%s".',
                    $entityClass,
                    $parentResourceClass
                )
            );
        }
    }

    private function loadParentResourceMetadata(
        string $parentResourceClass,
        EntityDefinitionConfig $config,
        string $version,
        RequestType $requestType,
        array $extras
    ): ?EntityMetadata {
        $config->setParentResourceClass(null);
        try {
            return $this->metadataProvider->getMetadata(
                $parentResourceClass,
                $version,
                $requestType,
                $config,
                $extras
            );
        } finally {
            $config->setParentResourceClass($parentResourceClass);
        }
    }
}
