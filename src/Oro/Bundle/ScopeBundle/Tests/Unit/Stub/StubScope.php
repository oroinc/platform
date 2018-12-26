<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Stub;

use Oro\Bundle\ScopeBundle\Entity\Scope;

class StubScope extends Scope
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var mixed
     */
    protected $scopeField;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @return mixed
     */
    public function getScopeField()
    {
        return $this->scopeField;
    }

    /**
     * @param mixed $scopeField
     */
    public function setScopeField($scopeField)
    {
        $this->scopeField = $scopeField;
    }

    /**
     * @param mixed $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        if (strpos($name, 'get') === 0) {
            $name = lcfirst(substr($name, 3));

            return $this->attributes[$name];
        }

        if (strpos($name, 'set') === 0) {
            $name = lcfirst(substr($name, 3));

            $this->attributes[$name] = $args[0];

            return $this;
        }

        throw new \InvalidArgumentException();
    }
}
