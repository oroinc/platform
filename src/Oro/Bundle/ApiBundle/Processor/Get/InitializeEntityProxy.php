<?php

namespace Oro\Bundle\ApiBundle\Processor\Get;

use Doctrine\ORM\Proxy\Proxy;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks the result is instance of "Doctrine\ORM\Proxy\Proxy" and makes sure it is initialized.
 */
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
        if ($data instanceof Proxy && !$data->__isInitialized()) {
            $data->__load();
        }
    }
}
