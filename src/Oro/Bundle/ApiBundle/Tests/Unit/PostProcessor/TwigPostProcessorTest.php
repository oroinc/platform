<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\PostProcessor;

use Oro\Bundle\ApiBundle\PostProcessor\TwigPostProcessor;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class TwigPostProcessorTest extends TestCase
{
    private Environment&MockObject $twig;
    private TwigPostProcessor $postProcessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $container = TestContainerBuilder::create()
            ->add(Environment::class, $this->twig)
            ->getContainer($this);

        $this->postProcessor = new TwigPostProcessor($container);
    }

    public function testProcessForNullValue(): void
    {
        $this->twig->expects(self::never())
            ->method('render');

        self::assertNull($this->postProcessor->process(null, ['template' => 'twig_template', 'option1' => 'value1']));
    }

    public function testProcess(): void
    {
        $value = 'test';
        $renderedValue = 'rendered';

        $this->twig->expects(self::once())
            ->method('render')
            ->with('twig_template', ['option1' => 'value1', 'value' => $value])
            ->willReturn($renderedValue);

        self::assertSame(
            $renderedValue,
            $this->postProcessor->process($value, ['template' => 'twig_template', 'option1' => 'value1'])
        );
    }
}
