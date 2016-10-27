<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowDatagridLabelListener;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;

class WorkflowDatagridLabelListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var WorkflowDatagridLabelListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->listener = new WorkflowDatagridLabelListener($this->translator);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->listener);
    }

    public function testTrans()
    {
        $this->translator
            ->expects($this->atLeastOnce())
            ->method('trans')
            ->with(
                $this->anything(),
                $this->anything(),
                WorkflowTranslationHelper::TRANSLATION_DOMAIN,
                $this->anything()
            );
        $this->listener->trans('ANY_STRING');
    }

    public function testOnBuildBefore()
    {
        $datagridConfiguration = $this->getStubConfiguration();
        $mockEvent = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEvent->expects($this->atLeastOnce())->method('getConfig')->willReturn($datagridConfiguration);
        $this->listener->onBuildBefore($mockEvent);

        //Verify Column
        $this->assertEquals(
            [
                'frontend_type' => 'html',
                'type' => 'callback',
                'callable' => [$this->listener, "trans"],
                'params' => ['c2'],
                'label' => 'Name',
                'translatable' => false,
            ],
            $datagridConfiguration->offsetGetByPath('[columns][c2]')
        );

        //Verify Filter
        $this->assertEquals(
            [
                'label' => 'Name',
                'type' => 'entity',
                'data_name' => 't4.id',
                'options' => [
                    'field_type' => WorkflowStepSelectType::NAME,
                    'field_options' => [
                        'workflow_entity_class' => 'SomeEntity',
                        'multiple' => true
                    ]
                ],
            ],
            $datagridConfiguration->offsetGetByPath('[filters][columns][c2]')
        );

        //Verify Sorter
        $this->assertNull($datagridConfiguration->offsetGetByPath('[sorters][columns][c2]'));
    }

    /**
     * @return DatagridConfiguration
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getStubConfiguration()
    {
        return DatagridConfiguration::create(
            [
                'columns' => [
                    'c2' => [
                        'frontend_type' => 'string',
                        'label' => 'Name',
                        'translatable' => false,
                    ],
                ],
                'sorters' => [
                    'columns' => [
                        'c2' => [
                            'data_name' => 'c2',
                        ],
                    ],
                ],
                'filters' => [
                    'columns' => [
                        'c2' => [
                            'type' => 'string',
                            'data_name' => 'c2',
                            'translatable' => false,
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            't4.label as c2',
                        ],
                        'from' => [
                            [
                                'table' => 'SomeEntity',
                                'alias' => 't1',
                            ],
                        ],
                    ],
                    'query_config' => [
                        'column_aliases' => [
                            'workflowItems_virtual+Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowItem::curr' .
                            'entStep+Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowStep::label' => 'c2',
                        ],
                    ],
                ],
            ]
        );
    }
}
