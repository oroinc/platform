<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\OperationActionGroup;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OperationActionGroupTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var OperationActionGroup */
    protected $operationActionGroup;

    protected function setUp()
    {
        $this->operationActionGroup = new OperationActionGroup();
    }

    protected function tearDown()
    {
        unset($this->operationActionGroup);
    }

    public function testGettersAndSetters()
    {
        static::assertPropertyAccessors(
            $this->operationActionGroup,
            [
                ['name', 'test'],
                ['argumentsMapping', ['argument1', 'argument2'], []],
            ]
        );
    }
}
