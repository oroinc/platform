<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\AssociationChoiceType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AssociationChoiceTypeTest extends AssociationTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnArgument(0));

        return new AssociationChoiceType(
            new AssociationTypeHelper($this->configManager, $entityClassResolver),
            $this->configManager
        );
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit($newVal, $oldVal, $state, $isSetStateExpected)
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

    public function submitProvider()
    {
        return [
            [false, false, ExtendScope::STATE_ACTIVE, false],
            [true, true, ExtendScope::STATE_ACTIVE, false],
            [false, true, ExtendScope::STATE_ACTIVE, false],
            [true, false, ExtendScope::STATE_ACTIVE, true],
            [true, false, ExtendScope::STATE_UPDATE, false],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('oro_entity_extend_association_choice', $this->getFormType()->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->getFormType()->getParent());
    }

    /**
     * @param string|null $cssClass
     * @return array
     */
    protected function getDisabledFormView($cssClass = null)
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
