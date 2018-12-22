<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateLoadedCollection implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data)) {
            return;
        }

        $config = $context->getConfig();
        if (!$config->hasField('computedIds') || $config->getField('computedIds')->isExcluded()) {
            return;
        }

        $ids = $context->getIdentifierValues($data, 'id');
        sort($ids);
        $computedIds = sprintf('[%s] (%s)', implode(',', $ids), $context->getClassName());

        foreach ($data as $key => $item) {
            $data[$key]['computedIds'] = $computedIds;
        }
        $context->setResult($data);
    }
}
