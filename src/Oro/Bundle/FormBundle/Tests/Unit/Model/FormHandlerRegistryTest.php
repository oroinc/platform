<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class FormHandlerRegistryTest extends TestCase
{
    private FormHandlerInterface&MockObject $handler1;
    private FormHandlerRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->handler1 = $this->createMock(FormHandlerInterface::class);
        $handlers = new ServiceLocator([
            'handler1' => function () {
                return $this->handler1;
            }
        ]);

        $this->registry = new FormHandlerRegistry($handlers);
    }

    public function testHasAndGetForKnownHandler(): void
    {
        self::assertTrue($this->registry->has('handler1'));
        self::assertSame($this->handler1, $this->registry->get('handler1'));
    }

    public function testHasForUnknownHandler(): void
    {
        self::assertFalse($this->registry->has('unknown'));
    }

    public function testGetForUnknownHandler(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown form handler with alias "unknown".');

        $this->registry->get('unknown');
    }
}
