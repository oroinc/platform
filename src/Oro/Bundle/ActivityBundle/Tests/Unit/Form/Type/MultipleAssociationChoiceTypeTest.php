<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\ActivityBundle\Form\Type\MultipleAssociationChoiceType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type\AssociationTypeTestCase;

class MultipleAssociationChoiceTypeTest extends AssociationTypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var MultipleAssociationChoiceType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new MultipleAssociationChoiceType(
            new AssociationTypeHelper($this->configManager, $entityClassResolver),
            $this->configManager
        );
    }

    public function testFinishViewForDisabled()
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

        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity2')
            ->will($this->returnValue(false));
        $this->testConfigProvider->expects($this->never())
            ->method('getConfig');

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity2'),
            'association_class' => 'test'
        ];

        $view->vars['disabled'] = false;

        $view->children[0] = new FormView($view);
        $view->children[1] = new FormView($view);

        $view->children[0]->vars['value'] = 'Test\Entity1';
        $view->children[1]->vars['value'] = 'Test\Entity2';

        $this->type->finishView($view, $form, $options);

        $this->assertEquals(
            [
                'attr'     => [],
                'value'    => 'Test\Entity1'
            ],
            $view->children[0]->vars
        );
        $this->assertEquals(
            [
                'attr'     => [],
                'disabled' => true,
                'value'    => 'Test\Entity2'
            ],
            $view->children[1]->vars
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_activity_multiple_association_choice', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_entity_extend_multiple_association_choice', $this->type->getParent());
    }
}
