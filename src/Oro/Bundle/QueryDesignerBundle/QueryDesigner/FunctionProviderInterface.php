<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;

interface FunctionProviderInterface
{
    /**
     * Returns function definition
     *
     * @param string $name
     * @param string $groupName
     * @param string $groupType
     * @return array
     * @throws InvalidConfigurationException if the function was not found
     */
    public function getFunction($name, $groupName, $groupType);
}
