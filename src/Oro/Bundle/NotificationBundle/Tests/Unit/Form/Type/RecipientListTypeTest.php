<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\NotificationBundle\Form\Type\RecipientListType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipientListTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var RecipientListType */
    protected $type;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->type = new RecipientListType($this->entityManager);
    }

    public function testBuildForm()
    {
        /** @var FormBuilder|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(3))->method('add');

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }
}
