<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\RelationDataConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class RelationDataConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var  RelationDataConfigDumperExtension */
    protected $extension;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->setMethods(['getProviderBag', 'getProvider', 'getConfigChangeSet'])
            ->getMock();

        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($this->extendConfigProvider));

        $this->extension = new RelationDataConfigDumperExtension($this->configManager);
    }

    public function testSupportsPreUpdate()
    {
        $this->assertTrue(
            $this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );
    }

    public function testSupportsPostUpdate()
    {
        $this->assertFalse(
            $this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE)
        );
    }

    public function testCreateOneToMany()
    {
        $config1 = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config2 = new Config(new EntityConfigId('extend', 'Acme\Entity'));
        $config2->set('owner', ExtendScope::OWNER_CUSTOM);

        $configs = [
            $config1, $config2
        ];

        $fieldsConfigs = [
            $this->getConfigNewField(
                [
                    'state' => ExtendScope::STATE_NEW,
                    'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                ],
                'oneToMany'
            ),
        ];

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('getConfigs')
            ->with('Acme\Entity')
            ->will($this->returnValue($fieldsConfigs));

        $selfConfig = new Config(new EntityConfigId('extend'));
        $this->extendConfigProvider
            ->expects($this->any())
            ->method('getConfig')
            ->with('TestClass')
            ->will($this->returnValue($selfConfig));

        $this->extendConfigProvider->expects($this->any())
            ->method('persist');

        $this->extension->preUpdate($configs);
    }

    /**
     * FieldConfig
     *
     * @param array $values
     * @param string $type
     * @param string $scope
     *
     * @return Config
     */
    protected function getConfigNewField($values = [], $type = 'string', $scope = 'extend')
    {
        $resultValues = [
            'owner'      => ExtendScope::OWNER_CUSTOM,
            'state'      => ExtendScope::STATE_NEW,
            'is_extend'  => true,
            'is_deleted' => false,
        ];

        if (count($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $fieldConfigId = new FieldConfigId($scope, 'TestClass', 'testFieldName', $type);
        $config   = new Config($fieldConfigId);
        $config->setValues($resultValues);

        return $config;
    }
}
