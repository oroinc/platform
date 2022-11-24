<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType as SymfonyIntegerType;

class IntegerTypeTest extends AbstractConfigTypeTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getFormType(): AbstractType
    {
        return new IntegerType(
            new ConfigTypeHelper($this->configManager),
            $this->configManager
        );
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(string|int $newVal, ?int $oldVal, string $state, bool $isSetStateExpected)
    {
        $this->doTestSubmit(
            'testAttr',
            IntegerType::class,
            [
                'config_id' => new EntityConfigId('test', 'Test\Entity'),
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
            ['', null, ExtendScope::STATE_ACTIVE, false],
            ['', 0, ExtendScope::STATE_ACTIVE, false],
            ['', 123, ExtendScope::STATE_ACTIVE, true],
            [1234, null, ExtendScope::STATE_ACTIVE, true],
            [1234, 123, ExtendScope::STATE_ACTIVE, true],
            [1234, 123, ExtendScope::STATE_UPDATE, false],
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(
            'oro_entity_extend_integer',
            $this->getFormType()->getBlockPrefix()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            SymfonyIntegerType::class,
            $this->getFormType()->getParent()
        );
    }
}
