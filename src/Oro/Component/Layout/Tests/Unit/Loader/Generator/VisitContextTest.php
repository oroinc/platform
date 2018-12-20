<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator;

use CG\Generator\PhpClass;
use Oro\Component\Layout\Loader\Generator\VisitContext;

class VisitContextTest extends \PHPUnit\Framework\TestCase
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

    public function testGetUpdateMethodWriter()
    {
        $class = $this->getClass();

        $visitContext = new VisitContext($class);
        $this->assertSame($visitContext->getUpdateMethodWriter(), $visitContext->getUpdateMethodWriter());
    }

    /**
     * @return PhpClass
     */
    protected function getClass()
    {
        return PhpClass::create(uniqid('testClassName', true));
    }
}
