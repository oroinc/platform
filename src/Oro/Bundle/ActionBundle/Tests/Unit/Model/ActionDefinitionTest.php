<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionDefinition;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ActionDefinitionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ActionDefinition */
    protected $definition;

    protected function setUp()
    {
        $this->definition = new ActionDefinition();
    }

    /**
     * @dataProvider defaultsDataProvider
     *
     * @param string $method
     * @param mixed $value
     */
    public function testDefaults($method, $value)
    {
        static::assertEquals($value, $this->definition->$method());
    }

    /**
     * @return array
     */
    public function defaultsDataProvider()
    {
        return [
            [
                'method' => 'isEnabled',
                'value' => true
            ],
            [
                'method' => 'getEntities',
                'value' => []
            ],
            [
                'method' => 'getRoutes',
                'value' => []
            ],
            [
                'method' => 'getGroups',
                'value' => []
            ],
            [
                'method' => 'getApplications',
                'value' => []
            ],
            [
                'method' => 'getOrder',
                'value' => 0
            ],
        ];
    }

    public function testSetAndGetConditions()
    {
        $this->definition->setConditions('name1', ['cond1', 'cond2']);

        $this->assertEquals(['cond1', 'cond2'], $this->definition->getConditions('name1'));
        $this->assertEquals(['name1' => ['cond1', 'cond2']], $this->definition->getConditions());
    }

    public function testSetAndGetFunctions()
    {
        $this->definition->setFunctions('name1', ['func1', 'func2']);

        $this->assertEquals(['func1', 'func2'], $this->definition->getFunctions('name1'));
        $this->assertEquals(['name1' => ['func1', 'func2']], $this->definition->getFunctions());
    }

    public function testGetAllowedConditions()
    {
        $this->assertEquals(
            [ActionDefinition::PRECONDITIONS, ActionDefinition::CONDITIONS],
            ActionDefinition::getAllowedConditions()
        );
    }

    public function testGetAllowedFunctions()
    {
        $this->assertEquals(
            [ActionDefinition::PREFUNCTIONS, ActionDefinition::FORM_INIT, ActionDefinition::FUNCTIONS],
            ActionDefinition::getAllowedFunctions()
        );
    }

    public function testGettersAndSetters()
    {
        static::assertPropertyAccessors(
            $this->definition,
            [
                ['name', 'test'],
                ['label', 'test'],
                ['substituteAction', 'test_action_name_to_substitute'],
                ['enabled', false],
                ['entities', ['entity1', 'entity2']],
                ['routes', ['route1', 'route2']],
                ['groups', ['group1', 'group2']],
                ['applications', ['application1', 'application2']],
                ['order', 77],
                ['frontendOptions', ['config1', 'config2']],
                ['buttonOptions', ['config1', 'config2']],
                ['formOptions', ['config1', 'config2']],
                ['formType', 'test_form_type'],
                ['attributes', ['config1', 'config2']],
            ]
        );
    }

    /**
     * @dataProvider hasContextFiltersProvider
     *
     * @param array $entities
     * @param array $routes
     * @param array $datagrids
     * @param bool $expected
     */
    public function testHasContextFilters(array $entities, array $routes, array $datagrids, $expected)
    {
        $this->definition->setEntities($entities);
        $this->definition->setRoutes($routes);
        $this->definition->setDatagrids($datagrids);

        $this->assertEquals($expected, $this->definition->hasContextFilters());
    }

    /**
     * @return array
     */
    public function hasContextFiltersProvider()
    {
        return [
            [
                'entities' => [],
                'routes' => [],
                'datagrids' => [],
                'expected' => false
            ],
            [
                'entities' => ['TestEntity'],
                'routes' => [],
                'datagrids' => [],
                'expected' => true
            ],
            [
                'entities' => [],
                'routes' => ['test_route'],
                'datagrids' => [],
                'expected' => true
            ],
            [
                'entities' => [],
                'routes' => [],
                'datagrids' => ['test_datagrid'],
                'expected' => true
            ],
            [
                'entities' => ['TestEntity'],
                'routes' => ['test_route'],
                'datagrids' => [],
                'expected' => true
            ],
            [
                'entities' => [],
                'routes' => ['test_route'],
                'datagrids' => ['test_datagrid'],
                'expected' => true
            ],
            [
                'entities' => ['TestEntity'],
                'routes' => [],
                'datagrids' => ['test_datagrid'],
                'expected' => true
            ]
        ];
    }
}
