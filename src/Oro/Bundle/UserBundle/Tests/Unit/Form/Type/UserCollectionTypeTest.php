<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Form\Type\UserCollectionType;

class UserCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_USER_ENTITY = 'Oro\Bundle\UserBundle\Entity\User';

    /** @var UserCollectionType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new UserCollectionType();
        $this->formType->setDataClass(self::CLASS_USER_ENTITY);
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'required' => false,
                    'class'  => self::CLASS_USER_ENTITY,
                    'property' => 'fullName',
                    'multiple' => true,
                    'attr' => [
                        'class' => 'user-user-collection',
                    ],
                ]
            );

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('genemu_jqueryselect2_entity', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(UserCollectionType::NAME, $this->formType->getName());
    }
}
