<?php

namespace Oro\Bundle\CacheBundle\Tests\Functional\Stub;

class SetStateClassStub
{
    /**
     * @var array
     */
    public static $values = [];

    /**
     * @param array $data
     *
     * @return SetStateClassStub
     */
    public static function __set_state($data)
    {
        self::$values = $data;

        return new self($data['value']);
    }
}
