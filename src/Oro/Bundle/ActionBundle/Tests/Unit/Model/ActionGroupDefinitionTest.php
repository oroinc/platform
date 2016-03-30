<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ActionGroupDefinitionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ActionGroupDefinition */
    protected $actionGroupDefinition;

    protected function setUp()
    {
        $this->actionGroupDefinition = new ActionGroupDefinition();
    }

    protected function tearDown()
    {
        unset($this->actionGroupDefinition);
    }

    public function testGettersAndSetters()
    {
        static::assertPropertyAccessors(
            $this->actionGroupDefinition,
            [
                ['name', 'test'],
                ['actions', ['config1', 'config2'], []],
                ['conditions', ['config1', 'config2'], []],
                ['parameters', ['config1', 'config2'], []],
            ]
        );
    }
}
