<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Builds response based on the Context state.
 */
class BuildSingleItemResultDocument extends BuildResultDocument
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(Context $context)
    {
        $result = $context->getResult();
        if (null === $result) {
            $this->documentBuilder->setDataObject($result);
        } else {
            $this->documentBuilder->setDataObject($result, $context->getMetadata());
        }
    }
}
