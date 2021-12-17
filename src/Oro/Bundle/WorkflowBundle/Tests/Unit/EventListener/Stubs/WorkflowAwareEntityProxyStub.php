<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Stubs;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;

class WorkflowAwareEntityProxyStub extends WorkflowAwareEntity
{
    protected string $hiddenProperty = 'hiddenPropertyValue';

    public function __get($property)
    {
        return $this->{$property};
    }

    public function __set($property, $value)
    {
        if ('hiddenProperty' === $property) {
            throw new \RuntimeException('Trying to set property "hiddenProperty" that forbid to rewrite.');
        }

        $this->{$property} = $value;
    }

    public function __isset(string $property): bool
    {
        return property_exists($this, $property);
    }
}
