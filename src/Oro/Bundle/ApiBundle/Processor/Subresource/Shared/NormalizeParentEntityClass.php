<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Exception\ResourceNotAccessibleException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes sure that the class name of the parent entity exists in the context.
 * Converts entity type to FQCN of an entity.
 */
class NormalizeParentEntityClass implements ProcessorInterface
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
        /** @var SubresourceContext $context */

        $parentEntityClass = $context->getParentClassName();
        if (!$parentEntityClass) {
            $context->addError(Error::createValidationError(
                Constraint::ENTITY_TYPE,
                'The parent entity class must be set in the context.'
            ));

            return;
        }

        if (!str_contains($parentEntityClass, '\\')) {
            $normalizedEntityClass = $this->getEntityClass($parentEntityClass, $context->getRequestType());
            $context->setParentClassName($normalizedEntityClass);
            if (null === $normalizedEntityClass) {
                $context->addError(Error::createValidationError(
                    Constraint::ENTITY_TYPE,
                    sprintf('Unknown parent entity type: %s.', $parentEntityClass),
                    Response::HTTP_NOT_FOUND
                ));

                return;
            }
            $parentEntityClass = $normalizedEntityClass;
        }

        if (!$this->resourcesProvider->isResourceAccessibleAsAssociation(
            $parentEntityClass,
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
