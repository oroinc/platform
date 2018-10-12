<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Form\Extension\AclProtectedTypeExtension;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Bridge\Doctrine\Form\ChoiceList\DoctrineChoiceLoader;
use Symfony\Bridge\Doctrine\Form\ChoiceList\IdReader;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AclProtectedTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = 'AcmeEntity';

    /**
     * @var AclProtectedTypeExtension
     */
    private $extension;

    protected function setUp()
    {
        /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject $fieldAclHelper */
        $fieldAclHelper = self::createMock(AclHelper::class);

        $this->extension = new AclProtectedTypeExtension($fieldAclHelper);
    }

    public function testGetExtendedType()
    {
        self::assertEquals(EntityType::class, $this->extension->getExtendedType());
    }

    public function testConfigureOptionsWithEnabledAclOptions()
    {
        $classMetadata = new ClassMetadata(self::CLASS_NAME);
        $idReader = self::createMock(IdReader::class);

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = self::createMock(QueryBuilder::class);

        /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = self::createMock(EntityRepository::class);
        $repository->expects(self::once())->method('createQueryBuilder')->willReturn($queryBuilder);

        /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = self::createMock(ObjectManager::class);
        $entityManager->expects(self::once())->method('getRepository')->willReturn($repository);
        $entityManager->expects(self::once())->method('getClassMetadata')->willReturn($classMetadata);

        $optionResolver = new OptionsResolver();
        $this->extension->configureOptions($optionResolver);
        $optionResolver->setDefaults([
            'class' => self::CLASS_NAME,
            'query_builder' => null,
            'em' => $entityManager,
            'choices' => null,
            'id_reader' => $idReader,
            'acl_options' => ['disable' => false]
        ]);
        $options = $optionResolver->resolve();
        self::assertInstanceOf(DoctrineChoiceLoader::class, $options['choice_loader']);
    }

    public function testConfigureOptionsWithDisabledAclOptions()
    {
        $classMetadata = new ClassMetadata(self::CLASS_NAME);
        $idReader = self::createMock(IdReader::class);

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = self::createMock(QueryBuilder::class);

        /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = self::createMock(EntityRepository::class);
        $repository->expects(self::once())->method('createQueryBuilder')->willReturn($queryBuilder);

        /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = self::createMock(ObjectManager::class);
        $entityManager->expects(self::once())->method('getRepository')->willReturn($repository);
        $entityManager->expects(self::once())->method('getClassMetadata')->willReturn($classMetadata);

        $optionResolver = new OptionsResolver();
        $this->extension->configureOptions($optionResolver);
        $optionResolver->setDefaults([
            'class' => self::CLASS_NAME,
            'query_builder' => null,
            'em' => $entityManager,
            'choices' => null,
            'id_reader' => $idReader,
            'acl_options' => ['disable' => true]
        ]);
        $options = $optionResolver->resolve();
        self::assertInstanceOf(DoctrineChoiceLoader::class, $options['choice_loader']);
    }

    public function testConfigureOptionsWithChoices()
    {
        $optionResolver = new OptionsResolver();

        $this->extension->configureOptions($optionResolver);
        $optionResolver->setDefaults([
            'class' => self::CLASS_NAME,
            'query_builder' => null,
            'em' => null,
            'choices' => [1],
            'id_reader' => null,
        ]);
        $options = $optionResolver->resolve();

        self::assertNull($options['choice_loader']);
    }
}
