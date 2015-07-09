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
    protected $securityClasses = [];

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
     * @param string $class
     */
    public function addSupportedClass($class)
    {
        if (!in_array($class, $this->securityClasses, true)) {
            $this->securityClasses[] = $class;
        }
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->securityClasses) {
            return;
        }

        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->needWarmup = $this->checkEntities($uow->getScheduledEntityInsertions())
            || $this->checkEntities($uow->getScheduledEntityUpdates())
            || $this->checkEntities($uow->getScheduledEntityDeletions());

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
            if (in_array(ClassUtils::getClass($entity), $this->securityClasses, true)) {
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
