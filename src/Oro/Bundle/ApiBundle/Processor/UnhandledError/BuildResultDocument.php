<?php

namespace Oro\Bundle\ApiBundle\Processor\UnhandledError;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildResultDocument as BaseBuildResultDocument;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;

/**
 * Builds the response for "unhandled_error" action.
 */
class BuildResultDocument extends BaseBuildResultDocument
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(DocumentBuilderInterface $documentBuilder, Context $context): void
    {
        throw new RuntimeException('Invalid error handling: the context must contain an error object.');
    }

    /**
     * {@inheritdoc}
     */
    protected function getExceptionLoggingContext(Context $context): array
    {
        return ['action' => $context->getAction()];
    }
}
