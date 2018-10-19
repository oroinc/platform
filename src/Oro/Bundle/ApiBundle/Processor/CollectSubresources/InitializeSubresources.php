<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Initializes sub-resources for all API resources based on API configuration and metadata.
 */
class InitializeSubresources extends LoadSubresources
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectSubresourcesContext $context */

        $subresources = $context->getResult();
        if (!$subresources->isEmpty()) {
            // already initialized
            return;
        }

        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $accessibleResources = \array_fill_keys($context->getAccessibleResources(), true);
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

    /**
     * @param ApiResource $resource
     * @param string      $version
     * @param RequestType $requestType
     * @param array       $accessibleResources
     *
     * @return ApiResourceSubresources|null
     */
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
        if (!empty($associations) && $this->isSubresourcesEnabled($resource)) {
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
