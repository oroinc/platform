<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\Rest;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes the result for "configuration" resource is compatible with old REST API:
 * * Returns only a list of configuration options.
 * * Renames "dataType" property to "type" for each configuration option.
 * * Removes "scope" property for each configuration option.
 */
class FixConfigurationSectionResultDocument implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $data = $context->getResult();
        if (!$data || !$context->isSuccessResponse()) {
            // no data or the result document contains info about errors
            return;
        }

        if (\array_key_exists('options', $data)) {
            $result = [];
            $options = $data['options'];
            foreach ($options as $option) {
                $option['type'] = $option['dataType'];
                unset($option['dataType'], $option['scope']);
                $result[] = $option;
            }
            $context->setResult($result);
        }
    }
}
