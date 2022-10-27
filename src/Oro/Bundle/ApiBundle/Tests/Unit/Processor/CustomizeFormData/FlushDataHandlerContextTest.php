<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ParameterBagInterface;

class FlushDataHandlerContextTest extends \PHPUnit\Framework\TestCase
{
    public function testContext(): void
    {
        $entityContexts = [$this->createMock(FormContext::class)];
        $sharedData = $this->createMock(ParameterBagInterface::class);

        $context = new FlushDataHandlerContext($entityContexts, $sharedData);

        self::assertSame($entityContexts, $context->getEntityContexts());
        self::assertSame($sharedData, $context->getSharedData());
    }
}
