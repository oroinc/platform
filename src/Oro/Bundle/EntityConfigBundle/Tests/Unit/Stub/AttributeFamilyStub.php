<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;

class AttributeFamilyStub extends AttributeFamily
{
    use LocalizedEntityTrait;

    private array $localizedFields = [
        'label' => 'labels'
    ];

    public function setImage($image)
    {
    }

    public function getImage()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function __call($name, $arguments)
    {
        return $this->localizedMethodCall($this->localizedFields, $name, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function __get(string $name)
    {
        if (\array_key_exists($name, $this->localizedFields)) {
            return $this->localizedFieldGet($this->localizedFields, $name);
        }

        throw new \RuntimeException(sprintf('It is not expected to get non-existing property "%s".', $name));
    }

    /**
     * {@inheritDoc}
     */
    public function __set(string $name, $value): void
    {
        if (\array_key_exists($name, $this->localizedFields)) {
            $this->localizedFieldSet($this->localizedFields, $name, $value);

            return;
        }

        throw new \RuntimeException(sprintf('It is not expected to set non-existing property "%s".', $name));
    }
}
