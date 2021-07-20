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

    /**
     * {@inheritdoc}
     */
    public function getCriteriaField()
    {
        return $this->criteriaField;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValue()
    {
        return $this->criteriaValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return $this->criteriaValueType;
    }
}
