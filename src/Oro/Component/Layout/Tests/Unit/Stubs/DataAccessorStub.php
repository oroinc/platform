<?php

namespace Oro\Component\Layout\Tests\Unit\Stubs;

use Oro\Component\Layout\DataAccessor;

class DataAccessorStub extends DataAccessor
{
    /**
     * @var array
     */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($name): mixed
    {
        return $this->data[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($name): bool
    {
        return array_key_exists($name, $this->data);
    }
}
