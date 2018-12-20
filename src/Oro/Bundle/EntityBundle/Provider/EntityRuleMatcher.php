<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * Compares an entity or an entity field with a set of rules this matcher contains.
 */
class EntityRuleMatcher
{
    private const ENTITY = 'entity';
    private const FIELD  = 'field';
    private const TYPE   = 'type';

    /** @var EntityHierarchyProviderInterface */
    private $entityHierarchyProvider;

    /** @var array */
    private $rules;

    /** @var array [entity class => true, ...] */
    private $entityRules;

    /** @var array [entity class => [field name => true], ...] */
    private $fieldRules;

    /** @var array [data type => true, ...] */
    private $typeRules;

    /**
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     * @param array                            $rules
     */
    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        array $rules
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->rules = $rules;
    }

    /**
     * Checks if an entity matches at least one rule this matcher contains.
     *
     * @param string $entityClass
     *
     * @return bool
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
     * Checks if an entity field matches at least one rule this matcher contains.
     *
     * @param string      $entityClass
     * @param string      $fieldName
     * @param string|null $fieldType
     *
     * @return bool
     */
    public function isFieldMatched(string $entityClass, string $fieldName, string $fieldType = null): bool
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
