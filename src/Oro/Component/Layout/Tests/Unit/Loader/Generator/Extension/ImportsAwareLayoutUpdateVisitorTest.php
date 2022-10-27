<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator\Extension;

use Oro\Component\Layout\Loader\Generator\Extension\ImportsAwareLayoutUpdateVisitor;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\PhpUtils\ClassGenerator;

class ImportsAwareLayoutUpdateVisitorTest extends \PHPUnit\Framework\TestCase
{
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
        $phpClass = new ClassGenerator('Test\LayoutUpdateWithImport');
        $visitContext = new VisitContext($phpClass);

        $condition->startVisit($visitContext);
        $visitContext->appendToUpdateMethodBody('echo 123;');
        $condition->endVisit($visitContext);

        $phpClass->addMethod('testMethod')->addBody($visitContext->getUpdateMethodBody());

        self::assertSame(
            <<<CODE
namespace Test;

class LayoutUpdateWithImport implements \Oro\Component\Layout\ImportsAwareLayoutUpdateInterface
{
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

    public function testMethod()
    {
        echo 123;
    }
}

CODE
            ,
            $visitContext->getClass()->print()
        );
    }
}
