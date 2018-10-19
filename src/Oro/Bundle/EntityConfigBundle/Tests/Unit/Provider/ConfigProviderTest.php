<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;
use Oro\Component\TestUtils\ORM\Mocks\ConnectionMock;
use Oro\Component\TestUtils\ORM\Mocks\DriverMock;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var Config
     */
    protected $entityConfig;

    /**
     * @var Config
     */
    protected $fieldConfig;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->entityConfig = new Config(new EntityConfigId('testScope', DemoEntity::ENTITY_NAME));
        $this->fieldConfig  = new Config(
            new FieldConfigId('testScope', DemoEntity::ENTITY_NAME, 'testField', 'string')
        );

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())->method('getConfig')->will($this->returnValue($this->entityConfig));
        $this->configManager->expects($this->any())
            ->method('getEntityConfig')
            ->will($this->returnValue($this->entityConfig));
        $this->configManager->expects($this->any())->method('hasConfig')->will($this->returnValue(true));
        $this->configManager->expects($this->any())->method('persist')->will($this->returnValue(true));
        $this->configManager->expects($this->any())->method('flush')->will($this->returnValue(true));

        $this->configProvider  = new ConfigProvider(
            $this->configManager,
            'testScope',
            new PropertyConfigBag([])
        );
    }

    /**
     * @dataProvider getIdProvider
     */
    public function testGetId($className, $fieldName, $fieldType, $expectedResult)
    {
        $result = $this->configProvider->getId($className, $fieldName, $fieldType);
        $this->assertEquals($expectedResult, $result);
    }

    public function testConfig()
    {
        $this->assertEquals($this->configManager, $this->configProvider->getConfigManager());
        $this->assertEquals(true, $this->configProvider->hasConfig(DemoEntity::ENTITY_NAME));
        $this->assertEquals($this->entityConfig, $this->configProvider->getConfig(DemoEntity::ENTITY_NAME));
        $this->assertEquals('testScope', $this->configProvider->getScope());

        $entityConfigId = new EntityConfigId('testScope', DemoEntity::ENTITY_NAME);
        $fieldConfigId  = new FieldConfigId('testScope', DemoEntity::ENTITY_NAME, 'testField', 'string');

        $this->assertEquals($entityConfigId, $this->configProvider->getId(DemoEntity::ENTITY_NAME));
        $this->assertEquals(
            $fieldConfigId,
            $this->configProvider->getId(DemoEntity::ENTITY_NAME, 'testField', 'string')
        );

        $entityConfigIdWithOtherScope = new EntityConfigId('otherScope', DemoEntity::ENTITY_NAME);

        $this->assertEquals($this->entityConfig, $this->configProvider->getConfigById($entityConfigIdWithOtherScope));
    }

    public function testGetClassName()
    {
        $this->assertEquals(DemoEntity::ENTITY_NAME, $this->configProvider->getClassName(DemoEntity::ENTITY_NAME));

        $className  = DemoEntity::ENTITY_NAME;
        $demoEntity = new $className();
        $this->assertEquals(DemoEntity::ENTITY_NAME, $this->configProvider->getClassName($demoEntity));

        $this->assertEquals(DemoEntity::ENTITY_NAME, $this->configProvider->getClassName(array($demoEntity)));

        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())->method('getName')->will($this->returnValue(DemoEntity::ENTITY_NAME));

        $connectionMock       = new ConnectionMock(array(), new DriverMock());
        $emMock               = EntityManagerMock::create($connectionMock);
        $persistentCollection = new PersistentCollection($emMock, $classMetadata, new ArrayCollection);

        $this->assertEquals(DemoEntity::ENTITY_NAME, $this->configProvider->getClassName($persistentCollection));

        $this->expectException('Oro\Bundle\EntityConfigBundle\Exception\RuntimeException');
        $this->assertEquals(DemoEntity::ENTITY_NAME, $this->configProvider->getClassName(array()));
    }

    public function testGetIds()
    {
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->with('testScope', DemoEntity::ENTITY_NAME, false)
            ->will($this->returnValue(array($this->entityConfig->getId())));

        $this->assertEquals(
            array($this->entityConfig->getId()),
            $this->configProvider->getIds(DemoEntity::ENTITY_NAME)
        );
    }

    public function testGetIdsWithHidden()
    {
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->with('testScope', DemoEntity::ENTITY_NAME, true)
            ->will($this->returnValue(array($this->entityConfig->getId())));

        $this->assertEquals(
            array($this->entityConfig->getId()),
            $this->configProvider->getIds(DemoEntity::ENTITY_NAME, true)
        );
    }

    public function testGetConfigs()
    {
        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('testScope', DemoEntity::ENTITY_NAME, false)
            ->will($this->returnValue(array($this->entityConfig)));

        $this->assertEquals(
            array($this->entityConfig),
            $this->configProvider->getConfigs(DemoEntity::ENTITY_NAME)
        );
    }

    public function testGetConfigsWithHidden()
    {
        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('testScope', DemoEntity::ENTITY_NAME, true)
            ->will($this->returnValue(array($this->entityConfig)));

        $this->assertEquals(
            array($this->entityConfig),
            $this->configProvider->getConfigs(DemoEntity::ENTITY_NAME, true)
        );
    }

    public function testMap()
    {
        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('testScope', DemoEntity::ENTITY_NAME, false)
            ->will($this->returnValue(array($this->entityConfig)));

        $entityConfig = new Config(new EntityConfigId('testScope', DemoEntity::ENTITY_NAME));
        $entityConfig->set('key', 'value');
        $this->assertEquals(
            array($entityConfig),
            $this->configProvider->map(
                function (ConfigInterface $config) {
                    return $config->set('key', 'value');
                },
                DemoEntity::ENTITY_NAME
            )
        );
    }

    public function testFilter()
    {
        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('testScope', DemoEntity::ENTITY_NAME, false)
            ->will($this->returnValue(array($this->entityConfig)));

        $this->assertEquals(
            array(),
            $this->configProvider->filter(
                function (ConfigInterface $config) {
                    return $config->getId()->getScope() == 'wrongScope';
                },
                DemoEntity::ENTITY_NAME
            )
        );
    }

    public function getIdProvider()
    {
        return [
            [null, null, null, new EntityConfigId('testScope')],
            ['TestCls', null, null, new EntityConfigId('testScope', 'TestCls')],
            ['TestCls', 'fieldName', 'int', new FieldConfigId('testScope', 'TestCls', 'fieldName', 'int')],
        ];
    }
}
