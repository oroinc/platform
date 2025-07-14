<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserMultiSelectTypeTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserMultiSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->type = new UserMultiSelectType($doctrine);
    }

    public function testBuildView(): void
    {
        $builder = $this->createMock(FormBuilder::class);

        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->isInstanceOf(EntitiesToIdsTransformer::class));

        $this->type->buildForm($builder, ['entity_class' => User::class]);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent(): void
    {
        $this->assertEquals(OroJquerySelect2HiddenType::class, $this->type->getParent());
    }
}
