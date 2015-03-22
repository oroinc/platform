<?php

namespace Oro\Component\Layout\Extension\Theme\Generator;

interface ElementDependentLayoutUpdateInterface
{
    /**
     * @return string The id of an layout item to which this layout update depends
     */
    public function getElement();
}
