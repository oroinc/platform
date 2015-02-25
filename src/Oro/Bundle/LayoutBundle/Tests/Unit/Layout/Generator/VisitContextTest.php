<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Generator;

use CG\Generator\PhpClass;

use Oro\Bundle\LayoutBundle\Layout\Generator\VisitContext;

class VisitContextTest extends \PHPUnit_Framework_TestCase
{
    public function testContextClassGetter()
    {
        $class = $this->getClass();

        $visitContext = new VisitContext($class);
        $this->assertSame($class, $visitContext->getClass());
    }

    public function testCreateWriter()
    {
        $class = $this->getClass();

        $visitContext = new VisitContext($class);
        $writer       = $visitContext->createWriter();

        $this->assertInstanceOf('CG\Generator\Writer', $writer);

        $this->assertNotSame($writer, $visitContext->createWriter());
    }

    /**
     * @return PhpClass
     */
    protected function getClass()
    {
        return PhpClass::create(uniqid('testClassName', true));
    }
}
