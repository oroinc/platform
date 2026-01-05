<?php

namespace Oro\Bundle\SecurityBundle\Attribute;

use Attribute;
use InvalidArgumentException;

/**
 * The attribute that can be used to reference another ACL attribute.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class AclAncestor
{
    public function __construct(
        private ?string $id = null,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromArray(?array $data = null): static
    {
        if ($data === null) {
            return new static();
        }

        $data['value'] ??= null;
        if (empty($data['value']) || str_contains($data['value'], ' ')) {
            throw new InvalidArgumentException('ACL id must not be empty or contain blank spaces.');
        }

        return new static(
            id: $data['value'],
        );
    }

    /**
     * Gets id of ACL attribute this ancestor is referred to
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function __serialize(): array
    {
        return [$this->id];
    }

    public function __unserialize(array $serialized): void
    {
        [$this->id] = $serialized;
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return AclAncestor A new instance of a AclAncestor object
     */
    // phpcs:disable
    public static function __set_state($data)
    {
        $result     = AclAncestor::fromArray();
        $result->id = $data['id'];

        return $result;
    }
    // phpcs:enable
}
