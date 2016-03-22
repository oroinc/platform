<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Parameter;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ParameterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var Parameter */
    protected $parameter;

    protected function setUp()
    {
        $this->parameter = new Parameter();
    }

    protected function tearDown()
    {
        unset($this->actionGroupDefinition);
    }

    public function testGettersAndSetters()
    {
        static::assertPropertyAccessors(
            $this->parameter,
            [
                ['name', 'test'],
                ['type', 'TestType'],
                ['message', 'Test Message'],
                ['default', ['Test Default Value']],
                ['required', true, false],
            ]
        );
    }
}
