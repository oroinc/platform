<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Makes sure that the class name of the parent entity exists in the Context,
 * if required converts it to FQCN of an entity
 * and check that this entity is accessible through Data API.
 */
class NormalizeParentEntityClass implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var ResourcesProvider */
    protected $resourcesProvider;

    /**
     * @param ValueNormalizer   $valueNormalizer
     * @param ResourcesProvider $resourcesProvider
     */
    public function __construct(ValueNormalizer $valueNormalizer, ResourcesProvider $resourcesProvider)
    {
        $this->valueNormalizer = $valueNormalizer;
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $parentEntityClass = $context->getParentClassName();
        if (!$parentEntityClass) {
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_TYPE,
                    'The parent entity class must be set in the context.'
                )
            );

            return;
        }

        if (false !== strpos($parentEntityClass, '\\')) {
            // the parent entity class is already normalized
            return;
        }

        $normalizedEntityClass = $this->getEntityClass(
            $parentEntityClass,
            $context->getVersion(),
            $context->getRequestType()
        );
        if (null !== $normalizedEntityClass) {
            $context->setParentClassName($normalizedEntityClass);
        } else {
            $context->setParentClassName(null);
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_TYPE,
                    sprintf('Unknown parent entity type: %s.', $parentEntityClass)
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
        if (null !== $entityClass
            && !$this->resourcesProvider->isResourceAccessible($entityClass, $version, $requestType)
        ) {
            $entityClass = null;
        }

        return $entityClass;
    }
}
