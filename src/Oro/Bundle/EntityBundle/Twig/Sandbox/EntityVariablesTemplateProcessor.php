<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Doctrine\Inflector\Inflector;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

/**
 * Parses the given TWIG template and computes values for entity variables.
 * Adds filters to the entity variables and computed variables.
 */
class EntityVariablesTemplateProcessor
{
    private TwigEnvironment $twigEnvironment;

    private EntityVariableComputer $entityVariableComputer;

    private Inflector $inflector;

    private TranslatorInterface $translator;

    private string $entitySection = 'entity';

    private string $computedSection = 'computed';

    private string $pathSeparator = '.';

    private string $variableNotFoundMessage = 'oro.entity.template_renderer.entity_variable_not_found';

    public function __construct(
        TwigEnvironment $twigEnvironment,
        EntityVariableComputer $entityVariableComputer,
        Inflector $inflector,
        TranslatorInterface $translator
    ) {
        $this->twigEnvironment = $twigEnvironment;
        $this->entityVariableComputer = $entityVariableComputer;
        $this->inflector = $inflector;
        $this->translator = $translator;
    }

    public function setEntitySection(string $entitySection): void
    {
        $this->entitySection = $entitySection;
    }

    public function setComputedSection(string $computedSection): void
    {
        $this->computedSection = $computedSection;
    }

    public function setPathSeparator(string $pathSeparator): void
    {
        $this->pathSeparator = $pathSeparator;
    }

    public function setVariableNotFoundMessage(string $variableNotFoundMessage): void
    {
        $this->variableNotFoundMessage = $variableNotFoundMessage;
    }

    public function processEntityVariables(string $templateContent, TemplateData $templateData): string
    {
        if ($templateData->hasRootEntity()) {
            $templateContent = $this->doProcessEntityVariables($templateContent, $templateData);
            $templateContent = $this->addDefaultFiltersForEntityVariables($templateContent, $templateData);
        }

        return $templateContent;
    }

    /**
     * Parses the given TWIG template and computes values for entity variables
     * (they start with "entity.") that has a processor.
     */
    private function doProcessEntityVariables(string $templateContent, TemplateData $templateData): string
    {
        return \preg_replace_callback(
            // Find expression that should be displayed by twig (strings between {{ and }})
            '/{{(.+?)}}/u',
            function ($match) use ($templateData) {
                [$outputStr, $variableExpr] = $match;

                $replaceExpr = \preg_replace_callback(
                    // Search entity variables (they start with "entity.")
                    '/('. $this->entitySection .'\.[\w|\.]+)/u',
                    function ($m) use ($templateData) {
                        // $variable contains string that starts with "entity."
                        $variable = $m[0];

                        // Trying to replace entity.* with data provided by processor if any
                        $computedPath = $this->entityVariableComputer->computeEntityVariable($variable, $templateData);
                        if ($computedPath) {
                            return $computedPath;
                        }

                        // If no processor registered return raw variable
                        return $variable;
                    },
                    $variableExpr
                );

                return \str_replace($variableExpr, $replaceExpr, $outputStr);
            },
            $templateContent
        );
    }

    /**
     * Parses the given TWIG template and replaces entity variables
     * (they start with "entity." or "computed.entity__") with expression that adds default filters.
     */
    private function addDefaultFiltersForEntityVariables(string $templateContent, TemplateData $templateData): string
    {
        /** @var EntityFormatExtension $formatExtension */
        $formatExtension = $this->twigEnvironment->getExtension(EntityFormatExtension::class);
        $errorMessage = $this->translator->trans($this->variableNotFoundMessage);

        return \preg_replace_callback(
            '/{{\s*([\w\_\-\.]+?)\s*}}/u',
            function ($match) use ($formatExtension, $errorMessage, $templateData) {
                [$result, $variable] = $match;
                $variablePath = $variable;
                if (str_starts_with($variable, $this->getComputedSectionPrefix())) {
                    $variablePath = $templateData->getVariablePath($variable);
                }
                if (str_starts_with($variablePath, $this->getEntitySectionPrefix())) {
                    $lastSeparatorPos = \strrpos($variablePath, $this->pathSeparator);
                    $result = $formatExtension->getSafeFormatExpression(
                        \lcfirst($this->inflector->classify(\substr($variablePath, $lastSeparatorPos + 1))),
                        $variable,
                        $this->getFinalVariable(\substr($variablePath, 0, $lastSeparatorPos), $templateData),
                        $errorMessage
                    );
                }

                return $result;
            },
            $templateContent
        );
    }

    private function getFinalVariable(string $variable, TemplateData $data): string
    {
        if ($data->hasComputedVariable($variable)) {
            return $data->getComputedVariablePath($variable);
        }

        $prefix = $variable;
        $suffix = '';
        $lastSeparatorPos = \strrpos($variable, $this->pathSeparator);
        while (false !== $lastSeparatorPos) {
            $suffix = \substr($variable, $lastSeparatorPos) . $suffix;
            $variable = \substr($variable, 0, $lastSeparatorPos);
            if ($data->hasComputedVariable($variable)) {
                $prefix = $data->getComputedVariablePath($variable);
                break;
            }
            $prefix = $variable;
            $lastSeparatorPos = \strrpos($variable, $this->pathSeparator);
        }

        return $prefix . $suffix;
    }

    private function getEntitySectionPrefix(): string
    {
        return $this->entitySection . $this->pathSeparator;
    }

    private function getComputedSectionPrefix(): string
    {
        return $this->computedSection . $this->pathSeparator;
    }
}
