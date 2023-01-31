<?php

namespace Oro\Bundle\SegmentBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;

/**
 * Removes records from segment snapshot when referenced entity is removed.
 */
class DoctrinePreRemoveListener
{
    /** @var ConfigManager */
    private $configManager;

    /** @var array */
    private $deleteEntities = [];

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        $className = ClassUtils::getClass($entity);

        if ($this->configManager->hasConfig($className)) {
            $metadata = $args->getObjectManager()->getClassMetadata($className);
            $entityIds = $metadata->getIdentifierValues($entity);
            $this->deleteEntities[] = [
                'id'     => reset($entityIds),
                'entity' => $entity
            ];
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->deleteEntities) {
            $em = $args->getEntityManager();
            $knownNamespaces = $em->getConfiguration()->getEntityNamespaces();
            if (!empty($knownNamespaces['OroSegmentBundle'])) {
                $em->getRepository(SegmentSnapshot::class)->massRemoveByEntities($this->deleteEntities);
                $this->deleteEntities = [];
            }
        }
    }
}
