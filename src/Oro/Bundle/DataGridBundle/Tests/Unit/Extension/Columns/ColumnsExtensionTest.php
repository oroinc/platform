<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Columns;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension;

class ColumnsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ColumnsExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockBuilder */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockBuilder */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockBuilder */
    protected $aclHelper;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade
            ->expects(static::any())
            ->method('isGranted')
            ->will(static::returnValue(true));

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ColumnsExtension($this->registry, $this->securityFacade, $this->aclHelper);
    }

    /**
     * @param $input
     * @param $result
     *
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable($input, $result)
    {
        static::assertEquals(
            $this->extension->isApplicable(
                DatagridConfiguration::create($input)
            ),
            $result
        );
    }

    /**
     * @return array
     */
    public function isApplicableProvider()
    {
        return [
            'applicable'     => [
                'input'  => [
                    'columns' => [
                        'name'  => [],
                        'label' => []
                    ]
                ],
                'result' => true
            ],
            'not applicable' => [
                'input'  => [
                    'columns' => []
                ],
                'result' => false
            ]
        ];
    }

    /**
     * @param array $columnsConfigArray
     * @param array $dataState
     * @param array $columnsDataArray
     * @param array $gridViewColumnsData
     * @param array $gridViewId
     * @param array $stateResult
     * @param array $initialStateResult
     * @param array $dataInitialState
     * @param bool  $isGridView
     *
     * @dataProvider configDataProvider
     */
    public function testVisitMetadata(
        $columnsConfigArray,
        $dataState,
        $columnsDataArray,
        $gridViewColumnsData,
        $gridViewId,
        $stateResult,
        $initialStateResult,
        $dataInitialState,
        $isGridView = true
    ) {
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade
            ->expects(static::any())
            ->method('getLoggedUser')
            ->will(static::returnValue($user));

        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $quantity = ($isGridView) ? 3 : 2;

        $config
            ->expects(static::exactly($quantity))
            ->method('offsetGet')
            ->with('columns')
            ->will(static::returnValue($columnsConfigArray));

        $config
            ->expects(static::once())
            ->method('getName')
            ->will(static::returnValue('test-grid'));

        $data = MetadataObject::createNamed('test-grid', []);
        $data->offsetSet('columns', $columnsDataArray);
        $data->offsetSet('state', $dataState);
        $data->offsetSet('initialState', $dataInitialState);
        $data->offsetSet(
            'gridViews',
            [
                'views' => [
                    ['name' => '__all__', 'label' => 'label', 'columns' => []]
                ]
            ]
        );

        $repository = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository')
            ->setMethods(['findGridViews'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry
            ->expects(static::once())
            ->method('getRepository')
            ->with('OroDataGridBundle:GridView')
            ->will(static::returnValue($repository));

        $gridView = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Entity\GridView')
            ->disableOriginalConstructor()
            ->getMock();

        if ($isGridView) {
            $gridView
                ->expects(static::once())
                ->method('getId')
                ->will(static::returnValue($gridViewId));

            $gridView
                ->expects(static::once())
                ->method('getColumnsData')
                ->will(static::returnValue($gridViewColumnsData));

            $repository
                ->expects(static::once())
                ->method('findGridViews')
                ->will(static::returnValue([$gridView]));
        } else {
            $repository
                ->expects(static::once())
                ->method('findGridViews')
                ->will(static::returnValue(null));
        }

        $this->extension->visitMetadata($config, $data);

        static::assertEquals($data->offsetGet('state'), $stateResult);

        static::assertEquals($data->offsetGet('initialState'), $initialStateResult);

        $gridViews = $data->offsetGet('gridViews');

        if ($isGridView) {
            foreach ($gridViews['views'] as $gridView) {
                if ('__all__' === $gridView['name']) {
                    static::assertEquals($gridView['columns'], $initialStateResult['columns']);
                    break;
                }
            }
        }
    }

    public function configDataProvider()
    {
        return [
            'same state'         => [
                'columnsConfigArray'  => [
                    'name'  => ['order' => 2, 'label' => 'name', 'type' => 'string'],
                    'label' => ['order' => 1, 'label' => 'label', 'type' => 'string'],
                    'some'  => ['label' => 'label', 'type' => 'string']
                ],
                'dataState'           => ['gridView' => 1, 'filters' => []],
                'columnsDataArray'    => [
                    ['label' => 'Test Name', 'type' => 'string', 'name' => 'name', 'order' => 2],
                    ['label' => 'Test Label', 'type' => 'string', 'name' => 'label'],
                    ['label' => 'Test Some', 'type' => 'string', 'name' => 'some']
                ],
                'gridViewColumnsData' => [
                    'name'  => ['renderable' => true, 'order' => 2],
                    'label' => ['renderable' => true, 'order' => 0],
                    'some'  => ['renderable' => true, 'order' => 1],
                ],
                'gridViewId'          => 1,
                'stateResult'         => [
                    'gridView' => 1,
                    'filters'  => [],
                    'columns'  => [
                        'name'  => ['renderable' => true, 'order' => 2],
                        'label' => ['renderable' => true, 'order' => 0],
                        'some'  => ['renderable' => true, 'order' => 1],
                    ]
                ],
                'initialStateResult'  => [
                    'gridView' => '__all__',
                    'filters'  => [],
                    'columns'  => [
                        'name'  => ['order' => 2],
                        'label' => ['order' => 1],
                        'some'  => ['order' => 3]
                    ]
                ],
                'dataInitialState'    => ['gridView' => '__all__', 'filters' => []],
            ],
            'different state id' => [
                'columnsConfigArray'  => [
                    'name'  => ['order' => 2, 'label' => 'name', 'type' => 'string'],
                    'label' => ['order' => 1, 'label' => 'label', 'type' => 'string'],
                    'some'  => ['label' => 'label', 'type' => 'string']
                ],
                'dataState'           => ['gridView' => '__all__', 'filters' => []],
                'columnsDataArray'    => [
                    ['label' => 'Test Name', 'type' => 'string', 'name' => 'name', 'order' => 2],
                    ['label' => 'Test Label', 'type' => 'string', 'name' => 'label'],
                    ['label' => 'Test Some', 'type' => 'string', 'name' => 'some']
                ],
                'gridViewColumnsData' => [
                    'name'  => ['renderable' => true, 'order' => 2],
                    'label' => ['renderable' => true, 'order' => 0],
                    'some'  => ['renderable' => true, 'order' => 1],
                ],
                'gridViewId'          => 0,
                'stateResult'         => [
                    'gridView' => '__all__',
                    'filters'  => [],
                    'columns'  => [
                        'name'  => ['renderable' => true, 'order' => 2],
                        'label' => ['renderable' => true, 'order' => 0],
                        'some'  => ['renderable' => true, 'order' => 1],
                    ]
                ],
                'initialStateResult'  => [
                    'gridView' => '__all__',
                    'filters'  => [],
                    'columns'  => [
                        'name'  => ['order' => 2],
                        'label' => ['order' => 1],
                        'some'  => ['order' => 3]
                    ]
                ],
                'dataInitialState'    => ['gridView' => '__all__', 'filters' => []],
            ],
            'No grid view'       => [
                'columnsConfigArray'  => [
                    'name'  => ['order' => 2, 'label' => 'name', 'type' => 'string'],
                    'label' => ['order' => 1, 'label' => 'label', 'type' => 'string'],
                    'some'  => ['label' => 'label', 'type' => 'string']
                ],
                'dataState'           => ['gridView' => '__all__', 'filters' => []],
                'columnsDataArray'    => [
                    ['label' => 'Test Name', 'type' => 'string', 'name' => 'name', 'order' => 2],
                    ['label' => 'Test Label', 'type' => 'string', 'name' => 'label'],
                    ['label' => 'Test Some', 'type' => 'string', 'name' => 'some']
                ],
                'gridViewColumnsData' => [],
                'gridViewId'          => 0,
                'stateResult'         => [
                    'gridView' => '__all__',
                    'filters'  => [],
                    'columns'  => [
                        'name'  => ['order' => 2],
                        'label' => ['order' => 1],
                        'some'  => ['order' => 3],
                    ]
                ],
                'initialStateResult'  => [
                    'gridView' => '__all__',
                    'filters'  => [],
                    'columns'  => [
                        'name'  => ['order' => 2],
                        'label' => ['order' => 1],
                        'some'  => ['order' => 3]
                    ]
                ],
                'dataInitialState'    => ['gridView' => '__all__', 'filters' => []],
                'isGridView'          => false
            ],
        ];
    }
}
