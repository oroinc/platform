<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;

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

        $accessibleResources = array_fill_keys($context->getAccessibleResources(), true);
        $resources = $context->getResources();
        foreach ($resources as $resource) {
            $subresources->add(
                $this->createEntitySubresources($resource, $version, $requestType, $accessibleResources)
            );
        }
    }

    /**
     * @param ApiResource $resource
     * @param string      $version
     * @param RequestType $requestType
     * @param array       $accessibleResources
     *
     * @return ApiResourceSubresources
     */
    protected function createEntitySubresources(
        ApiResource $resource,
        $version,
        RequestType $requestType,
        array $accessibleResources
    ) {
        $entityClass = $resource->getEntityClass();
        $config = $this->getConfig($entityClass, $version, $requestType);
        $metadata = $this->getMetadata($entityClass, $version, $requestType, $config);
        if (null === $metadata) {
            throw new RuntimeException(sprintf('A metadata for "%s" entity does not exist.', $entityClass));
        }

        $subresourceExcludedActions = $this->getSubresourceExcludedActions($resource);
        $entitySubresources = new ApiResourceSubresources($entityClass);
        $associations = $metadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            if ($this->isExcludedAssociation($associationName, $config)) {
                continue;
            }

            $entitySubresources->addSubresource(
                $associationName,
                $this->createSubresource($association, $accessibleResources, $subresourceExcludedActions)
            );
        }

        return $entitySubresources;
    }
}
