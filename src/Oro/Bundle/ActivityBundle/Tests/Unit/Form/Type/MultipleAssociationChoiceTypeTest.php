<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ActivityBundle\Form\Type\MultipleAssociationChoiceType;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\MultipleAssociationChoiceType as BaseMultipleAssociationChoiceType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type\AssociationTypeTestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;

class MultipleAssociationChoiceTypeTest extends AssociationTypeTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getFormType(): AbstractType
    {
        return new MultipleAssociationChoiceType(
            new AssociationTypeHelper($this->configManager, $this->createMock(EntityClassResolver::class)),
            $this->configManager
        );
    }

    public function testFinishViewForDisabled()
    {
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['test', $this->testConfigProvider],
            ]);

        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity2')
            ->willReturn(false);
        $this->testConfigProvider->expects($this->never())
            ->method('getConfig');

        $view    = new FormView();
        $form    = new Form($this->createMock(FormConfigInterface::class));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity2'),
            'association_class' => 'test'
        ];

        $view->vars['disabled'] = false;

        $view->children[0] = new FormView($view);
        $view->children[1] = new FormView($view);

        $view->children[0]->vars['value'] = 'Test\Entity1';
        $view->children[1]->vars['value'] = 'Test\Entity2';

        $type = $this->getFormType();
        $type->finishView($view, $form, $options);

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

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_activity_multiple_association_choice', $this->getFormType()->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(BaseMultipleAssociationChoiceType::class, $this->getFormType()->getParent());
    }
}
