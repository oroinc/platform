<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProviderInterface;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Filesystem\Filesystem;

class ExtendConfigDumperTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    const CLASS_NAMESPACE = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\Dumper';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityManagerBag;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var ConfigProviderMock */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $generator;

    /** @var string */
    protected $cacheDir;

    /** @var ExtendConfigDumper */
    protected $dumper;

    /** @var ExtendEntityConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $extendEntityConfigProvider;

    public function setUp()
    {
        $this->entityManagerBag = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag');

        $this->configManager = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\ConfigManager');

        $this->configProvider = new ConfigProviderMock($this->configManager, 'extend');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->configProvider);

        $this->generator = $this->createMock('Oro\Bundle\EntityExtendBundle\Tools\EntityGenerator');

        $this->extendEntityConfigProvider = $this->createMock(ExtendEntityConfigProviderInterface::class);

        $this->cacheDir = $this->getTempDir('ExtendConfigDumperTest', false);

        $this->dumper = new ExtendConfigDumper(
            $this->entityManagerBag,
            $this->configManager,
            new ExtendDbIdentifierNameGenerator(),
            new FieldTypeHelper([]),
            $this->generator,
            $this->extendEntityConfigProvider,
            $this->cacheDir
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

        $this->dumper->checkConfig();

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

        $this->configManager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                $this->configProvider->getConfig(self::CLASS_NAMESPACE . '\Entity\TestEntity1'),
                $this->configProvider->getConfig(self::CLASS_NAMESPACE . '\Entity\TestEntity2')
            );

        $this->configManager->expects($this->once())
            ->method('flush');

        $this->dumper->setCacheDir($this->cacheDir . '_other');
        $this->dumper->checkConfig();

        self::assertEquals(
            [
                'class'   => self::CLASS_NAMESPACE . '\Entity\TestEntity1',
                'entity'  => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity1',
                'type'    => 'Extend',
                'parent'  => self::CLASS_NAMESPACE . '\Model\ExtendEntity1',
                'inherit' => false
            ],
            $this->configProvider->getConfig(self::CLASS_NAMESPACE . '\Entity\TestEntity1')->get('schema')
        );

        self::assertEquals(
            [
                'class'   => self::CLASS_NAMESPACE . '\Entity\TestEntity2',
                'entity'  => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity2',
                'type'    => 'Extend',
                'parent'  => self::CLASS_NAMESPACE . '\Model\ExtendEntity2',
                'inherit' => self::CLASS_NAMESPACE . '\TestAbstractClass'
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
                    'parent'  => self::CLASS_NAMESPACE . '\Model\ExtendEntity1',
                    'inherit' => false
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
                    'parent'  => self::CLASS_NAMESPACE . '\Model\ExtendEntity2',
                    'inherit' => self::CLASS_NAMESPACE . '\TestAbstractClass'
                ]
            ],
            true
        );

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->configManager->expects($this->never())
            ->method('flush');

        $this->dumper->setCacheDir($this->cacheDir . '_other');
        $this->dumper->checkConfig();
    }

    public function testUpdateConfig()
    {
        $this->entityManagerBag->expects($this->exactly(2))
            ->method('getEntityManagers')
            ->willReturn([]);

        /** @var AbstractEntityConfigDumperExtension $extension */
        $extension = $this->createMock(AbstractEntityConfigDumperExtension::class);
        $configId = new EntityConfigId('somescope', 'SomeClass');
        $config = new Config(
            $configId,
            ['param1' => 'value1', 'upgradeable' => true, 'extend_class' => \stdClass::class]
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
                'default'   => true
            ]
        );

        $this->dumper->addExtension($extension);
        $this->dumper->updateConfig();

        self::assertEquals(
            new Config(
                $configId,
                [
                    'param1'       => 'value1',
                    'upgradeable'  => true,
                    'schema'       => [
                        'class'     => 'SomeClass',
                        'entity'    => \stdClass::class,
                        'type'      => 'Extend',
                        'property'  => [
                            'field1' => []
                        ],
                        'relation'  => [],
                        'default'   => [],
                        'addremove' => [],
                        'doctrine'  => [
                            \stdClass::class => [
                                'type'   => 'mappedSuperclass',
                                'fields' => [
                                    'field1' => [
                                        'column'    => 'field1',
                                        'type'      => 'boolean',
                                        'nullable'  => true,
                                        'length'    => null,
                                        'precision' => null,
                                        'scale'     => null,
                                        'default'   => true,
                                    ]
                                ]
                            ]
                        ],
                        'parent'    => false,
                        'inherit'   => false
                    ],
                    'state'        => ExtendScope::STATE_ACTIVE,
                    'extend_class' => \stdClass::class
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

        $this->dumper->clear();

        self::assertFalse($fs->exists($aliasDataFile));
        self::assertTrue($fs->exists($entityCacheDir));

        $fs->remove($entityCacheDir);
    }
}
