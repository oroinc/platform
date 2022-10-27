<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Util\Fixtures;

class JsonSerializableClass implements \JsonSerializable
{
    public $keyPublic = 'public';

    public function jsonSerialize(): array
    {
        return [
            'key' => 'value',
        ];
    }
}
