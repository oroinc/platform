<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\FormBundle\Layout\Block\Type\CaptchaType;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use PHPUnit\Framework\TestCase;

class CaptchaTypeTest extends TestCase
{
    private CaptchaType $captchaType;

    protected function setUp(): void
    {
        $this->captchaType = new CaptchaType();
    }

    public function testGetName(): void
    {
        $this->assertEquals('captcha', $this->captchaType->getName());
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->captchaType->configureOptions($resolver);

        $options = $resolver->resolve();
        $this->assertEquals('captcha', $options['name']);
    }

    public function testBuildView(): void
    {
        $formName = 'captcha';

        $blockView = new BlockView();
        $block = $this->createMock(BlockInterface::class);
        $options = $this->createMock(Options::class);
        $options->expects($this->once())
            ->method('get')
            ->with('name')
            ->willReturn($formName);

        $this->captchaType->buildView($blockView, $block, $options);

        $this->assertArrayHasKey('name', $blockView->vars);
        $this->assertSame($formName, $blockView->vars['name']);
    }
}
