<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\AssociationChoiceType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AssociationChoiceTypeTest extends AssociationTypeTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getFormType(): AbstractType
    {
        $entityClassResolver = $this->createMock(EntityClassResolver::class);
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->willReturnArgument(0);

        return new AssociationChoiceType(
            new AssociationTypeHelper($this->configManager, $entityClassResolver),
            $this->configManager
        );
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(bool $newVal, bool $oldVal, string $state, bool $isSetStateExpected)
    {
        $this->doTestSubmit(
            'enabled',
            AssociationChoiceType::class,
            [
                'config_id'         => new EntityConfigId('test', 'Test\Entity'),
                'association_class' => 'Test\AssocEntity'
            ],
            [],
            $newVal,
            $oldVal,
            $state,
            $isSetStateExpected
        );
    }

    public function submitProvider(): array
    {
        return [
            [false, false, ExtendScope::STATE_ACTIVE, false],
            [true, true, ExtendScope::STATE_ACTIVE, false],
            [false, true, ExtendScope::STATE_ACTIVE, false],
            [true, false, ExtendScope::STATE_ACTIVE, true],
            [true, false, ExtendScope::STATE_UPDATE, false],
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_entity_extend_association_choice', $this->getFormType()->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->getFormType()->getParent());
    }

    protected function getDisabledFormView(string $cssClass = null): array
    {
        return [
            'disabled' => true,
            'attr'     => [
                'class' => empty($cssClass) ? 'disabled-choice' : $cssClass . ' disabled-choice'
            ],
            'value'    => null
        ];
    }
}
