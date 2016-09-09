<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Makes sure that the class name of the parent entity exists in the Context.
 * Converts entity type to FQCN of an entity.
 */
class NormalizeParentEntityClass implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
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

        $normalizedEntityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $parentEntityClass,
            $context->getRequestType(),
            false
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
}
