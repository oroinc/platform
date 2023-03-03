<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface as ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Twig\GetAttributeNodeExtension;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;

/**
 * The base class to render TWIG templates in a sandboxed environment.
 */
abstract class TemplateRenderer
{
    private const PATH_SEPARATOR   = '.';
    private const SYSTEM_SECTION   = 'system';
    private const ENTITY_SECTION   = 'entity';
    private const COMPUTED_SECTION = 'computed';
    private const ENTITY_PREFIX    = self::ENTITY_SECTION . self::PATH_SEPARATOR;
    private const COMPUTED_PREFIX  = self::COMPUTED_SECTION . self::PATH_SEPARATOR;

    /** @var TwigEnvironment */
    protected $environment;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var array [variable path => filter, ...] */
    private $systemVariableDefaultFilters = [];

    /** @var bool */
    private $sandboxConfigured = false;

    /** @var EntityVariableComputer */
    private $entityVariableComputer;

    /** @var EntityDataAccessor */
    private $entityDataAccessor;
    private Inflector $inflector;

    public function __construct(
        TwigEnvironment $environment,
        ConfigProvider $configProvider,
        VariableProcessorRegistry $variableProcessors,
        Inflector $inflector
    ) {
        $this->environment = $environment;
        $this->configProvider = $configProvider;
        $this->entityDataAccessor = new EntityDataAccessor($configProvider);
        $this->entityVariableComputer = new EntityVariableComputer(
            $configProvider,
            $variableProcessors,
            $this->entityDataAccessor
        );
        $this->inflector = $inflector;
    }

    /**
     * Registers TWIG extension in the sandbox.
     */
    public function addExtension(ExtensionInterface $extension): void
    {
        $this->environment->addExtension($extension);
    }

    /**
     * Adds TWIG filter that should be applied to the given system variable if not any other filter is applied to it.
     */
    public function addSystemVariableDefaultFilter(string $variable, string $filter): void
    {
        $this->systemVariableDefaultFilters[self::SYSTEM_SECTION . self::PATH_SEPARATOR . $variable] = $filter;
    }

    /**
     * Renders the given TWIG template.
     *
     * @throws \Twig\Error\Error if the given template cannot be rendered
     */
    public function renderTemplate(string $template, array $templateParams = []): string
    {
        $this->ensureSandboxConfigured();

        $data = $this->createTemplateData($templateParams);
        $template = $this->prepareTemplate($template, $data);
        $templateWrapper = $this->environment->createTemplate($template);

        return $templateWrapper->render($data->getData());
    }

    /**
     * Validates syntax of the given TWIG template.
     *
     * @throws \Twig\Error\SyntaxError if the given template has errors
     */
    public function validateTemplate(string $template): void
    {
        $this->ensureSandboxConfigured();
        $source = new Source($template, '');
        $stream = $this->environment->tokenize($source);

        $this->environment->parse($stream);
    }

    /**
     * @return string
     */
    abstract protected function getVariableNotFoundMessage(): ?string;

    protected function ensureSandboxConfigured(): void
    {
        if (!$this->sandboxConfigured) {
            $this->configureSandbox();
            $this->sandboxConfigured = true;
        }
    }

    protected function configureSandbox(): void
    {
        $config = $this->configProvider->getConfiguration();
        /** @var SandboxExtension $sandbox */
        $sandbox = $this->environment->getExtension(SandboxExtension::class);
        /** @var SecurityPolicy $security */
        $security = $sandbox->getSecurityPolicy();
        $security->setAllowedProperties($config[ConfigProvider::PROPERTIES]);
        $methods = $this->enableToStringMethod($config[ConfigProvider::METHODS]);
        $security->setAllowedMethods($methods);

        $formatExtension = new EntityFormatExtension();
        $formatExtension->setFormatters($config[ConfigProvider::DEFAULT_FORMATTERS]);
        $this->environment->addExtension($formatExtension);
        $getAttrNodeExtension = new GetAttributeNodeExtension();
        $this->environment->addExtension($getAttrNodeExtension);
    }

    private function enableToStringMethod(array $configMethods): array
    {
        foreach ($configMethods as $className => &$methods) {
            $methods[] = '__toString';
        }

        return $configMethods;
    }

