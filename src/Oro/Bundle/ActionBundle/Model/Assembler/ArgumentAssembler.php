<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\Argument;

class ArgumentAssembler extends AbstractAssembler
{
    /**
     * @param array $configuration
     * @return Argument[]
     */
    public function assemble(array $configuration)
    {
        $arguments = [];
        foreach ($configuration as $name => $options) {
            $arguments[$name] = $this->assembleArgument($name, $options);
        }

        return $arguments;
    }

    /**
     * @param string $name
     * @param array $options
     * @return Argument
     */
    protected function assembleArgument($name, array $options = [])
    {
        $argument = new Argument();
        $argument
            ->setName($name)
            ->setType($this->getOption($options, 'type'))
            ->setMessage($this->getOption($options, 'message', ''))
            ->setDefault($this->getOption($options, 'default'))
            ->setRequired($this->getOption($options, 'required', false));

        return $argument;
    }
}
