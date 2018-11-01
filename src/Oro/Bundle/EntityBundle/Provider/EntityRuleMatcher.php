<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * Compares an entity or an entity field with on a set of rules this matcher contains.
 */
class EntityRuleMatcher
{
    const ENTITY = 'entity';
    const FIELD  = 'field';
    const TYPE   = 'type';

    /** @var EntityHierarchyProviderInterface */
    protected $entityHierarchyProvider;

    /** @var array */
    protected $rules;

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
        $rules
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->rules = $rules;
    }

    /**
     * Checks if the object (entity or field) with the given properties matches at least one rule
     *
     * @param array $objectProperties
     *
     * @return bool
     * @deprecated will be replaced with "isEntityMatched(string $entityClass): bool" in 3.0
     */
    public function isMatched($objectProperties)
    {
        if (is_string($objectProperties)) {
            $this->ensureRulesInitialized();

            if (isset($this->entityRules[$objectProperties])) {
                return true;
            }
            $parentClasses = $this->entityHierarchyProvider->getHierarchyForClassName($objectProperties);
            foreach ($parentClasses as $parentClass) {
                if (isset($this->entityRules[$parentClass])) {
                    return true;
                }
            }

            return false;
        }

        // BC layer
        $result = false;
        foreach ($this->rules as $rule) {
            if ($this->isRuleMatched($rule, $objectProperties)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Checks if the object (entity or field) with the given properties matches the given rule
     *
     * @param array $rule
     * @param array $objectProperties
     *
     * @return bool
     * @deprecated will be removed in 3.0
     */
    protected function isRuleMatched($rule, $objectProperties)
    {
        $matchCount = 0;
        foreach ($rule as $key => $val) {
            if (isset($objectProperties[$key])) {
                if ($key === 'entity') {
                    // special case to compare entity class names
                    if ($this->isEntityMatched($objectProperties[$key], $val)) {
                        $matchCount++;
                    }
                } elseif ($objectProperties[$key] === $val) {
                    $matchCount++;
                }
            }
        }

        return count($rule) === $matchCount;
    }

    /**
     * Checks if $entityClassName is equal to $className
     * or has $className as one of its parent entities/mapped superclasses
     *
     * @param string $entityClassName
     * @param string $className
     *
     * @return bool
     * @deprecated will be removed in 3.0
     */
    protected function isEntityMatched($entityClassName, $className)
    {
        if ($entityClassName === $className) {
            return true;
        }

        return in_array(
            $className,
            $this->entityHierarchyProvider->getHierarchyForClassName($entityClassName),
            true
        );
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
    public function isFieldMatched($entityClass, $fieldName, $fieldType = null)
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
    private function ensureRulesInitialized()
    {
        if (null !== $this->rules) {
            $this->initializeRules();
            $this->rules = null;
        }
    }

    /**
     * Parses rules and splits them to parts that are used to make the matching faster.
     */
    private function initializeRules()
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
