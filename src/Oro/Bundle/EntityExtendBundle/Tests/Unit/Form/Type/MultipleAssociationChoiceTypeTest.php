<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\View\ChoiceView;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\MultipleAssociationChoiceType;

class MultipleAssociationChoiceTypeTest extends AssociationChoiceTypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var MultipleAssociationChoiceType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

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
                'association_class' => 'test',
                'expanded'          => true,
            ],
            $resolver->resolve(['association_class' => 'test'])
        );
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit($newVal, $oldVal, $state, $isSetStateExpected)
    {
        $this->doTestSubmit(
            'items',
            $this->type,
            [
                'config_id'         => new EntityConfigId('test', 'Test\Entity'),
                'association_class' => 'test'
            ],
            [
                'grouping' => $this->groupingConfigProvider,
                'entity'   => $this->entityConfigProvider,
            ],
            $newVal,
            $oldVal,
            $state,
            $isSetStateExpected
        );
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
        $this->prepareBuildViewTest();

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
        $this->prepareBuildViewTest();

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity3'),
            'association_class' => 'test'
        ];

        $this->type->buildView($view, $form, $options);

        $this->assertEquals(
            $this->getDisabledFormView(),
            $view->vars
        );
    }

    public function testBuildViewForDisabledWithCssClass()
    {
        $this->prepareBuildViewTest();

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity3'),
            'association_class' => 'test'
        ];

        $view->vars['attr']['class'] = 'test-class';

        $this->type->buildView($view, $form, $options);

        $this->assertEquals(
            $this->getDisabledFormView('test-class'),
            $view->vars
        );
    }

    public function testBuildViewForEmptyClass()
    {
        $this->prepareBuildViewTest();

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', null),
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

    public function testBuildViewForDictionary()
    {
        $this->prepareBuildViewTest();

        $groupingConfig = new Config(new EntityConfigId('grouping', 'Test\Entity'));
        $groupingConfig->set('groups', [GroupingScope::GROUP_DICTIONARY]);
        $this->groupingConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(true));
        $this->groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($groupingConfig));

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $this->type->buildView($view, $form, $options);

        $this->assertEquals(
            $this->getDisabledFormView(),
            $view->vars
        );
    }

    public function testBuildViewForImmutable()
    {
        $this->prepareBuildViewTest();

        $testConfig = new Config(new EntityConfigId('test', 'Test\Entity'));
        $testConfig->set('immutable', true);
        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(true));
        $this->testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($testConfig));

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $this->type->buildView($view, $form, $options);

        $this->assertEquals(
            $this->getDisabledFormView(),
            $view->vars
        );
    }

    public function testFinishViewNoConfig()
    {
        $this->prepareFinishViewTest();

        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(false));
        $this->testConfigProvider->expects($this->never())
            ->method('getConfig');

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $view->children[0] = new FormView($view);
        $view->children[1] = new FormView($view);

        $view->children[0]->vars['value'] = 'Test\Entity1';
        $view->children[1]->vars['value'] = 'Test\Entity2';

        $this->type->finishView($view, $form, $options);

        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity1'
            ],
            $view->children[0]->vars
        );
        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity2'
            ],
            $view->children[1]->vars
        );
    }

    public function testFinishViewNoImmutable()
    {
        $this->prepareFinishViewTest();

        $testConfig = new Config(new EntityConfigId('test', 'Test\Entity'));
        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(true));
        $this->testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($testConfig));

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $view->children[0] = new FormView($view);
        $view->children[1] = new FormView($view);

        $view->children[0]->vars['value'] = 'Test\Entity1';
        $view->children[1]->vars['value'] = 'Test\Entity2';

        $this->type->finishView($view, $form, $options);

        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity1'
            ],
            $view->children[0]->vars
        );
        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity2'
            ],
            $view->children[1]->vars
        );
    }

    public function testFinishViewWithImmutable()
    {
        $this->prepareFinishViewTest();

        $testConfig = new Config(new EntityConfigId('test', 'Test\Entity'));
        $testConfig->set('immutable', ['Test\Entity1']);
        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(true));
        $this->testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($testConfig));

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $view->children[0] = new FormView($view);
        $view->children[1] = new FormView($view);

        $view->children[0]->vars['value'] = 'Test\Entity1';
        $view->children[1]->vars['value'] = 'Test\Entity2';

        $this->type->finishView($view, $form, $options);

        $this->assertEquals(
            [
                'attr'     => [],
                'disabled' => true,
                'value'    => 'Test\Entity1'
            ],
            $view->children[0]->vars
        );
        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity2'
            ],
            $view->children[1]->vars
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

    /**
     * @param string|null $cssClass
     * @return array
     */
    protected function getDisabledFormView($cssClass = null)
    {
        return [
            'disabled' => true,
            'attr'     => [
                'class' => empty($cssClass) ? 'disabled-choice' : $cssClass . ' disabled-choice'
            ],
            'value'    => null
        ];
    }

    protected function prepareFinishViewTest()
    {
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['test', $this->testConfigProvider],
                    ]
                )
            );
    }
}
