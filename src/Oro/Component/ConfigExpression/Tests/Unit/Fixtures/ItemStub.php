<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Fixtures;

class ItemStub
{
    protected $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function __set($name, $value)
    {
        if ($name === 'data') {
            $this->data = $value;
        } else {
            $this->data[$name] = $value;
        }
    }

    public function __get($name)
    {
        if ($name === 'data') {
            return $this->data;
        } else {
            if (isset($this->data[$name]) || array_key_exists($name, $this->data)) {
                return $this->data[$name];
            }

            $trace = debug_backtrace();
            trigger_error(
                sprintf(
                    'Undefined property "%s" via __get() in %s on line %s',
                    $name,
                    $trace[0]['file'],
                    $trace[0]['line']
                ),
                E_USER_NOTICE
            );

            return null;
        }
    }

    public function __isset($name)
    {
        return isset($this->data[$name]) || array_key_exists($name, $this->data);
    }

    public function getData()
    {
        return $this->data;
    }
}
