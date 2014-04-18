<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FilterBundle\EventListener\DisabledFiltersListener;

class DisabledFiltersListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $testConfigWithFilters = [
        'query'   => ['someQueryData'],
        'sorters' => 'someSortersData',
        'filters' => ['columnsData' => [], 'default' => []]
    ];

    /** @var array */
    protected $testConfigWithOutFilters = [
        'query'   => ['someQueryData'],
        'sorters' => 'someSortersData',
        'options' => ['toolbarOptions' => ['addResetAction' => false]],
    ];

    /** @var DisabledFiltersListener */
    protected $listener;

    public function setUp()
    {
        $this->listener = new DisabledFiltersListener();
    }

    public function tearDown()
    {
        unset($this->listener);
    }

    /**
     * @dataProvider configParametersProvider
     *
     * @param array $parameters
     * @param array $expectedConfigData
     */
    public function testBuildBefore(array $parameters, array $expectedConfigData)
    {
        $gridMock       = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $testGridConfig = DatagridConfiguration::create($this->testConfigWithFilters);

        $event = new BuildBefore($gridMock, $testGridConfig, $parameters);
        $this->listener->onBuildBefore($event);

        $this->assertSame($expectedConfigData, $testGridConfig->toArray());
    }

    /**
     * @return array
     */
    public function configParametersProvider()
    {
        return [
            'should not clear filters, param not given'       => [['someParam'], $this->testConfigWithFilters],
            'should not clear filters, param given but false' => [
                ['datagrid-no-filters' => false],
                $this->testConfigWithFilters
            ],
            'should clear filters'                            => [
                ['datagrid-no-filters' => true],
                $this->testConfigWithOutFilters
            ],
        ];
    }
}
