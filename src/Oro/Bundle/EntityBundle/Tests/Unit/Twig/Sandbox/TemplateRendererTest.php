<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesTemplateProcessor;
use Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesTemplateProcessor;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateDataFactory;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment as TwigEnvironment;
use Twig\Template;
use Twig\TemplateWrapper;

class TemplateRendererTest extends \PHPUnit\Framework\TestCase
{
    private TwigEnvironment|MockObject $twigEnvironment;

    private TemplateDataFactory|MockObject $templateDataFactory;

    private SystemVariablesTemplateProcessor|MockObject $systemVariablesTemplateProcessor;

    private EntityVariablesTemplateProcessor|MockObject $entityVariablesTemplateProcessor;

    private TemplateRenderer $renderer;

    protected function setUp(): void
    {
        $this->twigEnvironment = $this->createMock(TwigEnvironment::class);
        $this->templateDataFactory = $this->createMock(TemplateDataFactory::class);
        $this->systemVariablesTemplateProcessor = $this->createMock(SystemVariablesTemplateProcessor::class);
        $this->entityVariablesTemplateProcessor = $this->createMock(EntityVariablesTemplateProcessor::class);

        $this->renderer = new TemplateRenderer(
            $this->twigEnvironment,
            $this->templateDataFactory,
            $this->systemVariablesTemplateProcessor,
            $this->entityVariablesTemplateProcessor
        );
    }

    public function testRenderTemplate(): void
    {
        $templateParams = ['sample_key' => 'sample_value'];
        $templateData = $this->createMock(TemplateData::class);
        $this->templateDataFactory
            ->expects(self::once())
            ->method('createTemplateData')
            ->with($templateParams)
            ->willReturn($templateData);

        $templateContent = 'sample content';
        $systemVarsProcessedTemplateContent = 'sample content (system vars processed)';
        $this->systemVariablesTemplateProcessor
            ->expects(self::once())
            ->method('processSystemVariables')
            ->with($templateContent)
            ->willReturn($systemVarsProcessedTemplateContent);

        $entityVarsProcessedTemplateContent = 'sample content (system vars processed) (entity vars processed)';
        $this->entityVariablesTemplateProcessor
            ->expects(self::once())
            ->method('processEntityVariables')
            ->with($systemVarsProcessedTemplateContent, $templateData)
            ->willReturn($entityVarsProcessedTemplateContent);

        $template = $this->createMock(Template::class);
        $templateWrapper = new TemplateWrapper($this->twigEnvironment, $template);
        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->with($entityVarsProcessedTemplateContent)
            ->willReturn($templateWrapper);

        $templateData
            ->expects(self::once())
            ->method('getData')
            ->willReturn($templateParams);

        $renderedContent = 'rendered content';
        $template
            ->expects(self::once())
            ->method('render')
            ->with($templateParams)
            ->willReturn($renderedContent);

        self::assertEquals($renderedContent, $this->renderer->renderTemplate($templateContent, $templateParams));
    }
}
