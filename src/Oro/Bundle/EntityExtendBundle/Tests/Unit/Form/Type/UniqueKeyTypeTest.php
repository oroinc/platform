<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyType;

class UniqueKeyTypeTest extends TypeTestCase
{
    /**
     * @var UniqueKeyType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $fields = [
            'firstName' => 'First Name',
            'lastName'  => 'Last Name',
            'email'     => 'Email',
        ];

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
