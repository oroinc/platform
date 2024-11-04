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
 * Disables the update operation for the primary entity.
 */
class DisableUpdateOperation implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        if (!$context->has(SetOperationFlags::UPDATE_FLAG)) {
            // the update operation was not requested
            return;
        }

        if ($context->isMainRequest()) {
            $context->addError(
                Error::createValidationError(Constraint::VALUE, 'The option is not supported.')
                    ->setSource(ErrorSource::createByPointer('/' . JsonApiDoc::META . '/' . JsonApiDoc::META_UPDATE))
            );
        }
    }
}
