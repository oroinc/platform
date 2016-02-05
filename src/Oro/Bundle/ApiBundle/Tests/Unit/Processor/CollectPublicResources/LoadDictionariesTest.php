<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectPublicResources;

use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\CollectPublicResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\LoadDictionaries;
use Oro\Bundle\ApiBundle\Request\PublicResource;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class LoadDictionariesTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var LoadDictionaries */
    protected $processor;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadDictionaries($this->configManager);
    }

    public function testProcess()
    {
        $context = new CollectPublicResourcesContext();

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('grouping', null, true)
            ->willReturn(
                [
                    $this->getEntityConfig('grouping', 'Test\Entity1', ['groups' => ['dictionary']]),
                    $this->getEntityConfig('grouping', 'Test\Entity2', ['groups' => ['dictionary', 'another_group']]),
                    $this->getEntityConfig('grouping', 'Test\Entity3', ['groups' => ['dictionary']]),
                    $this->getEntityConfig('grouping', 'Test\Entity4'),
                ]
            );
        $this->configManager->expects($this->exactly(3))
            ->method('getEntityConfig')
            ->willReturnMap(
                [
                    ['extend', 'Test\Entity1', $this->getEntityConfig('extend', 'Test\Entity1')],
                    ['extend', 'Test\Entity2', $this->getEntityConfig('extend', 'Test\Entity2')],
                    [
                        'extend',
                        'Test\Entity3',
                        $this->getEntityConfig('extend', 'Test\Entity3', ['is_extend' => true, 'is_deleted' => true])
                    ],
                ]
            );

        $this->processor->process($context);

        $this->assertEquals(
            [
                new PublicResource('Test\Entity1'),
                new PublicResource('Test\Entity2'),
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
