<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;

/**
 * The main processor for "create" action.
 */
class CreateProcessor extends RequestActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): CreateContext
    {
        return new CreateContext($this->configProvider, $this->metadataProvider);
    }

    /**
     * {@inheritDoc}
     */
    protected function getLogContext(NormalizeResultContext $context): array
    {
        $result = parent::getLogContext($context);
        if (\array_key_exists('id', $result) && empty($result['id'])) {
            unset($result['id']);
        }

        return $result;
    }
}
