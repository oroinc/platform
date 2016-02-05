<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectPublicResources;

use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\CollectPublicResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\LoadPublicEnums;
use Oro\Bundle\ApiBundle\Request\PublicResource;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class LoadPublicEnumsTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var LoadPublicEnums */
    protected $processor;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadPublicEnums($this->configManager);
    }

    public function testProcess()
    {
        $context = new CollectPublicResourcesContext();

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('enum', null, true)
            ->willReturn(
                [
                    $this->getEntityConfig('enum', 'Test\Entity1', ['code' => 'enum1', 'public' => true]),
                    $this->getEntityConfig('enum', 'Test\Entity2', ['code' => 'enum2', 'public' => true]),
                    $this->getEntityConfig('enum', 'Test\Entity3', ['code' => 'enum3']),
                    $this->getEntityConfig('enum', 'Test\Entity4'),
                ]
            );
        $this->configManager->expects($this->exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap(
                [
                    ['extend', 'Test\Entity1', $this->getEntityConfig('extend', 'Test\Entity1')],
                    [
                        'extend',
                        'Test\Entity2',
                        $this->getEntityConfig('extend', 'Test\Entity2', ['is_extend' => true, 'is_deleted' => true])
                    ],
                ]
            );

        $this->processor->process($context);

        $this->assertEquals(
            [
                new PublicResource('Test\Entity1'),
            ],
            $context->getResult()->toArray()
        );
    }

    /**
     * @param string $scope
     * @param string $className
     * @param array  $values
     *
     * @return Config
     */
    protected function getEntityConfig($scope, $className, $values = [])
    {
        $configId = new EntityConfigId($scope, $className);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }
}
