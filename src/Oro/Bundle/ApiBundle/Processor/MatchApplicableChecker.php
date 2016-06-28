<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\MatchApplicableChecker as BaseMatchApplicableChecker;

/**
 * {@inheritdoc}
 * Also this applicable checker can checks whether a value in the Context
 * is instance of a value of processor's attribute.
 * Such attributes should be marked as "class" attributes.
 */
class MatchApplicableChecker extends BaseMatchApplicableChecker
{
    /** @var string[] */
    protected $classAttributes;

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
     *
     * @param string $attribute
     */
    public function addClassAttribute($attribute)
    {
        $this->classAttributes[$attribute] = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function isMatchScalars($value, $contextValue, $name)
    {
        if (!isset($this->classAttributes[$name]) || !is_string($value) || !$value) {
            return parent::isMatchScalars($value, $contextValue, $name);
        }

        return 0 === strpos($value, '!')
            ? !is_a($contextValue, substr($value, 1), true)
            : is_a($contextValue, $value, true);
    }
}
