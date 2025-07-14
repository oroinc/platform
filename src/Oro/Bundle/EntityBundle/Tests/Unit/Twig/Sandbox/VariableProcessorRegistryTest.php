<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class VariableProcessorRegistryTest extends TestCase
{
    private VariableProcessorInterface&MockObject $processor1;
    private VariableProcessorRegistry $registry;

    #[\Override]
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

    public function testHasAndGetForKnownProcessor(): void
    {
        self::assertTrue($this->registry->has('processor1'));
        self::assertSame($this->processor1, $this->registry->get('processor1'));
    }

    public function testHasForUnknownProcessor(): void
    {
        self::assertFalse($this->registry->has('unknown'));
    }

    public function testGetForUnknownProcessor(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown variable processor with alias "unknown"');

        $this->registry->get('unknown');
    }
}
