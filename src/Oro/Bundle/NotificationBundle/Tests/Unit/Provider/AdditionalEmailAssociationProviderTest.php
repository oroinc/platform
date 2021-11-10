<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Provider\AdditionalEmailAssociationProvider;
use Oro\Bundle\NotificationBundle\Tests\Unit\Fixtures\Entity\EmailHolderTestEntity;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdditionalEmailAssociationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var AdditionalEmailAssociationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($value) {
                return 'translated_' . $value;
            });

        $this->provider = new AdditionalEmailAssociationProvider(
            $this->doctrine,
            $this->configProvider,
            $translator
        );
    }

    public function testGetAssociationsWithNonSupportedEntity()
    {
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null);

        self::assertEquals([], $this->provider->getAssociations(\stdClass::class));
    }

    public function testGetAssociationsWithEntityWithEmptyAssociations()
    {
        $entityClass = \stdClass::class;

        $classMetadata = new ClassMetadata('test_entity');
        $classMetadata->associationMappings = [];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($em);

        self::assertEquals([], $this->provider->getAssociations($entityClass));
    }

    public function testGetAssociations()
    {
        $entityClass = \stdClass::class;

        $classMetadata = new ClassMetadata('test_entity');
        $classMetadata->associationMappings = [
            'association1' => ['targetEntity' => 'Test\TargetEntity1'],
            'association2' => ['targetEntity' => 'Test\TargetEntity2'],
            'association3' => ['targetEntity' => 'Test\TargetEntity3']
        ];

        $this->configProvider->expects(self::exactly(3))
            ->method('hasConfig')
            ->willReturnMap([
                [$entityClass, 'association1', true],
                [$entityClass, 'association2', false],
                [$entityClass, 'association3', false]
            ]);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, 'association1')
            ->willReturn(new Config($this->createMock(ConfigIdInterface::class), ['label' => 'association_1']));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($em);

        self::assertEquals(
            [
                'association1' => ['label' => 'translated_association_1', 'target_class' => 'Test\TargetEntity1'],
                'association2' => ['label' => 'Association2', 'target_class' => 'Test\TargetEntity2'],
                'association3' => ['label' => 'Association3', 'target_class' => 'Test\TargetEntity3']
            ],
            $this->provider->getAssociations($entityClass)
        );
    }

    public function testIsAssociationSupportedForSupportedEntity()
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn(new ClassMetadata($entityClass));

        self::assertTrue($this->provider->isAssociationSupported($entity, 'test_field'));
    }

    public function testIsAssociationSupportedForNotSupportedEntity()
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn(null);

        self::assertFalse($this->provider->isAssociationSupported($entity, 'test_field'));
    }

    public function testGetAssociationValue()
    {
        $entity = new EmailHolderTestEntity();
        $entity->setTestField('test_value');
        $entityClass = get_class($entity);
        $associationName = 'testField';

        $classMetadata = new ClassMetadata($entityClass);
        $classMetadata->associationMappings = [
            $associationName => ['targetEntity' => 'Test\TargetEntity1']
        ];
        $classMetadata->wakeupReflection(new RuntimeReflectionService());

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);

        self::assertEquals('test_value', $this->provider->getAssociationValue($entity, $associationName));
    }
}
