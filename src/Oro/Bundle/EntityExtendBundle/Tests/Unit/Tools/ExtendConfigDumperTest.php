<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class ExtendConfigDumperTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAMESPACE = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\Dumper';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManagerBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ConfigProviderMock */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $generator;

    /** @var string */
    protected $cacheDir;

    /** @var ExtendConfigDumper */
    protected $dumper;

    public function setUp()
    {
        $this->entityManagerBag = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = new ConfigProviderMock($this->configManager, 'extend');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->configProvider);

        $this->generator = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\EntityGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheDir = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR
            . 'Dumper' . DIRECTORY_SEPARATOR . 'cache';

        $this->dumper = new ExtendConfigDumper(
            $this->entityManagerBag,
            $this->configManager,
            new ExtendDbIdentifierNameGenerator(),
            new FieldTypeHelper([]),
            $this->generator,
            $this->cacheDir
        );
    }

    public function testCheckConfigWhenAliasesExists()
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

        $this->configManager->expects($this->never())
            ->method('flush');

        $this->dumper->checkConfig();
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

        $this->assertEquals(
            [
                'class'   => self::CLASS_NAMESPACE . '\Entity\TestEntity1',
                'entity'  => self::CLASS_NAMESPACE . '\cache\EX_OroEntityConfigBundle_Entity1',
                'type'    => 'Extend',
                'parent'  => self::CLASS_NAMESPACE . '\Model\ExtendEntity1',
                'inherit' => false
            ],
            $this->configProvider->getConfig(self::CLASS_NAMESPACE . '\Entity\TestEntity1')->get('schema')
        );

        $this->assertEquals(
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
}
