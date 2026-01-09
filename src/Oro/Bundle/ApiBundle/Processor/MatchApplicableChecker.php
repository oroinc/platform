<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\MatchApplicableChecker as BaseMatchApplicableChecker;

/**
 * Extends the base match applicable checker to support class-based attribute matching.
 *
 * This checker enhances the chain processor's applicability matching by allowing certain attributes
 * to be compared using the `instanceof` operator instead of equality comparison. This is particularly
 * useful for matching processor configurations that specify class names as attributes. It includes
 * special handling for outdated enum option entities to prevent double execution of processors
 * in scenarios where both `customize_loaded_data` and `customize_form_data` actions are involved.
 * Attributes can be marked as `class` attributes via the constructor or the {@see addClassAttribute} method.
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

    #[\Override]
    protected function isMatchScalars(mixed $value, mixed $contextValue, string $name): bool
    {
        return isset($this->classAttributes[$name]) && \is_string($value) && $value
            ? $this->isMatchClass($value, $contextValue)
            : parent::isMatchScalars($value, $contextValue, $name);
    }

    private function isMatchClass(mixed $value, mixed $contextValue): bool
    {
        if (ExtendHelper::isOutdatedEnumOptionEntity($value)) {
            return $contextValue === $value;
        }
        if (ExtendHelper::isOutdatedEnumOptionEntity($contextValue)) {
            return is_a(EnumOption::class, $value, true);
        }
        if (EnumOption::class === $contextValue) {
            // ignore all processors here to prevent double execution of the same processor
            // for "customize_loaded_data" and "customize_form_data" actions
            // when a processor has "class: Oro\Bundle\EntityExtendBundle\Entity\EnumOption" attribute
            return false;
        }

        return is_a($contextValue, $value, true);
    }
}
