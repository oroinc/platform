<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;

class VariableProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var VariableProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processor1;

    /** @var VariableProcessorRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->processor1 = $this->createMock(VariableProcessorInterface::class);
        $processors = new ServiceLocator([
            'processor1' => function () {
                return $this->processor1;
            }
        ]);

        $this->registry = new VariableProcessorRegistry($processors);
    }

    public function testHasAndGetForKnownProcessor()
    {
        self::assertTrue($this->registry->has('processor1'));
        self::assertSame($this->processor1, $this->registry->get('processor1'));
    }

    public function testHasForUnknownProcessor()
    {
        self::assertFalse($this->registry->has('unknown'));
    }

    public function testGetForUnknownProcessor()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown variable processor with alias "unknown"');

        $this->registry->get('unknown');
    }
}
