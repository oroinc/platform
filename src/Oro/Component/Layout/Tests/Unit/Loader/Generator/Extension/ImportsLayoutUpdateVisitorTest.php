<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Core\DefaultGeneratorStrategy;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Generator\Extension\ImportsLayoutUpdateVisitor;

class ImportsLayoutUpdateVisitorTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {
        $import = [
            'id' => 'import_id',
            'root' => 'root_block_id',
            'namespace' => 'import_namespace'
        ];
        $condition    = new ImportsLayoutUpdateVisitor($import);
        $phpClass = PhpClass::create('LayoutUpdateWithImport');
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
class LayoutUpdateWithImport implements \Oro\Component\Layout\ImportsAwareLayoutUpdateInterface
{
    public function testMethod()
    {
        echo 123;
    }

    public function getImports()
    {
        return array (
          'id' => 'import_id',
          'root' => 'root_block_id',
          'namespace' => 'import_namespace',
        );
    }
}
CLASS
        ,
        $strategy->generate($visitContext->getClass()));
    }
    //codingStandardsIgnoreEnd
}
