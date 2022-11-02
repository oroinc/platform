<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException;
use Oro\Bundle\ActionBundle\Form\Type\OperationType;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationAssembler;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class OperationAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OperationAssembler */
    private $assembler;

    protected function setUp(): void
    {
        $this->assembler = new OperationAssembler(
            $this->createMock(ActionFactoryInterface::class),
            $this->createMock(ConditionFactory::class),
            $this->createMock(AttributeAssembler::class),
            $this->createMock(FormOptionsAssembler::class)
        );
    }

    /**
     * @dataProvider assembleProvider
     */
    public function testCreateOperation(array $configuration, array $expected)
    {
        foreach ($configuration as $name => $config) {
            $operation = $this->assembler->createOperation($name, $config);

            $this->assertEquals($expected[$name], $operation);
        }
    }

    public function testCreateOperationWithMissedRequiredOptions()
    {
        $this->expectException(MissedRequiredOptionException::class);
        $this->expectExceptionMessage('Option "label" is required');

        $this->assembler->createOperation('test', []);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function assembleProvider(): array
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
                            TestEntity1::class
                        ],
                    ]
                ]
                ,
                'expected' => [
                    'minimum_name' => new Operation(
                        $this->createMock(ActionFactoryInterface::class),
                        $this->createMock(ConditionFactory::class),
                        $this->createMock(AttributeAssembler::class),
                        $this->createMock(FormOptionsAssembler::class),
                        $definition1
                    )
                ],
            ],
            'maximum data' => [
                [
                    'maximum_name' => [
                        'label' => 'My Label',
                        'substitute_operation' => 'test_operation_to_substitute',
                        'entities' => [TestEntity1::class],
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
                        $this->createMock(ActionFactoryInterface::class),
                        $this->createMock(ConditionFactory::class),
                        $this->createMock(AttributeAssembler::class),
                        $this->createMock(FormOptionsAssembler::class),
                        $definition2
                    )
                ],
            ],
            'maximum data and acl_resource' => [
                [
                    'maximum_name_and_acl' => [
                        'label' => 'My Label',
                        'substitute_operation' => 'test_operation_to_substitute',
                        'entities' => [TestEntity1::class],
                        'routes' => ['my_route'],
                        'groups' => ['my_group'],
                        'enabled' => false,
                        'for_all_entities' => true,
                        'exclude_entities' => [TestEntity2::class],
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
                        $this->createMock(ActionFactoryInterface::class),
                        $this->createMock(ConditionFactory::class),
                        $this->createMock(AttributeAssembler::class),
                        $this->createMock(FormOptionsAssembler::class),
                        $definition3
                    )
                ],
            ],
        ];
    }
}
