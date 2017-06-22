<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

interface GridMappedChildInterface
{
    /**
     * @param string $name
     * @return string
     */
    public function getMappedChildElementName($name);
}
