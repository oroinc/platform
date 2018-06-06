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
    /** @var MetadataProvider */
    protected $metadataProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param MetadataProvider $metadataProvider
     * @param DoctrineHelper   $doctrineHelper
     */
    public function __construct(MetadataProvider $metadataProvider, DoctrineHelper $doctrineHelper)
    {
        $this->metadataProvider = $metadataProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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

    /**
     * @param string $entityClass
     * @param string $parentResourceClass
     */
    protected function assertRelationship($entityClass, $parentResourceClass)
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

    /**
     * @param string                 $parentResourceClass
     * @param EntityDefinitionConfig $config
     * @param string                 $version
     * @param RequestType            $requestType
     * @param array                  $extras
     *
     * @return EntityMetadata|null
     */
    protected function loadParentResourceMetadata(
        $parentResourceClass,
        EntityDefinitionConfig $config,
        $version,
        RequestType $requestType,
        array $extras
    ) {
        $entityMetadata = null;
        $config->setParentResourceClass(null);
        try {
            $entityMetadata = $this->metadataProvider->getMetadata(
                $parentResourceClass,
                $version,
                $requestType,
                $config,
                $extras
            );
        } finally {
            $config->setParentResourceClass($parentResourceClass);
        }

        return $entityMetadata;
    }
}
