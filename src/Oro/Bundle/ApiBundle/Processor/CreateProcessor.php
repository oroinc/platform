<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;

class CreateProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new CreateContext($this->configProvider, $this->metadataProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLogContext(NormalizeResultContext $context): array
    {
        $result = parent::getLogContext($context);
        if (array_key_exists('id', $result) && empty($result['id'])) {
            unset($result['id']);
        }

        return $result;
    }
}
