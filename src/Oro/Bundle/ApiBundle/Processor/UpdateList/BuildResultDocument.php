<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildResultDocument as BaseBuildResultDocument;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Builds the response for the "update_list" action based on the context state.
 */
class BuildResultDocument extends BaseBuildResultDocument
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        parent::process($context);

        if ($context->hasResult() && !$this->isSingleItemResponse($context)) {
            $context->setResponseDocumentBuilder(null);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function processResult(DocumentBuilderInterface $documentBuilder, Context $context): void
    {
        /** @var UpdateListContext $context */

        if ($this->isSingleItemResponse($context)) {
            $documentBuilder->setDataObject(
                $context->getResult(),
                $context->getRequestType(),
                $context->getMetadata()
            );
        }
    }

    private function isSingleItemResponse(UpdateListContext $context): bool
    {
        return
            !$context->isSynchronousMode()
            || is_a($context->getMetadata()->getClassName(), AsyncOperation::class, true);
    }
}