    /**
     * Creates new instance of TemplateData that is used as the context for rendering of TWIG template.
     */
    protected function createTemplateData(array $templateParams): TemplateData
    {
        $templateParams[self::SYSTEM_SECTION] = $this->configProvider->getSystemVariableValues();

        return new TemplateData(
            $templateParams,
            $this->entityVariableComputer,
            $this->entityDataAccessor,
            self::SYSTEM_SECTION,
            self::ENTITY_SECTION,
            self::COMPUTED_SECTION
        );
    }

    /**
     * Prepares the given TWIG template to render.
     */
    protected function prepareTemplate(string $template, TemplateData $data): string
    {
        $template = $this->processDefaultFiltersForSystemVariables($template);

        if ($data->hasRootEntity()) {
            $template = $this->processEntityVariables($template, $data);
            $template = $this->processDefaultFiltersForEntityVariables($template, $data);
        }

        return $template;
    }

    /**
     * Parses the given TWIG template and adds filters to the system variables
     * defined in $this->systemVariableDefaultFilters.
     */
    private function processDefaultFiltersForSystemVariables(string $template): string
    {
        foreach ($this->systemVariableDefaultFilters as $var => $filter) {
            $template = \preg_replace('/{{\s' . $var . '\s}}/u', \sprintf('{{ %s|%s }}', $var, $filter), $template);
        }

        return $template;
    }

    /**
     * Parses the given TWIG template and replaces entity variables
     * (they start with "entity." or "computed.entity__") with expression that adds default filters.
     */
    private function processDefaultFiltersForEntityVariables(string $template, TemplateData $data): string
    {
        /** @var EntityFormatExtension $formatExtension */
        $formatExtension = $this->environment->getExtension(EntityFormatExtension::class);
        $errorMessage = $this->getVariableNotFoundMessage();

        return \preg_replace_callback(
            '/{{\s*([\w\_\-\.]+?)\s*}}/u',
            function ($match) use ($formatExtension, $errorMessage, $data) {
                [$result, $variable] = $match;
                $variablePath = $variable;
                if (str_starts_with($variable, self::COMPUTED_PREFIX)) {
                    $variablePath = $data->getVariablePath($variable);
                }
                if (str_starts_with($variablePath, self::ENTITY_PREFIX)) {
                    $lastSeparatorPos = \strrpos($variablePath, self::PATH_SEPARATOR);
                    $result = $formatExtension->getSafeFormatExpression(
                        \lcfirst($this->inflector->classify(\substr($variablePath, $lastSeparatorPos + 1))),
                        $variable,
                        $this->getFinalVariable(\substr($variablePath, 0, $lastSeparatorPos), $data),
                        $errorMessage
                    );
                }

                return $result;
            },
            $template
        );
    }

    /**
     * Parses the given TWIG template and computes values for entity variables
     * (they start with "entity.") that has a processor.
     */
    private function processEntityVariables(string $template, TemplateData $data): string
    {
        return \preg_replace_callback(
            // Find expression that should be displayed by twig (strings between {{ and }})
            '/{{(.+?)}}/u',
            function ($match) use ($data) {
                [$outputStr, $variableExpr] = $match;

                $replaceExpr = \preg_replace_callback(
                    // Search entity variables (they start with "entity.")
                    '/('. self::ENTITY_SECTION .'\.[\w|\.]+)/u',
                    function ($m) use ($data) {
                        // $variable contains string that starts with "entity."
                        $variable = $m[0];

                        // Trying to replace entity.* with data provided by processor if any
                        $computedPath = $this->entityVariableComputer->computeEntityVariable($variable, $data);
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
            $template
        );
    }

    private function getFinalVariable(string $variable, TemplateData $data): string
    {
        if ($data->hasComputedVariable($variable)) {
            return $data->getComputedVariablePath($variable);
        }

        $prefix = $variable;
        $suffix = '';
        $lastSeparatorPos = \strrpos($variable, self::PATH_SEPARATOR);
        while (false !== $lastSeparatorPos) {
            $suffix = \substr($variable, $lastSeparatorPos) . $suffix;
            $variable = \substr($variable, 0, $lastSeparatorPos);
            if ($data->hasComputedVariable($variable)) {
                $prefix = $data->getComputedVariablePath($variable);
                break;
            }
            $prefix = $variable;
            $lastSeparatorPos = \strrpos($variable, self::PATH_SEPARATOR);
        }

        return $prefix . $suffix;
    }
}
