<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Security policy for Twig email templates.
 *
 * Works as a decorator for Twig security policy:
 *   - Adds the allowed methods and properties for entity classes from the template renderer config provider.
 *   - Adds getters for tags, functions, filters, methods and properties.
 */
class EmailTemplateSecurityPolicy implements SecurityPolicyInterface
{
    /**
     * @var array<string>
     */
    private array $tags = [];

    /**
     * @var array<string>
     */
    private array $functions = [];

    /**
     * @var array<string>
     */
    private array $filters = [];

    /**
     * @var array<string, array<string>> List of allowed methods grouped by entity class name.
     */
    private array $methods = [];

    /**
     * @var array<string, array<string>> List of allowed properties grouped by entity class name.
     */
    private array $properties = [];

    private bool $initialized = false;

    public function __construct(
        private SecurityPolicyInterface $securityPolicy,
        private TemplateRendererConfigProviderInterface $templateRendererConfigProvider,
    ) {
    }

    public function __call($name, $arguments)
    {
        return $this->securityPolicy->{$name}(...$arguments);
    }

    public function setAllowedTags(array $tags): void
    {
        if ($this->securityPolicy instanceof SecurityPolicy) {
            $this->securityPolicy->setAllowedTags($tags);
        }

        $this->tags = $tags;
    }

    public function setAllowedFunctions(array $functions): void
    {
        if ($this->securityPolicy instanceof SecurityPolicy) {
            $this->securityPolicy->setAllowedFunctions($functions);
        }

        $this->functions = $functions;
    }

    public function setAllowedFilters(array $filters): void
    {
        if ($this->securityPolicy instanceof SecurityPolicy) {
            $this->securityPolicy->setAllowedFilters($filters);
        }

        $this->filters = $filters;
    }

    public function setAllowedMethods(array $methods): void
    {
        if ($this->securityPolicy instanceof SecurityPolicy) {
            $this->securityPolicy->setAllowedMethods($methods);
        }

        $allowedMethods = array_map(static fn ($m) => array_map('strtolower', \is_array($m) ? $m : [$m]), $methods);

        $this->methods = $allowedMethods;
    }

    public function setAllowedProperties(array $properties): void
    {
        if ($this->securityPolicy instanceof SecurityPolicy) {
            $this->securityPolicy->setAllowedProperties($properties);
        }

        $this->properties = $properties;
    }

    /**
     * Gets the list of allowed Twig tags.
     *
     * @return array<string> List of allowed tag names.
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Gets the list of allowed Twig functions.
     *
     * @return array<string> List of allowed function names.
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * Gets the list of allowed Twig filters.
     *
     * @return array<string> List of allowed filter names.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Gets the list of allowed methods grouped by entity class name.
     *
     * @return array<string, array<string>> List of allowed methods grouped by entity class name.
     */
    public function getMethods(): array
    {
        $this->ensureInitialized();

        return $this->methods;
    }

    /**
     * Gets the list of allowed properties grouped by entity class name.
     *
     * @return array<string, array<string>> List of allowed properties grouped by entity class name.
     */
    public function getProperties(): array
    {
        $this->ensureInitialized();

        return $this->properties;
    }

    #[\Override]
    public function checkSecurity($tags, $filters, $functions): void
    {
        $this->ensureInitialized();

        $this->securityPolicy->checkSecurity($tags, $filters, $functions);
    }

    #[\Override]
    public function checkMethodAllowed($obj, $method): void
    {
        // __toString is a PHP string-coercion magic method, not an entity field accessor.
        if (strtolower($method) === '__tostring') {
            return;
        }
        $this->ensureInitialized();

        $this->securityPolicy->checkMethodAllowed($obj, $method);
    }

    #[\Override]
    public function checkPropertyAllowed($obj, $property): void
    {
        $this->ensureInitialized();

        $this->securityPolicy->checkPropertyAllowed($obj, $property);
    }

    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            $this->initialize();
        }
    }

    private function initialize(): void
    {
        $this->initialized = true;

        $configuration = $this->templateRendererConfigProvider->getConfiguration();

        $this->securityPolicy->setAllowedMethods($configuration[TemplateRendererConfigProviderInterface::METHODS]);
        $this->securityPolicy
            ->setAllowedProperties($configuration[TemplateRendererConfigProviderInterface::PROPERTIES]);
    }
}
