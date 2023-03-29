<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

class TestEntityWithMagicMethods
{
    public mixed $publicProperty = null;
    private ?string $typedAttribute = null;
    private mixed $magicAttribute = null;

    public function getTypedAttribute(): ?string
    {
        return $this->typedAttribute;
    }

    public function setTypedAttribute(?string $value): void
    {
        $this->typedAttribute = $value;
    }

    public function __set($name, $value)
    {
        if ('magicAttribute' === $name) {
            $this->magicAttribute = $value;
        }
    }

    public function __get($name)
    {
        if ('magicAttribute' === $name) {
            return $this->magicAttribute;
        }

        throw new \InvalidArgumentException(sprintf('The attribute "%s" does not exist.', $name));
    }

    public function __isset($name)
    {
        return 'magicAttribute' === $name;
    }

    public function __unset($name)
    {
        if ('magicAttribute' === $name) {
            $this->magicAttribute = null;
        }
    }
}
