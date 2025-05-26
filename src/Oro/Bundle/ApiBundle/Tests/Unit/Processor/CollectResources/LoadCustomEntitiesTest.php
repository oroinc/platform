<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadCustomEntities;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoadCustomEntitiesTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private LoadCustomEntities $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->processor = new LoadCustomEntities($this->configManager);
    }

    private function getEntityConfig(string $className, array $values = []): Config
    {
        $configId = new EntityConfigId('extend', $className);
        $config = new Config($configId);
        $config->setValues($values);

        return $config;
    }

    public function testProcess(): void
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
}
