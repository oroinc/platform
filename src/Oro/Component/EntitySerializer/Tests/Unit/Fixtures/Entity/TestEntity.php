<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

class TestEntity extends AbstractTestEntity
{
    public mixed $publicProperty;
    protected mixed $protectedProperty;
    private mixed $privateProperty;
    private mixed $value;

    public function __construct(mixed $value = null)
    {
        parent::__construct($value);
        $this->publicProperty = $value;
        $this->protectedProperty = $value;
        $this->privateProperty = $value;
        $this->value = $value;
    }

    public function getPublicAccessor(): mixed
    {
        return $this->value;
    }

    public function isPublicIsAccessor(): mixed
    {
        return $this->value;
    }

    public function hasPublicHasAccessor(): mixed
    {
        return $this->value;
    }

    public function canPublicCanAccessor(): mixed
    {
        return $this->value;
    }

    public function publicGetSetter(mixed $value = null): mixed
    {
        if (null !== $value) {
            $this->value = $value;
        }

        return $this->value;
    }

    public function valueGetter(): mixed
    {
        return $this->value;
    }

    public function getValueGetGetter(): mixed
    {
        return $this->value;
    }

    public function isValueIsGetter(): mixed
    {
        return $this->value;
    }

    public function hasValueHasGetter(): mixed
    {
        return $this->value;
    }

    public function canValueCanGetter(): mixed
    {
        return $this->value;
    }

    protected function getProtectedAccessor(): mixed
    {
        return 'foobar';
    }

    protected function isProtectedIsAccessor(): mixed
    {
        return 'foobar';
    }

    protected function hasProtectedHasAccessor(): mixed
    {
        return 'foobar';
    }

    protected function canProtectedCanAccessor(): mixed
    {
        return 'foobar';
    }

    private function getPrivateAccessor(): mixed
    {
        return 'foobar';
    }

    private function isPrivateIsAccessor(): mixed
    {
        return 'foobar';
    }

    private function hasPrivateHasAccessor(): mixed
    {
        return 'foobar';
    }

    private function canPrivateCanAccessor(): mixed
    {
        return 'foobar';
    }

    public function getPublicAccessorWithParameter(mixed $prm): mixed
    {
        return 'foobar';
    }

    public function isPublicIsAccessorWithParameter(mixed $prm): mixed
    {
        return 'foobar';
    }

    public function hasPublicHasAccessorWithParameter(mixed $prm): mixed
    {
        return 'foobar';
    }

    public function canPublicCanAccessorWithParameter(mixed $prm): mixed
    {
        return 'foobar';
    }
}
