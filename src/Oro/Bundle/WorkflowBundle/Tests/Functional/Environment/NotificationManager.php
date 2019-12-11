<?php


namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Environment;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Provider\NotificationManager as BaseNotificationManager;
use Psr\Container\ContainerInterface;

class NotificationManager extends BaseNotificationManager
{
    /** @var string[] */
    private $handlerIds;

    /**
     * @param string[] $handlerIds
     * @param ContainerInterface $handlerLocator
     * @param Cache $cache
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        array $handlerIds,
        ContainerInterface $handlerLocator,
        Cache $cache,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($handlerIds, $handlerLocator, $cache, $doctrine);

        $this->handlerIds = $handlerIds;
    }

    /**
     * @return string[]
     */
    public function getHandlerIds(): array
    {
        return $this->handlerIds;
    }
}
