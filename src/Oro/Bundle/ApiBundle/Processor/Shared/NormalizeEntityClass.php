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
    private ValueNormalizer $valueNormalizer;
    private ResourcesProvider $resourcesProvider;

    public function __construct(ValueNormalizer $valueNormalizer, ResourcesProvider $resourcesProvider)
    {
        $this->valueNormalizer = $valueNormalizer;
        $this->resourcesProvider = $resourcesProvider;
    }

    #[\Override]
    public function process(ContextInterface $context): void
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

        if (!str_contains($entityClass, '\\')) {
            $normalizedEntityClass = $this->getEntityClass($entityClass, $context->getRequestType());
            $context->setClassName($normalizedEntityClass);
            if (null === $normalizedEntityClass) {
                $context->addError(Error::createValidationError(
                    Constraint::ENTITY_TYPE,
                    sprintf('Unknown entity type: %s.', $entityClass),
                    Response::HTTP_NOT_FOUND
                ));

                return;
            }
            $entityClass = $normalizedEntityClass;
        }

        if (!$this->resourcesProvider->isResourceAccessible(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType()
        )) {
            throw new ResourceNotAccessibleException();
        }
    }

    private function getEntityClass(string $entityType, RequestType $requestType): ?string
    {
        return ValueNormalizerUtil::tryConvertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $requestType
        );
    }
}
