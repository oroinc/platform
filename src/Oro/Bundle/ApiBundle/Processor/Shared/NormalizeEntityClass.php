<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\ApiBundle\Provider\ResourcesLoader;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Checks that the "class" attribute of the Context represents FQCN of an entity
 * and this entity is accessible through Data API.
 */
class NormalizeEntityClass implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var ResourcesLoader */
    protected $resourcesLoader;

    /** @var ResourcesCache */
    protected $resourcesCache;

    /**
     * @param ValueNormalizer $valueNormalizer
     * @param ResourcesLoader $resourcesLoader
     * @param ResourcesCache  $resourcesCache
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        ResourcesLoader $resourcesLoader,
        ResourcesCache $resourcesCache
    ) {
        $this->valueNormalizer = $valueNormalizer;
        $this->resourcesLoader = $resourcesLoader;
        $this->resourcesCache = $resourcesCache;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $entityClass = $context->getClassName();
        if (false !== strpos($entityClass, '\\')) {
            // an entity class is already normalized
            return;
        }

        $normalizedEntityClass = $this->getEntityClass(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType()
        );
        if (null !== $normalizedEntityClass) {
            $context->setClassName($normalizedEntityClass);
        } else {
            $context->setClassName(null);
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_TYPE,
                    sprintf('Unknown entity type: %s.', $entityClass)
                )
            );
        }
    }

    /**
     * @param string      $entityType
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return string|null
     */
    protected function getEntityClass($entityType, $version, RequestType $requestType)
    {
        $entityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $requestType,
            false
        );
        if (null !== $entityClass && !$this->isResourceAccessible($entityClass, $version, $requestType)) {
            $entityClass = null;
        }

        return $entityClass;
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return bool
     */
    protected function isResourceAccessible($entityClass, $version, RequestType $requestType)
    {
        $accessibleResources = $this->resourcesCache->getAccessibleResources($version, $requestType);
        if (null === $accessibleResources) {
            $this->resourcesLoader->getResources($version, $requestType);
            $accessibleResources = $this->resourcesCache->getAccessibleResources($version, $requestType);
        }

        return in_array($entityClass, $accessibleResources, true);
    }
}
