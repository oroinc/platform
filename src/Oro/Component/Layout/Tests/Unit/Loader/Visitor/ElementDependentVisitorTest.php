<?php

declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Visitor;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\ElementDependentVisitor;
use Oro\Component\PhpUtils\ClassGenerator;
use PHPUnit\Framework\TestCase;

class ElementDependentVisitorTest extends TestCase
{
    public function testVisit(): void
    {
        $conditionObject = new ElementDependentVisitor('header');

        $phpClass = new ClassGenerator('LayoutUpdateClass');
        $visitContext = new VisitContext($phpClass);

        $conditionObject->startVisit($visitContext);
        $conditionObject->endVisit($visitContext);

        self::assertSame(
            <<<CONTENT
class LayoutUpdateClass implements Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface
{
    public function getElement()
    {
        return 'header';
    }
}

CONTENT
            ,
            $visitContext->getClass()->print()
        );
    }
}
