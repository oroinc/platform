<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProviderInterface;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\EntityGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Filesystem\Filesystem;

class ExtendConfigDumperTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const CLASS_NAMESPACE = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\Dumper';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityManagerBag;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigProviderMock */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $generator;

    /** @var string */
    private $cacheDir;

    /** @var ExtendConfigDumper */
    private $dumper;

    /** @var ExtendEntityConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $extendEntityConfigProvider;

    protected function setUp(): void
    {
        $this->entityManagerBag = $this->createMock(EntityManagerBag::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configProvider = new ConfigProviderMock($this->configManager, 'extend');
        $this->generator = $this->createMock(EntityGenerator::class);
        $this->extendEntityConfigProvider = $this->createMock(ExtendEntityConfigProviderInterface::class);
        $this->cacheDir = $this->getTempDir('ExtendConfigDumperTest', false);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->configProvider);
    }

    /**
     * @param array $extensions
     *
     * @return ExtendConfigDumper
     */
    private function getExtendConfigDumper($extensions = [])
    {
        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn([]);

        return new ExtendConfigDumper(
            $this->entityManagerBag,
            $this->configManager,
            new ExtendDbIdentifierNameGenerator(),
            new FieldTypeHelper($entityExtendConfigurationProvider),
            $this->generator,
            $this->extendEntityConfigProvider,
            $this->cacheDir,
            $extensions
        );
    }
    public function testCheckConfigWhenAliasesExists()
    {
        $fs = new Filesystem();
        $fs->mkdir(ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir));
        $fs->touch(ExtendClassLoadingUtils::getAliasesPath($this->cacheDir));

        $this->configProvider->addEntityConfig(
            self::CLASS_NAMESPACE . '\Entity\TestEntity1',
            [
                'schema' => [
                    'class'  => self::CLASS_NAMESPACE . '\Entity\TestEntity1',
                    'entity' => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity1',
                    'type'   => 'Extend'
                ]
            ]
        );

        $this->configManager->expects($this->never())
            ->method('flush');

        $dumper = $this->getExtendConfigDumper();
        $dumper->checkConfig();

        $fs->remove(ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir));
    }

    public function testCheckConfig()
    {
        $this->configProvider->addEntityConfig(
            self::CLASS_NAMESPACE . '\Entity\TestEntity1',
            [
                'schema' => [
                    'class'  => self::CLASS_NAMESPACE . '\Entity\TestEntity1',
                    'entity' => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity1',
                    'type'   => 'Extend'
                ]
            ]
        );
        $this->configProvider->addEntityConfig(
            self::CLASS_NAMESPACE . '\Entity\TestEntity2',
            [
                'schema' => [
                    'class'  => self::CLASS_NAMESPACE . '\Entity\TestEntity2',
                    'entity' => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity2',
                    'type'   => 'Extend'
                ]
            ],
            true
        );

        $this->configManager->expects($this->once())
            ->method('persist')
            ->withConsecutive(
                [$this->configProvider->getConfig(self::CLASS_NAMESPACE . '\Entity\TestEntity2')],
                [$this->configProvider->getConfig(self::CLASS_NAMESPACE . '\Entity\TestEntity1')]
            );

        $this->configManager->expects($this->once())
            ->method('flush');

        $dumper = $this->getExtendConfigDumper();
        $dumper->setCacheDir($this->cacheDir . '_other');
        $dumper->checkConfig();

        self::assertEquals(
            [
                'class'   => self::CLASS_NAMESPACE . '\Entity\TestEntity1',
                'entity'  => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity1',
                'type'    => 'Extend',
            ],
            $this->configProvider->getConfig(self::CLASS_NAMESPACE . '\Entity\TestEntity1')->get('schema')
        );

        self::assertEquals(
            [
                'class'   => self::CLASS_NAMESPACE . '\Entity\TestEntity2',
                'entity'  => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity2',
                'type'    => 'Extend',
                'parent'  => self::CLASS_NAMESPACE . '\TestAbstractClass',
                'inherit' => false
            ],
            $this->configProvider->getConfig(self::CLASS_NAMESPACE . '\Entity\TestEntity2')->get('schema')
        );
    }

    public function testCheckConfigWhenItIsAlreadyUpToDate()
    {
        $this->configProvider->addEntityConfig(
            self::CLASS_NAMESPACE . '\Entity\TestEntity1',
            [
                'schema' => [
                    'class'   => self::CLASS_NAMESPACE . '\Entity\TestEntity1',
                    'entity'  => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity1',
                    'type'    => 'Extend',
                    'parent'  => self::CLASS_NAMESPACE . '\TestAbstractClass',
                    'inherit' => self::CLASS_NAMESPACE . '\TestAbstractClass'
                ]
            ]
        );
        $this->configProvider->addEntityConfig(
            self::CLASS_NAMESPACE . '\Entity\TestEntity2',
            [
                'schema' => [
                    'class'   => self::CLASS_NAMESPACE . '\Entity\TestEntity2',
                    'entity'  => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity2',
                    'type'    => 'Extend',
                    'parent'  => self::CLASS_NAMESPACE . '\TestAbstractClass',
                    'inherit' => false
                ]
            ],
            true
        );

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->configManager->expects($this->never())
            ->method('flush');

        $dumper = $this->getExtendConfigDumper();
        $dumper->setCacheDir($this->cacheDir . '_other');
        $dumper->checkConfig();
    }

    public function testUpdateConfig()
    {
        $this->entityManagerBag->expects($this->exactly(2))
            ->method('getEntityManagers')
            ->willReturn([]);

        $extension = $this->createMock(AbstractEntityConfigDumperExtension::class);
        $configId = new EntityConfigId('somescope', 'SomeClass');
        $config = new Config(
            $configId,
            ['param1' => 'value1', 'upgradeable' => true]
        );

        $this->extendEntityConfigProvider->expects($this->once())
            ->method('getExtendEntityConfigs')
            ->willReturn([$config]);

        $this->configManager->expects($this->once())
            ->method('flush');

        $this->configProvider->addFieldConfig(
            'SomeClass',
            'field1',
            'boolean',
            [
                'is_extend' => true,
                'default'   => true,
                'nullable'  => false
            ]
        );
        $this->configProvider->addFieldConfig(
            'SomeClass',
            'field2',
            'integer',
            [
                'is_extend' => true
            ]
        );

        $dumper = $this->getExtendConfigDumper([$extension]);
        $dumper->updateConfig();

        self::assertEquals(
            new Config(
                $configId,
                [
                    'param1'       => 'value1',
                    'upgradeable'  => true,
                    'schema'       => [
                        'class'     => 'SomeClass',
                        'entity'    => 'SomeClass',
                        'type'      => 'Extend',
                        'property'  => [
                            'field1' => [],
                            'field2' => [],
                        ],
                        'relation'  => [],
                        'default'   => [],
                        'addremove' => [],
                        'doctrine'  => [
                            'SomeClass' => [
                                'type'   => 'entity',
                                'fields' => [
                                    'field1' => [
                                        'column'    => 'field1',
                                        'type'      => 'boolean',
                                        'nullable'  => false,
                                        'length'    => null,
                                        'precision' => null,
                                        'scale'     => null,
                                        'default'   => true,
                                    ],
                                    'field2' => [
                                        'column'    => 'field2',
                                        'type'      => 'integer',
                                        'nullable'  => true,
                                        'length'    => null,
                                        'precision' => null,
                                        'scale'     => null,
                                        'default'   => null,
                                    ]
                                ]
                            ]
                        ],
                        'parent'    => false,
                        'inherit'   => false
                    ],
                    'state'        => ExtendScope::STATE_ACTIVE,
                ]
            ),
            $config
        );
    }

    public function testClearWithEntityEliasExists()
    {
        $fs = new Filesystem();
        $entityCacheDir = ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir);
        $fs->mkdir($entityCacheDir);
        $aliasDataFile = ExtendClassLoadingUtils::getAliasesPath($this->cacheDir);
        $fs->touch($aliasDataFile);
        self::assertTrue($fs->exists($aliasDataFile));

        $this->entityManagerBag->expects($this->once())
            ->method('getEntityManagers')
            ->willReturn([]);

        $dumper = $this->getExtendConfigDumper();
        $dumper->clear();

        self::assertFalse($fs->exists($aliasDataFile));
        self::assertTrue($fs->exists($entityCacheDir));

        $fs->remove($entityCacheDir);
    }
}
