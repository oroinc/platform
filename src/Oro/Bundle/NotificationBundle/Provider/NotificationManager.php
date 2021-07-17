<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\EventHandlerInterface;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides a functionality to handle notification events by all registered notification event handlers.
 */
class NotificationManager implements ResetInterface
{
    private const RULES_CACHE_KEY = 'rules';

    /** @var iterable|EventHandlerInterface[] */
    private $handlers;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var Cache */
    private $cache;

    /** @var EmailNotification[]|null */
    private $notificationRules;

    /**
     * @param iterable|EventHandlerInterface[] $handlers
     * @param Cache                            $cache
     * @param ManagerRegistry                  $doctrine
     */
    public function __construct(iterable $handlers, Cache $cache, ManagerRegistry $doctrine)
    {
        $this->handlers = $handlers;
        $this->cache = $cache;
        $this->doctrine = $doctrine;
    }

    /**
     * Process the given event by handlers.
     */
    public function process(NotificationEvent $event, string $eventName): NotificationEvent
    {
        $notificationRules = $this->getRulesByCriteria(ClassUtils::getClass($event->getEntity()), $eventName);
        if (!empty($notificationRules)) {
            foreach ($this->handlers as $handler) {
                $handler->handle($event, $notificationRules);
                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }

        return $event;
    }

    /**
     * Clears all internal caches.
     */
    public function clearCache(): void
    {
        $this->reset();
        $this->cache->delete(self::RULES_CACHE_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->notificationRules = null;
    }

    /**
     * @param string $entityName
     * @param string $eventName
     *
     * @return EmailNotification[]
     */
    private function getRulesByCriteria(string $entityName, string $eventName): array
    {
        $filteredRules = [];
        if ($this->hasRules($entityName, $eventName)) {
            $rules = $this->getRules();
            foreach ($rules as $rule) {
                if ($rule->getEntityName() === $entityName && $rule->getEventName() === $eventName) {
                    $filteredRules[] = $rule;
                }
            }
        }

        return $filteredRules;
    }

    private function hasRules(string $entityName, string $eventName): bool
    {
        $ruleMap = $this->cache->fetch(self::RULES_CACHE_KEY);
        if (false === $ruleMap) {
            $ruleMap = $this->loadRuleMap();
            $this->cache->save(self::RULES_CACHE_KEY, $ruleMap);
        }

        return
            isset($ruleMap[$entityName])
            && in_array($eventName, $ruleMap[$entityName], true);
    }

    /**
     * @return array [entity name => [event name, ...], ...]
     */
    private function loadRuleMap(): array
    {
        $data = $this->getEntityManager()
            ->createQueryBuilder()
            ->from(EmailNotification::class, 'e')
            ->distinct()
            ->select('e.entityName, e.eventName')
            ->getQuery()
            ->getArrayResult();

        $ruleMap = [];
        foreach ($data as $row) {
            $ruleMap[$row['entityName']][] = $row['eventName'];
        }

        return $ruleMap;
    }

    /**
     * @return EmailNotification[]
     */
    private function getRules(): array
    {
        if (null === $this->notificationRules) {
            $this->notificationRules = $this->loadRules();
        }

        return $this->notificationRules;
    }

    /**
     * @return EmailNotification[]
     */
    private function loadRules(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->from(EmailNotification::class, 'e')
            ->select('e')
            ->getQuery()
            ->getResult();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(EmailNotification::class);
    }
}
