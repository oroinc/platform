<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\Parameter;

class ParameterAssembler extends AbstractAssembler
{
    /**
     * @param array $configuration
     * @return Parameter[]
     */
    public function assemble(array $configuration)
    {
        $parameters = [];
        foreach ($configuration as $name => $options) {
            $parameters[$name] = $this->assembleParameter($name, $options);
        }

        return $parameters;
    }

    /**
     * @param string $name
     * @param array $options
     * @return Parameter
     */
    protected function assembleParameter($name, array $options = [])
    {
        $parameter = new Parameter($name);
        $parameter
            ->setType($this->getOption($options, 'type'))
            ->setMessage($this->getOption($options, 'message', ''))
            ->setDefault($this->getOption($options, 'default', Parameter::NO_DEFAULT));

        return $parameter;
    }
}
