<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;
use Oro\Bundle\SearchBundle\Entity\UpdateEntity;

class SearchEntityConfigListener
{
    /**  @var ManagerRegistry*/
    protected $registry;

    /** @var ConfigManager*/
    protected $configManager;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param PersistConfigEvent $event
     */
    public function persistConfig(PersistConfigEvent $event)
    {
        $eventConfig   = $event->getConfig();
        $eventConfigId = $event->getConfigId();

        if ($eventConfigId->getScope() !== 'search') {
            return;
        }
        $event->getConfigManager()->calculateConfigChangeSet($eventConfig);
        $change = $event->getConfigManager()->getConfigChangeSet($eventConfig);
        if (empty($change) || !array_key_exists('searchable', $change)) {
            return;
        }
        $class = $eventConfigId->getClassName();
        if ($event->getConfigManager()
                ->getProvider('extend')
                ->getConfig($class)
                ->get('state') === ExtendScope::STATE_ACTIVE
        ) {
            $this->addReindexJob($eventConfigId->getClassName());
        } else {
            $this->addPostponeJob($eventConfigId->getClassName());
        }
    }

    /**
     * @param string $entityClass
     */
    protected function addPostponeJob($entityClass)
    {
        $update = $this->registry->getRepository('OroSearchBundle:UpdateEntity')->find($entityClass);
        if (!$update) {
            $em     = $this->registry->getManager();
            $update = new UpdateEntity();
            $update->setEntity($entityClass);
            $em->persist($update);
            $em->flush($update);
        }
    }

    /**
     * @param string $entityClass
     */
    protected function addReindexJob($entityClass)
    {
        $job = new Job(ReindexCommand::COMMAND_NAME, ['class' => $entityClass]);
        $em  = $this->registry->getManager();
        $em->persist($job);
        $em->flush($job);
    }
}
