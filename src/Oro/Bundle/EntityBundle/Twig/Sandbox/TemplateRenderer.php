<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Util\ClassUtils;
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
    private const PROCESSOR        = 'processor';

    /** @var \Twig_Environment */
    protected $environment;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var VariableProcessorRegistry */
    private $variableProcessors;

    /** @var array [variable path => filter, ...] */
    private $systemVariableDefaultFilters = [];

    /** @var bool */
    private $sandboxConfigured = false;

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
        $this->variableProcessors = $variableProcessors;
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

        $templateParams[self::SYSTEM_SECTION] = $this->configProvider->getSystemVariableValues();
        $data = new TemplateData(
            $templateParams,
            self::SYSTEM_SECTION,
            self::ENTITY_SECTION,
            self::COMPUTED_SECTION
        );

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

        if ($data->hasEntityData()) {
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
            '/{{\s([\w\_\-\.]*?)\s}}/u',
            function ($match) use ($formatExtension, $errorMessage, $data) {
                list($result, $variable) = $match;
                $variablePath = $variable;
                if (\strpos($variable, self::COMPUTED_PREFIX) === 0) {
                    $variablePath = $data->getVariablePath($variable);
                }
                if (\strpos($variablePath, self::ENTITY_PREFIX) === 0) {
                    $lastDelimiterPos = \strrpos($variablePath, self::PATH_SEPARATOR);
                    $parentVariable = \substr($variablePath, 0, $lastDelimiterPos);
                    if ($data->hasComputedVariable($parentVariable)) {
                        $parentVariable = $data->getComputedVariablePath($parentVariable);
                    }
                    $result = $formatExtension->getSafeFormatExpression(
                        \lcfirst(Inflector::classify(\substr($variablePath, $lastDelimiterPos + 1))),
                        $variable,
                        $parentVariable,
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
                    $computedPath = $this->tryComputeEntityVariable($variable, $data);
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
     * @return string|null
     */
    private function tryComputeEntityVariable(string $variable, TemplateData $data): ?string
    {
        if ($data->hasComputedVariable($variable)) {
            return $data->getComputedVariablePath($variable);
        }

        $path = \explode(self::PATH_SEPARATOR, $variable);
        if (\count($path) === 2) {
            $result = null;
            if ($this->tryProcessEntityVariable($variable, $data->getEntityData(), $path[1], $data)
                && $data->hasComputedVariable($variable)
            ) {
                $result = $data->getComputedVariablePath($variable);
            }

            return $result;
        }

        return $this->tryComputeNestedEntityVariable(
            $variable,
            $data,
            $path[0],
            $data->getEntityData(),
            \array_slice($path, 1)
        );
    }

    /**
     * @param string       $variable
     * @param TemplateData $data
     * @param string       $rootVariable
     * @param object       $rootEntity
     * @param string[]     $path
     *
     * @return string|null
     */
    private function tryComputeNestedEntityVariable(
        string $variable,
        TemplateData $data,
        $rootVariable,
        $rootEntity,
        array $path
    ): ?string {
        $pathCount = \count($path);
        if (1 === $pathCount) {
            $result = null;
            if ($this->tryProcessEntityVariable($variable, $rootEntity, $path[0], $data)) {
                if ($data->hasComputedVariable($variable)) {
                    $result = $data->getComputedVariablePath($variable);
                }
            } elseif ($data->hasComputedVariable($rootVariable)) {
                $result = $data->getComputedVariablePath($rootVariable) . self::PATH_SEPARATOR . $path[0];
            }

            return $result;
        }

        $propertyPath = \implode(self::PATH_SEPARATOR, $path);
        $propertyVariable = \substr($variable, 0, -\strlen($propertyPath))
            . \str_replace(self::PATH_SEPARATOR, '_', $propertyPath);
        if ($this->tryProcessEntityVariable($propertyVariable, $rootEntity, $propertyPath, $data)
            && $data->hasComputedVariable($propertyVariable)
        ) {
            return $data->getComputedVariablePath($propertyVariable);
        }

        $parentValue = null;
        $parentVariable = $rootVariable . self::PATH_SEPARATOR . $path[0];
        if ($data->hasComputedVariable($parentVariable)) {
            $parentValue = $data->getComputedVariable($parentVariable);
        } elseif (!$this->tryProcessEntityVariable($parentVariable, $rootEntity, $path[0], $data)) {
            $parentValue = $this->getEntityFieldValue($rootEntity, $path[0]);
        } elseif ($data->hasComputedVariable($parentVariable)) {
            $parentValue = $data->getComputedVariable($parentVariable);
        }
        if (!\is_object($parentValue)) {
            return null;
        }

        return $this->tryComputeNestedEntityVariable(
            $variable,
            $data,
            $parentVariable,
            $parentValue,
            \array_slice($path, 1)
        );
    }

    /**
     * @param string       $variable
     * @param object       $entity
     * @param string       $propertyName
     * @param TemplateData $data
     *
     * @return bool
     */
    private function tryProcessEntityVariable(
        string $variable,
        $entity,
        string $propertyName,
        TemplateData $data
    ): bool {
        $processorDefinitions = $this->configProvider->getEntityVariableProcessors(ClassUtils::getClass($entity));
        if (!isset($processorDefinitions[$propertyName][self::PROCESSOR])) {
            return false;
        }

        $processorDefinition = $processorDefinitions[$propertyName];
        if (!$this->variableProcessors->has($processorDefinition[self::PROCESSOR])) {
            return false;
        }

        if (!$data->hasComputedVariable($variable)) {
            $this->variableProcessors->get($processorDefinition[self::PROCESSOR])
                ->process($variable, $processorDefinition, $data);
        }

        return true;
    }

    /**
     * @param object $entity
     * @param string $propertyName
     *
     * @return mixed
     */
    private function getEntityFieldValue($entity, string $propertyName)
    {
        $result = null;
        $entityClass = ClassUtils::getClass($entity);
        $config = $this->configProvider->getConfiguration();
        if (isset($config[ConfigProvider::ACCESSORS][$entityClass])) {
            $accessors = $config[ConfigProvider::ACCESSORS][$entityClass];
            if (\array_key_exists($propertyName, $accessors)) {
                $accessor = $accessors[$propertyName];
                try {
                    if ($accessor) {
                        // method
                        $result = $entity->{$accessor}();
                    } else {
                        // property
                        $result = $entity->{$accessor};
                    }
                } catch (\Throwable $e) {
                    // ignore any errors here
                }
            }
        }

        return $result;
    }
}
