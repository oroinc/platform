<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Initializes sub-resources based on associations from API metadata.
 */
class InitializeSubresources extends LoadSubresources
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CollectSubresourcesContext $context */

        $subresources = $context->getResult();
        if (!$subresources->isEmpty()) {
            // already initialized
            return;
        }

        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $accessibleResources = array_fill_keys($context->getAccessibleResources(), true);
        $resources = $context->getResources();
        foreach ($resources as $resource) {
            $entitySubresources = $this->createEntitySubresources(
                $resource,
                $version,
                $requestType,
                $accessibleResources
            );
            if (null !== $entitySubresources) {
                $subresources->add($entitySubresources);
            }
        }
    }

    private function createEntitySubresources(
        ApiResource $resource,
        string $version,
        RequestType $requestType,
        array $accessibleResources
    ): ?ApiResourceSubresources {
        $entityClass = $resource->getEntityClass();
        $config = $this->getConfig($entityClass, $version, $requestType);
        if (null === $config) {
            return null;
        }
        $metadata = $this->getMetadata($entityClass, $version, $requestType, $config);
        if (null === $metadata) {
            return null;
        }

        $entitySubresources = new ApiResourceSubresources($entityClass);
        $associations = $metadata->getAssociations();
        if (!empty($associations) && SubresourceUtil::isSubresourcesEnabled($resource)) {
            $subresourceExcludedActions = $this->getSubresourceExcludedActions($resource);
            foreach ($associations as $associationName => $association) {
                if ($this->isExcludedAssociation($associationName, $config)) {
                    continue;
                }

                $entitySubresources->addSubresource(
                    $associationName,
                    $this->createSubresource($association, $accessibleResources, $subresourceExcludedActions)
                );
            }
        }

        return $entitySubresources;
    }
}
