<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\Rest;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes the result for "configuration" resource is compatible with old REST API:
 * * Converts a list of configuration section objects
 *   to a list of configuration section names.
 */
class FixConfigurationSectionsResultDocument implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        $data = $context->getResult();
        if (!$data || !$context->isSuccessResponse()) {
            // no data or the result document contains info about errors
            return;
        }

        $sectionNames = [];
        foreach ($data as $item) {
            $sectionNames[] = $item['id'];
        }
        $context->setResult($sectionNames);
    }
}
