<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Form\Type\OperationType;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationAssembler;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class OperationAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OperationAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->assembler = new OperationAssembler(
            $this->getActionFactory(),
            $this->getConditionFactory(),
            $this->getAttributeAssembler(),
            $this->getFormOptionsAssembler()
        );
    }

    protected function tearDown()
    {
        unset($this->assembler, $this->conditionFactory, $this->attributeAssembler);
    }

    /**
     * @param array $configuration
     * @param array $expected
     *
     * @dataProvider assembleProvider
     */
    public function testCreateOperation(array $configuration, array $expected)
    {
        foreach ($configuration as $name => $config) {
            $operation = $this->assembler->createOperation($name, $config);

            $this->assertEquals($expected[$name], $operation);
        }
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException
     * @expectedExceptionMessage Option "label" is required
     */
    public function testCreateOperationWithMissedRequiredOptions()
    {
        $this->assembler->createOperation('test', []);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function assembleProvider()
    {
        $definition1 = new OperationDefinition();
        $definition1
            ->setName('minimum_name')
            ->setLabel('My Label')
            ->setConditions(OperationDefinition::CONDITIONS, [])
            ->setConditions(OperationDefinition::PRECONDITIONS, [])
            ->setActions(OperationDefinition::PREACTIONS, [])
            ->setActions(OperationDefinition::ACTIONS, [])
            ->setActions(OperationDefinition::FORM_INIT, [])
            ->setFormType(OperationType::class)
            ->setConditions(
                OperationDefinition::PRECONDITIONS,
                [
                    '@and' => [
                        ['@feature_resource_enabled' => ['resource' => 'minimum_name', 'resource_type' => 'operations']]
                    ]
                ]
            );

        $definition2 = clone $definition1;
        $definition2
            ->setName('maximum_name')
            ->setSubstituteOperation('test_operation_to_substitute')
            ->setEnabled(false)
            ->setAttributes(['config_attr'])
            ->setConditions(
                OperationDefinition::PRECONDITIONS,
                [
                    '@and' => [
                        ['config_pre_cond'],
                        ['@feature_resource_enabled' => ['resource' => 'maximum_name', 'resource_type' => 'operations']]
                    ]
                ]
            )
            ->setConditions(OperationDefinition::CONDITIONS, ['config_cond'])
            ->setActions(OperationDefinition::PREACTIONS, ['config_pre_func'])
            ->setActions(OperationDefinition::ACTIONS, ['@action' => 'action_config'])
            ->setActions(OperationDefinition::FORM_INIT, ['config_form_init_func'])
            ->setFormOptions(['config_form_options'])
            ->setFrontendOptions(['config_frontend_options'])
            ->setOrder(77)
            ->setFormType(OperationType::class);

        $definition3 = clone $definition2;
        $definition3
            ->setName('maximum_name_and_acl')
            ->setEnabled(false)
            ->setPageReload(false)
            ->setAttributes(['config_attr'])
            ->setConditions(
                OperationDefinition::PRECONDITIONS,
                [
                    '@and' => [
                        [
                            '@and' => [
                                ['config_pre_cond'],
                                [
                                    '@feature_resource_enabled' => [
                                        'resource' => 'maximum_name_and_acl',
                                        'resource_type' => 'operations'
                                    ]
                                ]
                            ]
                        ],
                        ['@acl_granted' => 'test_acl']
                    ]
                ]
            )
            ->setActions(OperationDefinition::PREACTIONS, ['config_pre_func'])
            ->setActions(OperationDefinition::ACTIONS, ['@action' => 'action_config'])
            ->setActions(OperationDefinition::FORM_INIT, ['config_form_init_func'])
            ->setFormOptions(['config_form_options'])
            ->setFrontendOptions(['config_frontend_options'])
            ->setOrder(77)
            ->setFormType(OperationType::class);

        return [
            'minimum data' => [
                [
                    'minimum_name' => [
                        'label' => 'My Label',
                        'entities' => [
                            '\Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'
                        ],
                    ]
                ]
                ,
                'expected' => [
                    'minimum_name' => new Operation(
                        $this->getActionFactory(),
                        $this->getConditionFactory(),
                        $this->getAttributeAssembler(),
                        $this->getFormOptionsAssembler(),
                        $definition1
                    )
                ],
            ],
            'maximum data' => [
                [
                    'maximum_name' => [
                        'label' => 'My Label',
                        'substitute_operation' => 'test_operation_to_substitute',
                        'entities' => ['\Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                        'routes' => ['my_route'],
                        'groups' => ['my_group'],
                        'enabled' => false,
                        'applications' => ['application1'],
                        'attributes' => ['config_attr'],
                        OperationDefinition::PREACTIONS => ['config_pre_func'],
                        OperationDefinition::PRECONDITIONS => ['config_pre_cond'],
                        OperationDefinition::CONDITIONS => ['config_cond'],
                        OperationDefinition::ACTIONS => ['@action' => 'action_config'],
                        OperationDefinition::FORM_INIT => ['config_form_init_func'],
                        'form_options' => ['config_form_options'],
                        'frontend_options' => ['config_frontend_options'],
                        'order' => 77,
                    ]
                ],
                'expected' => [
                    'maximum_name' => new Operation(
                        $this->getActionFactory(),
                        $this->getConditionFactory(),
                        $this->getAttributeAssembler(),
                        $this->getFormOptionsAssembler(),
                        $definition2
                    )
                ],
            ],
            'maximum data and acl_resource' => [
                [
                    'maximum_name_and_acl' => [
                        'label' => 'My Label',
                        'substitute_operation' => 'test_operation_to_substitute',
                        'entities' => ['\Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                        'routes' => ['my_route'],
                        'groups' => ['my_group'],
                        'enabled' => false,
                        'for_all_entities' => true,
                        'exclude_entities' => ['\Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2'],
                        'applications' => ['application1'],
                        'attributes' => ['config_attr'],
                        OperationDefinition::PREACTIONS => ['config_pre_func'],
                        OperationDefinition::PRECONDITIONS => ['config_pre_cond'],
                        OperationDefinition::CONDITIONS => ['config_cond'],
                        OperationDefinition::ACTIONS => ['@action' => 'action_config'],
                        OperationDefinition::FORM_INIT => ['config_form_init_func'],
                        'form_options' => ['config_form_options'],
                        'frontend_options' => ['config_frontend_options'],
                        'order' => 77,
                        'acl_resource' => 'test_acl',
                        'page_reload' => false
                    ]
                ],
                'expected' => [
                    'maximum_name_and_acl' => new Operation(
                        $this->getActionFactory(),
                        $this->getConditionFactory(),
                        $this->getAttributeAssembler(),
                        $this->getFormOptionsAssembler(),
                        $definition3
                    )
                ],
            ],
        ];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ActionFactory
     */
    protected function getActionFactory()
    {
        return $this->createMock('Oro\Component\Action\Action\ActionFactoryInterface');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConditionFactory
     */
    protected function getConditionFactory()
    {
        return $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|AttributeAssembler
     */
    protected function getAttributeAssembler()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FormOptionsAssembler
     */
    protected function getFormOptionsAssembler()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
