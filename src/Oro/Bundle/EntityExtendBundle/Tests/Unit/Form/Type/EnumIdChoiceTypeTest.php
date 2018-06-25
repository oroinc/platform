<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumIdChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnumIdChoiceTypeTest extends TypeTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var EnumIdChoiceType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->type = new EnumIdChoiceType($this->doctrine);
    }

    public function testGetParent()
    {
        $this->assertEquals(EnumChoiceType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_enum_id_choice', $this->type->getName());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver **/
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['multiple' => true]);

        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['enum_code']);

        $this->type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('addModelTransformer');

        $this->type->buildForm($builder, ['enum_code' => 'test_enum', 'multiple' => true]);
    }
}
