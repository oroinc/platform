<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyType;
use Symfony\Component\Form\Test\TypeTestCase;

class UniqueKeyTypeTest extends TypeTestCase
{
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $fields = array(
            new FieldConfigId('entity', 'Oro\Bundle\UserBundle\Entity\User', 'firstName', 'string'),
            new FieldConfigId('entity', 'Oro\Bundle\UserBundle\Entity\User', 'lastName', 'string'),
            new FieldConfigId('entity', 'Oro\Bundle\UserBundle\Entity\User', 'email', 'string'),
        );

        $this->type = new UniqueKeyType($fields);
    }

    public function testType()
    {
        $formData = array(
            'name' => 'test',
            'key'  => array('firstName', 'lastName', 'email')
        );

        $form = $this->factory->create($this->type);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
    }

    public function testNames()
    {
        $this->assertEquals('oro_entity_extend_unique_key_type', $this->type->getName());
    }
}
