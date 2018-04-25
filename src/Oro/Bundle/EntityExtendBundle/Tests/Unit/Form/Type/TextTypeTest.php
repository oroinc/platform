<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextType as SymfonyTextType;

class TextTypeTest extends AbstractConfigTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new TextType(
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
            TextType::class,
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
            ['', '', ExtendScope::STATE_ACTIVE, false],
            ['', 'old', ExtendScope::STATE_ACTIVE, true],
            ['new', '', ExtendScope::STATE_ACTIVE, true],
            ['new', 'old', ExtendScope::STATE_ACTIVE, true],
            ['new', 'old', ExtendScope::STATE_UPDATE, false],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_text',
            $this->getFormType()->getName()
        );
    }

    public function testGetParent()
    {
        $type = $this->getFormType();
        $this->assertEquals(
            SymfonyTextType::class,
            $this->getFormType()->getParent()
        );
    }
}
