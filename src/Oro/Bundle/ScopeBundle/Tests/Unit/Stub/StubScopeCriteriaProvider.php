<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Stub;

use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;

class StubScopeCriteriaProvider implements ScopeCriteriaProviderInterface
{
    /** @var string */
    private $criteriaField;

    /** @var object|null */
    private $criteriaValue;

    /** @var string */
    private $criteriaValueType;

    /**
     * @param string      $criteriaField
     * @param object|null $criteriaValue
     * @param string      $criteriaValueType
     */
    public function __construct(string $criteriaField, $criteriaValue, string $criteriaValueType)
    {
        $this->criteriaField = $criteriaField;
        $this->criteriaValue = $criteriaValue;
        $this->criteriaValueType = $criteriaValueType;
    }

    #[\Override]
    public function getCriteriaField()
    {
        return $this->criteriaField;
    }

    #[\Override]
    public function getCriteriaValue()
    {
        return $this->criteriaValue;
    }

    #[\Override]
    public function getCriteriaValueType()
    {
        return $this->criteriaValueType;
    }
}
