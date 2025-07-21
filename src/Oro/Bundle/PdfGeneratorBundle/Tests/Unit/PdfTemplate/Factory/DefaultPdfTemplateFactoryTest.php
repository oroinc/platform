<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfTemplate\Factory;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory\DefaultPdfTemplateFactory;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplate;
use PHPUnit\Framework\TestCase;
use Twig\Environment as TwigEnvironment;
use Twig\Template;
use Twig\TemplateWrapper;

final class DefaultPdfTemplateFactoryTest extends TestCase
{
    private DefaultPdfTemplateFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DefaultPdfTemplateFactory();
    }

    public function testIsApplicableWithStringTemplate(): void
    {
        self::assertTrue($this->factory->isApplicable('pdf_template.html.twig'));
    }

    public function testIsApplicableWithTemplateWrapper(): void
    {
        $templateWrapper = new TemplateWrapper(
            $this->createMock(TwigEnvironment::class),
            $this->createMock(Template::class)
        );

        self::assertTrue($this->factory->isApplicable($templateWrapper));
    }

    public function testCreatePdfTemplateWithStringTemplate(): void
    {
        $template = 'pdf_template.html.twig';
        $context = ['sample_key' => 'sample_value'];

        $result = $this->factory->createPdfTemplate($template, $context);

        self::assertInstanceOf(PdfTemplate::class, $result);
        self::assertSame($template, $result->getTemplate());
        self::assertSame($context, $result->getContext());
    }

    public function testCreatePdfTemplateWithTemplateWrapper(): void
    {
        $templateWrapper = new TemplateWrapper(
            $this->createMock(TwigEnvironment::class),
            $this->createMock(Template::class)
        );
        $context = ['sample_key' => 'sample_value'];

        $result = $this->factory->createPdfTemplate($templateWrapper, $context);

        self::assertInstanceOf(PdfTemplate::class, $result);
        self::assertSame($templateWrapper, $result->getTemplate());
        self::assertSame($context, $result->getContext());
    }
}
