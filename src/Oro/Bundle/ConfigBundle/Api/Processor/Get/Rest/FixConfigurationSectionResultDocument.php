<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\Get\Rest;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Makes the result for "configuration" resource is compatible with old REST API:
 * * Returns only a list of configuration options.
 * * Renames "dataType" property to "type" for each configuration option.
 * * Removes "scope" property for each configuration option.
 */
class FixConfigurationSectionResultDocument implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $data = $context->getResult();
        if (empty($data) || !$context->isSuccessResponse()) {
            // no data or the result document contains info about errors
            return;
        }

        $data = $context->getResult();
        if (array_key_exists('options', $data)) {
            $result = [];
            $options = $data['options'];
            foreach ($options as $option) {
                $option['type'] = $option['dataType'];
                unset($option['dataType']);
                unset($option['scope']);
                $result[] = $option;
            }
            $context->setResult($result);
        }
    }
}
