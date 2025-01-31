<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * Compares an entity or an entity field with a set of exclusion rules this matcher contains.
 */
class EntityRuleMatcher
{
    private const ENTITY = 'entity';
    private const FIELD = 'field';
    private const TYPE = 'type';

    private EntityHierarchyProviderInterface $entityHierarchyProvider;
    private ?array $rules;
    /** @var array|null [entity class => true, ...] */
    private ?array $entityRules = null;
    /** @var array|null [entity class => [field name => true], ...] */
    private ?array $fieldRules = null;
    /** @var array|null [data type => true, ...] */
    private ?array $typeRules = null;

    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        array $rules
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->rules = $rules;
    }

    /**
     * Checks if an entity matches at least one rule this matcher contains.
     */
    public function isEntityMatched(string $entityClass): bool
    {
        $this->ensureRulesInitialized();

        if (isset($this->entityRules[$entityClass])) {
            return true;
        }
        $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($entityClass);
        foreach ($parentClasses as $parentClass) {
            if (isset($this->entityRules[$parentClass])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if an entity field matches at least one field or entity rule this matcher contains.
     */
    public function isFieldMatched(string $entityClass, string $fieldName, ?string $fieldType = null): bool
    {
        $this->ensureRulesInitialized();

        if ($fieldType && isset($this->typeRules[$fieldType])) {
            return true;
        }

        if (isset($this->entityRules[$entityClass]) || isset($this->fieldRules[$entityClass][$fieldName])) {
            return true;
        }
        $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($entityClass);
        foreach ($parentClasses as $parentClass) {
            if (isset($this->entityRules[$parentClass]) || isset($this->fieldRules[$parentClass][$fieldName])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Makes sure that rules are parsed and ready to use.
     */
    private function ensureRulesInitialized(): void
    {
        if (null !== $this->rules) {
            $this->initializeRules();
            $this->rules = null;
        }
    }

    /**
     * Parses rules and splits them to parts that are used to make the matching faster.
     */
    private function initializeRules(): void
    {
        $this->entityRules = [];
        $this->fieldRules = [];
        $this->typeRules = [];
        foreach ($this->rules as $rule) {
            if (!empty($rule[self::ENTITY])) {
                $entity = $rule[self::ENTITY];
                if (empty($rule[self::FIELD])) {
                    $this->entityRules[$entity] = true;
                } else {
                    $this->fieldRules[$entity][$rule[self::FIELD]] = true;
                }
            } elseif (!empty($rule[self::TYPE])) {
                $this->typeRules[$rule[self::TYPE]] = true;
            }
        }
    }
}
