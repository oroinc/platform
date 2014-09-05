<?php

namespace Oro\Bundle\ConfigBundle\Config\ApiTree;

class VariableDefinition
{
    /** @var string */
    protected $key;

    /** @var string */
    protected $type;

    /**
     * @param string $key
     * @param string $type
     */
    public function __construct($key, $type)
    {
        $this->key  = $key;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'key'  => $this->key,
            'type' => $this->type,
        ];
    }
}
