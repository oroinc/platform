<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Fixtures;

class DummyObject
{
    public function __construct(
        private readonly string $strValue = ''
    ) {
    }

    public function __toString(): string
    {
        return $this->strValue;
    }
}
