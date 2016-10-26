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
                        'workflow_entity_class' => 'Oro\\Bundle\\ProductBundle\\Entity\\Product',
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
                'name' => 'oro_report_table_1',
                'columns' =>
                    [
                        'c1' =>
                            [
                                'frontend_type' => 'integer',
                                'label' => 'Id',
                                'translatable' => false,
                            ],
                        'c2' =>
                            [
                                'frontend_type' => 'string',
                                'label' => 'Name',
                                'translatable' => false,
                            ],
                        'workflowStepLabel' =>
                            [
                                'label' => 'oro.workflow.workflowstep.grid.label',
                                'type' => 'twig',
                                'frontend_type' => 'html',
                                'template' => 'OroWorkflowBundle:Datagrid:Column/workflowStep.html.twig',
                            ],
                    ],
                'sorters' =>
                    [
                        'columns' =>
                            [
                                'c1' =>
                                    [
                                        'data_name' => 'c1',
                                    ],
                                'c2' =>
                                    [
                                        'data_name' => 'c2',
                                    ],
                                'workflowStepLabel' =>
                                    [
                                        'data_name' => 'workflowStepLabel.stepOrder',
                                    ],
                            ],
                    ],
                'filters' =>
                    [
                        'columns' =>
                            [
                                'c1' =>
                                    [
                                        'type' => 'number-range',
                                        'options' =>
                                            [
                                                'data_type' => 'data_integer',
                                            ],
                                        'data_name' => 'c1',
                                        'translatable' => false,
                                    ],
                                'c2' =>
                                    [
                                        'type' => 'string',
                                        'data_name' => 'c2',
                                        'translatable' => false,
                                    ],
                                'workflowStepLabelByWorkflowStep' =>
                                    [
                                        'label' => 'oro.workflow.workflowstep.grid.label',
                                        'type' => 'entity',
                                        'data_name' => 'workflowStepLabel.id',
                                        'options' =>
                                            [
                                                'field_type' => 'oro_workflow_step_select',
                                                'field_options' =>
                                                    [
                                                        'workflow_entity_class' => 'Oro\\Bundle\\ProductBundle\\Entity\\Product',
                                                        'multiple' => true,
                                                    ],
                                            ],
                                    ],
                            ],
                    ],
                'source' =>
                    [
                        'query' =>
                            [
                                'select' =>
                                    [
                                        0 => 't1.id as c1',
                                        1 => 't4.label as c2',
                                        2 => 't1.id',
                                    ],
                                'from' =>
                                    [
                                        0 =>
                                            [
                                                'table' => 'Oro\\Bundle\\ProductBundle\\Entity\\Product',
                                                'alias' => 't1',
                                            ],
                                    ],
                                'join' =>
                                    [
                                        'left' =>
                                            [
                                                0 =>
                                                    [
                                                        'join' => 'Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowItem',
                                                        'alias' => 't2',
                                                        'conditionType' => 'WITH',
                                                        'condition' => 'CAST(t1.id as string) = CAST(t2.entityId as string) AND t2.entityClass = \'Oro\\Bundle\\ProductBundle\\Entity\\Product\'',
                                                    ],
                                                1 =>
                                                    [
                                                        'join' => 't2.currentStep',
                                                        'alias' => 't3',
                                                    ],
                                                2 =>
                                                    [
                                                        'join' => 't2.currentStep',
                                                        'alias' => 't4',
                                                    ],
                                                3 =>
                                                    [
                                                        'join' => 'Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowItem',
                                                        'alias' => 'workflowItem',
                                                        'conditionType' => 'WITH',
                                                        'condition' => 'CAST(t1.id as string) = CAST(workflowItem.entityId as string) AND workflowItem.entityClass = \'Oro\\Bundle\\ProductBundle\\Entity\\Product\'',
                                                    ],
                                                4 =>
                                                    [
                                                        'join' => 'workflowItem.currentStep',
                                                        'alias' => 'workflowStepLabel',
                                                    ],
                                            ],
                                    ],
                            ],
                        'query_config' =>
                            [
                                'table_aliases' =>
                                    [
                                        '' => 't1',
                                        'Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowItem|left|WITH|CAST(t1.id as string) = CAST(t2.entityId as string) AND t2.entityClass = \'Oro\\Bundle\\ProductBundle\\Entity\\Product\'' => 't2',
                                        'Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowItem|left|WITH|CAST(t1.id as string) = CAST(t2.entityId as string) AND t2.entityClass = \'Oro\\Bundle\\ProductBundle\\Entity\\Product\'+t2.currentStep|left' => 't3',
                                        'Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowItem|left|WITH|CAST(t1.id as string) = CAST(t2.entityId as string) AND t2.entityClass = \'Oro\\Bundle\\ProductBundle\\Entity\\Product\'+t2.currentStep|left+Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowItem::currentStep' => 't4',
                                    ],
                                'column_aliases' =>
                                    [
                                        'id' => 'c1',
                                        'workflowItems_virtual+Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowItem::currentStep+Oro\\Bundle\\WorkflowBundle\\Entity\\WorkflowStep::label' => 'c2',
                                    ],
                            ],
                        'type' => 'orm',
                        'hints' =>
                            [
                                0 =>
                                    [
                                        'name' => 'doctrine.customOutputWalker',
                                        'value' => 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker',
                                    ],
                            ],
                        'acl_resource' => 'oro_report_view',
                    ],
                'properties' =>
                    [
                        'id' => null,
                        'view_link' =>
                            [
                                'type' => 'url',
                                'route' => 'oro_product_view',
                                'params' =>
                                    [
                                        0 => 'id',
                                    ],
                            ],
                    ],
                'actions' =>
                    [
                        'view' =>
                            [
                                'type' => 'navigate',
                                'label' => 'oro.report.datagrid.row.action.view',
                                'acl_resource' => 'VIEW;entity:Oro\\Bundle\\ProductBundle\\Entity\\Product',
                                'icon' => 'eye-open',
                                'link' => 'view_link',
                                'rowAction' => true,
                            ],
                    ],
                'options' =>
                    [
                        'export' => true,
                        'entity_pagination' => true,
                    ],
            ]
        );
    }
}
