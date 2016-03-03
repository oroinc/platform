<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\OperationDefinition;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OperationDefinitionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var OperationDefinition */
    protected $definition;

    protected function setUp()
    {
        $this->definition = new OperationDefinition();
    }

    protected function tearDown()
    {
        unset($this->definition);
    }

    public function testSetAndGetActions()
    {
        $this->definition->setActions('name1', ['func1', 'func2']);

        $this->assertEquals(['func1', 'func2'], $this->definition->getActions('name1'));
        $this->assertEquals(['name1' => ['func1', 'func2']], $this->definition->getActions());
    }

    public function testGetAllowedFunctions()
    {
        $this->assertEquals(
            [OperationDefinition::PREACTIONS, OperationDefinition::FORM_INIT],
            OperationDefinition::getAllowedActions()
        );
    }

    public function testGettersAndSetters()
    {
        static::assertPropertyAccessors(
            $this->definition,
            [
                ['name', 'test'],
                ['label', 'test'],
                ['enabled', false, true],
                ['entities', ['entity1', 'entity2'], []],
                ['routes', ['route1', 'route2'], []],
                ['datagrids', ['datagrid1', 'datagrid2'], []],
                ['applications', ['application1', 'application2'], []],
                ['order', 77, 0],
                ['frontendOptions', ['config1', 'config2'], []],
                ['buttonOptions', ['config1', 'config2'], []],
                ['datagridOptions', ['datagridConfig1', 'datagridConfig2'], []],
                ['formOptions', ['config1', 'config2'], []],
                ['formType', 'test_form_type'],
                ['attributes', ['config1', 'config2'], []],
                ['preconditions', ['cond1', 'cond2'], []],
                ['actionGroups', ['cond1', 'cond2'], []]
            ]
        );
    }
}
