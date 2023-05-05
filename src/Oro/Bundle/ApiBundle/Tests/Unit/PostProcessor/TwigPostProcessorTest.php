<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\PostProcessor;

use Oro\Bundle\ApiBundle\PostProcessor\TwigPostProcessor;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Twig\Environment;

class TwigPostProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var TwigPostProcessor */
    private $postProcessor;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $container = TestContainerBuilder::create()
            ->add(Environment::class, $this->twig)
            ->getContainer($this);

        $this->postProcessor = new TwigPostProcessor($container);
    }

    public function testProcessForNullValue()
    {
        $this->twig->expects(self::never())
            ->method('render');

        self::assertNull($this->postProcessor->process(null, ['template' => 'twig_template', 'option1' => 'value1']));
    }

    public function testProcess()
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
