<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Makes sure that the class name of an entity exists in the Context.
 */
class ValidateClassNameExists implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $entityClass = $context->getClassName();
        if (empty($entityClass)) {
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_TYPE,
                    'The name of an entity class must be set in the context.'
                )
            );
        }
    }
}
