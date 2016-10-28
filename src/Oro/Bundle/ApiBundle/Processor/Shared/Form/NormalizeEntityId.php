<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Form;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;

/**
 * Checks whether a string representation of entity identifier exists in the Context,
 * and if so, converts it to its original type.
 */
class NormalizeEntityId implements ProcessorInterface
{
    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /**
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(EntityIdTransformerInterface $entityIdTransformer)
    {
        $this->entityIdTransformer = $entityIdTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext|SingleItemContext $context */

        $entityId = $context->getId();
        if (!is_string($entityId)) {
            // an entity identifier does not exist or it is already normalized
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null !== $includedEntities && null !== $includedEntities->get($context->getClassName(), $entityId)) {
            // keep the id of an included entity as is
            return;
        }

        try {
            $context->setId(
                $this->entityIdTransformer->reverseTransform($context->getClassName(), $entityId)
            );
        } catch (\Exception $e) {
            $context->addError(
                Error::createValidationError(Constraint::ENTITY_ID)->setInnerException($e)
            );
        }
    }
}
