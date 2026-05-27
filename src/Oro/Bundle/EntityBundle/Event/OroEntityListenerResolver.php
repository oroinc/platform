<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Event;

use Doctrine\Bundle\DoctrineBundle\Mapping\ContainerEntityListenerResolver;
use Doctrine\Bundle\DoctrineBundle\Mapping\EntityListenerServiceResolver;
use Psr\Container\ContainerInterface;

/**
 * Decorates Doctrine's EntityListenerServiceResolver to provide
 * additional control over entity listeners, allowing dynamic disabling and enabling of listeners
 * based on class name regular expressions.
 *
 * @see EntityListenerServiceResolver
 */
class OroEntityListenerResolver implements EntityListenerServiceResolver
{
    private ContainerEntityListenerResolver $innerResolver;

    /**
     * @var list<string>
     */
    private array $disabledListenerRegexps = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        $this->innerResolver = new ContainerEntityListenerResolver($container);
    }

    #[\Override]
    public function clear($className = null): void
    {
        $this->innerResolver->clear($className);
    }

    #[\Override]
    public function resolve($className): object
    {
        if ($this->isListenerEnabled($className)) {
            return $this->innerResolver->resolve($className);
        }

        return new NoopEventListener();
    }

    #[\Override]
    public function register($object): void
    {
        $this->innerResolver->register($object);
    }

    #[\Override]
    public function registerService($className, $serviceId): void
    {
        $this->innerResolver->registerService($className, $serviceId);
    }

    /**
     * Disables entity listeners whose class names match the given regular expression.
     *
     * @param string $classNameRegexp Regular expression to match listener class names. Defaults to '.*'.
     */
    public function disableListeners(string $classNameRegexp = '.*'): void
    {
        $this->disabledListenerRegexps[] = $classNameRegexp;
    }

    /**
     * Clears all disabled entity listener regular expressions, re-enabling all listeners.
     */
    public function clearDisabledListeners(): void
    {
        $this->disabledListenerRegexps = [];
    }

    /**
     * Checks if any entity listener class name regular expressions are currently set to disable listeners.
     *
     * @return bool True if there are disabled listener regexps, false otherwise.
     */
    public function hasDisabledListeners(): bool
    {
        return !empty($this->disabledListenerRegexps);
    }

    private function isListenerEnabled(string $className): bool
    {
        foreach ($this->disabledListenerRegexps as $regexp) {
            if (preg_match('~' . $regexp . '~', $className)) {
                return false;
            }
        }

        return true;
    }
}
