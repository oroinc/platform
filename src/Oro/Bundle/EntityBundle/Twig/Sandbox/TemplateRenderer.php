<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Twig\Environment as TwigEnvironment;
use Twig\Source;

/**
 * The base class to render TWIG templates in a sandboxed environment.
 */
class TemplateRenderer
{
    private TwigEnvironment $twigEnvironment;

    private TemplateDataFactory $templateDataFactory;

    private SystemVariablesTemplateProcessor $systemVariablesTemplateProcessor;

    private EntityVariablesTemplateProcessor $entityVariablesTemplateProcessor;

    public function __construct(
        TwigEnvironment $twigEnvironment,
        TemplateDataFactory $templateDataFactory,
        SystemVariablesTemplateProcessor $systemVariablesTemplateProcessor,
        EntityVariablesTemplateProcessor $entityVariablesTemplateProcessor
    ) {
        $this->twigEnvironment = $twigEnvironment;
        $this->templateDataFactory = $templateDataFactory;
        $this->systemVariablesTemplateProcessor = $systemVariablesTemplateProcessor;
        $this->entityVariablesTemplateProcessor = $entityVariablesTemplateProcessor;
    }

    /**
     * Renders the given TWIG template.
     *
     * @throws \Twig\Error\Error if the given template cannot be rendered
     */
    public function renderTemplate(string $templateContent, array $templateParams = []): string
    {
        $templateData = $this->templateDataFactory->createTemplateData($templateParams);
        $templateContent = $this->prepareTemplateContent($templateContent, $templateData);

        return $this->twigEnvironment
            ->createTemplate($templateContent)
            ->render($templateData->getData());
    }

    /**
     * Validates syntax of the given TWIG template.
     *
     * @throws \Twig\Error\SyntaxError if the given template has errors
     */
    public function validateTemplate(string $templateContent): void
    {
        $source = new Source($templateContent, '');
        $stream = $this->twigEnvironment->tokenize($source);

        $this->twigEnvironment->parse($stream);
    }

    private function prepareTemplateContent(string $templateContent, TemplateData $templateData): string
    {
        $templateContent = $this->systemVariablesTemplateProcessor->processSystemVariables($templateContent);

        return $this->entityVariablesTemplateProcessor->processEntityVariables($templateContent, $templateData);
    }
}
