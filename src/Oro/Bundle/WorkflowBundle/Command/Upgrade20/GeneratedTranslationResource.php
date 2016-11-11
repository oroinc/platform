<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

class GeneratedTranslationResource
{
    /** @var array */
    private $path;

    /** @var string */
    private $key;

    /** @var string */
    private $value;

    /**
     * @param array $path
     * @param string $key
     * @param string $value
     */
    public function __construct(array $path, $key, $value)
    {
        $this->path = $path;
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @return array
     */
    public function getPath()
    {
        return $this->path;
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
    public function getValue()
    {
        return $this->value;
    }
}
