<?php

namespace Oro\Bundle\ActionBundle\Model;

class OperationActionGroup
{
    /** @var string */
    private $name;

    /** @var array */
    private $argumentsMapping = [];

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getArgumentsMapping()
    {
        return $this->argumentsMapping;
    }

    /**
     * @param array $argumentsMapping
     * @return $this
     */
    public function setArgumentsMapping(array $argumentsMapping)
    {
        $this->argumentsMapping = $argumentsMapping;

        return $this;
    }
}
