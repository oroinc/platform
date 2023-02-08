<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateLoadedData implements ProcessorInterface
{
    private string $suffix;

    public function __construct(string $suffix = '')
    {
        $this->suffix = $suffix;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if (\array_key_exists('computedName', $data) || !\array_key_exists('name', $data)) {
            return;
        }

        $config = $context->getConfig();
        if (!$config->hasField('computedName') || $config->getField('computedName')->isExcluded()) {
            return;
        }

        $data['computedName'] = $data['name'] . ' (computed' . $this->suffix . ')';
        $context->setData($data);
    }
}
