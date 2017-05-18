<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\NotificationBundle\Form\Type\RecipientListType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipientListTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var RecipientListType */
    protected $type;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->type = new RecipientListType($this->entityManager);
    }

    public function testGetName()
    {
        $this->assertEquals(RecipientListType::NAME, $this->type->getName());
    }

    public function testBuildForm()
    {
        /** @var FormBuilder|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(4))->method('add');

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->setDefaultOptions($resolver);
    }
}
