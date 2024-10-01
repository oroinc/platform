<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\Rest;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes the result for "configurationoptions" resource is compatible with old REST API:
 * * Renames "dataType" property to "type".
 * * Removes "scope" property.
 */
class FixConfigurationOptionsResultDocument implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $data = $context->getResult();
        if (empty($data) || !$context->isSuccessResponse()) {
            // no data or the result document contains info about errors
            return;
        }

        foreach ($data as $i => $option) {
            $data[$i]['type'] = $option['dataType'];
            unset($data[$i]['dataType'], $data[$i]['scope']);
        }
        $context->setResult($data);
    }
}
