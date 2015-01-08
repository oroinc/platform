<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;

class OwnerTreeListener
{
    /**
     * Array with classes need to be checked for
     *
     * @var array
     */
    protected $securityClasses = [
        'Oro\Bundle\UserBundle\Entity\User',
        'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
        'Oro\Bundle\OrganizationBundle\Entity\Organization',
    ];

    /**
     * @var ServiceLink
     */
    protected $treeProvider;

    /**
     * @var bool
     */
    protected $needWarmup;

    /**
     * @param ServiceLink $treeProviderLink
     */
    public function __construct(ServiceLink $treeProviderLink)
    {
        $this->treeProviderLink = $treeProviderLink;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->needWarmup = false;
        if ($this->checkEntities($uow->getScheduledEntityInsertions())) {
            $this->needWarmup = true;
        }
        if (!$this->needWarmup && $this->checkEntities($uow->getScheduledEntityUpdates())) {
            $this->needWarmup = true;
        }
        if (!$this->needWarmup && $this->checkEntities($uow->getScheduledEntityDeletions())) {
            $this->needWarmup = true;
        }

        if ($this->needWarmup) {
            $this->getTreeProvider()->clear();
        }
    }

    /**
     * @param array $entities
     * @return bool
     */
    protected function checkEntities(array $entities)
    {
        foreach ($entities as $entity) {
            if (in_array(ClassUtils::getClass($entity), $this->securityClasses)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return OwnerTreeProvider
     */
    protected function getTreeProvider()
    {
        return $this->treeProviderLink->getService();
    }
}
