<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\MatchApplicableChecker as BaseMatchApplicableChecker;

/**
 * {@inheritDoc}
 * Also this applicable checker can check whether a value in the context
 * is instance of a value of processor's attribute.
 * Such attributes should be marked as "class" related attributes.
 */
class MatchApplicableChecker extends BaseMatchApplicableChecker
{
    /** @var array [attribute name => true, ...] */
    private array $classAttributes;

    /**
     * @param string[] $ignoredAttributes
     * @param string[] $classAttributes
     */
    public function __construct(array $ignoredAttributes = ['group'], array $classAttributes = [])
    {
        parent::__construct($ignoredAttributes);
        $this->classAttributes = array_fill_keys($classAttributes, true);
    }

    /**
     * Marks an attribute as "class" related attribute.
     * Such attributes are compared using "instance of" operator rather that "equal".
     */
    public function addClassAttribute(string $attribute): void
    {
        $this->classAttributes[$attribute] = true;
    }

    /**
     * {@inheritDoc}
     */
    protected function isMatchScalars(mixed $value, mixed $contextValue, string $name): bool
    {
        return isset($this->classAttributes[$name]) && \is_string($value) && $value
            ? is_a($contextValue, $value, true)
            : parent::isMatchScalars($value, $contextValue, $name);
    }
}
