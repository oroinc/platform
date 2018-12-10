<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateLoadedData implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data) || array_key_exists('computedName', $data) || !array_key_exists('name', $data)) {
            return;
        }

        $config = $context->getConfig();
        if (!$config->hasField('computedName') || $config->getField('computedName')->isExcluded()) {
            return;
        }

        $data['computedName'] = $data['name'] . ' (computed)';
        $context->setResult($data);
    }
}
