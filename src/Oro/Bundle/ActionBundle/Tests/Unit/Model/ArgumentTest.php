<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Argument;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ArgumentTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var Argument */
    protected $argument;

    protected function setUp()
    {
        $this->argument = new Argument();
    }

    protected function tearDown()
    {
        unset($this->actionGroupDefinition);
    }

    public function testGettersAndSetters()
    {
        static::assertPropertyAccessors(
            $this->argument,
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
