<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

/**
 * Base for the levels of an audit type whose object class is not an entity of the application: which levels
 * the application has, and how each of them maps to an object class and to the label of that class.
 */
abstract class AbstractAuditLevelProvider
{
    private ?array $levels = null;

    public function __construct(
        private readonly string $classPrefix,
        private readonly string $classSuffix,
        private readonly string $labelPrefix,
        private readonly string $genericLabel
    ) {
    }

    /**
     * @return array<string, string> [object class => level] of every level of this application
     */
    public function all(): array
    {
        if (null === $this->levels) {
            $this->levels = [];
            foreach ($this->getLevels() as $level) {
                $this->levels[$this->getClassForLevel($level)] = $level;
            }
        }

        return $this->levels;
    }

    public function isType(?string $objectClass): bool
    {
        if (null === $objectClass) {
            return false;
        }

        return isset($this->all()[$objectClass])
            || ('' !== $this->getLevelFromClass($objectClass)
                && str_starts_with($objectClass, $this->classPrefix)
                && str_ends_with($objectClass, $this->classSuffix));
    }

    public function getLabelKey(string $objectClass): ?string
    {
        $level = $this->all()[$objectClass] ?? null;

        return null !== $level ? $this->labelPrefix . $this->underscore($level) : null;
    }

    public function getGenericLabel(string $objectClass): string
    {
        $words = str_replace('_', ' ', $this->underscore($this->getLevelFromClass($objectClass)));

        return $this->genericLabel . ': ' . ucwords($words);
    }

    /**
     * @return string[]
     */
    abstract protected function getLevels(): array;

    protected function getClassForLevel(string $level): string
    {
        $name = str_replace(' ', '', ucwords(str_replace('_', ' ', $level)));

        return $this->classPrefix . $name . $this->classSuffix;
    }

    private function getLevelFromClass(string $objectClass): string
    {
        return substr($objectClass, \strlen($this->classPrefix), -\strlen($this->classSuffix));
    }

    private function underscore(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
