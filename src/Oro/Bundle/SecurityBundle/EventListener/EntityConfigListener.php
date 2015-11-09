<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\Security\Acl\Model\AclCacheInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\SecurityBundle\Model\Action\AceQueryInterface;

class EntityConfigListener
{
    /** @var AceQueryInterface */
    protected $qbManager;

    /** @var ManagerRegistry */
    private $registry;

    /** @var AclCacheInterface */
    protected $aclCache;

    /**
     * @param AceQueryInterface $qbManager
     * @param ManagerRegistry $registry
     * @param AclCacheInterface $aclCache
     */
    public function __construct(
        AceQueryInterface $qbManager,
        ManagerRegistry $registry,
        AclCacheInterface $aclCache
    ) {
        $this->qbManager = $qbManager;
        $this->registry = $registry;
        $this->aclCache = $aclCache;
    }

    /**
     * Removes ACEs if share scope was removed from entity config
     *
     * @param PostFlushConfigEvent $event
     */
    public function postFlushConfig(PostFlushConfigEvent $event)
    {
        $configManager = $event->getConfigManager();
        foreach ($event->getModels() as $model) {
            /** @var EntityConfigModel $model */
            if ($model instanceof EntityConfigModel) {
                $aclClass = $this->registry->getRepository('OroSecurityBundle:AclClass')
                    ->findOneBy(['classType' => $model->getClassName()]);

                if (!$aclClass) {
                    continue;
                }

                $changeSet = $configManager->getConfigChangeSet(
                    $configManager->getProvider('security')->getConfig($model->getClassName())
                );
                $removeScopes = [];

                if ($changeSet) {
                    $removeScopes = array_diff($changeSet['share_scopes'][0], $changeSet['share_scopes'][1]);
                }

                if (empty($removeScopes)) {
                    continue;
                }

                $qb = $this->qbManager->getRemoveAceQueryBuilder($aclClass, $removeScopes);
                $qb->getQuery()->execute();
                $this->aclCache->clearCache();
            }
        }
    }
}
