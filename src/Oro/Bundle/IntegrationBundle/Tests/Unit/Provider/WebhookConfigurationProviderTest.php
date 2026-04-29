<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\IntegrationBundle\Event\WebhookTopicCollectEvent;
use Oro\Bundle\IntegrationBundle\Model\WebhookTopic;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebhookConfigurationProviderTest extends TestCase
{
    private ConfigManager&MockObject $entityConfigManager;
    private EntityAliasProviderInterface&MockObject $entityAliasProvider;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private TranslatorInterface&MockObject $translator;
    private WebhookConfigurationProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(ConfigManager::class);
        $this->entityAliasProvider = $this->createMock(EntityAliasProviderInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new WebhookConfigurationProvider(
            $this->entityConfigManager,
            $this->entityAliasProvider,
            $this->eventDispatcher,
            $this->translator
        );
    }

    public function testGetAvailableTopicsReturnsEntityTopics(): void
    {
        $integrationConfig1 = new Config(
            new EntityConfigId('integration', 'Test\Entity1'),
            ['webhook_accessible' => true]
        );
        $integrationConfig2 = new Config(
            new EntityConfigId('integration', 'Test\Entity2'),
            ['webhook_accessible' => false]
        );
        $integrationConfig3 = new Config(
            new EntityConfigId('integration', 'Test\Entity3'),
            ['webhook_accessible' => true]
        );

        $entityConfig1 = new Config(
            new EntityConfigId('entity', 'Test\Entity1'),
            ['label' => 'entity1.label', 'icon' => 'fa-entity1']
        );
        $entityConfig3 = new Config(
            new EntityConfigId('entity', 'Test\Entity3'),
            ['label' => 'entity3.label', 'icon' => 'fa-entity3']
        );

        $this->entityConfigManager->expects(self::once())
            ->method('getConfigs')
            ->with(WebhookConfigurationProvider::ENTITY_CONFIG_SCOPE)
            ->willReturn([$integrationConfig1, $integrationConfig2, $integrationConfig3]);

        $this->entityConfigManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['entity', 'Test\Entity1', $entityConfig1],
                ['entity', 'Test\Entity3', $entityConfig3],
            ]);

        $this->entityAliasProvider->expects(self::exactly(2))
            ->method('getEntityAlias')
            ->willReturnMap([
                ['Test\Entity1', new EntityAlias('entity1', 'entity1_plural')],
                ['Test\Entity3', new EntityAlias('entity3', 'entity3_plural')],
            ]);

        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters = []) {
                $result = 'translated:' . $id;
                foreach ($parameters as $key => $value) {
                    $result .= '[' . $key . '=' . $value . ']';
                }

                return $result;
            });

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(WebhookTopicCollectEvent::class),
                WebhookTopicCollectEvent::NAME
            )
            ->willReturnArgument(0);

        $result = $this->provider->getAvailableTopics();

        self::assertCount(6, $result);

        $expectedTopics = [
            'entity1.created' => [
                'entityClass' => 'Test\Entity1',
                'icon' => 'fa-entity1',
                'labelKey' => 'entity1.label',
                'event' => 'created'
            ],
            'entity1.updated' => [
                'entityClass' => 'Test\Entity1',
                'icon' => 'fa-entity1',
                'labelKey' => 'entity1.label',
                'event' => 'updated'
            ],
            'entity1.deleted' => [
                'entityClass' => 'Test\Entity1',
                'icon' => 'fa-entity1',
                'labelKey' => 'entity1.label',
                'event' => 'deleted'
            ],
            'entity3.created' => [
                'entityClass' => 'Test\Entity3',
                'icon' => 'fa-entity3',
                'labelKey' => 'entity3.label',
                'event' => 'created'
            ],
            'entity3.updated' => [
                'entityClass' => 'Test\Entity3',
                'icon' => 'fa-entity3',
                'labelKey' => 'entity3.label',
                'event' => 'updated'
            ],
            'entity3.deleted' => [
                'entityClass' => 'Test\Entity3',
                'icon' => 'fa-entity3',
                'labelKey' => 'entity3.label',
                'event' => 'deleted'
            ]
        ];

        foreach ($expectedTopics as $topicName => $expected) {
            self::assertArrayHasKey($topicName, $result);
            self::assertInstanceOf(WebhookTopic::class, $result[$topicName]);
            self::assertEquals($topicName, $result[$topicName]->getName());
            self::assertEquals(
                'translated:oro.integration.webhook_topic.entity_topic.label'
                . '[%entity%=translated:' . $expected['labelKey'] . ']'
                . '[%event%=translated:oro.integration.webhook_topic.entity_topic.event.' . $expected['event'] . ']',
                $result[$topicName]->getLabel()
            );
            self::assertEquals(
                ['entityClass' => $expected['entityClass'], 'icon' => $expected['icon']],
                $result[$topicName]->getMetadata()
            );
        }
    }

    public function testGetAvailableTopicsWithNoAccessibleEntities(): void
    {
        $config = new Config(
            new EntityConfigId('integration', 'Test\Entity1'),
            ['webhook_accessible' => false]
        );

        $this->entityConfigManager->expects(self::once())
            ->method('getConfigs')
            ->willReturn([$config]);

        $this->entityConfigManager->expects(self::never())
            ->method('getEntityConfig');

        $this->entityAliasProvider->expects(self::never())
            ->method('getEntityAlias');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $result = $this->provider->getAvailableTopics();

        self::assertEquals([], $result);
    }

    public function testGetAvailableTopicsDispatchesEventAllowingTopicAddition(): void
    {
        $this->entityConfigManager->expects(self::once())
            ->method('getConfigs')
            ->willReturn([]);

        $extraTopic = new WebhookTopic('extra', 'Extra Topic Label');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (WebhookTopicCollectEvent $event) use ($extraTopic) {
                $event->addTopic($extraTopic);

                return $event;
            });

        $result = $this->provider->getAvailableTopics();

        self::assertArrayHasKey('extra', $result);
        self::assertSame($extraTopic, $result['extra']);
    }

    public function testIsEntityAccessibleByWebhooksReturnsTrueForAccessibleEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = \stdClass::class;

        $config = new Config(
            new EntityConfigId('integration', $entityClass),
            ['webhook_accessible' => true]
        );

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->entityConfigManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('integration', $entityClass)
            ->willReturn($config);

        self::assertTrue($this->provider->isEntityAccessibleByWebhooks($entity));
    }

    public function testIsEntityAccessibleByWebhooksReturnsFalseForNonAccessibleEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = \stdClass::class;

        $config = new Config(
            new EntityConfigId('integration', $entityClass),
            ['webhook_accessible' => false]
        );

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->entityConfigManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('integration', $entityClass)
            ->willReturn($config);

        self::assertFalse($this->provider->isEntityAccessibleByWebhooks($entity));
    }

    public function testIsEntityClassAccessibleByWebhooksReturnsTrueWhenAccessible(): void
    {
        $entityClass = 'Test\Entity1';

        $config = new Config(
            new EntityConfigId('integration', $entityClass),
            ['webhook_accessible' => true]
        );

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->entityConfigManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('integration', $entityClass)
            ->willReturn($config);

        self::assertTrue($this->provider->isEntityClassAccessibleByWebhooks($entityClass));
    }

    public function testIsEntityClassAccessibleByWebhooksReturnsFalseWhenNotAccessible(): void
    {
        $entityClass = 'Test\Entity1';

        $config = new Config(
            new EntityConfigId('integration', $entityClass),
            ['webhook_accessible' => false]
        );

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->entityConfigManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('integration', $entityClass)
            ->willReturn($config);

        self::assertFalse($this->provider->isEntityClassAccessibleByWebhooks($entityClass));
    }

    public function testIsEntityClassAccessibleByWebhooksReturnsFalseWhenNoConfig(): void
    {
        $entityClass = 'Test\Entity1';

        $this->entityConfigManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $this->entityConfigManager->expects(self::never())
            ->method('getEntityConfig');

        self::assertFalse($this->provider->isEntityClassAccessibleByWebhooks($entityClass));
    }

    public function testGetTopicNameByEntityReturnsAlias(): void
    {
        $entity = new \stdClass();
        $entityClass = \stdClass::class;

        $this->entityAliasProvider->expects(self::once())
            ->method('getEntityAlias')
            ->with($entityClass)
            ->willReturn(new EntityAlias('test_alias', 'test_plural'));

        self::assertEquals('test_alias.test', $this->provider->getTopicNameByEntityEvent($entity, 'test'));
    }

    public function testGetTopicNameByEntityClassReturnsAliasWhenAvailable(): void
    {
        $entityClass = 'Test\Entity1';

        $this->entityAliasProvider->expects(self::once())
            ->method('getEntityAlias')
            ->with($entityClass)
            ->willReturn(new EntityAlias('entity1', 'entity1_plural'));

        self::assertEquals('entity1.test', $this->provider->getTopicNameByEntityClassAndEvent($entityClass, 'test'));
    }

    public function testGetTopicNameByEntityClassReturnsFallbackWhenNoAlias(): void
    {
        $entityClass = 'Test\Entity1';

        $this->entityAliasProvider->expects(self::once())
            ->method('getEntityAlias')
            ->with($entityClass)
            ->willReturn(null);

        self::assertEquals(
            'Test_Entity1.test',
            $this->provider->getTopicNameByEntityClassAndEvent($entityClass, 'test')
        );
    }
}
