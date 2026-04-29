<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\IntegrationBundle\Event\WebhookTopicCollectEvent;
use Oro\Bundle\IntegrationBundle\Model\WebhookTopic;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides information about available channels and entities that can be synchronized via webhooks.
 */
class WebhookConfigurationProvider
{
    public const string ENTITY_CONFIG_SCOPE = 'integration';

    public const string EVENT_CREATE = 'created';
    public const string EVENT_UPDATE = 'updated';
    public const string EVENT_DELETE = 'deleted';
    private const array ENTITY_EVENTS = [self::EVENT_CREATE, self::EVENT_UPDATE, self::EVENT_DELETE];

    public const string ENTITY_CLASS_KEY = 'entityClass';
    public const string ICON_KEY = 'icon';

    public function __construct(
        private ConfigManager $entityConfigManager,
        private EntityAliasProviderInterface $entityAliasProvider,
        private EventDispatcherInterface $eventDispatcher,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @return WebhookTopic[] [topic name => topic, ...]
     */
    public function getAvailableTopics(): array
    {
        $channels = $this->getEntityTopics();

        $event = new WebhookTopicCollectEvent($channels);
        $this->eventDispatcher->dispatch($event, WebhookTopicCollectEvent::NAME);

        return $event->getTopics();
    }

    public function isEntityAccessibleByWebhooks(object $entity): bool
    {
        return $this->isEntityClassAccessibleByWebhooks(ClassUtils::getClass($entity));
    }

    public function isEntityClassAccessibleByWebhooks(string $entityClass): bool
    {
        if (!$this->entityConfigManager->hasConfig($entityClass)) {
            return false;
        }

        return $this->entityConfigManager
            ->getEntityConfig('integration', $entityClass)
            ->is('webhook_accessible');
    }

    public function getTopicNameByEntityEvent(object $entity, string $event): string
    {
        return $this->getTopicNameByEntityClassAndEvent(ClassUtils::getClass($entity), $event);
    }

    public function getTopicNameByEntityClassAndEvent(string $className, string $event): string
    {
        return $this->getEntityAlias($className) . '.' . $event;
    }

    private function getEntityAlias(string $className): string
    {
        $alias = $this->entityAliasProvider->getEntityAlias($className);
        if ($alias instanceof EntityAlias) {
            return $alias->getAlias();
        }

        return str_replace('\\', '_', $className);
    }

    private function getEntityTopics(): array
    {
        $topics = [];
        $integrationConfigs = $this->entityConfigManager->getConfigs(self::ENTITY_CONFIG_SCOPE);
        foreach ($integrationConfigs as $integrationConfig) {
            if (!$integrationConfig->is('webhook_accessible')) {
                continue;
            }

            $entityClass = $integrationConfig->getId()->getClassName();
            $entityConfig = $this->entityConfigManager->getEntityConfig('entity', $entityClass);
            $entityAlias = $this->getEntityAlias($entityClass);
            foreach (self::ENTITY_EVENTS as $event) {
                $topicName = $entityAlias . '.' . $event;
                $label = $this->translator->trans(
                    'oro.integration.webhook_topic.entity_topic.label',
                    [
                        '%entity%' => $this->translator->trans($entityConfig->get('label')),
                        '%event%' => $this->translator->trans(
                            'oro.integration.webhook_topic.entity_topic.event.' . $event
                        )
                    ],
                );

                $topics[$topicName] = new WebhookTopic(
                    $topicName,
                    $label,
                    [
                        self::ENTITY_CLASS_KEY => $entityClass,
                        self::ICON_KEY => $entityConfig->get('icon') ?? 'fa-podcast'
                    ]
                );
            }
        }

        return $topics;
    }
}
