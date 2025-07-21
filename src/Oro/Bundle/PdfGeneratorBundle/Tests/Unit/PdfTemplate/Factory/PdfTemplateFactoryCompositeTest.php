<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfTemplate\Factory;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory\PdfTemplateFactoryComposite;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory\PdfTemplateFactoryInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PdfTemplateFactoryCompositeTest extends TestCase
{
    private PdfTemplateFactoryComposite $factory;

    private MockObject&PdfTemplateFactoryInterface $innerFactory1;

    private MockObject&PdfTemplateFactoryInterface $innerFactory2;

    protected function setUp(): void
    {
        $this->innerFactory1 = $this->createMock(PdfTemplateFactoryInterface::class);
        $this->innerFactory2 = $this->createMock(PdfTemplateFactoryInterface::class);

        $this->factory = new PdfTemplateFactoryComposite([$this->innerFactory1, $this->innerFactory2]);
    }

    public function testIsApplicableWhenNoFactoriesAreApplicable(): void
    {
        $template = 'pdf_template.html.twig';
        $context = ['sample_key' => 'sample_value'];

        $this->innerFactory1
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(false);

        $this->innerFactory2
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(false);

        self::assertFalse($this->factory->isApplicable($template, $context));
    }

    public function testIsApplicableWhenFirstFactoryIsApplicable(): void
    {
        $template = 'pdf_template.html.twig';
        $context = ['sample_key' => 'sample_value'];

        $this->innerFactory1
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(true);

        // Second factory should not be called due to short-circuiting
        $this->innerFactory2
            ->expects(self::never())
            ->method('isApplicable');

        self::assertTrue($this->factory->isApplicable($template, $context));
    }

    public function testIsApplicableWhenSecondFactoryIsApplicable(): void
    {
        $template = 'pdf_template.html.twig';
        $context = ['sample_key' => 'sample_value'];

        $this->innerFactory1
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(false);

        $this->innerFactory2
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(true);

        self::assertTrue($this->factory->isApplicable($template, $context));
    }

    public function testCreatePdfTemplateWhenFirstFactoryIsApplicable(): void
    {
        $template = 'pdf_template.html.twig';
        $context = ['sample_key' => 'sample_value'];
        $expectedResult = $this->createMock(PdfTemplateInterface::class);

        $this->innerFactory1
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(true);

        $this->innerFactory1
            ->expects(self::once())
            ->method('createPdfTemplate')
            ->with($template, $context)
            ->willReturn($expectedResult);

        // Second factory should not be called
        $this->innerFactory2
            ->expects(self::never())
            ->method('isApplicable');
        $this->innerFactory2
            ->expects(self::never())
            ->method('createPdfTemplate');

        self::assertSame($expectedResult, $this->factory->createPdfTemplate($template, $context));
    }

    public function testCreatePdfTemplateWhenSecondFactoryIsApplicable(): void
    {
        $template = 'pdf_template.html.twig';
        $context = ['sample_key' => 'sample_value'];
        $expectedResult = $this->createMock(PdfTemplateInterface::class);

        $this->innerFactory1
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(false);

        $this->innerFactory2
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(true);

        $this->innerFactory2
            ->expects(self::once())
            ->method('createPdfTemplate')
            ->with($template, $context)
            ->willReturn($expectedResult);

        self::assertSame($expectedResult, $this->factory->createPdfTemplate($template, $context));
    }

    public function testCreatePdfTemplateWhenNoFactoryIsApplicable(): void
    {
        $template = 'pdf_template.html.twig';
        $context = ['sample_key' => 'sample_value'];

        $this->innerFactory1
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(false);

        $this->innerFactory2
            ->expects(self::once())
            ->method('isApplicable')
            ->with($template, $context)
            ->willReturn(false);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No applicable PDF template factory found for template "pdf_template.html.twig"');

        $this->factory->createPdfTemplate($template, $context);
    }

    public function testCreatePdfTemplateWithEmptyInnerFactories(): void
    {
        $factory = new PdfTemplateFactoryComposite([]);
        $template = 'pdf_template.html.twig';
        $context = ['sample_key' => 'sample_value'];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No applicable PDF template factory found for template "pdf_template.html.twig"');

        $factory->createPdfTemplate($template, $context);
    }
}
