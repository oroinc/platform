<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Prepares REST API request data to be processed by Symfony Forms.
 */
class NormalizeRequestData extends AbstractNormalizeRequestData
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $this->context = $context;
        try {
            $context->setRequestData(
                $this->normalizeData($context->getRequestData(), $context->getMetadata())
            );
        } finally {
            $this->context = null;
        }
    }
}
