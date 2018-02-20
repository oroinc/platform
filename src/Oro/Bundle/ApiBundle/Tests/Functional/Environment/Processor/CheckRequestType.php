<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class CheckRequestType implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $testAspect = $context->getRequestHeaders()->get('X-Test-Request-Type');
        if ($testAspect) {
            $requestType = $context->getRequestType();
            if (!$requestType->contains($testAspect)) {
                $requestType->add($testAspect);
            }
        }
    }
}
