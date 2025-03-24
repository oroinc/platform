<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ActivityBundle\Form\Type\MultipleAssociationChoiceType;
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
    #[\Override]
    protected function getFormType(): AbstractType
    {
        return new MultipleAssociationChoiceType(
            new AssociationTypeHelper($this->configManager),
            $this->configManager
        );
    }

    public function testFinishViewForDisabled(): void
    {
        $this->configManager->expects(self::any())
            ->method('getProvider')
            ->willReturnMap([
                ['test', $this->testConfigProvider],
            ]);

        $this->testConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with('Test\Entity2')
            ->willReturn(false);
        $this->testConfigProvider->expects(self::never())
            ->method('getConfig');

        $view = new FormView();
        $form = new Form($this->createMock(FormConfigInterface::class));
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

        self::assertEquals(
            [
                'attr'     => [],
                'value'    => 'Test\Entity1'
            ],
            $view->children[0]->vars
        );
        self::assertEquals(
            [
                'attr'     => [],
                'disabled' => true,
                'value'    => 'Test\Entity2'
            ],
            $view->children[1]->vars
        );
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_activity_multiple_association_choice', $this->getFormType()->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(BaseMultipleAssociationChoiceType::class, $this->getFormType()->getParent());
    }
}
