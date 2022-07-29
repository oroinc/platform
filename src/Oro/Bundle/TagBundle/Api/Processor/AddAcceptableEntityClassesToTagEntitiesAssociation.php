<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds acceptable entity classes to "entities" association of Tag entity.
 */
class AddAcceptableEntityClassesToTagEntitiesAssociation implements ProcessorInterface
{
    private TaggableHelper $taggableHelper;
    private ResourcesProvider $resourcesProvider;

    public function __construct(TaggableHelper $taggableHelper, ResourcesProvider $resourcesProvider)
    {
        $this->taggableHelper = $taggableHelper;
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var MetadataContext $context */

        $entityMetadata = $context->getResult();
        if (null === $entityMetadata) {
            return;
        }

        $entitiesAssociation = $entityMetadata->getAssociation('entities');
        if (null === $entitiesAssociation) {
            return;
        }

        $entitiesAssociation->setAcceptableTargetClassNames(
            $this->getAcceptableTaggableEntities($context->getVersion(), $context->getRequestType())
        );
        $entitiesAssociation->setEmptyAcceptableTargetsAllowed(false);
    }

    private function getAcceptableTaggableEntities(string $version, RequestType $requestType): array
    {
        $acceptableTaggableEntities = [];
        $taggableEntities = $this->taggableHelper->getTaggableEntities();
        foreach ($taggableEntities as $entityClass) {
            if ($this->resourcesProvider->isResourceAccessible($entityClass, $version, $requestType)) {
                $acceptableTaggableEntities[] = $entityClass;
            }
        }

        return $acceptableTaggableEntities;
    }
}
