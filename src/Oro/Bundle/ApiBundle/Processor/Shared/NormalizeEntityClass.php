<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\ResourceNotAccessibleException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes sure that an entity class name exists in the context.
 * Converts entity type to FQCN of an entity.
 * Checks that this entity is accessible through API.
 */
class NormalizeEntityClass implements ProcessorInterface
{
    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var ResourcesProvider */
    private $resourcesProvider;

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
        /** @var Context $context */

        $entityClass = $context->getClassName();
        if (!$entityClass) {
            $context->addError(Error::createValidationError(
                Constraint::ENTITY_TYPE,
                'The entity class must be set in the context.'
            ));

            return;
        }

        if (false !== strpos($entityClass, '\\')) {
            // an entity class is already normalized
            return;
        }

        $normalizedEntityClass = $this->getEntityClass(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType()
        );
        $context->setClassName($normalizedEntityClass);
        if (null === $normalizedEntityClass) {
            $context->addError(Error::createValidationError(
                Constraint::ENTITY_TYPE,
                sprintf('Unknown entity type: %s.', $entityClass),
                Response::HTTP_NOT_FOUND
            ));
        }
    }

    private function getEntityClass(string $entityType, string $version, RequestType $requestType): ?string
    {
        $entityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $requestType,
            false
        );
        if (!$entityClass) {
            return null;
        }
        if (!$this->resourcesProvider->isResourceAccessible($entityClass, $version, $requestType)) {
            throw new ResourceNotAccessibleException();
        }

        return $entityClass;
    }
}
