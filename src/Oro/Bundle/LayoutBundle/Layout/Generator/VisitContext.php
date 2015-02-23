<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

use CG\Generator\PhpClass;
use CG\Generator\Writer;

class VisitContext
{
    /** @var PhpClass */
    protected $class;

    public function __construct(PhpClass $class)
    {
        $this->class = $class;
    }

    /**
     * @return PhpClass
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return Writer
     */
    public function createWriter()
    {
        return new Writer();
    }
}
