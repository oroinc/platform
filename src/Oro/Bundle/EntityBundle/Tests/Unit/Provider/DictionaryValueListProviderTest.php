<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Provider\DictionaryValueListProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DictionaryValueListProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private EntityManagerInterface&MockObject $em;
    private DictionaryValueListProvider $dictionaryValueListProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->dictionaryValueListProvider = new DictionaryValueListProvider($this->configManager, $doctrine);
    }

    private function getEntityConfig(string $className, array $values): Config
    {
        return new Config(new EntityConfigId('grouping', $className), $values);
    }

    private function getEntityFieldConfig(string $className, string $fieldName, array $values): Config
    {
        return new Config(new FieldConfigId('extend', $className, $fieldName), $values);
    }

    public function testSupports(): void
    {
        $className = 'Test\Dictionary';

        $groupingConfig = $this->getEntityConfig($className, ['groups' => ['dictionary', 'another']]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', $className)
            ->willReturn($groupingConfig);

        self::assertTrue($this->dictionaryValueListProvider->supports($className));
    }

    public function testSupportsForNotConfigurableEntity(): void
    {
        $className = 'Test\NotConfigurableEntity';

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);

        self::assertFalse($this->dictionaryValueListProvider->supports($className));
    }

    public function testSupportsForNotDictionary(): void
    {
        $className = 'Test\NotDictionary';

        $groupingConfig = $this->getEntityConfig($className, []);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', $className)
            ->willReturn($groupingConfig);

        self::assertFalse($this->dictionaryValueListProvider->supports($className));
    }

    public function testSupportsForNotDictionaryWithGroups(): void
    {
        $className = 'Test\NotDictionaryWithGroups';

        $groupingConfig = $this->getEntityConfig($className, ['groups' => ['another']]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', $className)
            ->willReturn($groupingConfig);

        self::assertFalse($this->dictionaryValueListProvider->supports($className));
    }

    public function testGetValueListQueryBuilder(): void
    {
        $className = 'Test\Dictionary';

        $qb = $this->createMock(QueryBuilder::class);
        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('from')
            ->with($className, 'e')
            ->willReturnSelf();

        self::assertSame($qb, $this->dictionaryValueListProvider->getValueListQueryBuilder($className));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetSerializationConfig(): void
    {
        $className = 'Test\Dictionary';
        $manyToOneTargetClassName = 'Test\ManyToOne';

        $metadata = $this->createMock(ClassMetadata::class);

        $manyToOneTargetMetadata = $this->createMock(ClassMetadata::class);

        $this->em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [$className, $metadata],
                [$manyToOneTargetClassName, $manyToOneTargetMetadata],
            ]);

        $metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'default', 'extend_field']);
        $metadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn(['manyToOne', 'manyToMany', 'extend_association']);
        $metadata->expects(self::exactly(2))
            ->method('getAssociationMapping')
            ->willReturnMap([
                [
                    'manyToOne',
                    [
                        'type'         => ClassMetadata::MANY_TO_ONE,
                        'isOwningSide' => true,
                        'targetEntity' => $manyToOneTargetClassName
                    ]
                ],
                [
                    'manyToMany',
                    [
                        'type'         => ClassMetadata::MANY_TO_MANY,
                        'isOwningSide' => true,
                        'targetEntity' => 'Test\ManyToMany'
                    ]
                ],
            ]);

        $manyToOneTargetMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->configManager->expects(self::exactly(7))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    $className,
                    'id',
                    $this->getEntityFieldConfig($className, 'id', [])
                ],
                [
                    'extend',
                    $className,
                    'name',
                    $this->getEntityFieldConfig($className, 'name', [])
                ],
                [
                    'extend',
                    $className,
                    'default',
                    $this->getEntityFieldConfig($className, 'default', [])
                ],
                [
                    'extend',
                    $className,
                    'extend_field',
                    $this->getEntityFieldConfig($className, 'extend_field', ['is_extend' => true])
                ],
                [
                    'extend',
                    $className,
                    'manyToOne',
                    $this->getEntityFieldConfig($className, 'manyToOne', [])
                ],
                [
                    'extend',
                    $className,
                    'manyToMany',
                    $this->getEntityFieldConfig($className, 'manyToMany', [])
                ],
                [
                    'extend',
                    $className,
                    'extend_association',
                    $this->getEntityFieldConfig($className, 'extend_field', ['is_extend' => true])
                ]
            ]);

        self::assertEquals(
            [
                'exclusion_policy' => 'all',
                'hints'            => ['HINT_TRANSLATABLE'],
                'fields'           => [
                    'id'        => null,
                    'name'      => null,
                    'default'   => null,
                    'manyToOne' => ['fields' => 'id']
                ]
            ],
            $this->dictionaryValueListProvider->getSerializationConfig($className)
        );
    }

    public function testGetSupportedEntityClasses(): void
    {
        $configs = [
            $this->getEntityConfig('Test\Dictionary', ['groups' => ['dictionary', 'another']]),
            $this->getEntityConfig('Test\NotDictionary', []),
            $this->getEntityConfig('Test\NotDictionaryWithGroups', ['groups' => ['another']])
        ];

        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('grouping', null, true)
            ->willReturn($configs);

        self::assertEquals(
            ['Test\Dictionary'],
            $this->dictionaryValueListProvider->getSupportedEntityClasses()
        );
    }
}
