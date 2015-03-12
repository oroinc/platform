<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

interface ElementDependentLayoutUpdateInterface
{
    /**
     * @return string Element to which it depends
     */
    public function getElement();
}
