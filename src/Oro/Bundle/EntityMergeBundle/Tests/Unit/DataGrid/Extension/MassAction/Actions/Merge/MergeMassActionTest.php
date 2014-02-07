<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\DataGrid\Extension\MassAction\Actions\Merge;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\Actions\Merge\MergeMassAction;

class MergeMassActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeMassAction $target
     */
    private $target;

    protected function setUp()
    {
        $this->target = new MergeMassAction();
    }

    /**
     * @dataProvider getOptionsDataProvider
     */
    public function testGetOptions(array $actualOptions, array $expectedOptions)
    {
        $this->target->setOptions(ActionConfiguration::create($actualOptions));
        $this->assertEquals($expectedOptions, $this->target->getOptions()->toArray());
    }

    public function getOptionsDataProvider()
    {
        return array(
            'default_values' => array(
                'actual' => array(
                    'entity_name' => 'SomeEntityClass'
                ),
                'expected' => array(
                    'entity_name' => 'SomeEntityClass',
                    'frontend_handle' => 'redirect',
                    'frontend_type' => 'merge-mass',
                    'data_identifier' => 'id',
                    'max_element_count' => '5',
                    'route' => 'oro_entity_merge',
                    'handler' => 'oro_entity_merge.mass_action.data_handler',
                    'route_parameters'=>array()
                )
            ),
            'override_values' => array(
                'actual' => array(
                    'entity_name' => 'SomeEntityClass',
                    'frontend_handle' => 'custom_handler',
                    'frontend_type' => 'custom-merge-mass',
                    'data_identifier' => 'code',
                    'max_element_count' => 10,
                    'route' => 'custom_route',
                    'handler' => 'oro_entity_merge.mass_action.data_handler',
                    'route_parameters'=>array()
                ),
                'expected' => array(
                    'entity_name' => 'SomeEntityClass',
                    'frontend_handle' => 'custom_handler',
                    'frontend_type' => 'custom-merge-mass',
                    'data_identifier' => 'code',
                    'max_element_count' => 10,
                    'route' => 'custom_route',
                    'handler' => 'oro_entity_merge.mass_action.data_handler',
                    'route_parameters'=>array()
                )
            )
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to get name of unnamed object
     */
    public function testMergeMassActionSetOptionShouldThrowExceptionIfClassNameOptionIsEmpty()
    {
        $this->target->setOptions(ActionConfiguration::create(array()));
    }
}
