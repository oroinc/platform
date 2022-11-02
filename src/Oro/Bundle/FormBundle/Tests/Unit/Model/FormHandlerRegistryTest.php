<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;

class FormHandlerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $handler1;

    /** @var FormHandlerRegistry */
    private $registry;

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

    public function testHasAndGetForKnownHandler()
    {
        self::assertTrue($this->registry->has('handler1'));
        self::assertSame($this->handler1, $this->registry->get('handler1'));
    }

    public function testHasForUnknownHandler()
    {
        self::assertFalse($this->registry->has('unknown'));
    }

    public function testGetForUnknownHandler()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown form handler with alias "unknown".');

        $this->registry->get('unknown');
    }
}
