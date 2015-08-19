<?php

namespace Oro\Component\Layout\Loader\Generator;

interface ElementDependentLayoutUpdateInterface
{
    /**
     * @return string The id of an layout item to which this layout update depends
     */
    public function getElement();
}
