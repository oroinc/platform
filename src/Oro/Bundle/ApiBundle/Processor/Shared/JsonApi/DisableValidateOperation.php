<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables the validate operation for the entity.
 */
class DisableValidateOperation implements ProcessorInterface
{
    /**
     * @param ContextInterface&FormContext $context
     */
    #[\Override]
    public function process(ContextInterface $context): void
    {
        if (!$context->has(SetOperationFlags::VALIDATE_FLAG)) {
            // the validate operation was not requested
            return;
        }

        if (!$context->getConfig()?->isValidationEnabled()) {
            $context->addError(
                Error::createValidationError(Constraint::VALUE, 'The option is not supported.')
                    ->setSource(ErrorSource::createByPointer('/' . JsonApiDoc::META . '/' . JsonApiDoc::META_VALIDATE))
            );
        }
    }
}
