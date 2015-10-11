<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Util;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;

class AssociationTypeHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassResolver;

    /** @var AssociationTypeHelper */
    protected $typeHelper;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->typeHelper = new AssociationTypeHelper($this->configManager, $this->entityClassResolver);
    }

    public function testIsDictionaryNoConfig()
    {
        $className = 'Test\Entity';

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('grouping')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(false));
        $configProvider->expects($this->never())
            ->method('getConfig');

        $this->assertFalse(
            $this->typeHelper->isDictionary($className)
        );
    }

    /**
     * @dataProvider isDictionaryProvider
     */
    public function testIsDictionary($groups, $expected)
    {
        $className = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('groups')
            ->will($this->returnValue($groups));

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('grouping')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($config));

        $this->assertEquals(
            $expected,
            $this->typeHelper->isDictionary($className)
        );
    }

    public function isDictionaryProvider()
    {
        return [
            [null, false],
            [[], false],
            [['some_group'], false],
            [['dictionary'], true],
            [['some_group', 'dictionary'], true],
        ];
    }

    /**
     * @dataProvider isActivitySupport
     */
    public function testIsActivitySupport($dictionaryOptions, $expected)
    {
        $className = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('activity_support')
            ->will($this->returnValue($dictionaryOptions));

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('dictionary')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($config));

        $this->assertEquals(
            $expected,
            $this->typeHelper->isSupportActivityEnabled($className)
        );
    }

    public function isActivitySupport()
    {
        return [
            [null, false],
            [['some'], false],
            ['true', true],
            ['false', false],
        ];
    }

    public function testIsAssociationOwningSideEntityForNotOwningSideEntity()
    {
        $className        = 'Test\Entity1';
        $associationClass = 'Test\Entity';

        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with($associationClass)
            ->will($this->returnValue($associationClass));

        $this->assertFalse(
            $this->typeHelper->isAssociationOwningSideEntity($className, $associationClass)
        );
    }

    public function testIsAssociationOwningSideEntityWithClassName()
    {
        $className        = 'Test\Entity';
        $associationClass = 'Test\Entity';

        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with($associationClass)
            ->will($this->returnValue($className));

        $this->assertTrue(
            $this->typeHelper->isAssociationOwningSideEntity($className, $associationClass)
        );
    }

    public function testIsAssociationOwningSideEntityWithEntityName()
    {
        $className        = 'Test\Entity';
        $associationClass = 'Test:Entity';

        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with($associationClass)
            ->will($this->returnValue($className));

        $this->assertTrue(
            $this->typeHelper->isAssociationOwningSideEntity($className, $associationClass)
        );
    }

    public function testIsAssociationOwningSideEntityWithGroupName()
    {
        $config1 = new Config(new EntityConfigId('grouping', 'Test\Entity1'));
        $config1->set('groups', ['some_group', 'another_group']);
        $config2 = new Config(new EntityConfigId('grouping', 'Test\Entity2'));
        $config2->set('groups', ['another_group']);
        $config3 = new Config(new EntityConfigId('grouping', 'Test\Entity3'));

        $configs = [$config1, $config2, $config3];

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('grouping')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($configs));

        $this->assertTrue(
            $this->typeHelper->isAssociationOwningSideEntity('Test\Entity1', 'some_group')
        );
        $this->assertFalse(
            $this->typeHelper->isAssociationOwningSideEntity('Test\Entity', 'some_group')
        );
        $this->assertFalse(
            $this->typeHelper->isAssociationOwningSideEntity('Test\Entity2', 'some_group')
        );
    }

    public function testGetOwningSideEntities()
    {
        $config1 = new Config(new EntityConfigId('grouping', 'Test\Entity1'));
        $config1->set('groups', ['some_group', 'another_group']);
        $config2 = new Config(new EntityConfigId('grouping', 'Test\Entity2'));
        $config2->set('groups', ['another_group']);
        $config3 = new Config(new EntityConfigId('grouping', 'Test\Entity3'));

        $configs = [$config1, $config2, $config3];

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->with('grouping')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will($this->returnValue($configs));

        $this->assertEquals(
            ['Test\Entity1'],
            $this->typeHelper->getOwningSideEntities('some_group')
        );
        // one more call to check caching
        $this->assertEquals(
            ['Test\Entity1'],
            $this->typeHelper->getOwningSideEntities('some_group')
        );
        // call with another group to check a caching has no collisions
        $this->assertEquals(
            ['Test\Entity1', 'Test\Entity2'],
            $this->typeHelper->getOwningSideEntities('another_group')
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
