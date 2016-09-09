<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\GetList\Rest;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ListContext;

/**
 * Makes the result for "configuration" resource is compatible with old REST API:
 * * Converts a list of configuration section objects
 *   to a list of configuration section names.
 */
class FixConfigurationSectionsResultDocument implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ListContext $context */

        $data = $context->getResult();
        if (empty($data) || !$context->isSuccessResponse()) {
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
