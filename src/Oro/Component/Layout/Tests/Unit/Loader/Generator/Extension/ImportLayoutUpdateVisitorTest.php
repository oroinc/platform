<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Core\DefaultGeneratorStrategy;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Generator\Extension\ImportLayoutUpdateVisitor;

class ImportLayoutUpdateVisitorTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {

        $condition    = new ImportLayoutUpdateVisitor();
        $phpClass = PhpClass::create('ImportedLayoutUpdate');
        $visitContext = new VisitContext($phpClass);

        $method = PhpMethod::create('testMethod');

        $condition->startVisit($visitContext);
        $visitContext->getUpdateMethodWriter()->writeln('echo 123;');
        $condition->endVisit($visitContext);

        $method->setBody($visitContext->getUpdateMethodWriter()->getContent());
        $phpClass->setMethod($method);
        $strategy = new DefaultGeneratorStrategy();
        $this->assertSame(
<<<CLASS
use Oro\Component\Layout\ImportLayoutManipulator;

class ImportedLayoutUpdate implements \Oro\Component\Layout\LayoutUpdateImportInterface
{
    private \$import;

    public function testMethod()
    {
        if (null === \$this->import) {
            throw new \RuntimeException('Missing impost configuration for layout update');
        }

        \$layoutManipulator  = new ImportLayoutManipulator(\$layoutManipulator, \$this->import);
            echo 123;
    }

    public function setImport(\Oro\Component\Layout\Model\LayoutUpdateImport \$import)
    {
        \$this->import = \$import;
    }
}
CLASS
        ,
        $strategy->generate($visitContext->getClass()));
    }
    //codingStandardsIgnoreEnd
}
