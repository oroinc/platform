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

        $method = PhpMethod::create('getImports');

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
    private \$import;

    public function getImports()
    {
        if (null === \$this->import) {
            throw new \RuntimeException('Missing import for layout update');
        }
        echo 123;
        return \$this->import;
    }

    public function __construct(\$import)
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
