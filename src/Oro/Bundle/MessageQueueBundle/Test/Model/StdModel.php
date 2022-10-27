<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Model;

/**
 * Model that stores arbitrary data.
 */
class StdModel implements \JsonSerializable
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function jsonSerialize(): array
    {
        return ['error' => 'Object of this class must not be encoded to JSON'];
    }
}
