<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Builds response based on the Context state.
 */
class BuildListResultDocument extends BuildResultDocument
{
    /**
     * {@inheritdoc}
     */
    protected function processResult(Context $context)
    {
        $result = $context->getResult();
        if (empty($result)) {
            $this->documentBuilder->setDataCollection($result);
        } else {
            $this->documentBuilder->setDataCollection($result, $context->getMetadata());
        }
    }
}
