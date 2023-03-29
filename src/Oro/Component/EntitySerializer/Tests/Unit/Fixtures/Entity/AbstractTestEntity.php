<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

abstract class AbstractTestEntity
{
    public mixed $publicBaseProperty;
    protected mixed $protectedBaseProperty;
    private mixed $privateBaseProperty;
    private mixed $baseValue;

    public function __construct(mixed $baseValue)
    {
        $this->publicBaseProperty = $baseValue;
        $this->protectedBaseProperty = $baseValue;
        $this->privateBaseProperty = $baseValue;
        $this->baseValue = $baseValue;
    }

    public function baseValueGetter(): mixed
    {
        return $this->baseValue;
    }

    public function getBaseValueGetGetter(): mixed
    {
        return $this->baseValue;
    }

    public function isBaseValueIsGetter(): mixed
    {
        return $this->baseValue;
    }

    public function hasBaseValueHasGetter(): mixed
    {
        return $this->baseValue;
    }

    public function canBaseValueCanGetter(): mixed
    {
        return $this->baseValue;
    }
}
