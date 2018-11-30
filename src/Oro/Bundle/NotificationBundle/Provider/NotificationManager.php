<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\EventHandlerInterface;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Psr\Container\ContainerInterface;

/**
 * Provides a functionality to handle notification events by all registered notification event handlers.
 */
class NotificationManager
{
    private const RULES_CACHE_KEY = 'rules';

    /** @var string[] */
    private $handlerIds;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ContainerInterface */
    private $handlerLocator;

    /** @var Cache */
    private $cache;

    /** @var EmailNotification[]|null */
    private $notificationRules;

    /**
     * @param string[]           $handlerIds
     * @param ContainerInterface $handlerLocator
     * @param Cache              $cache
     * @param ManagerRegistry    $doctrine
     */
    public function __construct(
        array $handlerIds,
        ContainerInterface $handlerLocator,
        Cache $cache,
        ManagerRegistry $doctrine
    ) {
        $this->handlerIds = $handlerIds;
        $this->handlerLocator = $handlerLocator;
        $this->cache = $cache;
        $this->doctrine = $doctrine;
    }

    /**
     * Process the given event by handlers.
     *
     * @param NotificationEvent $event
     * @param string            $eventName
     *
     * @return NotificationEvent
     */
    public function process(NotificationEvent $event, string $eventName): NotificationEvent
    {
        $notificationRules = $this->getRulesByCriteria(ClassUtils::getClass($event->getEntity()), $eventName);
        if (!empty($notificationRules)) {
            foreach ($this->handlerIds as $handlerId) {
                $this->getHandler($handlerId)->handle($event, $notificationRules);
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
        $this->notificationRules = null;
        $this->cache->delete(self::RULES_CACHE_KEY);
    }

    /**
     * @param string $handlerId
     *
     * @return EventHandlerInterface
     */
    private function getHandler(string $handlerId): EventHandlerInterface
    {
        return $this->handlerLocator->get($handlerId);
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
                if ($rule->getEntityName() === $entityName && $rule->getEvent()->getName() === $eventName) {
                    $filteredRules[] = $rule;
                }
            }
        }

        return $filteredRules;
    }

    /**
     * @param string $entityName
     * @param string $eventName
     *
     * @return bool
     */
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
            ->select('e.entityName, event.name as eventName')
            ->innerJoin('e.event', 'event')
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
            ->select(['e', 'event'])
            ->leftJoin('e.event', 'event')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(EmailNotification::class);
    }
}
