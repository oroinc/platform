<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Extension\ConfigExtension;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\MultipleAssociationChoiceType;

class MultipleAssociationChoiceTypeTest extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $groupingConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var MultipleAssociationChoiceType */
    protected $type;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $config1 = new Config(new EntityConfigId('grouping', 'Test\Entity1'));
        $config2 = new Config(new EntityConfigId('grouping', 'Test\Entity2'));
        $config2->set('groups', []);
        $config3 = new Config(new EntityConfigId('grouping', 'Test\Entity3'));
        $config3->set('groups', ['test']);
        $config4 = new Config(new EntityConfigId('grouping', 'Test\Entity4'));
        $config4->set('groups', ['test', 'test1']);
        $this->groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupingConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will($this->returnValue([$config1, $config2, $config3, $config4]));

        $entityConfig3 = new Config(new EntityConfigId('entity', 'Test\Entity3'));
        $entityConfig3->set('plural_label', 'Entity3');
        $entityConfig4 = new Config(new EntityConfigId('entity', 'Test\Entity4'));
        $entityConfig4->set('plural_label', 'Entity4');
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['Test\Entity3', null, $entityConfig3],
                        ['Test\Entity4', null, $entityConfig4],
                    ]
                )
            );

        $this->type = new MultipleAssociationChoiceType($this->configManager);

        parent::setUp();
    }

    protected function getExtensions()
    {
        $configExtension = new ConfigExtension();

        return [
            new PreloadedExtension(
                [],
                [$configExtension->getExtendedType() => [$configExtension]]
            )
        ];
    }

    public function testSetDefaultOptions()
    {
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['grouping', $this->groupingConfigProvider],
                        ['entity', $this->entityConfigProvider],
                    ]
                )
            );

        $resolver = new OptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $this->assertEquals(
            [
                'empty_value'       => false,
                'choices'           => [
                    'Test\Entity3' => 'Entity3',
                    'Test\Entity4' => 'Entity4',
                ],
                'multiple'          => true,
                'association_class' => 'test'
            ],
            $resolver->resolve(['association_class' => 'test'])
        );
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit($newVal, $oldVal, $state, $isSetStateExpected)
    {
        $configId = new EntityConfigId('test', 'Test\Entity');
        $config = new Config($configId);
        $config->set('items', $oldVal);
        $extendConfigId = new EntityConfigId('extend', 'Test\Entity');
        $extendConfig = new Config($extendConfigId);
        $extendConfig->set('state', $state);
        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($extendConfig));
        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->with($configId)
            ->will($this->returnValue($config));
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['grouping', $this->groupingConfigProvider],
                        ['entity', $this->entityConfigProvider],
                        ['extend', $extendConfigProvider],
                    ]
                )
            );

        $expectedExtendConfig = new Config($extendConfigId);
        if ($isSetStateExpected) {
            $expectedExtendConfig->set('state', ExtendScope::STATE_UPDATED);
            $extendConfigProvider->expects($this->once())
                ->method('persist')
                ->with($expectedExtendConfig);
            $extendConfigProvider->expects($this->once())
                ->method('flush');
        } else {
            $expectedExtendConfig->set('state', $state);
            $extendConfigProvider->expects($this->never())
                ->method('persist');
            $extendConfigProvider->expects($this->never())
                ->method('flush');
        }

        $options  = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];
        $form = $this->factory->createNamed('items', $this->type, $oldVal, $options);

        $form->submit($newVal);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    public function submitProvider()
    {
        return [
            [[], null, ExtendScope::STATE_ACTIVE, false],
            [[], [], ExtendScope::STATE_ACTIVE, false],
            [['Test\Entity3'], ['Test\Entity3'], ExtendScope::STATE_ACTIVE, false],
            [[], ['Test\Entity3'], ExtendScope::STATE_ACTIVE, false],
            [['Test\Entity3'], [], ExtendScope::STATE_ACTIVE, true],
            [['Test\Entity3', 'Test\Entity4'], ['Test\Entity4'], ExtendScope::STATE_ACTIVE, true],
            [['Test\Entity3'], ['Test\Entity4'], ExtendScope::STATE_ACTIVE, true],
            [['Test\Entity3'], [], ExtendScope::STATE_UPDATED, false],
        ];
    }

    public function testBuildView()
    {
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['grouping', $this->groupingConfigProvider],
                    ]
                )
            );

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $this->type->buildView($view, $form, $options);

        $this->assertEquals(
            [
                'attr'  => [],
                'value' => null
            ],
            $view->vars
        );
    }

    public function testBuildViewForDisabled()
    {
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['grouping', $this->groupingConfigProvider],
                    ]
                )
            );

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity3'),
            'association_class' => 'test'
        ];

        $this->type->buildView($view, $form, $options);

        $this->assertEquals(
            [
                'disabled' => true,
                'attr'     => [
                    'class' => 'disabled-choice'
                ],
                'value'    => null
            ],
            $view->vars
        );
    }

    public function testBuildViewForDisabledWithCssClass()
    {
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['grouping', $this->groupingConfigProvider],
                    ]
                )
            );

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity3'),
            'association_class' => 'test'
        ];

        $view->vars['attr']['class'] = 'test-class';

        $this->type->buildView($view, $form, $options);

        $this->assertEquals(
            [
                'disabled' => true,
                'attr'     => [
                    'class' => 'test-class disabled-choice'
                ],
                'value'    => null
            ],
            $view->vars
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_entity_extend_multiple_association_choice', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }
}
