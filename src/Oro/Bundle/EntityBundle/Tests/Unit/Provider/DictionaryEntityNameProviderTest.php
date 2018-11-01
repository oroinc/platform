<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\DictionaryEntityNameProvider;
use Oro\Bundle\EntityBundle\Tests\Unit\Provider\Fixtures\DictionaryEntity;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DictionaryEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityNameProvider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->entityNameProvider = new DictionaryEntityNameProvider(
            $this->configManager,
            $this->doctrine,
            new PropertyAccessor()
        );
    }

    /**
     * @param string $scope
     * @param string $entityClass
     * @param array  $values
     *
     * @return Config
     */
    private function getEntityConfig($scope, $entityClass, $values = [])
    {
        return new Config(
            new EntityConfigId($scope, $entityClass),
            $values
        );
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     * @param string $hasField
     */
    private function setHasFieldExpectations($entityClass, $fieldName, $hasField)
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('hasField')
            ->with($fieldName)
            ->willReturn($hasField);
    }

    public function testGetNameForNotManageableEntity()
    {
        $entity = new DictionaryEntity();
        $entity->setName('testName');
        $entity->setLabel('testLabel');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(false);

        self::assertFalse(
            $this->entityNameProvider->getName('test', null, $entity)
        );
    }

    public function testGetNameDQLForNotManageableEntity()
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(false);

        self::assertFalse(
            $this->entityNameProvider->getNameDQL('test', null, DictionaryEntity::class, 'e')
        );
    }

    public function testGetNameForNotDictionaryEntity()
    {
        $entity = new DictionaryEntity();
        $entity->setName('testName');
        $entity->setLabel('testLabel');

        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', DictionaryEntity::class)
            ->willReturn($groupingConfig);

        self::assertFalse(
            $this->entityNameProvider->getName('test', null, $entity)
        );
    }

    public function testGetNameDQLForNotDictionaryEntity()
    {
        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', DictionaryEntity::class)
            ->willReturn($groupingConfig);

        self::assertFalse(
            $this->entityNameProvider->getNameDQL('test', null, DictionaryEntity::class, 'e')
        );
    }

    public function testGetNameForDictionaryWithoutConfiguredAndDefaultRepresentationField()
    {
        $entity = new DictionaryEntity();
        $entity->setName('testName');
        $entity->setLabel('testLabel');

        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class, ['groups' => ['dictionary']]);
        $dictionaryConfig = $this->getEntityConfig('dictionary', DictionaryEntity::class);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['grouping', DictionaryEntity::class, $groupingConfig],
                ['dictionary', DictionaryEntity::class, $dictionaryConfig]
            ]);

        $this->setHasFieldExpectations(DictionaryEntity::class, 'label', false);

        self::assertFalse(
            $this->entityNameProvider->getName('test', null, $entity)
        );
    }

    public function testGetNameDQLForDictionaryWithoutConfiguredAndDefaultRepresentationField()
    {
        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class, ['groups' => ['dictionary']]);
        $dictionaryConfig = $this->getEntityConfig('dictionary', DictionaryEntity::class);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['grouping', DictionaryEntity::class, $groupingConfig],
                ['dictionary', DictionaryEntity::class, $dictionaryConfig]
            ]);

        $this->setHasFieldExpectations(DictionaryEntity::class, 'label', false);

        self::assertFalse(
            $this->entityNameProvider->getNameDQL('test', null, DictionaryEntity::class, 'e')
        );
    }

    public function testGetNameForDictionaryWithoutConfiguredRepresentationField()
    {
        $entity = new DictionaryEntity();
        $entity->setName('testName');
        $entity->setLabel('testLabel');

        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class, ['groups' => ['dictionary']]);
        $dictionaryConfig = $this->getEntityConfig('dictionary', DictionaryEntity::class);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['grouping', DictionaryEntity::class, $groupingConfig],
                ['dictionary', DictionaryEntity::class, $dictionaryConfig]
            ]);

        $this->setHasFieldExpectations(DictionaryEntity::class, 'label', true);

        self::assertEquals(
            $entity->getLabel(),
            $this->entityNameProvider->getName('test', null, $entity)
        );
    }

    public function testGetNameDQLForDictionaryWithoutConfiguredRepresentationField()
    {
        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class, ['groups' => ['dictionary']]);
        $dictionaryConfig = $this->getEntityConfig('dictionary', DictionaryEntity::class);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['grouping', DictionaryEntity::class, $groupingConfig],
                ['dictionary', DictionaryEntity::class, $dictionaryConfig]
            ]);

        $this->setHasFieldExpectations(DictionaryEntity::class, 'label', true);

        self::assertEquals(
            'e.label',
            $this->entityNameProvider->getNameDQL('test', null, DictionaryEntity::class, 'e')
        );
    }

    public function testGetNameForDictionaryWithConfiguredRepresentationField()
    {
        $entity = new DictionaryEntity();
        $entity->setName('testName');
        $entity->setLabel('testLabel');

        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class, ['groups' => ['dictionary']]);
        $dictionaryConfig = $this->getEntityConfig(
            'dictionary',
            DictionaryEntity::class,
            ['representation_field' => 'name']
        );

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['grouping', DictionaryEntity::class, $groupingConfig],
                ['dictionary', DictionaryEntity::class, $dictionaryConfig]
            ]);

        self::assertEquals(
            $entity->getName(),
            $this->entityNameProvider->getName('test', null, $entity)
        );
    }

    public function testGetNameDQLForDictionaryWithConfiguredRepresentationField()
    {
        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class, ['groups' => ['dictionary']]);
        $dictionaryConfig = $this->getEntityConfig(
            'dictionary',
            DictionaryEntity::class,
            ['representation_field' => 'name']
        );

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['grouping', DictionaryEntity::class, $groupingConfig],
                ['dictionary', DictionaryEntity::class, $dictionaryConfig]
            ]);

        self::assertEquals(
            'e.name',
            $this->entityNameProvider->getNameDQL('test', null, DictionaryEntity::class, 'e')
        );
    }

    public function testGetNameForDictionaryWithoutConfiguredRepresentationFieldButWithSearchFields()
    {
        $entity = new DictionaryEntity();
        $entity->setName('testName');
        $entity->setLabel('testLabel');

        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class, ['groups' => ['dictionary']]);
        $dictionaryConfig = $this->getEntityConfig(
            'dictionary',
            DictionaryEntity::class,
            ['search_fields' => ['name', 'label']]
        );

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['grouping', DictionaryEntity::class, $groupingConfig],
                ['dictionary', DictionaryEntity::class, $dictionaryConfig]
            ]);

        self::assertEquals(
            $entity->getName() . ' ' . $entity->getLabel(),
            $this->entityNameProvider->getName('test', null, $entity)
        );
    }

    public function testGetNameDQLForDictionaryWithoutConfiguredRepresentationFieldButWithSearchFields()
    {
        $groupingConfig = $this->getEntityConfig('grouping', DictionaryEntity::class, ['groups' => ['dictionary']]);
        $dictionaryConfig = $this->getEntityConfig(
            'dictionary',
            DictionaryEntity::class,
            ['search_fields' => ['name', 'label']]
        );

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(DictionaryEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['grouping', DictionaryEntity::class, $groupingConfig],
                ['dictionary', DictionaryEntity::class, $dictionaryConfig]
            ]);

        self::assertEquals(
            'CONCAT(e.name, e.label)',
            $this->entityNameProvider->getNameDQL('test', null, DictionaryEntity::class, 'e')
        );
    }
}
