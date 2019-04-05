<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface as ConfigProvider;

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

    /** @var \Twig_Environment */
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

    /**
     * @param \Twig_Environment         $environment
     * @param ConfigProvider            $configProvider
     * @param VariableProcessorRegistry $variableProcessors
     */
    public function __construct(
        \Twig_Environment $environment,
        ConfigProvider $configProvider,
        VariableProcessorRegistry $variableProcessors
    ) {
        $this->environment = $environment;
        $this->configProvider = $configProvider;
        $this->entityDataAccessor = new EntityDataAccessor($configProvider);
        $this->entityVariableComputer = new EntityVariableComputer(
            $configProvider,
            $variableProcessors,
            $this->entityDataAccessor
        );
    }

    /**
     * Registers TWIG extension in the sandbox.
     *
     * @param \Twig_ExtensionInterface $extension
     */
    public function addExtension(\Twig_ExtensionInterface $extension): void
    {
        $this->environment->addExtension($extension);
    }

    /**
     * Adds TWIG filter that should be applied to the given system variable if not any other filter is applied to it.
     *
     * @param string $variable
     * @param string $filter
     */
    public function addSystemVariableDefaultFilter(string $variable, string $filter): void
    {
        $this->systemVariableDefaultFilters[self::SYSTEM_SECTION . self::PATH_SEPARATOR . $variable] = $filter;
    }

    /**
     * Renders the given TWIG template.
     *
     * @param string $template
     * @param array  $templateParams
     *
     * @return string
     *
     * @throws \Twig_Error if the given template cannot be rendered
     */
    public function renderTemplate(string $template, array $templateParams = []): string
    {
        $this->ensureSandboxConfigured();

        $data = $this->createTemplateData($templateParams);
        $template = $this->prepareTemplate($template, $data);

        return $this->environment->render($template, $data->getData());
    }

    /**
     * Validates syntax of the given TWIG template.
     *
     * @param string $template
     *
     * @throws \Twig_Error_Syntax if the given template has errors
     */
    public function validateTemplate(string $template): void
    {
        $this->ensureSandboxConfigured();

        $this->environment->parse($this->environment->tokenize($template));
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
        /** @var \Twig_Extension_Sandbox $sandbox */
        $sandbox = $this->environment->getExtension('sandbox');
        /** @var \Twig_Sandbox_SecurityPolicy $security */
        $security = $sandbox->getSecurityPolicy();
        $security->setAllowedProperties($config[ConfigProvider::PROPERTIES]);
        $security->setAllowedMethods($config[ConfigProvider::METHODS]);

        $formatExtension = new EntityFormatExtension();
        $formatExtension->setFormatters($config[ConfigProvider::DEFAULT_FORMATTERS]);
        $this->environment->addExtension($formatExtension);
    }

    /**
     * Creates new instance of TemplateData that is used as the context for rendering of TWIG template.
     *
     * @param array $templateParams
     *
     * @return TemplateData
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
     *
     * @param string       $template
     * @param TemplateData $data
     *
     * @return string
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
     *
     * @param string $template
     *
     * @return string
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
     * (they are starts with "entity." or "computed.entity__") with expression that adds default filters.
     *
     * @param string       $template
     * @param TemplateData $data
     *
     * @return string
     */
    private function processDefaultFiltersForEntityVariables(string $template, TemplateData $data): string
    {
        /** @var EntityFormatExtension $formatExtension */
        $formatExtension = $this->environment->getExtension(EntityFormatExtension::class);
        $errorMessage = $this->getVariableNotFoundMessage();

        return \preg_replace_callback(
            '/{{\s([\w\_\-\.]+?)\s}}/u',
            function ($match) use ($formatExtension, $errorMessage, $data) {
                list($result, $variable) = $match;
                $variablePath = $variable;
                if (\strpos($variable, self::COMPUTED_PREFIX) === 0) {
                    $variablePath = $data->getVariablePath($variable);
                }
                if (\strpos($variablePath, self::ENTITY_PREFIX) === 0) {
                    $lastSeparatorPos = \strrpos($variablePath, self::PATH_SEPARATOR);
                    $result = $formatExtension->getSafeFormatExpression(
                        \lcfirst(Inflector::classify(\substr($variablePath, $lastSeparatorPos + 1))),
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
     * (they are starts with "entity.") that has a processor.
     *
     * @param string       $template
     * @param TemplateData $data
     *
     * @return string
     */
    private function processEntityVariables(string $template, TemplateData $data): string
    {
        return \preg_replace_callback(
            '/({{\s)([\w\_\-\.]+?)((\|.+)*\s}})/u',
            function ($match) use ($data) {
                list($result, $prefix, $variable, $suffix) = $match;
                if (\strpos($variable, self::ENTITY_PREFIX) === 0) {
                    $computedPath = $this->entityVariableComputer->computeEntityVariable($variable, $data);
                    if ($computedPath) {
                        $result = $prefix . $computedPath . $suffix;
                    }
                }

                return $result;
            },
            $template
        );
    }

    /**
     * @param string       $variable
     * @param TemplateData $data
     *
     * @return string
     */
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
