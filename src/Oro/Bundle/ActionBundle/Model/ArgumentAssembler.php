<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Exception\AssemblerException;

class ArgumentAssembler extends AbstractAssembler
{
    /**
     * @param array $configuration
     * @return ArrayCollection
     * @throws AssemblerException If configuration is invalid
     */
    public function assemble(array $configuration)
    {
        $arguments = new ArrayCollection();
        foreach ($configuration as $name => $options) {
            $arguments->set($name, $this->assembleArgument($name, $options));
        }

        return $arguments;
    }

    /**
     * @param string $name
     * @param array $options
     * @return Attribute
     */
    protected function assembleArgument($name, array $options = [])
    {
        $argument = new Argument();
        $argument->setName($name);
        $argument->setType($this->getOption($options, 'type'));
        $argument->setMessage($this->getOption($options, 'message', ''));
        $argument->setDefault($this->getOption($options, 'default'));
        $argument->setRequired($this->getOption($options, 'required', false));

        return $argument;
    }
}
