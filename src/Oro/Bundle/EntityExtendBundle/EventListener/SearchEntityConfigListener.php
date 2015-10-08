<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
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
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('search');
        if (null === $config) {
            return;
        }

        $configManager = $event->getConfigManager();
        $changeSet     = $configManager->getConfigChangeSet($config);
        if (!isset($changeSet['searchable'])) {
            return;
        }

        $className    = $config->getId()->getClassName();
        $extendConfig = $configManager->getProvider('extend')->getConfig($className);
        if ($extendConfig->get('state') === ExtendScope::STATE_ACTIVE) {
            $this->addReindexJob($className);
        } else {
            $this->addPostponeJob($className);
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
        $job = $this->registry->getRepository('JMSJobQueueBundle:Job')->createQueryBuilder('job')
            ->select('job')
            ->where('job.command = :command')
            ->andWhere('cast(job.args as text) = :args')
            ->andWhere('job.state in (\'pending\', \'running\')')
            ->setParameter('command', ReindexCommand::COMMAND_NAME)
            ->setParameter('args', ['class' => $entityClass], Type::JSON_ARRAY)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$job) {
            $job = new Job(ReindexCommand::COMMAND_NAME, ['class' => $entityClass]);
            $em  = $this->registry->getManager();
            $em->persist($job);
            $em->flush($job);
        }
    }
}
