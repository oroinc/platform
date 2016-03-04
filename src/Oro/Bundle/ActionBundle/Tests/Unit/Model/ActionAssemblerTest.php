<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Form\Type\ActionType;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionAssembler;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\ActionBundle\Model\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\FormOptionsAssembler;

use Oro\Component\Action\Action\ActionFactory as FunctionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActionAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->assembler = new ActionAssembler(
            $this->getFunctionFactory(),
            $this->getConditionFactory(),
            $this->getAttributeAssembler(),
            $this->getFormOptionsAssembler()
        );
    }

    protected function tearDown()
    {
        unset($this->assembler, $this->functionFactory, $this->conditionFactory, $this->attributeAssembler);
    }

    /**
     * @param array $configuration
     * @param array $expected
     *
     * @dataProvider assembleProvider
     */
    public function testAssemble(array $configuration, array $expected)
    {
        $definitions = $this->assembler->assemble($configuration);

        static::assertEquals($expected, $definitions);
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException
     * @expectedExceptionMessage Option "label" is required
     */
    public function testAssembleWithMissedRequiredOptions()
    {
        $configuration = [
            'test_config' => [],
        ];

        $this->assembler->assemble($configuration);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function assembleProvider()
    {
        $definition1 = new ActionDefinition();
        $definition1
            ->setName('minimum_name')
            ->setLabel('My Label')
            ->setEntities(['My\Entity'])
            ->setConditions('conditions', [])
            ->setConditions('preconditions', [])
            ->setFunctions('prefunctions', [])
            ->setFunctions('form_init', [])
            ->setFunctions('functions', [])
            ->setFormType(ActionType::NAME);

        $definition2 = new ActionDefinition();
        $definition2
            ->setName('maximum_name')
            ->setLabel('My Label')
            ->setEntities(['My\Entity'])
            ->setRoutes(['my_route'])
            ->setEnabled(false)
            ->setApplications(['application1'])
            ->setAttributes(['config_attr'])
            ->setConditions('preconditions', ['config_pre_cond'])
            ->setConditions('conditions', ['config_cond'])
            ->setFunctions('prefunctions', ['config_pre_func'])
            ->setFunctions('form_init', ['config_form_init_func'])
            ->setFunctions('functions', ['config_post_func'])
            ->setFormOptions(['config_form_options'])
            ->setFrontendOptions(['config_frontend_options'])
            ->setOrder(77)
            ->setFormType(ActionType::NAME);

        $definition3 = new ActionDefinition();
        $definition3
            ->setName('maximum_name_and_acl')
            ->setLabel('My Label')
            ->setEntities(['My\Entity'])
            ->setRoutes(['my_route'])
            ->setEnabled(false)
            ->setApplications(['application1'])
            ->setAttributes(['config_attr'])
            ->setConditions('preconditions', [
                '@and' => [
                    ['@acl_granted' => 'test_acl'],
                    ['config_pre_cond']
                ]
             ])
            ->setConditions('conditions', ['config_cond'])
            ->setFunctions('prefunctions', ['config_pre_func'])
            ->setFunctions('form_init', ['config_form_init_func'])
            ->setFunctions('functions', ['config_post_func'])
            ->setFormOptions(['config_form_options'])
            ->setFrontendOptions(['config_frontend_options'])
            ->setOrder(77)
            ->setFormType(ActionType::NAME);

        return [
            'no data' => [
                [],
                'expected' => [],
            ],
            'minimum data' => [
                [
                    'minimum_name' => [
                        'label' => 'My Label',
                        'entities' => [
                            'My\Entity'
                        ],
                    ]
                ]
                ,
                'expected' => [
                    'minimum_name' => new Action(
                        $this->getFunctionFactory(),
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
                        'entities' => ['My\Entity'],
                        'routes' => ['my_route'],
                        'enabled' => false,
                        'applications' => ['application1'],
                        'attributes' => ['config_attr'],
                        'conditions' => ['config_cond'],
                        'prefunctions' => ['config_pre_func'],
                        'preconditions' => ['config_pre_cond'],
                        'form_init' => ['config_form_init_func'],
                        'functions' => ['config_post_func'],
                        'form_options' => ['config_form_options'],
                        'frontend_options' => ['config_frontend_options'],
                        'order' => 77,
                    ]
                ],
                'expected' => [
                    'maximum_name' => new Action(
                        $this->getFunctionFactory(),
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
                        'entities' => ['My\Entity'],
                        'routes' => ['my_route'],
                        'enabled' => false,
                        'applications' => ['application1'],
                        'attributes' => ['config_attr'],
                        'conditions' => ['config_cond'],
                        'prefunctions' => ['config_pre_func'],
                        'preconditions' => ['config_pre_cond'],
                        'form_init' => ['config_form_init_func'],
                        'functions' => ['config_post_func'],
                        'form_options' => ['config_form_options'],
                        'frontend_options' => ['config_frontend_options'],
                        'order' => 77,
                        'acl_resource' => 'test_acl',
                    ]
                ],
                'expected' => [
                    'maximum_name_and_acl' => new Action(
                        $this->getFunctionFactory(),
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
     * @return \PHPUnit_Framework_MockObject_MockObject|FunctionFactory
     */
    protected function getFunctionFactory()
    {
        return $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConditionFactory
     */
    protected function getConditionFactory()
    {
        return $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler
     */
    protected function getAttributeAssembler()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeAssembler')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler
     */
    protected function getFormOptionsAssembler()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
