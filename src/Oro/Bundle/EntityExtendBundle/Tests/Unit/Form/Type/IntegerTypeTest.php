<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType as SymfonyIntegerType;

class IntegerTypeTest extends AbstractConfigTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new IntegerType(
            new ConfigTypeHelper($this->configManager),
            $this->configManager
        );
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit($newVal, $oldVal, $state, $isSetStateExpected)
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

    public function submitProvider()
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

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_integer',
            $this->getFormType()->getName()
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
