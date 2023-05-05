<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator\Extension;

use Oro\Component\Layout\Loader\Generator\Extension\ImportLayoutUpdateVisitor;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\PhpUtils\ClassGenerator;

class ImportLayoutUpdateVisitorTest extends \PHPUnit\Framework\TestCase
{
    // @codingStandardsIgnoreStart
    public function testVisit()
    {
        $condition = new ImportLayoutUpdateVisitor();
        $phpClass = new ClassGenerator('ImportedLayoutUpdate');
        $visitContext = new VisitContext($phpClass);

        $condition->startVisit($visitContext);
        $visitContext->appendToUpdateMethodBody('echo 123;');
        $condition->endVisit($visitContext);

        $phpClass->addMethod('testMethod')->addBody($visitContext->getUpdateMethodBody());

        self::assertSame(
            <<<'CODE'
class ImportedLayoutUpdate implements Oro\Component\Layout\LayoutUpdateImportInterface, Oro\Component\Layout\IsApplicableLayoutUpdateInterface
{
    private $import = null;
    private $parentLayoutUpdate = null;

    public function isApplicable(Oro\Component\Layout\ContextInterface $context)
    {
        return true;
    }

    public function getImport()
    {
        return $this->import;
    }

    public function setImport(Oro\Component\Layout\Model\LayoutUpdateImport $import)
    {
        $this->import = $import;
    }

    public function setParentUpdate(Oro\Component\Layout\ImportsAwareLayoutUpdateInterface $parentLayoutUpdate)
    {
        $this->parentLayoutUpdate = $parentLayoutUpdate;
    }

    public function testMethod()
    {
        if (null === $this->import) {
            throw new \RuntimeException('Missing import configuration for layout update');
        }

        if ($this->parentLayoutUpdate instanceof \Oro\Component\Layout\IsApplicableLayoutUpdateInterface
            && !$this->parentLayoutUpdate->isApplicable($item->getContext())) {
            return;
        }

        $layoutManipulator = new \Oro\Component\Layout\ImportLayoutManipulator($layoutManipulator, $this->import);
        echo 123;
    }
}

CODE
            ,
            $visitContext->getClass()->print()
        );
    }
    //codingStandardsIgnoreEnd
}
