<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\ChoiceAssociationPropertyType;

class ChoiceAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    /** @var ChoiceAssociationPropertyType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnArgument(0));

        $this->type = new ChoiceAssociationPropertyType($this->configManager, $entityClassResolver);
    }

    public function testBuildView()
    {
        $this->prepareBuildViewTest();

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'Test\AssocEntity'
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
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'Test\Entity'
        ];

        $this->type->buildView($view, $form, $options);

        $this->assertEquals(
            $this->getDisabledFormView(),
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
            'association_class' => 'Test\AssocEntity'
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
            'association_class' => 'Test\AssocEntity'
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
            'association_class' => 'Test\AssocEntity'
        ];

        $this->type->buildView($view, $form, $options);

        $this->assertEquals(
            $this->getDisabledFormView(),
            $view->vars
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_association_property_choice',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'choice',
            $this->type->getParent()
        );
    }

    /**
     * @return array
     */
    protected function getDisabledFormView()
    {
        return [
            'disabled' => true,
            'attr'     => [],
            'value'    => null
        ];
    }
}
