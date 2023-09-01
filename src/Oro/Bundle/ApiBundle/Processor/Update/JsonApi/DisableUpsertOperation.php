<?php

namespace Oro\Bundle\ApiBundle\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetOperationFlags;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables the upsert operation for resources with auto-generated identifier value.
 */
class DisableUpsertOperation implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var UpdateContext $context */

        if (!$context->get(SetOperationFlags::UPSERT_FLAG)) {
            // the upsert operation was not requested
            return;
        }

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            $this->addUpsertFlagValidationError($context, 'The upsert operation is not supported.');
        } elseif ($metadata->hasIdentifierGenerator()) {
            $this->addUpsertFlagValidationError(
                $context,
                'The upsert operation is not supported for resources with auto-generated identifier value.'
            );
        }
    }

    private function addUpsertFlagValidationError(UpdateContext $context, string $detail): void
    {
        $context->addError(
            Error::createValidationError(Constraint::VALUE, $detail)
                ->setSource(ErrorSource::createByPointer('/' . JsonApiDoc::META . '/' . JsonApiDoc::META_UPSERT))
        );
    }
}
