<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadCustomEntities;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class LoadCustomEntitiesTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var LoadCustomEntities */
    protected $processor;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadCustomEntities($this->configManager);
    }

    public function testProcess()
    {
        $context = new CollectResourcesContext();

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('extend', null, true)
            ->willReturn(
                [
                    $this->getEntityConfig(
                        'Test\Entity1',
                        ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
                    ),
                    $this->getEntityConfig(
                        'Test\Entity2',
                        ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM]
                    ),
                    $this->getEntityConfig('Test\Entity3'),
                    $this->getEntityConfig(
                        'Test\Entity4',
                        ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM, 'is_deleted' => true]
                    ),
                ]
            );

        $this->processor->process($context);

        $this->assertEquals(
            [
                'Test\Entity1' => new ApiResource('Test\Entity1'),
            ],
            $context->getResult()->toArray()
        );
    }

    /**
     * @param string $className
     * @param array  $values
     *
     * @return Config
     */
    protected function getEntityConfig($className, $values = [])
    {
        $configId = new EntityConfigId('extend', $className);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }
}
