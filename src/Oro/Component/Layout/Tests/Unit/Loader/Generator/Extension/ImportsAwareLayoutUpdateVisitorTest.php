<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator\Extension;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use Oro\Component\Layout\Loader\Generator\Extension\ImportsAwareLayoutUpdateVisitor;
use Oro\Component\Layout\Loader\Generator\VisitContext;

class ImportsAwareLayoutUpdateVisitorTest extends \PHPUnit\Framework\TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {
        $imports = [
            [
                'id' => 'import_id',
                'root' => 'root_block_id',
                'namespace' => 'import_namespace'
            ],
            [
                'id' => 'import_id_2',
                'root' => 'root_block_id_2',
                'namespace' => 'import_namespace_2'
            ],
        ];
        $condition = new ImportsAwareLayoutUpdateVisitor($imports);
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
          0 => 
          array (
            'id' => 'import_id',
            'root' => 'root_block_id',
            'namespace' => 'import_namespace',
          ),
          1 => 
          array (
            'id' => 'import_id_2',
            'root' => 'root_block_id_2',
            'namespace' => 'import_namespace_2',
          ),
        );
    }
}
CLASS
        ,
        $strategy->generate($visitContext->getClass())
        );
    }
    //codingStandardsIgnoreEnd
}
