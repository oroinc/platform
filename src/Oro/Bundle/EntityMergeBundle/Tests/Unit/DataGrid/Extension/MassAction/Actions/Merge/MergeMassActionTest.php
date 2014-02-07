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

    public function setup()
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
                    'route_parameters' => array(
                        'data_identifier' => 'id',
                        'entity_name' => 'SomeEntityClass',
                    )
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
                    'route_parameters' => array(
                        'custom_parameter' => 'test_value'
                    ),
                ),
                'expected' => array(
                    'entity_name' => 'SomeEntityClass',
                    'frontend_handle' => 'custom_handler',
                    'frontend_type' => 'custom-merge-mass',
                    'data_identifier' => 'code',
                    'max_element_count' => 10,
                    'route' => 'custom_route',
                    'route_parameters' => array(
                        'data_identifier' => 'code',
                        'entity_name' => 'SomeEntityClass',
                        'custom_parameter' => 'test_value',
                    )
                )
            )
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage There is no option "entity_name" for action "merge".
     */
    public function testMergeMassActionSetOptionShouldThrowExceptionIfClassNameOptionIsEmpty()
    {
        $this->target->setOptions(ActionConfiguration::create(array()));
    }
}
