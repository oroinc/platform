<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator;

/**
 * Detached values of an owner exposed as dynamic properties, which is what DeepCopy's reflection-based
 * matchers and filters operate on.
 */
#[\AllowDynamicProperties]
final class PropertyBag implements PropertyBagInterface
{
    /** Starts with an underscore, which an extended field name cannot, so no entry collides with it. */
    private const string OWNER_CLASS_PROPERTY = '_ownerClass';

    public function __construct(
        private readonly string $_ownerClass,
        array $data
    ) {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
    }

    #[\Override]
    public function getOwnerClass(): string
    {
        return $this->_ownerClass;
    }

    #[\Override]
    public function toArray(): array
    {
        $data = get_object_vars($this);
        unset($data[self::OWNER_CLASS_PROPERTY]);

        return $data;
    }
}
