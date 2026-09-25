<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Stub;

class MenuItemConditionStub
{
    public function __construct(
        private string $value
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
