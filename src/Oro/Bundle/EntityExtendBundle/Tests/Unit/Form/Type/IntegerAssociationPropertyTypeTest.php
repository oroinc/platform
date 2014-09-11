<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\IntegerAssociationPropertyType;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;

class IntegerAssociationPropertyTypeTest extends AssociationTypeTestCase
{
    /** @var IntegerAssociationPropertyType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnArgument(0));

        $this->type = new IntegerAssociationPropertyType(
            new AssociationTypeHelper($this->configManager, $entityClassResolver),
            $this->configManager
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_association_property_integer',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'integer',
            $this->type->getParent()
        );
    }

    /**
     * @return array
     */
    protected function getDisabledFormView()
    {
        return [
            'disabled' => true,
            'attr'     => [],
            'value'    => null
        ];
    }
}
