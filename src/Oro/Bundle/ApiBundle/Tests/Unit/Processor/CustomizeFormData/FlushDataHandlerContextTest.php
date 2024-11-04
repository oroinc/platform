<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use PHPUnit\Framework\TestCase;

class FlushDataHandlerContextTest extends TestCase
{
    public function testContext(): void
    {
        $entityContexts = [$this->createMock(FormContext::class)];
        $sharedData = $this->createMock(ParameterBagInterface::class);

        $context = new FlushDataHandlerContext($entityContexts, $sharedData);

        self::assertSame($entityContexts, $context->getEntityContexts());
        self::assertSame($sharedData, $context->getSharedData());
        self::assertFalse($context->isBatchOperation());

        $context = new FlushDataHandlerContext($entityContexts, $sharedData, true);
        self::assertTrue($context->isBatchOperation());
    }
}
