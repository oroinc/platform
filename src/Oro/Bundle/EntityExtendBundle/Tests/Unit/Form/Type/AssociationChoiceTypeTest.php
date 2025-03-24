<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\AssociationChoiceType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AssociationChoiceTypeTest extends AssociationTypeTestCase
{
    #[\Override]
    protected function getFormType(): AbstractType
    {
        return new AssociationChoiceType(
            new AssociationTypeHelper($this->configManager),
            $this->configManager
        );
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(bool $newVal, bool $oldVal, string $state, bool $isSetStateExpected): void
    {
        $this->doTestSubmit(
            'enabled',
            AssociationChoiceType::class,
            [
                'config_id' => new EntityConfigId('test', 'Test\Entity'),
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

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_entity_extend_association_choice', $this->getFormType()->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(ChoiceType::class, $this->getFormType()->getParent());
    }
}
