<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OperationDefinitionTest extends \PHPUnit\Framework\TestCase
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
    
    public function testSetAndGetConditions()
    {
        $this->definition->setConditions('name1', ['cond1', 'cond2']);

        $this->assertEquals(['cond1', 'cond2'], $this->definition->getConditions('name1'));
        $this->assertEquals(['name1' => ['cond1', 'cond2']], $this->definition->getConditions());
    }

    public function testGetAllowedActions()
    {
        $this->assertEquals(
            [OperationDefinition::PREACTIONS, OperationDefinition::FORM_INIT, OperationDefinition::ACTIONS],
            OperationDefinition::getAllowedActions()
        );
    }

    public function testGetAllowedConditions()
    {
        $this->assertEquals(
            [OperationDefinition::PRECONDITIONS, OperationDefinition::CONDITIONS],
            OperationDefinition::getAllowedConditions()
        );
    }

    public function testGettersAndSetters()
    {
        static::assertPropertyAccessors(
            $this->definition,
            [
                ['name', 'test'],
                ['label', 'test'],
                ['substituteOperation', 'test_operation_name_to_substitute'],
                ['enabled', false, true],
                ['pageReload', false, true],
                ['order', 77, 0],
                ['frontendOptions', ['config1', 'config2'], []],
                ['buttonOptions', ['config1', 'config2'], []],
                ['datagridOptions', ['datagridConfig1', 'datagridConfig2'], []],
                ['formOptions', ['config1', 'config2'], []],
                ['formType', 'test_form_type'],
                ['attributes', ['config1', 'config2'], []],
                ['actionGroups', ['action_group1', 'action_group2'], []]
            ]
        );
    }
}
