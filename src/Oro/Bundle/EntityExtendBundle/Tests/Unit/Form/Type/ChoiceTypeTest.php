<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as SymfonyChoiceType;

class ChoiceTypeTest extends AbstractConfigTypeTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getFormType(): AbstractType
    {
        return new ChoiceType(
            new ConfigTypeHelper($this->configManager),
            $this->configManager
        );
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(bool $newVal, bool $oldVal, string $state, bool $isSetStateExpected)
    {
        $this->doTestSubmit(
            'testAttr',
            ChoiceType::class,
            [
                'config_id' => new EntityConfigId('test', 'Test\Entity'),
                'choices'   => ['No' => false, 'Yes' => true]
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
            [false, true, ExtendScope::STATE_ACTIVE, true],
            [true, false, ExtendScope::STATE_ACTIVE, true],
            [true, false, ExtendScope::STATE_UPDATE, false],
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(
            'oro_entity_extend_choice',
            $this->getFormType()->getBlockPrefix()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            SymfonyChoiceType::class,
            $this->getFormType()->getParent()
        );
    }
}
