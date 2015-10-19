<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Doctrine\ORM\Proxy\Proxy;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class InitializeEntityProxy implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context->hasResult()) {
            // no result
            return;
        }

        $data = $context->getResult();
        foreach ($data as $value) {
            if ($value instanceof Proxy && !$value->__isInitialized()) {
                $value->__load();
            }
        }
    }
}
