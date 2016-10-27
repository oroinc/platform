<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

class TestEntity
{
    public $publicProperty;
    protected $protectedProperty;
    private $privateProperty;

    private $value;

    public function __construct($value)
    {
        $this->publicProperty = $value;
        $this->protectedProperty = $value;
        $this->privateProperty = $value;
        $this->value = $value;
    }

    public function getPublicAccessor()
    {
        return $this->value;
    }

    public function isPublicIsAccessor()
    {
        return $this->value;
    }

    public function hasPublicHasAccessor()
    {
        return $this->value;
    }

    public function publicGetSetter($value = null)
    {
        if (null !== $value) {
            $this->value = $value;
        }

        return $this->value;
    }

    protected function getProtectedAccessor()
    {
        return 'foobar';
    }

    protected function isProtectedIsAccessor()
    {
        return 'foobar';
    }

    protected function hasProtectedHasAccessor()
    {
        return 'foobar';
    }

    private function getPrivateAccessor()
    {
        return 'foobar';
    }

    private function isPrivateIsAccessor()
    {
        return 'foobar';
    }

    private function hasPrivateHasAccessor()
    {
        return 'foobar';
    }

    public function getPublicAccessorWithParameter($prm)
    {
        return 'foobar';
    }

    public function isPublicIsAccessorWithParameter($prm)
    {
        return 'foobar';
    }

    public function hasPublicHasAccessorWithParameter($prm)
    {
        return 'foobar';
    }
}
