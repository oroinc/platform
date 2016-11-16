<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowDatagridLabelListener;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
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

        //Verify Step Name Column
        $this->assertEquals(
            [
                'frontend_type' => 'html',
                'type' => 'callback',
                'callable' => [$this->listener, "trans"],
                'params' => ['c2'],
                'label' => 'Step Name',
                'translatable' => false,
            ],
            $datagridConfiguration->offsetGetByPath('[columns][c2]')
        );

        //Verify Step Name Filter
        $this->assertEquals(
            [
                'label' => 'Step Name',
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

        //Verify Definition Name Column
        $this->assertEquals(
            [
                'frontend_type' => 'html',
                'type' => 'callback',
                'callable' => [$this->listener, "trans"],
                'params' => ['c3'],
                'label' => 'Workflow Name',
                'translatable' => false,
            ],
            $datagridConfiguration->offsetGetByPath('[columns][c3]')
        );

        //Verify Definition Name Filter
        $this->assertEquals(
            [
                'label' => 'Workflow Name',
                'type' => 'entity',
                'data_name' => 't5.name',
                'options' => [
                    'field_type' => WorkflowDefinitionSelectType::NAME,
                    'field_options' => [
                        'workflow_entity_class' => 'SomeEntity',
                        'multiple' => true
                    ]
                ],
            ],
            $datagridConfiguration->offsetGetByPath('[filters][columns][c3]')
        );

        //Verify Sorters
        $this->assertNull($datagridConfiguration->offsetGetByPath('[sorters][columns][c2]'));
        $this->assertNull($datagridConfiguration->offsetGetByPath('[sorters][columns][c3]'));
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
                        'label' => 'Step Name',
                        'translatable' => false,
                    ],
                    'c3' => [
                        'frontend_type' => 'string',
                        'label' => 'Workflow Name',
                        'translatable' => false,
                    ],
                ],
                'sorters' => [
                    'columns' => [
                        'c2' => [
                            'data_name' => 'c2',
                        ],
                        'c3' => [
                            'data_name' => 'c3',
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
                        'c3' => [
                            'type' => 'string',
                            'data_name' => 'c3',
                            'translatable' => false,
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            't4.label as c2',
                            't5.label as c3',
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
                            WorkflowStep::class . '::label' => 'c2',
                            WorkflowDefinition::class . '::label' => 'c3',
                        ],
                    ],
                ],
            ]
        );
    }
}
