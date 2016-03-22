<?php

namespace Oro\Bundle\ActionBundle\Model;

class OperationActionGroup
{
    /** @var string */
    private $name;

    /** @var array */
    private $parametersMapping = [];

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
    public function getParametersMapping()
    {
        return $this->parametersMapping;
    }

    /**
     * @param array $parametersMapping
     * @return $this
     */
    public function setParametersMapping(array $parametersMapping)
    {
        $this->parametersMapping = $parametersMapping;

        return $this;
    }
}
