<?php

namespace Oro\Bundle\EmailBundle\Processor;

interface VariableProcessorInterface
{
    /**
     * @param string $variable
     * @param array $definition
     * @param array $data
     *
     * @return string
     */
    public function process($variable, array $definition, array $data = []);
}
