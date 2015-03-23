<?php

namespace Oro\Component\Layout\Loader\Generator;

use CG\Generator\PhpClass;
use CG\Generator\Writer;

class VisitContext
{
    /** @var PhpClass */
    protected $class;

    /** @var Writer */
    protected $writer;

    public function __construct(PhpClass $class)
    {
        $this->class  = $class;
        $this->writer = $this->createWriter();
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

    /**
     * @return Writer
     */
    public function getUpdateMethodWriter()
    {
        return $this->writer;
    }
}
