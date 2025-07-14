<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class OperationDefinitionTest extends TestCase
{
    use EntityTestCaseTrait;

    private OperationDefinition $definition;

    #[\Override]
    protected function setUp(): void
    {
        $this->definition = new OperationDefinition();
    }

    public function testSetAndGetActions(): void
    {
        $this->definition->setActions('name1', ['func1', 'func2']);

        $this->assertEquals(['func1', 'func2'], $this->definition->getActions('name1'));
        $this->assertEquals(['name1' => ['func1', 'func2']], $this->definition->getActions());
    }

    public function testSetAndGetConditions(): void
    {
        $this->definition->setConditions('name1', ['cond1', 'cond2']);

        $this->assertEquals(['cond1', 'cond2'], $this->definition->getConditions('name1'));
        $this->assertEquals(['name1' => ['cond1', 'cond2']], $this->definition->getConditions());
    }

    public function testGetAllowedActions(): void
    {
        $this->assertEquals(
            [OperationDefinition::PREACTIONS, OperationDefinition::FORM_INIT, OperationDefinition::ACTIONS],
            OperationDefinition::getAllowedActions()
        );
    }

    public function testGetAllowedConditions(): void
    {
        $this->assertEquals(
            [OperationDefinition::PRECONDITIONS, OperationDefinition::CONDITIONS],
            OperationDefinition::getAllowedConditions()
        );
    }

    public function testGettersAndSetters(): void
    {
        self::assertPropertyAccessors(
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
