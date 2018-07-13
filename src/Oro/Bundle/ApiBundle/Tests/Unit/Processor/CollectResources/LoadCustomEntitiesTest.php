<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadCustomEntities;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class LoadCustomEntitiesTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var LoadCustomEntities */
    private $processor;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->processor = new LoadCustomEntities($this->configManager);
    }

    public function testProcess()
    {
        $context = new CollectResourcesContext();

        $this->configManager->expects(self::once())
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
                    )
                ]
            );

        $this->processor->process($context);

        self::assertEquals(
            [
                'Test\Entity1' => new ApiResource('Test\Entity1')
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
    private function getEntityConfig($className, array $values = [])
    {
        $configId = new EntityConfigId('extend', $className);
        $config = new Config($configId);
        $config->setValues($values);

        return $config;
    }
}
